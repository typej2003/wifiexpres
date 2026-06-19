<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use App\Models\UserMikrotik;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Permanencia extends Component
{
    public $fromDate;
    public $toDate;
    public $selectedPeriod = 'semana'; // Para los filtros rápidos de fecha
    public $selectedRouter = '';
    public $clientType = 'todos'; // todos, nuevos, recurrentes

    public $results = [];
    public $detailedClients = [];
    public $chartData = [];

    public function mount()
    {
        $this->setDatesForPeriod('semana'); // Establecer por defecto la última semana
        $this->consultar();
    }

    public function setDatesForPeriod($period)
    {
        $this->selectedPeriod = $period;
        if ($period === 'dia') {
            $this->fromDate = Carbon::now()->format('Y-m-d');
        } elseif ($period === 'semana') {
            $this->fromDate = Carbon::now()->subDays(7)->format('Y-m-d');
        } elseif ($period === 'mes') {
            $this->fromDate = Carbon::now()->subMonth()->format('Y-m-d');
        } elseif ($period === 'trimestre') {
            $this->fromDate = Carbon::now()->subMonths(3)->format('Y-m-d');
        }
        $this->toDate = Carbon::now()->format('Y-m-d');
    }

    public function consultar()
    {
        $start = Carbon::parse($this->fromDate)->startOfDay();
        $end = Carbon::parse($this->toDate)->endOfDay();
        
        $user = auth()->user();
        $allowedRouterIds = Router::when($user->role !== 'admin', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })->pluck('id');

        $query = TicketLog::whereBetween('created_at', [$start, $end])->whereIn('router_id', $allowedRouterIds);

        if ($this->selectedRouter) {
            $query->where('router_id', $this->selectedRouter);
        }

        $logs = $query->get();

        // 1. Optimización: Pre-procesar todos los nombres de usuario y manejar el prefijo 'T-'
        $allUsernames = $logs->pluck('username')->unique()->filter();
        $macs = $allUsernames->map(fn($u) => Str::after($u, 'T-'))->toArray();

        // Agrupamos usuarios por router y mac para búsqueda instantánea (O(1))
        $mikrotikUsers = UserMikrotik::whereIn('name', $macs)
            ->whereIn('router_id', $allowedRouterIds)
            ->get()
            ->groupBy(['router_id', 'name']);

        // Identificar quiénes son recurrentes en una sola consulta
        $recurrentUsernames = TicketLog::whereIn('username', $allUsernames)
            ->whereIn('router_id', $allowedRouterIds)
            ->where('created_at', '<', $start)
            ->distinct()
            ->pluck('username')
            ->toArray();

        // Agrupamos por Router para mostrar "Locales/Zonas"
        $grouped = $logs->groupBy('router_id');
        $this->results = [];
        $this->detailedClients = []; // Para la nueva tabla de clientes detallados
        $this->chartData = []; // Para los datos del gráfico

        // Pre-cargar routers para evitar N+1 consultas
        $routers = Router::whereIn('id', $grouped->keys())->get()->keyBy('id');

        foreach ($grouped as $routerId => $routerLogs) {
            $router = $routers->get($routerId);
            if (!$router) continue; // Si el router no existe o no está permitido, lo saltamos
            $uniqueUsernames = $routerLogs->pluck('username')->unique()->filter(); // Filtrar usernames vacíos
            
            $nuevos = 0;
            $totales = 0;
            $duracionTotal = 0;
            $conteoValidoDuracion = 0;

            foreach ($uniqueUsernames as $username) {
                // Extraer la MAC address del username (ej. T-7A:D2:3B:4C:5E -> 7A:D2:3B:4C:5E)
                $macAddress = Str::after($username, 'T-');

                // Buscar el UserMikrotik en la colección pre-cargada usando router_id y macAddress
                $userMikrotik = $mikrotikUsers->get($routerId)?->get($macAddress)?->first();

                $clientName = $userMikrotik->full_name ?? $userMikrotik->name ?? $macAddress;
                $clientCellphone = ($userMikrotik->cellphonecode ?? '') . ($userMikrotik->cellphone ?? '');
                $clientEmail = $userMikrotik->email ?? 'N/A';

                // Verificamos recurrencia contra el array pre-calculado
                $esRecurrente = in_array($username, $recurrentUsernames);
                $esNuevo = !$esRecurrente;

                // Filtrado por tipo de cliente según el botón seleccionado
                if ($this->clientType == 'nuevos' && !$esNuevo) continue;
                if ($this->clientType == 'recurrentes' && $esNuevo) continue;

                $userLogsInRange = $routerLogs->where('username', $username);
                
                if ($esNuevo) $nuevos++;
                $totales++;
                
                $userTotalDuration = $userLogsInRange->sum('duration_seconds');
                $userValidDurationCount = $userLogsInRange->where('duration_seconds', '>', 0)->count();
                $avgUserDuration = $userValidDurationCount > 0 ? ($userTotalDuration / $userValidDurationCount) : 0;

                $duracionTotal += $userTotalDuration;
                $conteoValidoDuracion += $userValidDurationCount;

                $this->detailedClients[$router->id][] = [
                    'user_id' => $userMikrotik ? $userMikrotik->id : null,
                    'username' => $username,
                    'client_name' => $clientName,
                    'cellphone' => $clientCellphone,
                    'email' => $clientEmail,
                    'is_new' => $esNuevo,
                    'total_visits_in_period' => $userLogsInRange->count(),
                    'total_duration_in_period' => $this->formatSeconds($userTotalDuration),
                    'avg_duration_in_period' => $this->formatSeconds($avgUserDuration),
                    'avg_duration_in_seconds' => $avgUserDuration, // Para el gráfico
                ];
            }

            if ($totales > 0) {
                $promedioSegundos = $conteoValidoDuracion > 0 ? ($duracionTotal / $conteoValidoDuracion) : 0;
                
                $this->results[] = [
                    'router_id' => $router->id,
                    'zona' => $router->identity ?? $router->location ?? 'Local Desconocido',
                    'nuevos' => $nuevos,
                    'totales' => $totales,
                    'promedio' => $this->formatSeconds($promedioSegundos)
                ];

                // Preparar datos para el gráfico de permanencia
                $bins = [
                    '0-15 min' => ['min' => 0, 'max' => 15 * 60],
                    '15-30 min' => ['min' => 15 * 60, 'max' => 30 * 60],
                    '30-60 min' => ['min' => 30 * 60, 'max' => 60 * 60],
                    '1-2 horas' => ['min' => 60 * 60, 'max' => 2 * 3600],
                    '>2 horas' => ['min' => 2 * 3600, 'max' => PHP_INT_MAX],
                ];
                $binCounts = array_fill_keys(array_keys($bins), 0);

                foreach ($this->detailedClients[$router->id] as $client) {
                    $duration = $client['avg_duration_in_seconds'];
                    foreach ($bins as $label => $range) {
                        if ($duration >= $range['min'] && $duration < $range['max']) {
                            $binCounts[$label]++;
                            break;
                        }
                    }
                }

                $this->chartData[$router->id] = [
                    'labels' => array_keys($bins),
                    'data' => array_values($binCounts),
                    'router_name' => $router->identity ?? $router->location ?? 'Local Desconocido'
                ];
            }
        }
    }

    private function formatSeconds($seconds)
    {
        if ($seconds === null || $seconds <= 0) return "N/A";

        $seconds = round($seconds);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return $hours . "h " . $minutes . "m";
        } elseif ($minutes > 0) {
            return $minutes . "m " . $remainingSeconds . "s";
        } else {
            return $remainingSeconds . "s";
        }
    }

    public function render()
    {
        $routers = Router::when(auth()->user()->role !== 'admin', function($q) {
                return $q->where('user_id', auth()->id());
            })->get();

        return view('livewire.mikrotik.smartdata.permanencia', compact('routers'));
    }
}
