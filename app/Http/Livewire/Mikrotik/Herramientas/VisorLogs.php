<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Router;
use App\Models\MikrotikLog;
use App\Models\Setting;
use RouterOS\Client;
use RouterOS\Query;
use Illuminate\Support\Facades\Auth;

class VisorLogs extends Component {
    use WithPagination;

    public $router_id;
    public $status_filter = 'all';
    public $type_filter = 'all';
    public $perPage = 15;
    public $loading = false;

    protected $paginationTheme = 'bootstrap';

    public function updatedRouterId() { 
        $this->syncLogs();
        $this->resetPage(); 
    }
    
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedTypeFilter() { $this->resetPage(); }

    /**
     * Sincroniza los logs del MikroTik a la DB Local
     */
    public function syncLogs() {
        if (!$this->router_id) return;

        $this->loading = true;
        $router = Router::findOrFail($this->router_id);

        try {
            $setting = Setting::where('user_id', Auth::id())->first();
            $isRemote = $setting && (int)$setting->mikrotik_connection_mode === 1;
            $host = $isRemote ? $router->dns : $router->ip;

            $client = new Client([
                'host'    => $host,
                'user'    => $router->admin,
                'pass'    => $router->password,
                'port'    => (int) ($router->api_port ?? 49152),
                'timeout' => 5
            ]);

            $responses = $client->query(new Query('/log/print'))->read();

            foreach ($responses as $item) {
                $msg = $item['message'] ?? '';
                $time = $item['time'] ?? 'N/A';
                $hash = md5($this->router_id . $time . $msg);

                if (!MikrotikLog::where('hash', $hash)->exists()) {
                    $parsed = $this->parseLogData($msg);
                    MikrotikLog::create([
                        'router_id'     => $this->router_id,
                        'time_mikrotik' => $time,
                        'category'      => $parsed['category'],
                        'type'          => $parsed['type'],
                        'message'       => $msg,
                        'hash'          => $hash
                    ]);
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
        $this->loading = false;
    }

    /**
     * LIMPIA EL BUFFER DEL MIKROTIK (Hardware)
     * No borra nada de la base de datos local.
     */
    public function clearMikrotikBuffer() {
        if (!$this->router_id) return;

        $router = Router::findOrFail($this->router_id);

        try {
            $setting = Setting::where('user_id', Auth::id())->first();
            $isRemote = $setting && (int)$setting->mikrotik_connection_mode === 1;
            $host = $isRemote ? $router->dns : $router->ip;

            $client = new Client([
                'host' => $host, 'user' => $router->admin, 'pass' => $router->password,
                'port' => (int) ($router->api_port ?? 49152), 'timeout' => 5
            ]);

            // Primero sincronizamos para no perder los últimos logs antes de borrar
            $this->syncLogs();

            // Ejecutamos la limpieza en el MikroTik reseteando el buffer memory
            // Paso 1: Reducir líneas a 1 (esto borra el buffer actual)
            $client->query((new Query('/system/logging/action/set'))
                ->equal('.id', 'memory')
                ->equal('memory-lines', '1'))->read();

            // Paso 2: Restaurar a 100 líneas (o el valor que prefieras)
            $client->query((new Query('/system/logging/action/set'))
                ->equal('.id', 'memory')
                ->equal('memory-lines', '100'))->read();

            session()->flash('success', 'Buffer del MikroTik reseteado con éxito. Los registros locales se mantienen intactos.');

        } catch (\Exception $e) {
            session()->flash('error', 'Error al limpiar hardware: ' . $e->getMessage());
        }
    }

    private function parseLogData($msg) {
        $category = 'info'; $type = 'Sistema';
        if (str_contains($msg, 'failure') || str_contains($msg, 'unauthorized')) {
            $category = 'danger'; $type = 'Seguridad';
        } elseif (str_contains($msg, 'hotspot') || str_contains($msg, 'logged')) {
            $type = 'Hotspot';
            if (str_contains($msg, 'logged in')) $category = 'success';
            if (str_contains($msg, 'logged out')) $category = 'warning';
        } elseif (str_contains($msg, 'error') || str_contains($msg, 'failed')) {
            $category = 'danger'; $type = 'Crítico';
        }
        return ['category' => $category, 'type' => $type];
    }

    public function clearLocalHistory() {
        if ($this->router_id) {
            MikrotikLog::where('router_id', $this->router_id)->delete();
            $this->resetPage();
        }
    }

    public function render() {
        $query = MikrotikLog::where('router_id', $this->router_id)->orderBy('id', 'desc');
        if ($this->status_filter !== 'all') $query->where('category', $this->status_filter);
        if ($this->type_filter !== 'all') $query->where('type', $this->type_filter);

        return view('livewire.mikrotik.herramientas.visor-logs', [
            'routers' => Router::all(),
            'logs'    => $query->paginate($this->perPage)
        ])->layout('layouts.app');
    }
}