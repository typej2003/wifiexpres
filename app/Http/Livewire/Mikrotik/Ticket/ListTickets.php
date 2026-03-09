<?php

namespace App\Http\Livewire\Mikrotik\Ticket;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ticket;
use App\Models\Router;
use App\Models\User;
use App\Models\Setting;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use RouterOS\Client;
use RouterOS\Query;

class ListTickets extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $selectedAliado = null, $selectedRouter = null, $filterStatus = '';
    public $isModalOpen = false, $isBulkModalOpen = false, $isConfigModalOpen = false;
    public $ticket_id, $username, $password, $identity, $plan, $tiempo_uso, $costo;
    public $bulk_count = 10, $bulk_plan, $bulk_tiempo, $bulk_costo;
    public $comercio_nombre, $hotspot_url, $logo_actual, $nuevo_logo;
    public $mikrotik_profiles = [];

    /**
     * Centraliza la configuración de conexión basándose en Settings
     */
    private function getMikrotikConfig($router)
    {
        $setting = Setting::where('user_id', $router->user_id)->first();
        $mode = $setting ? $setting->mikrotik_connection_mode : 0; 

        return [
            'host'    => $router->ip,
            'user'    => $router->admin,
            'pass'    => $router->password,
            'port' => (int) ($router->api_port ?? 49152),
            'timeout' => ($mode === 1) ? 10 : 3,
        ];
    }

    public function render()
    {
        $aliados = User::whereIn('role', ['aliado', 'afiliado', 'admin'])->get();
        $routers = $this->selectedAliado ? Router::where('user_id', $this->selectedAliado)->get() : [];
        $query = Ticket::with('router');
        
        if ($this->selectedRouter) {
            $this->checkTicketsUsage(); 
            $query->where('router_id', $this->selectedRouter);
            if ($this->filterStatus) $query->where('estado', $this->filterStatus);
        } else {
            $query->whereRaw('1 = 0');
        }

        return view('livewire.mikrotik.ticket.list-tickets', [
            'aliados' => $aliados,
            'routers' => $routers,
            'tickets' => $query->latest()->paginate(10)
        ]);
    }

    public function updateSingleTicketUsage($ticketId)
    {
        $ticket = Ticket::find($ticketId);
        $router = Router::find($this->selectedRouter);

        if (!$ticket || !$router) return;

        try {
            $client = new Client($this->getMikrotikConfig($router));
            $query = (new Query('/ip/hotspot/user/print'))->where('name', $ticket->username);
            $response = $client->query($query)->read();

            if (!empty($response)) {
                $uptime = $response[0]['uptime'] ?? '0s';
                $limitUptime = $response[0]['limit-uptime'] ?? null;

                $ticket->tiempo_consumido = (string) $uptime;
                
                if ($limitUptime && $uptime === $limitUptime) {
                    $ticket->estado = 'agotado';
                    $ticket->anulado = true;
                }

                $ticket->save();
                session()->flash('message', "Consumo actualizado: $uptime");
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar consumo.');
        }
    }

    public function checkTicketsUsage()
    {
        $router = Router::find($this->selectedRouter);
        if (!$router) return;
        try {
            $client = new Client($this->getMikrotikConfig($router));
            $usersMk = $client->query(new Query('/ip/hotspot/user/print'))->read();
            foreach ($usersMk as $uMk) {
                $comentario = $uMk['comment'] ?? '';
                // No procesar si no tiene comentario o si está anulado
                if (!empty($comentario) && !str_contains($comentario, 'Web-API') && !str_contains($comentario, 'ANULADO')) {
                    $ticket = Ticket::where('username', $uMk['name'])
                        ->where('router_id', $this->selectedRouter)
                        ->where('estado', 'disponible')
                        ->first();
                    if ($ticket) {
                        try { $fechaInicio = Carbon::parse(preg_replace('/\s+/', ' ', $comentario)); } 
                        catch (\Exception $e) { $fechaInicio = now(); }
                        $ticket->update(['activado' => true, 'fecha_uso' => $fechaInicio, 'estado' => 'en_uso']);
                    }
                }
            }
        } catch (\Exception $e) {}
    }

    public function loadMikrotikProfiles()
    {
        if (!$this->selectedRouter) return;
        $router = Router::findOrFail($this->selectedRouter);
        try {
            $client = new Client($this->getMikrotikConfig($router));
            $allProfiles = $client->query(new Query('/ip/hotspot/user/profile/print'))->read();
            $this->mikrotik_profiles = array_filter($allProfiles, function($p) {
                $name = strtolower($p['name']);
                return !in_array($name, ['default', 'default-encryption']) && !str_contains($name, 'neutro');
            });
        } catch (\Exception $e) { $this->mikrotik_profiles = []; }
    }

    public function anularTicket($id)
    {
        $ticket = Ticket::findOrFail($id);
        $router = Router::findOrFail($this->selectedRouter);
        try {
            $client = new Client($this->getMikrotikConfig($router));
            $userMk = $client->query((new Query('/ip/hotspot/user/print'))->where('name', $ticket->username))->read();
            
            if (!empty($userMk)) {
                $comentarioOriginal = $userMk[0]['comment'] ?? '';
                // Preservamos el comentario original pero marcamos como ANULADO al principio
                $nuevoComentario = "ANULADO:" . $comentarioOriginal;

                $client->query((new Query('/ip/hotspot/user/set'))
                    ->equal('.id', $userMk[0]['.id'])
                    ->equal('profile', 'neutro')
                    ->equal('comment', $nuevoComentario))->read();

                // Expulsar si está conectado
                $activeMk = $client->query((new Query('/ip/hotspot/active/print'))->where('user', $ticket->username))->read();
                foreach ($activeMk as $a) { $client->query((new Query('/ip/hotspot/active/remove'))->equal('.id', $a['.id']))->read(); }
            }
            $ticket->update(['anulado' => true, 'estado' => 'anulado']);
        } catch (\Exception $e) { }
    }

    public function restaurarTicket($id)
    {
        $ticket = Ticket::findOrFail($id);
        $router = Router::findOrFail($this->selectedRouter);
        try {
            $client = new Client($this->getMikrotikConfig($router));
            $userMk = $client->query((new Query('/ip/hotspot/user/print'))->where('name', $ticket->username))->read();
            
            if (!empty($userMk)) {
                $comentarioActual = $userMk[0]['comment'] ?? '';
                // Removemos solo el prefijo 'ANULADO:' para recuperar la fecha original si existía
                $comentarioRestaurado = str_replace('ANULADO:', '', $comentarioActual);

                $client->query((new Query('/ip/hotspot/user/set'))
                    ->equal('.id', $userMk[0]['.id'])
                    ->equal('profile', $ticket->plan)
                    ->equal('comment', $comentarioRestaurado))->read(); 
            }
            
            // Si el comentario restaurado no está vacío, significa que ya tenía uso previo
            $nuevoEstado = !empty($comentarioRestaurado) ? 'en_uso' : 'disponible';
            $yaActivado = !empty($comentarioRestaurado);

            $ticket->update([
                'anulado' => false, 
                'activado' => $yaActivado, 
                'estado' => $nuevoEstado
            ]);
        } catch (\Exception $e) { }
    }

    public function store()
    {
        $this->validate(['identity' => 'required', 'plan' => 'required']);
        $this->username = str_replace('-', '', $this->identity);
        if(!$this->password) $this->password = rand(10000, 99999);
        $ticket = Ticket::updateOrCreate(['id' => $this->ticket_id], [
            'router_id' => $this->selectedRouter, 'username' => $this->username, 'password' => $this->password,
            'identity' => $this->identity, 'plan' => $this->plan, 'tiempo_uso' => $this->tiempo_uso, 'costo' => $this->costo, 
            'sincronizado' => false, 'estado' => 'disponible'
        ]);
        $this->isModalOpen = false;
        $this->processSync([$ticket]);
    }

    public function generateBulkTickets()
    {
        $this->validate(['bulk_count' => 'required|integer|min:1', 'bulk_plan' => 'required']);
        $ultimo = Ticket::where('router_id', $this->selectedRouter)->where('identity', 'LIKE', '%-%')->latest()->first();
        $nuevoLote = $ultimo ? (int)explode('-', $ultimo->identity)[0] + 1 : 1;
        $tickets = [];
        for ($i = 1; $i <= $this->bulk_count; $i++) {
            $sec = str_pad($i, 4, '0', STR_PAD_LEFT);
            $tickets[] = Ticket::create([
                'router_id' => $this->selectedRouter, 'identity' => "{$nuevoLote}-{$sec}",
                'username' => "{$nuevoLote}{$sec}", 'password' => rand(10000, 99999),
                'plan' => $this->bulk_plan, 'tiempo_uso' => $this->bulk_tiempo, 'costo' => $this->bulk_costo, 
                'sincronizado' => false, 'estado' => 'disponible'
            ]);
        }
        $this->isBulkModalOpen = false;
        $this->processSync($tickets);
    }

    private function processSync($tickets)
    {
        $router = Router::find($this->selectedRouter);
        try {
            $client = new Client($this->getMikrotikConfig($router));
            foreach ($tickets as $t) {
                $client->query((new Query('/ip/hotspot/user/add'))
                    ->equal('name', $t->username)
                    ->equal('password', $t->password)
                    ->equal('profile', $t->plan)
                    ->equal('comment', ''))->read();
                $t->update(['sincronizado' => true]);
            }
        } catch (\Exception $e) { }
    }

    public function saveConfig() {
        $router = Router::find($this->selectedRouter);
        $data = ['comercio_nombre' => $this->comercio_nombre, 'hotspot_url' => $this->hotspot_url];
        if ($this->nuevo_logo) {
            if ($router->comercio_logo) Storage::disk('public')->delete($router->comercio_logo);
            $data['comercio_logo'] = $this->nuevo_logo->store('logos', 'public');
        }
        $router->update($data);
        $this->isConfigModalOpen = false;
    }

    public function updatedPlan($value) { $this->parsePlanData($value, 'individual'); }
    public function updatedBulkPlan($value) { $this->parsePlanData($value, 'bulk'); }
    private function parsePlanData($name, $type) {
        if (!$name || !str_contains($name, '-')) return;
        $parts = explode('-', $name);
        if ($type === 'individual') { $this->tiempo_uso = $parts[0]; $this->costo = $parts[1] ?? 0; } 
        else { $this->bulk_tiempo = $parts[0]; $this->bulk_costo = $parts[1] ?? 0; }
    }

    public function create() { $this->reset(['username', 'password', 'identity', 'plan', 'tiempo_uso', 'costo', 'ticket_id']); $this->loadMikrotikProfiles(); $this->isModalOpen = true; }
    public function openBulkModal() { $this->reset(['bulk_count', 'bulk_plan', 'bulk_tiempo', 'bulk_costo']); $this->loadMikrotikProfiles(); $this->isBulkModalOpen = true; }
    public function openConfigModal() { if (!$this->selectedRouter) return; $router = Router::findOrFail($this->selectedRouter); $this->comercio_nombre = $router->comercio_nombre; $this->hotspot_url = $router->hotspot_url; $this->logo_actual = $router->comercio_logo; $this->isConfigModalOpen = true; }
    public function edit(Ticket $ticket) { $this->ticket_id = $ticket->id; $this->username = $ticket->username; $this->password = $ticket->password; $this->identity = $ticket->identity; $this->plan = $ticket->plan; $this->tiempo_uso = $ticket->tiempo_uso; $this->costo = $ticket->costo; $this->loadMikrotikProfiles(); $this->isModalOpen = true; }
    public function syncPendingTickets() { $pending = Ticket::where('router_id', $this->selectedRouter)->where('sincronizado', false)->get(); if ($pending->isNotEmpty()) $this->processSync($pending); }
    public function closeModal() { $this->isModalOpen = false; }
    public function closeBulkModal() { $this->isBulkModalOpen = false; }
    public function closeConfigModal() { $this->isConfigModalOpen = false; }
}