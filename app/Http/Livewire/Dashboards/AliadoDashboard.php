<?php

namespace App\Http\Livewire\Dashboards;

use Livewire\Component;
use App\Models\Package;
use App\Models\Router;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AliadoDashboard extends Component
{
    public $showPlanModal = false;
    public $period = 'today';

    public function mount()
    {
        $this->checkInitialPlan();
    }

    public function checkInitialPlan()
    {
        $user = Auth::user();
        $hasActive = $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->exists();
        $hasPending = $user->packages()->wherePivot('status', 'pending')->exists();

        if (!$hasActive && !$hasPending) {
            $this->showPlanModal = true;
        }
    }

    public function selectPlan($packageId)
    {
        $package = Package::findOrFail($packageId);
        $user = Auth::user();
        
        $alreadyPending = $user->packages()->where('package_id', $packageId)->wherePivot('status', 'pending')->exists();
        if($alreadyPending) {
            session()->flash('error', 'Ya tienes una solicitud pendiente para este plan.');
            return;
        }

        $user->packages()->attach($package->id, [
            'start_date' => now(),
            'end_date' => now()->addMonths($package->duration_months),
            'status' => 'pending',
            'allowed_routers' => $package->limit_routers,
            'router_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->showPlanModal = false;
        session()->flash('message', '¡Solicitud enviada! Tu plan se activará pronto.');
    }

    public function openModal() { $this->showPlanModal = true; }
    
    public function closeModal() 
    { 
        $user = Auth::user();
        $hasPlan = $user->packages()->wherePivotIn('status', ['active', 'pending'])->exists();
        if ($hasPlan) { $this->showPlanModal = false; }
    }

    public function setPeriod($value) 
    { 
        $this->period = $value; 
        
        // Obtenemos datos frescos para emitir el evento de actualización de ambos charts
        $data = $this->getChartData();
        $this->emit('updateCharts', $data['lineLabels'], $data['lineValues'], $data['donutLabels'], $data['donutValues']);
    }

    /**
     * Centraliza la lógica de datos para las gráficas
     */
    private function getChartData()
    {
        $user = Auth::user();
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');

        [$start, $end] = match($this->period) {
            'weekly' => [now()->startOfWeek(), now()],
            'month' => [now()->startOfMonth(), now()],
            default => [now()->startOfDay(), now()],
        };

        // Datos para Gráfico de Líneas (Tráfico)
        $format = ($this->period == 'today') ? '%H:00' : '%d/%m';
        $lineQuery = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw("DATE_FORMAT(created_at, '$format') as label"), DB::raw('count(*) as total'))
            ->groupBy('label')->orderBy('label')->get();

        // Datos para Gráfico de Dona (Distribución por Router)
        $donutQuery = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$start, $end])
            ->select('router_id', DB::raw('count(*) as total'))
            ->groupBy('router_id')
            ->with('router:id,identity')
            ->get();

        return [
            'lineLabels' => $lineQuery->pluck('label'),
            'lineValues' => $lineQuery->pluck('total'),
            'donutLabels' => $donutQuery->map(fn($item) => $item->router->identity ?? "Router #{$item->router_id}"),
            'donutValues' => $donutQuery->pluck('total'),
        ];
    }

    public function render()
    {
        $user = Auth::user();
        $activePlans = $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->get();
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');

        [$start, $end] = match($this->period) {
            'weekly' => [now()->startOfWeek(), now()],
            'month' => [now()->startOfMonth(), now()],
            default => [now()->startOfDay(), now()],
        };

        $chartData = $this->getChartData();

        return view('livewire.dashboards.aliado-dashboard', [
            'availablePackages' => Package::where('is_active', true)->where('is_visible', true)->get(),
            'activePlans' => $activePlans,
            'pendingPlans' => $user->packages()->wherePivot('status', 'pending')->get(),
            'routers' => $routers,
            'stats' => [
                'total_routers' => $routers->count(),
                'limit_routers' => $activePlans->sum('pivot.allowed_routers'),
                'total_tickets' => Ticket::whereIn('router_id', $routerIds)->count(),
                'tickets_activos' => Ticket::whereIn('router_id', $routerIds)->where('estado', 'activo')->count(),
                'conexiones_periodo' => TicketLog::whereIn('router_id', $routerIds)->whereBetween('created_at', [$start, $end])->count(),
            ],
            'lineLabels' => $chartData['lineLabels'],
            'lineValues' => $chartData['lineValues'],
            'donutLabels' => $chartData['donutLabels'],
            'donutValues' => $chartData['donutValues'],
            'topUsuarios' => TicketLog::whereIn('router_id', $routerIds)
                ->select('username', DB::raw('count(*) as total_conexiones'), DB::raw('sum(duration_seconds) as tiempo_total'))
                ->groupBy('username')->orderBy('total_conexiones', 'desc')->take(5)->get(),
            'ultimosLogs' => TicketLog::with('router')->whereIn('router_id', $routerIds)
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->get(),
            'dollarRate' => ExchangeRateService::getBcvRate()
        ])->layout('layouts.app');
    }
}