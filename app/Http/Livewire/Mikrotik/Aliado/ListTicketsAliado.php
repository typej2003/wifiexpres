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
use Illuminate\Support\Carbon;

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
    public $bulk_chunk_size = 20; 
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
            $response = Http::timeout(10)->withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim($comando), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$response->successful()) return null;

            for ($i = 0; $i < 25; $i++) {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $output = trim($res->json('data'));
                    if (strtoupper($output) === 'OK') return 'SUCCESS';
                    if (str_contains(strtolower($output), 'error') || str_contains(strtolower($output), 'failure')) return null;
                    return $output ?: "SUCCESS";
                }
                usleep(900000); 
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
        $costoFinal = preg_match('/neutro|cortesia|trial|gratis/i', $planLower) ? 0 : $planInfo->price;
        $limitUptime = $planInfo->session_timeout ?? '0s';

        $router = Router::find($this->selectedRouter);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "BULK_" . time();

        $comandoInterno = ""; 
        $insertData = [];

        for ($i = 1; $i <= $cantidadAProcesar; $i++) {
            $posGlobal = $this->bulk_current_count + $i;
            $secStr = str_pad($posGlobal, 4, '0', STR_PAD_LEFT);
            
            // Mantenemos identity con guiones para lógica interna y de impresión
            $identityStr = "{$this->selectedRouter}-{$this->bulk_last_lote}-{$secStr}";
            
            // CREAMOS EL USERNAME SIN GUIONES (Solo números)
            $usernameSinGuion = "{$this->selectedRouter}{$this->bulk_last_lote}{$secStr}";
            
            $passStr = (string)rand(10000, 99999);

            // IMPORTANTE: En MikroTik mandamos el name sin guiones
            $comandoInterno .= "/ip hotspot user add name=\"$usernameSinGuion\" password=\"$passStr\" profile=\"$this->bulk_plan\" limit-uptime=\"$limitUptime\" comment=\"Lote {$this->bulk_last_lote}\";\n";
            
            $insertData[] = [
                'router_id'        => $this->selectedRouter,
                'identity'         => $identityStr, // El identity se queda con guiones para el LIKE del PDF
                'username'         => $usernameSinGuion, // El username para loguearse no tiene guiones
                'password'         => $passStr,
                'plan'             => $planInfo->name,
                'costo'            => $costoFinal,
                'estado'           => 'disponible',
                'tiempo_consumido' => '0s',
                'tiempo_uso'       => $limitUptime,
                'sincronizado'     => true,
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now()
            ];
        }

        $cmdFinal = ":do { $comandoInterno /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no }";

        $this->bulk_step = 'processing';
        $res = $this->sendCommandQuick($cmdFinal, $tid);

        if ($res === 'SUCCESS') {
            Ticket::insert($insertData);
            $this->bulk_current_count += $cantidadAProcesar;
            if ($this->bulk_current_count >= $this->bulk_total_requested) {
                $this->finishBulk();
            } else {
                $this->bulk_step = 'continue';
            }
        } else {
            $this->bulk_step = 'continue';
            session()->flash('error', 'El MikroTik no confirmó la creación.');
        }
    }

    public function finishBulk() {
        $this->bulk_step = 'input';
        $this->isBulkModalOpen = false;
        $this->bulk_current_count = 0;
        $this->bulk_total_requested = 0;
        session()->flash('message', 'Lote generado exitosamente.');
    }

    public function syncPendingTickets()
    {
        $this->showOverlay = true; 
        $router = Router::find($this->selectedRouter);
        $macActual = strtoupper($router->macAddress);
        $tid = "SYNC" . time();
        
        $comando = ":local res \"D:\"; :foreach i in=[/ip hotspot user find where name!=\"default-trial\"] do={ " .
                   ":local n [/ip hotspot user get \$i name]; " .
                   ":local p [/ip hotspot user get \$i password]; " .
                   ":local pr [/ip hotspot user get \$i profile]; " .
                   ":local u [/ip hotspot user get \$i uptime]; " . 
                   ":local c [/ip hotspot user get \$i comment]; " .
                   ":set res (\$res . \$n . \",\" . \$p . \",\" . \$pr . \",\" . \$u . \",\" . \$c . \"|\"); " .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\$res keep-result=no;";
        
        $raw = $this->sendCommandQuick($comando, $tid);
        
        if ($raw && str_contains($raw, 'D:')) {
            $datos = str_replace('D:', '', $raw);
            $filas = array_filter(explode('|', trim($datos, "| ")));
            $mikrotikUsernames = [];
            $planesCache = Plan::where('router_id', $this->selectedRouter)->get()->keyBy('mikrotik_profile');

            foreach ($filas as $fila) {
                $p = explode(',', $fila);
                if (count($p) < 3) continue;
                
                $uName = $p[0];
                $mikrotikUsernames[] = $uName;

                $profileName = $p[2];
                $uptimeReal = $p[3] ?: '0s'; 
                
                $planData = $planesCache->get($profileName);
                
                $costoSync = 0;
                $tiempoUsoSync = '0s';
                $nombrePlanSync = $profileName;

                if ($planData) {
                    $nombrePlanSync = $planData->name;
                    $planLower = strtolower($planData->name);
                    $esGratis = preg_match('/neutro|cortesia|trial|gratis/i', $planLower);
                    $costoSync = $esGratis ? 0 : $planData->price;
                    $tiempoUsoSync = $planData->session_timeout ?? '0s';
                }

                $ticketExistente = Ticket::where('router_id', $this->selectedRouter)->where('username', $uName)->first();
                $nuevoIdentity = (!empty($p[4]) && $p[4] !== "nil") ? $p[4] : ($ticketExistente ? $ticketExistente->identity : "IMP-{$uName}");

                Ticket::updateOrCreate(
                    ['router_id' => $this->selectedRouter, 'username' => $uName],
                    [
                        'password'         => $p[1] ?? '',
                        'plan'             => $nombrePlanSync,
                        'costo'            => $costoSync,
                        'identity'         => $nuevoIdentity,
                        'tiempo_consumido' => $uptimeReal,
                        'tiempo_uso'       => $tiempoUsoSync,
                        'sincronizado'     => true,
                        'estado'           => ($uptimeReal !== '0s' && $uptimeReal !== '') ? 'en_uso' : 'disponible'
                    ]
                );
            }
            Ticket::where('router_id', $this->selectedRouter)->whereNotIn('username', $mikrotikUsernames)->delete();
            session()->flash('message', 'Sincronización finalizada.');
        } else {
            session()->flash('error', 'Error en la respuesta del router.');
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
        $cmd = ":do {/ip hotspot user set [find name=\"{$ticket->username}\"] profile=\"neutro\" limit-uptime=1s;/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no}";
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
        
        $plan = Plan::where('name', $ticket->plan)->where('router_id', $this->selectedRouter)->first();
        $profile = $plan ? $plan->mikrotik_profile : $ticket->plan;
        $limitUptime = $plan ? $plan->session_timeout : '0s';

        $router = Router::find($this->selectedRouter);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "REST" . time();
        $cmd = ":do {/ip hotspot user set [find name=\"{$ticket->username}\"] profile=\"{$profile}\" limit-uptime=\"{$limitUptime}\";/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no}";
        if ($this->sendCommandQuick($cmd, $tid)) {
            $ticket->update(['estado' => 'disponible', 'anulado' => false]);
            session()->flash('message', "Ticket {$ticket->username} restaurado.");
        } else {
            session()->flash('error', "Error al restaurar.");
        }
    }

    public function loadMikrotikProfiles()
    {
        $this->mikrotik_profiles = Plan::where('router_id', $this->selectedRouter)
            ->active()->get()->map(fn($p) => ['name' => $p->mikrotik_profile, 'display' => $p->name])->toArray();
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