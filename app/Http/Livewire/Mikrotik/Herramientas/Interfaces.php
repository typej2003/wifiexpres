<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class Interfaces extends Component
{
    public $router_id;
    public $selectedAliado = null;
    public $routerStatus = []; 
    public $interfaces = []; // Array que llenaremos para la vista
    public $logs = [];
    public $loading = false;
    public $bridgeUrl = "http://188.95.113.44:3000";

    public function mount() {
        if (Auth::user()->role !== 'admin') abort(403);
        $this->refreshStatus();
    }

    public function refreshStatus() {
        try {
            $response = Http::timeout(5)->get("{$this->bridgeUrl}/api/routers-online");
            if ($response->successful()) {
                $activeMacs = collect($response->json())->map(fn($item) => strtoupper(trim($item['mac'])))->toArray();
                $this->routerStatus = Router::all()->mapWithKeys(fn($r) => [$r->id => in_array(strtoupper(trim($r->macAddress)), $activeMacs)])->toArray();
            }
        } catch (\Exception $e) { $this->routerStatus = []; }
    }

    public function cargarInterfaces() {
        $this->validate(['router_id' => 'required']);
        $this->loading = true;
        $this->interfaces = []; // Limpiar tabla
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "INT" . time();

        // SCRIPT MAESTRO: Formatea la salida para que PHP la entienda fácilmente
        $script = ":local res \"\"; /interface { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \"|\" . [get \$i disabled] . \"|\" . [get \$i type] . \"|\" . [get \$i mac-address] . \",\") } }; " .
                  "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        try {
            $this->logs[] = "📡 Petición enviada: Consultando interfaces...";
            
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            // Esperamos la respuesta (máximo 15 segundos)
            $rawResponse = $this->esperarRespuesta($mac, $tid);

            if ($rawResponse) {
                $this->procesarDatos($rawResponse);
                $this->logs[] = "✅ Respuesta recibida y procesada.";
            } else {
                $this->logs[] = "❌ TIMEOUT: El router no devolvió datos.";
            }
        } catch (\Exception $e) {
            $this->logs[] = "❌ ERROR: " . $e->getMessage();
        }
        $this->loading = false;
    }

    // Convierte el texto "Nombre|Estado|Tipo|MAC" en el Array de la tabla
    protected function procesarDatos($raw) {
        $filas = explode(',', rtrim($raw, ','));
        $tempInterfaces = [];

        foreach ($filas as $fila) {
            $datos = explode('|', $fila);
            if (count($datos) >= 4) {
                $tempInterfaces[] = [
                    'name' => $datos[0],
                    'disabled' => ($datos[1] == "true" || $datos[1] == "yes") ? 'true' : 'false',
                    'type' => $datos[2],
                    'mac-address' => $datos[3]
                ];
            }
        }
        $this->interfaces = $tempInterfaces;
    }

    protected function esperarRespuesta($mac, $tid) {
        for ($i = 0; $i < 15; $i++) {
            sleep(1);
            try {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    return $res->json('data');
                }
            } catch (\Exception $e) { }
        }
        return null;
    }

    public function toggleInterface($name, $status) {
        $accion = ($status == 'true') ? 'enable' : 'disable';
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "TOG" . time();

        $script = "/interface $accion [find name=\"$name\"]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no;";
        
        $this->logs[] = "⚙️ Ejecutando $accion en $name...";
        Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($script, 'text/plain')->post("{$this->bridgeUrl}/set-command");
        
        $this->esperarRespuesta($mac, $tid);
        $this->cargarInterfaces(); // Refrescar tabla automáticamente
    }

    public function render() {
        $routersOnline = Router::when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
            ->get()->filter(fn($r) => $this->routerStatus[$r->id] ?? false);

        return view('livewire.mikrotik.herramientas.interfaces', [
            'routers' => $routersOnline,
            'aliados' => User::where('role', 'aliado')->get()
        ]);
    }
}