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

            for ($i = 0; $i < 15; $i++) {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $output = $res->json('data');
                    if (str_contains(strtolower($output), 'failure')) return null;
                    return $output ?: "SUCCESS";
                }
                usleep(700000); 
            }
        } catch (\Exception $e) { Log::error("Error Bridge: " . $e->getMessage()); }
        return null; 
    }

    public function startBulkGeneration()
    {
        $this->validate([
            'bulk_count' => 'required|integer|min:1', 
            'bulk_plan' => 'required'
        ]);

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
        
        // Si ya terminamos, cerramos el proceso
        if ($restantes <= 0) {
            $this->finishBulk();
            return;
        }

        $cantidadAProcesar = min($this->bulk_chunk_size, $restantes);
        $planLower = strtolower($this->bulk_plan);
        $costoFinal = 0;

        // Lógica de costo según tus requerimientos guardados
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
                'router_id' => $this->selectedRouter,
                'identity' => $identityStr,
                'username' => $usernameStr, 
                'password' => $passStr,
                'plan' => $this->bulk_plan,
                'costo' => $costoFinal,
                'estado' => 'disponible',
                'sincronizado' => true,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Si el MikroTik responde bien, insertamos en DB
        if ($this->sendCommandQuick($comandoMasivo)) {
            Ticket::insert($insertData);
            $this->bulk_current_count += $cantidadAProcesar;
            
            // Verificamos si faltan más bloques o si ya terminamos
            if ($this->bulk_current_count >= $this->bulk_total_requested) {
                $this->finishBulk();
            } else {
                // Si hay más, seguimos con el siguiente bloque
                $this->processNextChunk();
            }
        } else {
            $this->bulk_step = 'input';
            session()->flash('error', 'El Router no respondió. Los tickets no se guardaron en la base de datos.');
        }
    }

    public function finishBulk() {
        $this->bulk_step = 'input';
        $this->isBulkModalOpen = false;
        $this->bulk_current_count = 0;
        session()->flash('message', 'Lote generado exitosamente en MikroTik y Base de Datos.');
    }

    // --- RESTO DE MÉTODOS (SIN CAMBIOS) ---

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