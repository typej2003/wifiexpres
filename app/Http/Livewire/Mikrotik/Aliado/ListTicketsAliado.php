<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ticket;
use App\Models\Router;
use App\Models\Plan;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ListTicketsAliado extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Estado de la UI
    public $selectedRouter = null, $router_name = ''; 
    public $isBulkModalOpen = false, $isConfigModalOpen = false, $isPrintModalOpen = false;
    public $showOverlay = false;

    // --- VARIABLES DE CONTROL DE LOTES ---
    public $bulk_step = 'input'; 
    public $bulk_total_requested = 0;
    public $bulk_current_count = 0; 
    public $bulk_last_lote = 0;
    public $bulk_chunk_size = 30; 
    public $bulk_count = 10, $bulk_plan;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    // Datos de Configuración / Diseño
    public $comercio_nombre, $hotspot_url, $logo_actual, $nuevo_logo;
    public $mikrotik_profiles = []; 
    
    // Variables de Impresión
    public $tipo_impresion = 'lote', $lote_imprimir, $desde_ticket, $hasta_ticket;

    public function mount($id = null)
    {
        if ($id) {
            $this->selectedRouter = $id;
            $this->loadRouterData();
        }
    }

    public function loadRouterData()
    {
        $router = Router::find($this->selectedRouter);
        if($router) {
            $this->router_name = $router->identity;
            $this->comercio_nombre = $router->comercio_nombre;
            $this->hotspot_url = $router->hotspot_url;
            $this->logo_actual = $router->comercio_logo; 
        }
    }

    protected function sendCommandQuick($comando, $tid = null)
    {
        $router = Router::findOrFail($this->selectedRouter);
        $mac = strtoupper(trim($router->macAddress));
        $tid = $tid ?? uniqid('Q');

        try {
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim($comando), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$response->successful()) return null;

            for ($i = 0; $i < 15; $i++) {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $output = $res->json('data');
                    // Validación crucial: Si el MikroTik reporta error en su lógica interna
                    if (str_contains(strtolower($output), 'failure') || str_contains(strtolower($output), 'error')) return null;
                    return $output ?: "SUCCESS";
                }
                usleep(800000); 
            }
        } catch (\Exception $e) { Log::error("Error Bridge: " . $e->getMessage()); }
        return null; 
    }

    public function startBulkGeneration()
    {
        if (!$this->bulk_plan || !$this->bulk_count) {
            session()->flash('error', 'Faltan datos para generar el lote.');
            return;
        }

        $this->bulk_total_requested = (int)$this->bulk_count;
        $this->bulk_current_count = 0;

        $ultimoTicket = Ticket::where('router_id', $this->selectedRouter)
                        ->orderBy('id', 'desc')
                        ->first();
        
        $this->bulk_last_lote = 1;
        if ($ultimoTicket && str_contains($ultimoTicket->identity, '-')) {
            $partes = explode('-', $ultimoTicket->identity);
            if (count($partes) >= 2) { 
                $this->bulk_last_lote = (int)$partes[1] + 1; 
            }
        }

        $this->processNextChunk();
    }

    public function processNextChunk()
    {
        $restantes = $this->bulk_total_requested - $this->bulk_current_count;
        if ($restantes <= 0) {
            $this->finishBulk();
            return;
        }

        $cantidadAProcesar = min($this->bulk_chunk_size, $restantes);
        
        $planInfo = Plan::where('mikrotik_profile', $this->bulk_plan)
                        ->where('router_id', $this->selectedRouter)
                        ->first();

        if (!$planInfo) {
            session()->flash('error', 'No se encontró información del plan.');
            return;
        }

        $planLower = strtolower($planInfo->name);
        $costoFinal = $planInfo->price;
        if (preg_match('/neutro|cortesia|trial/i', $planLower)) {
            $costoFinal = 0;
        }

        $router = Router::find($this->selectedRouter);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "BULK" . time() . "_" . $this->bulk_current_count;

        $comandoInterno = ""; 
        $insertData = [];

        for ($i = 1; $i <= $cantidadAProcesar; $i++) {
            $posGlobal = $this->bulk_current_count + $i;
            $secStr = str_pad($posGlobal, 4, '0', STR_PAD_LEFT);
            $identityStr = "{$this->selectedRouter}-{$this->bulk_last_lote}-{$secStr}";
            $usernameStr = $identityStr; 
            $passStr = (string)rand(10000, 99999);

            $comandoInterno .= "/ip hotspot user add name=\"$usernameStr\" password=\"$passStr\" profile=\"$this->bulk_plan\" comment=\"Lote {$this->bulk_last_lote}\";\n";
            
            $insertData[] = [
                'router_id'    => $this->selectedRouter,
                'identity'     => $identityStr,
                'username'     => $usernameStr, 
                'password'     => $passStr,
                'plan'         => $this->bulk_plan,
                'costo'        => $costoFinal,
                'estado'       => 'disponible',
                'tiempo_uso'   => $planInfo->session_timeout,
                'sincronizado' => true,
                'created_at'   => now(),
                'updated_at'   => now()
            ];
        }

        $cmdFinal = ":do {
            $comandoInterno
            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no
        } on-error={
            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no
        }";

        $this->bulk_step = 'processing';

        $response = $this->sendCommandQuick($cmdFinal, $tid);

        // SOLO SI EL MIKROTIK RESPONDIÓ "OK" O "SUCCESS" PROCEDEMOS
        if ($response && !str_contains(strtoupper($response), 'ERROR')) {
            Ticket::insert($insertData);
            $this->bulk_current_count += $cantidadAProcesar;
            
            if ($this->bulk_current_count >= $this->bulk_total_requested) {
                $this->finishBulk();
            } else {
                $this->bulk_step = 'continue';
            }
        } else {
            // Si falló el MikroTik, no avanzamos el contador y mostramos error
            $this->bulk_step = 'continue'; 
            session()->flash('error', 'El MikroTik no pudo procesar este bloque. Verifique conexión.');
        }
    }

    public function finishBulk() {
        $this->bulk_step = 'input';
        $this->isBulkModalOpen = false;
        $this->bulk_current_count = 0;
        $this->bulk_total_requested = 0;
        session()->flash('message', 'Lote de tickets generado exitosamente.');
    }

    public function syncPendingTickets()
    {
        $this->showOverlay = true; 
        $router = Router::find($this->selectedRouter);
        $macActual = strtoupper($router->macAddress);
        $tid = "SYNC" . time();
        
        $comando = ":local res \"DATA:\"; :foreach i in=[/ip hotspot user find] do={ :local n [/ip hotspot user get \$i name]; :local p [/ip hotspot user get \$i password]; :local pr [/ip hotspot user get \$i profile]; :local u [/ip hotspot user get \$i uptime]; :local lu [/ip hotspot user get \$i limit-uptime]; :local c [/ip hotspot user get \$i comment]; :set res (\$res . \$n . \",\" . \$p . \",\" . \$pr . \",\" . \$u . \",\" . \$lu . \",\" . \$c . \"|\"); }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\$res keep-result=no;";
        
        $raw = $this->sendCommandQuick($comando, $tid);
        
        if ($raw && str_contains($raw, 'DATA:')) {
            $datos = str_replace('DATA:', '', $raw);
            $filas = array_filter(explode('|', trim($datos, "| ")));
            $mikrotikUsernames = [];
            $planesCaché = Plan::where('router_id', $this->selectedRouter)->get()->keyBy('mikrotik_profile');

            foreach ($filas as $fila) {
                $p = explode(',', $fila);
                if (count($p) < 3) continue;

                $uName = $p[0];
                $planNombre = $p[2];
                $planLower = strtolower($planNombre);
                if ($uName === 'default-trial') continue;
                $mikrotikUsernames[] = $uName;

                $costoCalculado = 0;
                $esGratis = preg_match('/neutro|cortesia|trial/i', $planLower) || $planLower === 'default';
                
                $planData = $planesCaché->get($planNombre);
                if ($planData) {
                    $costoCalculado = $esGratis ? 0 : $planData->price;
                    $tiempoUsoValue = $planData->session_timeout;
                } else {
                    $tiempoUsoValue = '0s';
                }

                Ticket::updateOrCreate(
                    ['router_id' => $this->selectedRouter, 'username' => $uName],
                    [
                        'password' => $p[1] ?? '',
                        'plan' => $planNombre,
                        'costo' => $costoCalculado,
                        'identity' => $p[5] ?: "IMP-{$uName}",
                        'tiempo_consumido' => $p[3] ?: '0s',
                        'tiempo_uso' => $tiempoUsoValue,
                        'sincronizado' => true
                    ]
                );
            }
            Ticket::where('router_id', $this->selectedRouter)->whereNotIn('username', $mikrotikUsernames)->delete();
            session()->flash('message', 'Sincronización finalizada.');
        }
        $this->showOverlay = false; 
    }

    public function saveConfig()
    {
        $router = Router::find($this->selectedRouter);
        if ($this->nuevo_logo) { 
            $path = $this->nuevo_logo->store('logos', 'public'); 
            $router->comercio_logo = $path; 
            $this->logo_actual = $path; 
        }
        $router->comercio_nombre = $this->comercio_nombre; 
        $router->hotspot_url = $this->hotspot_url; 
        $router->save();
        $this->isConfigModalOpen = false;
        session()->flash('message', 'Diseño actualizado.');
    }

    public function loadMikrotikProfiles()
    {
        $this->mikrotik_profiles = Plan::where('router_id', $this->selectedRouter)
            ->active()
            ->get()
            ->map(function($plan) {
                return [
                    'name' => $plan->mikrotik_profile,
                    'display' => $plan->name
                ];
            })->toArray();
    }

    public function printRange()
    {
        if ($this->tipo_impresion == 'lote') {
            $patron = "{$this->selectedRouter}-{$this->lote_imprimir}-";
            $primero = Ticket::where('router_id', $this->selectedRouter)->where('identity', 'LIKE', $patron . '%')->orderBy('identity', 'asc')->first();
            $ultimo = Ticket::where('router_id', $this->selectedRouter)->where('identity', 'LIKE', $patron . '%')->orderBy('identity', 'desc')->first();
            if (!$primero) { session()->flash('error', 'No hay tickets en este lote.'); return; }
            $desde = $primero->identity; $hasta = $ultimo->identity;
        } else { $desde = $this->desde_ticket; $hasta = $this->hasta_ticket; }
        $url = route('tickets.print', ['router_id' => $this->selectedRouter, 'desde' => $desde, 'hasta' => $hasta]);
        $this->dispatchBrowserEvent('abrirImpresion', ['url' => $url]);
        $this->isPrintModalOpen = false;
    }

    public function anularTicket($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return;
        $router = Router::find($this->selectedRouter);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "ANUL" . time();
        $cmd = ":do {/ip hotspot user set [find name=\"{$ticket->username}\"] profile=\"neutro\" limit-uptime=0s;/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no}";
        if ($this->sendCommandQuick($cmd, $tid)) {
            $ticket->update(['estado' => 'anulado', 'anulado' => true]);
            session()->flash('message', "Ticket {$ticket->username} anulado.");
        } else {
            session()->flash('error', "Error al anular.");
        }
    }

    public function restaurarTicket($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return;
        $router = Router::find($this->selectedRouter);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "REST" . time();
        $cmd = ":do {/ip hotspot user set [find name=\"{$ticket->username}\"] profile=\"{$ticket->plan}\" limit-uptime=0s;/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no}";
        if ($this->sendCommandQuick($cmd, $tid)) {
            $ticket->update(['estado' => 'disponible', 'anulado' => false]);
            session()->flash('message', "Ticket {$ticket->username} restaurado.");
        } else {
            session()->flash('error', "Error al restaurar.");
        }
    }
    
    public function openBulkModal() { $this->bulk_step = 'input'; $this->loadMikrotikProfiles(); $this->isBulkModalOpen = true; }
    public function closeBulkModal() { $this->isBulkModalOpen = false; }
    public function openConfigModal() { $this->isConfigModalOpen = true; }
    public function closeConfigModal() { $this->isConfigModalOpen = false; }
    public function openPrintModal() { $this->isPrintModalOpen = true; }
    public function closePrintModal() { $this->isPrintModalOpen = false; }
    public function backToRouters() { return redirect()->route('aliado.routers'); }

    public function render()
    {
        return view('livewire.mikrotik.aliado.list-tickets-aliado', [
            'tickets' => Ticket::where('router_id', $this->selectedRouter)->latest('id')->paginate(10)
        ])->layout('layouts.app');
    }
}