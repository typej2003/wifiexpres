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

    // Datos de Configuración
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

            // Esperar confirmación (Polling al Bridge)
            for ($i = 0; $i < 15; $i++) {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $output = $res->json('data');
                    if (str_contains(strtolower($output), 'error')) return null;
                    return $output ?: "SUCCESS";
                }
                usleep(700000); 
            }
        } catch (\Exception $e) { Log::error("Error Bridge: " . $e->getMessage()); }
        return null; 
    }

    // --- NUEVO MÉTODO DE ANULACIÓN BASADO EN TU LÓGICA ---
    public function anularTicket($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return;

        $router = Router::find($this->selectedRouter);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "ANUL" . time();
        $username = $ticket->username;

        // Implementando tu lógica de fetch y on-error
        $cmd = ":do {/ip hotspot user set [find name=\"$username\"] profile=\"neutro\" limit-uptime=0s;/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no}";

        if ($this->sendCommandQuick($cmd, $tid)) {
            $ticket->update(['estado' => 'anulado', 'anulado' => true]);
            session()->flash('message', "Ticket $username anulado correctamente.");
        } else {
            session()->flash('error', "No se pudo anular: el MikroTik no confirmó la operación.");
        }
    }

    // --- NUEVO MÉTODO DE RESTAURACIÓN BASADO EN TU LÓGICA ---
    public function restaurarTicket($id)
    {
        $ticket = Ticket::find($id);
        if (!$ticket) return;

        $router = Router::find($this->selectedRouter);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "REST" . time();
        $username = $ticket->username;
        $profile = $ticket->plan;

        // Implementando tu lógica con el perfil original y reseteo de tiempo
        $cmd = ":do {/ip hotspot user set [find name=\"$username\"] profile=\"$profile\" limit-uptime=0s;/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no}";

        if ($this->sendCommandQuick($cmd, $tid)) {
            $ticket->update(['estado' => 'disponible', 'anulado' => false]);
            session()->flash('message', "Ticket $username restaurado al perfil $profile.");
        } else {
            session()->flash('error', "No se pudo restaurar: el MikroTik no confirmó la operación.");
        }
    }

    public function startBulkGeneration()
    {
        $this->validate(['bulk_count' => 'required|integer|min:1', 'bulk_plan' => 'required']);
        $this->bulk_total_requested = (int)$this->bulk_count;
        $this->bulk_current_count = 0;
        
        $ultimo = Ticket::where('router_id', $this->selectedRouter)
            ->where('identity', 'LIKE', $this->selectedRouter . '-%')
            ->latest('id')->first();
            
        $this->bulk_last_lote = $ultimo ? (int)explode('-', $ultimo->identity)[1] + 1 : 1;
        $this->bulk_step = 'processing';
        $this->processNextChunk(); 
    }

    public function processNextChunk()
    {
        $restantes = $this->bulk_total_requested - $this->bulk_current_count;
        if ($restantes <= 0) return;

        $cantidadAProcesar = min($this->bulk_chunk_size, $restantes);
        $planLower = strtolower($this->bulk_plan);
        $costoFinal = 0;

        $esGratis = str_contains($planLower, 'neutro') || str_contains($planLower, 'cortesia') || str_contains($planLower, 'trial') || str_contains($planLower, 'default');

        if (!$esGratis && str_contains($this->bulk_plan, '-')) {
            if (preg_match('/-(\d+(\.\d+)?)$/', $this->bulk_plan, $m)) {
                $costoFinal = (float)$m[1];
            }
        }

        $comandoMasivo = ""; 
        $insertData = [];

        for ($i = 1; $i <= $cantidadAProcesar; $i++) {
            $posGlobal = $this->bulk_current_count + $i;
            $secStr = str_pad($posGlobal, 4, '0', STR_PAD_LEFT);
            $identityStr = "{$this->selectedRouter}-{$this->bulk_last_lote}-{$secStr}";
            $usernameStr = "{$this->selectedRouter}{$this->bulk_last_lote}{$secStr}";
            $passStr = (string)rand(10000, 99999);

            $comandoMasivo .= ":do { /ip hotspot user add name=\"$usernameStr\" password=\"$passStr\" profile=\"$this->bulk_plan\" comment=\"$identityStr\" } on-error={}; \n";
            
            $insertData[] = [
                'router_id' => $this->selectedRouter, 'identity' => $identityStr, 'username' => $usernameStr, 
                'password' => $passStr, 'plan' => $this->bulk_plan, 'costo' => $costoFinal,
                'estado' => 'disponible', 'sincronizado' => true, 'created_at' => now(), 'updated_at' => now()
            ];
        }

        if ($this->sendCommandQuick($comandoMasivo)) {
            Ticket::insert($insertData);
            $this->bulk_current_count += $cantidadAProcesar;
        } else {
            session()->flash('error', 'Error en el bloque actual.');
        }
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
                $uName = $p[0]; $planNombre = $p[2];
                $planLower = strtolower($planNombre);
                if ($uName === 'default-trial') continue;
                $mikrotikUsernames[] = $uName;

                $costoCalculado = 0;
                $esGratis = str_contains($planLower, 'neutro') || str_contains($planLower, 'cortesia') || str_contains($planLower, 'trial') || $planLower === 'default';
                if (!$esGratis && str_contains($planNombre, '-')) {
                    if (preg_match('/-(\d+(\.\d+)?)$/', $planNombre, $m)) $costoCalculado = (float)$m[1];
                }

                $tiempoUsoValue = 0;
                $planData = $planesCaché->get($planNombre);
                if ($planData) $tiempoUsoValue = $planData->session_timeout;

                Ticket::updateOrCreate(
                    ['router_id' => $this->selectedRouter, 'username' => $uName],
                    [
                        'password' => $p[1] ?? '', 'plan' => $planNombre, 'costo' => $costoCalculado,
                        'identity' => $p[5] ?: "IMP-{$uName}", 'tiempo_consumido' => $p[3] ?: '0s',
                        'tiempo_uso' => $tiempoUsoValue ?: '0s', 'sincronizado' => true
                    ]
                );
            }
            Ticket::where('router_id', $this->selectedRouter)->whereNotIn('username', $mikrotikUsernames)->delete();
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
        $router = Router::find($this->selectedRouter);
        $raw = $this->sendCommandQuick(":local res \"DATA:\"; :foreach i in=[/ip hotspot user profile find] do={ :set res (\$res . [/ip hotspot user profile get \$i name] . \"|\"); }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=".strtoupper($router->macAddress)."&tid=PROF\" http-method=post http-data=\$res keep-result=no;", "PROF");
        if ($raw && str_contains($raw, 'DATA:')) {
            $profiles = [];
            foreach (explode('|', str_replace('DATA:', '', $raw)) as $name) {
                if (!empty($name) && !in_array($name, ['default', 'neutro'])) $profiles[] = ['name' => $name, 'display' => $name];
            }
            $this->mikrotik_profiles = $profiles;
        }
    }

    public function printRange()
    {
        if ($this->tipo_impresion == 'lote') {
            $patron = "{$this->selectedRouter}-{$this->lote_imprimir}-";
            $primero = Ticket::where('router_id', $this->selectedRouter)->where('identity', 'LIKE', $patron . '%')->orderBy('identity', 'asc')->first();
            $ultimo = Ticket::where('router_id', $this->selectedRouter)->where('identity', 'LIKE', $patron . '%')->orderBy('identity', 'desc')->first();
            if (!$primero) { session()->flash('error', 'No hay tickets.'); return; }
            $desde = $primero->identity; $hasta = $ultimo->identity;
        } else { $desde = $this->desde_ticket; $hasta = $this->hasta_ticket; }
        $url = route('tickets.print', ['router_id' => $this->selectedRouter, 'desde' => $desde, 'hasta' => $hasta]);
        $this->dispatchBrowserEvent('abrirImpresion', ['url' => $url]);
        $this->isPrintModalOpen = false;
    }

    public function finishBulk() { $this->bulk_step = 'input'; $this->isBulkModalOpen = false; }
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