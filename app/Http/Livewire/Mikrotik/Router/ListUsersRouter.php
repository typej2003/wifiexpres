<?php

namespace App\Http\Livewire\Mikrotik\Router;

use Livewire\Component;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

class ListUsersRouter extends Component
{
    use WithPagination;

    public $router;
    public $viewMode = 'active'; 
    public $allUsersData = []; // Guardamos los datos recibidos del bridge
    protected $paginationTheme = 'bootstrap';

    public function mount($id)
    {
        $this->router = Router::findOrFail($id);
        
        if ($this->router->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $this->loadUsers();
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->resetPage(); // Reiniciar a la página 1 al cambiar de modo
        $this->loadUsers();
    }

    public function loadUsers()
    {
        $this->allUsersData = [];
        $macActual = strtoupper($this->router->macAddress);
        
        if ($this->viewMode === 'active') {
            // COMANDO ACTIVO CORREGIDO: Evita nulos y filtra admin
            $comando = ":do { " .
                       ":local res \"DATA:\"; " .
                       ":foreach i in=[/ip hotspot active find] do={ " .
                            ":local u [/ip hotspot active get \$i user]; " .
                            ":local a [/ip hotspot active get \$i address]; " .
                            ":local m [/ip hotspot active get \$i mac-address]; " .
                            ":local t [/ip hotspot active get \$i uptime]; " .
                            ":if (\$u != \"admin\") do={ " .
                                ":set res (\$res . \$u . \",\" . \$a . \",\" . \$m . \",\" . \$t . \"|\"); " .
                            "} " .
                       "}; " .
                       "/tool fetch url=\"http://188.95.113.44:3000/post-result?mac=$macActual\" http-method=post http-data=\$res keep-result=no; " .
                       "} on-error={}";
        } else {
            // COMANDO TODOS CORREGIDO: Filtra admin y default-trial
            $comando = ":do { " .
                       ":local res \"DATA:\"; " .
                       ":foreach i in=[/ip hotspot user find where name!=\"default-trial\" and name!=\"admin\"] do={ " .
                            ":local u [/ip hotspot user get \$i name]; " .
                            ":local p [/ip hotspot user get \$i profile]; " .
                            ":local c [/ip hotspot user get \$i comment]; " .
                            ":if ([:len \$c] = 0) do={ :set c \"sin-comentario\" }; " .
                            ":set res (\$res . \$u . \",\" . \$p . \",\" . \$c . \"|\"); " .
                       "}; " .
                       "/tool fetch url=\"http://188.95.113.44:3000/post-result?mac=$macActual\" http-method=post http-data=\$res keep-result=no; " .
                       "} on-error={}";
        }

        try {
            $this->emitirAlSocket($comando);
            
            $respuestaRaw = null;
            // Espera de 12 segundos
            for ($i = 0; $i < 12; $i++) {
                sleep(1);
                $res = Http::get("http://127.0.0.1:3000/api/check-task-result", ['mac' => $macActual]);
                
                if ($res->successful() && $res->json('status') === 'ready') {
                    $respuestaRaw = $res->json('data');
                    break;
                }
            }

            if ($respuestaRaw && str_contains($respuestaRaw, 'DATA:')) {
                $datos = str_replace('DATA:', '', $respuestaRaw);
                $filas = array_filter(explode('|', trim($datos, "| ")));
                
                foreach ($filas as $fila) {
                    $p = explode(',', $fila);
                    if ($this->viewMode === 'active' && count($p) >= 4) {
                        $this->allUsersData[] = [
                            'username' => $p[0],
                            'ip'       => $p[1],
                            'mac'      => $p[2],
                            'uptime'   => $p[3]
                        ];
                    } elseif ($this->viewMode === 'all' && count($p) >= 3) {
                        $this->allUsersData[] = [
                            'username' => $p[0],
                            'profile'  => $p[1],
                            'comment'  => $p[2]
                        ];
                    }
                }
            } else {
                session()->flash('error', "No se recibió respuesta válida. Verifique si hay usuarios activos.");
            }
        } catch (\Exception $e) {
            session()->flash('error', "Error: " . $e->getMessage());
        }
    }

    protected function emitirAlSocket($comando)
    {
        $response = Http::withBody($comando, 'text/plain')->post("http://127.0.0.1:3000/set-command");
        if (!$response->successful()) throw new \Exception("El Bridge no está respondiendo.");
        return true;
    }

    public function backToRouters()
    {
        return redirect()->route('admin.routers');
    }

    public function render()
    {
        // Paginación manual de la lista recibida
        $items = collect($this->allUsersData);
        $currentPage = $this->page ?: 1;
        $perPage = 15;
        
        $paginatedItems = new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current()]
        );

        return view('livewire.mikrotik.router.list-users-router', [
            'users' => $paginatedItems
        ]);
    }
}