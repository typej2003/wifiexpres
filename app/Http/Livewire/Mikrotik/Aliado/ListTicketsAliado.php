<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ticket;
use App\Models\Router;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ListTicketsAliado extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $selectedRouter = null, $router_name = ''; 
    public $isModalOpen = false, $isBulkModalOpen = false, $isConfigModalOpen = false, $isPrintModalOpen = false;
    public $showOverlay = false;

    // --- VARIABLES DE CONTROL DE LOTES ---
    public $bulk_step = 'input'; 
    public $bulk_total_requested = 0;
    public $bulk_current_count = 0;
    public $bulk_last_lote = 0;
    public $bulk_chunk_size = 30;
    public $bulk_count = 10, $bulk_plan;

    public $showSyncWarning = false;
    protected $bridgeUrl = "http://188.95.113.44:3000";

    public $ticket_id, $username, $password, $identity, $plan;
    public $comercio_nombre, $hotspot_url, $logo_actual, $nuevo_logo;
    public $mikrotik_profiles = []; 
    
    public $tipo_impresion = 'lote'; 
    public $lote_imprimir, $desde_ticket, $hasta_ticket;

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

    // --- MODALES Y NAVEGACIÓN ---
    public function openBulkModal() { 
        $this->bulk_step = 'input';
        $this->loadMikrotikProfiles(); 
        $this->isBulkModalOpen = true; 
    }
    public function closeBulkModal() { $this->isBulkModalOpen = false; }

    public function openConfigModal() { $this->isConfigModalOpen = true; }
    public function closeConfigModal() { $this->isConfigModalOpen = false; }

    public function openPrintModal() { $this->isPrintModalOpen = true; }
    public function closePrintModal() { $this->isPrintModalOpen = false; }

    public function backToRouters() { return redirect()->route('aliado.routers'); }

    public function saveConfig()
    {
        $router = Router::findOrFail($this->selectedRouter);
        
        if ($this->nuevo_logo) {
            $path = $this->nuevo_logo->store('logos', 'public');
            $router->comercio_logo = $path;
            $this->logo_actual = $path;
        }

        $router->comercio_nombre = $this->comercio_nombre;
        $router->hotspot_url = $this->hotspot_url;
        $router->save();

        $this->isConfigModalOpen = false;
        session()->flash('message', 'Diseño actualizado correctamente.');
    }

    protected function sendCommandQuick($comando, $tid = null)
    {
        $router = Router::findOrFail($this->selectedRouter);
        $mac = strtoupper($router->macAddress);
        $tid = $tid ?? uniqid('Q');

        try {
            $comandoLimpio = trim($comando);
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($comandoLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$response->successful()) return null;

            for ($i = 0; $i < 15; $i++) {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $output = $res->json('data');
                    if (str_contains(strtolower($output), 'failure')) return null;
                    return $output ?: "SUCCESS";
                }
                usleep(800000); 
            }
        } catch (\Exception $e) { Log::error("Quick Command Exception: " . $e->getMessage()); }
        return null; 
    }

    public function startBulkGeneration()
    {
        $this->validate(['bulk_count' => 'required|integer|min:1|max:1000', 'bulk_plan' => 'required']);
        
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
        if ($restantes <= 0) { $this->finishBulk(); return; }

        $cantidadAProcesar = min($this->bulk_chunk_size, $restantes);

        try {
            $planLower = strtolower($this->bulk_plan);
            $esGratis = str_contains($planLower, 'neutro') || str_contains($planLower, 'cortesia') || str_contains($planLower, 'trial');
            
            $costoFinal = 0;
            if (!$esGratis) {
                if (preg_match('/(\d+(\.\d+)?)$/', $this->bulk_plan, $matches)) {
                    $costoFinal = (float)$matches[0];
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

                // Blindaje del comando
                $comandoMasivo .= ":do { /ip hotspot user add name=\"{$usernameStr}\" password=\"{$passStr}\" profile=\"{$this->bulk_plan}\" comment=\"{$identityStr}\" } on-error={}; \n";
                
                $insertData[] = [
                    'router_id' => $this->selectedRouter, 'identity' => $identityStr,
                    'username' => $usernameStr, 'password' => $passStr, 'plan' => $this->bulk_plan,
                    'costo' => $costoFinal, 'tiempo_uso' => '00:00:00', 'tiempo_consumido' => '0s',
                    'estado' => 'disponible', 'sincronizado' => true, 'created_at' => now(), 'updated_at' => now()
                ];
            }

            if ($this->sendCommandQuick($comandoMasivo)) {
                Ticket::insert($insertData);
                $this->bulk_current_count += $cantidadAProcesar;
                if ($this->bulk_current_count >= $this->bulk_total_requested) {
                    $this->finishBulk();
                } else {
                    session()->flash('chunk_message', "Bloque enviado con éxito.");
                    session()->flash('next_amount', min($this->bulk_chunk_size, $this->bulk_total_requested - $this->bulk_current_count));
                }
            } else {
                session()->flash('error', "El MikroTik no respondió. Reintente el bloque.");
            }
        } catch (\Exception $e) { session()->flash('error', "Error: " . $e->getMessage()); }
    }

    public function finishBulk()
    {
        $this->bulk_step = 'input';
        $this->isBulkModalOpen = false;
        session()->flash('message', "Lote completado correctamente.");
    }

    public function syncPendingTickets()
    {
        $this->showOverlay = true; 
        $router = Router::find($this->selectedRouter);
        $macActual = strtoupper($router->macAddress);
        $tid = "SYNC" . time();

        $comando = ":local res \"DATA:\"; :foreach i in=[/ip hotspot user find] do={ " .
                   ":local n [/ip hotspot user get \$i name]; :local p [/ip hotspot user get \$i password]; " .
                   ":local pr [/ip hotspot user get \$i profile]; :local u [/ip hotspot user get \$i uptime]; " .
                   ":local lu [/ip hotspot user get \$i limit-uptime]; :local c [/ip hotspot user get \$i comment]; " .
                   ":set res (\$res . \$n . \",\" . \$p . \",\" . \$pr . \",\" . \$u . \",\" . \$lu . \",\" . \$c . \"|\"); " .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        $raw = $this->sendCommandQuick($comando, $tid);

        if ($raw && str_contains($raw, 'DATA:')) {
            $datos = str_replace('DATA:', '', $raw);
            $filas = array_filter(explode('|', trim($datos, "| ")));
            $mikrotikUsernames = [];

            foreach ($filas as $fila) {
                $p = explode(',', $fila);
                $uName = $p[0];
                if (in_array($uName, ['default-trial', 'default']) || empty($p[2])) continue;

                $mikrotikUsernames[] = $uName;
                $costoSync = 0;
                if (!str_contains(strtolower($p[2]), 'neutro') && preg_match('/(\d+(\.\d+)?)$/', $p[2], $m)) $costoSync = (float)$m[0];

                Ticket::updateOrCreate(
                    ['router_id' => $this->selectedRouter, 'username' => $uName],
                    ['password' => $p[1] ?? '', 'plan' => $p[2], 'costo' => $costoSync, 'identity' => $p[5] ?: "IMP-{$uName}", 'tiempo_uso' => $p[4] ?: '00:00:00', 'tiempo_consumido' => $p[3] ?: '0s', 'sincronizado' => true]
                );
            }
            Ticket::where('router_id', $this->selectedRouter)->whereNotIn('username', $mikrotikUsernames)->delete();
            session()->flash('message', 'Sincronización terminada.');
        }
        $this->showOverlay = false; 
    }

    public function loadMikrotikProfiles()
    {
        if(count($this->mikrotik_profiles) > 0) return;
        $router = Router::find($this->selectedRouter);
        $raw = $this->sendCommandQuick(":local res \"DATA:\"; :foreach i in=[/ip hotspot user profile find] do={ :set res (\$res . [/ip hotspot user profile get \$i name] . \",\" . [/ip hotspot user profile get \$i session-timeout] . \"|\"); }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=".strtoupper($router->macAddress)."&tid=PROF\" http-method=post http-data=\$res keep-result=no;", "PROF");
        
        if ($raw && str_contains($raw, 'DATA:')) {
            $profiles = [];
            foreach (explode('|', str_replace('DATA:', '', $raw)) as $f) {
                $p = explode(',', $f);
                if (!empty($p[0]) && !in_array($p[0], ['default', 'neutro'])) $profiles[] = ['name' => $p[0], 'display' => $p[0]];
            }
            $this->mikrotik_profiles = $profiles;
        }
    }

    public function render()
    {
        return view('livewire.mikrotik.aliado.list-tickets-aliado', [
            'tickets' => Ticket::where('router_id', $this->selectedRouter)->latest('id')->paginate(10)
        ])->layout('layouts.app');
    }
}