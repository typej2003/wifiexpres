<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ticket;
use App\Models\Router;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    protected function sendCommandQuick($comando, $tid = null)
    {
        $router = Router::findOrFail($this->selectedRouter);
        $mac = strtoupper($router->macAddress);
        $tid = $tid ?? uniqid('Q');

        try {
            // Limpieza profunda del comando para MikroTik
            $comandoLimpio = trim($comando);
            
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($comandoLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$response->successful()) {
                Log::error("Bridge Error: " . $response->body());
                return null;
            }

            // Esperamos el resultado real del MikroTik
            for ($i = 0; $i < 12; $i++) {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $output = $res->json('data');
                    // Si el Mikrotik devuelve un error de sintaxis, lo atrapamos
                    if (str_contains(strtolower($output), 'failure') || str_contains(strtolower($output), 'error')) {
                        Log::error("Mikrotik execution error: " . $output);
                        return null;
                    }
                    return $output ?: "SUCCESS";
                }
                usleep(700000); 
            }
        } catch (\Exception $e) { Log::error("Quick Command Exception: " . $e->getMessage()); }
        
        return "TIMEOUT_BUT_SENT"; 
    }

    public function startBulkGeneration()
    {
        $this->validate([
            'bulk_count' => 'required|integer|min:1|max:1000', 
            'bulk_plan' => 'required'
        ]);
        
        $this->bulk_total_requested = (int)$this->bulk_count;
        $this->bulk_current_count = 0;

        $ultimo = Ticket::where('router_id', $this->selectedRouter)
            ->where('identity', 'LIKE', $this->selectedRouter . '-%')
            ->latest('id')->first();

        if ($ultimo) {
            $partes = explode('-', $ultimo->identity);
            $this->bulk_last_lote = isset($partes[1]) ? (int)$partes[1] + 1 : 1;
        } else {
            $this->bulk_last_lote = 1;
        }

        $this->bulk_step = 'processing';
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

        try {
            // Lógica de costo según tus reglas
            $costoFinal = 0;
            $tiempoUso = '00:00:00'; 
            $planLower = strtolower($this->bulk_plan);
            
            if (!str_contains($planLower, 'neutro') && !str_contains($planLower, 'cortesia') && !str_contains($planLower, 'trial')) {
                $partesPlan = explode('-', $this->bulk_plan);
                $costoFinal = isset($partesPlan[1]) ? (float)$partesPlan[1] : 0;
            }

            $comandoMasivo = ""; 
            $insertData = [];
            $tid = "BATCH_" . time();

            for ($i = 1; $i <= $cantidadAProcesar; $i++) {
                $posGlobal = $this->bulk_current_count + $i;
                $secStr = str_pad($posGlobal, 4, '0', STR_PAD_LEFT);
                
                $identityStr = "{$this->selectedRouter}-{$this->bulk_last_lote}-{$secStr}";
                $usernameStr = "{$this->selectedRouter}{$this->bulk_last_lote}{$secStr}";
                $passStr = (string)rand(10000, 99999);

                // IMPORTANTE: Un comando por línea con punto y coma para MikroTik Terminal
                $comandoMasivo .= "/ip hotspot user add name=\"{$usernameStr}\" password=\"{$passStr}\" profile=\"{$this->bulk_plan}\" comment=\"{$identityStr}\";\n";
                
                $insertData[] = [
                    'router_id' => $this->selectedRouter,
                    'identity' => $identityStr,
                    'username' => $usernameStr,
                    'password' => $passStr,
                    'plan' => $this->bulk_plan,
                    'costo' => $costoFinal,
                    'tiempo_uso' => $tiempoUso,
                    'tiempo_consumido' => '0s',
                    'estado' => 'disponible',
                    'sincronizado' => true,
                    'created_at' => now(), 'updated_at' => now()
                ];
            }

            $res = $this->sendCommandQuick($comandoMasivo, $tid);

            // SOLO insertamos si el Bridge/Mikrotik dio señal de éxito
            if ($res) {
                Ticket::insert($insertData);
                $this->bulk_current_count += $cantidadAProcesar;
                
                $pendientes = $this->bulk_total_requested - $this->bulk_current_count;

                if ($this->bulk_current_count >= $this->bulk_total_requested) {
                    $this->finishBulk();
                } else {
                    $siguienteMonto = min($this->bulk_chunk_size, $pendientes);
                    session()->flash('chunk_message', "Bloque enviado. Llevamos {$this->bulk_current_count} tickets.");
                    session()->flash('next_amount', $siguienteMonto);
                }
            } else {
                // Si llegamos aquí, algo falló en el MikroTik
                session()->flash('error', "El MikroTik rechazó el comando. Verifique que el perfil '{$this->bulk_plan}' exista en el router.");
            }

        } catch (\Exception $e) { 
            Log::error("Critical Error Bulk: " . $e->getMessage());
            session()->flash('error', "Error crítico: " . $e->getMessage()); 
        }
    }

    public function finishBulk()
    {
        $this->bulk_step = 'input';
        $this->isBulkModalOpen = false;
        $this->bulk_count = 10;
        session()->flash('message', "Lote generado correctamente en MikroTik y Base de Datos.");
    }

    // --- MÉTODOS DE SOPORTE ---

    public function syncPendingTickets()
    {
        $this->showOverlay = true; 
        set_time_limit(0);
        $macActual = strtoupper(Router::find($this->selectedRouter)->macAddress);
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

                $costoSync = 0;
                $pLower = strtolower($p[2]);
                if (!str_contains($pLower, 'neutro') && !str_contains($pLower, 'cortesia') && !str_contains($pLower, 'trial')) {
                    $costoSync = str_contains($p[2], '-') ? (float) collect(explode('-', $p[2]))->last() : 0;
                }
                
                $mikrotikUsernames[] = $uName;

                Ticket::updateOrCreate(
                    ['router_id' => $this->selectedRouter, 'username' => $uName],
                    ['password' => $p[1] ?? '', 'plan' => $p[2], 'costo' => $costoSync, 'identity' => $p[5] ?? "IMP-{$uName}", 'tiempo_uso' => $p[4] ?? '00:00:00', 'tiempo_consumido' => $p[3] ?? '0s', 'sincronizado' => true]
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
        if(!$router) return;
        
        $mac = strtoupper($router->macAddress);
        $raw = $this->sendCommandQuick(":local res \"DATA:\"; :foreach i in=[/ip hotspot user profile find] do={ :set res (\$res . [/ip hotspot user profile get \$i name] . \",\" . [/ip hotspot user profile get \$i session-timeout] . \"|\"); }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=PROF\" http-method=post http-data=\$res keep-result=no;", "PROF");
        
        if ($raw && $raw !== "TIMEOUT_BUT_SENT") {
            $profiles = [];
            $limpio = str_replace('DATA:', '', $raw);
            foreach (explode('|', $limpio) as $f) {
                $p = explode(',', $f);
                if (!empty($p[0]) && !in_array($p[0], ['default', 'neutro'])) {
                    $profiles[] = ['name' => $p[0], 'display' => $p[0], 'session_timeout' => $p[1] ?? '00:00:00'];
                }
            }
            $this->mikrotik_profiles = $profiles;
        }
    }

    public function openBulkModal() { 
        $this->bulk_step = 'input';
        $this->loadMikrotikProfiles(); 
        $this->isBulkModalOpen = true; 
    }
    public function closeBulkModal() { $this->isBulkModalOpen = false; }
    public function backToRouters() { return redirect()->route('aliado.routers'); }

    public function render()
    {
        return view('livewire.mikrotik.aliado.list-tickets-aliado', [
            'tickets' => Ticket::where('router_id', $this->selectedRouter)->latest('id')->paginate(10)
        ])->layout('layouts.app');
    }
}