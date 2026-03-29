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
        // Emitimos un evento para que JS sepa que debe redibujar con nuevos datos
        $this->emit('periodUpdated');
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

        $format = ($this->period == 'today') ? '%H:00' : '%d/%m';
        
        // Datos para gráfico de Líneas
        $chartQuery = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw("DATE_FORMAT(created_at, '$format') as label"), DB::raw('count(*) as total'))
            ->groupBy('label')->orderBy('label')->get();

        // Datos para gráfico de Torta (Distribución)
        $pieQuery = TicketLog::with('router')
            ->whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$start, $end])
            ->select('router_id', DB::raw('count(*) as total'))
            ->groupBy('router_id')->get();

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
            'chartLabels' => $chartQuery->pluck('label'),
            'chartData' => $chartQuery->pluck('total'),
            'pieLabels' => $pieQuery->map(fn($i) => $i->router->identity ?? 'MikroTik'),
            'pieValues' => $pieQuery->pluck('total'),
            'ultimosLogs' => TicketLog::with('router')->whereIn('router_id', $routerIds)
                ->whereBetween('created_at', [$start, $end])
                ->latest()->take(10)->get(),
            'dollarRate' => ExchangeRateService::getBcvRate()
        ])->layout('layouts.app');
    }
}