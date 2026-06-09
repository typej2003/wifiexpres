<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\TicketLog;
use App\Models\UserMikrotik;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShowChart extends Component
{
    public $selectedAliado = '';
    public $selectedRouter = '';
    public $periodo = 'hoy';
    public $fecha_desde, $fecha_hasta;
    public $isAdmin = false;

    public function mount()
    {
        $user = Auth::user();
        $this->isAdmin = in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ROOT]);
        $this->fecha_desde = now()->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function updatedPeriodo($value)
    {
        if ($value === 'hoy') {
            $this->fecha_desde = now()->format('Y-m-d');
            $this->fecha_hasta = now()->format('Y-m-d');
        } elseif ($value === 'semana') {
            $this->fecha_desde = now()->startOfWeek()->format('Y-m-d');
            $this->fecha_hasta = now()->format('Y-m-d');
        } elseif ($value === 'mes') {
            $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
            $this->fecha_hasta = now()->format('Y-m-d');
        }
    }

    public function updatedSelectedAliado()
    {
        $this->selectedRouter = '';
    }

    public function render()
    {
        $user = Auth::user();
        $desde = Carbon::parse($this->fecha_desde)->startOfDay();
        $hasta = Carbon::parse($this->fecha_hasta)->endOfDay();

        // Obtener lista de aliados si es admin
        $aliados = $this->isAdmin 
            ? User::whereIn('role', [User::ROLE_ALIADO, User::ROLE_ALIADOSMARTDATA])->orderBy('name')->get() 
            : [];

        // Obtener routers filtrados por el rol o aliado seleccionado
        $routersQuery = Router::query()
            ->when(!$this->isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->when($this->isAdmin && $this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
            ->orderBy('identity');
        
        $routers = $routersQuery->get();
        $routerIds = $routers->pluck('id');

        // 1. Datos: Sesiones por Router (Gráfico de Torta)
        $sessions = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$desde, $hasta])
            ->when($this->selectedRouter, fn($q) => $q->where('router_id', $this->selectedRouter))
            ->select('router_id', DB::raw('count(*) as total'))
            ->groupBy('router_id')
            ->with('router:id,identity')
            ->get();

        // 2. Datos: Género (Donut)
        $genders = UserMikrotik::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$desde, $hasta])
            ->when($this->selectedRouter, fn($q) => $q->where('router_id', $this->selectedRouter))
            ->select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->get();

        $genderLabels = $genders->map(fn($g) => $g->gender == 'F' ? 'Femenino' : ($g->gender == 'M' ? 'Masculino' : 'Otro'));

        // 3. Datos: Edades (Buckets)
        $ages = UserMikrotik::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$desde, $hasta])
            ->when($this->selectedRouter, fn($q) => $q->where('router_id', $this->selectedRouter))
            ->select(DB::raw("
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) < 18 THEN 'Menores de 18'
                    WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 18 AND 24 THEN '18-24 años'
                    WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 25 AND 35 THEN '25-35 años'
                    ELSE 'Mayores de 35'
                END as label
            "), DB::raw('count(*) as total'))
            ->groupBy('label')
            ->get();

        // Emitir evento para actualizar los gráficos en JS
        $this->dispatchBrowserEvent('updateCharts', [
            'routerLabels' => $sessions->pluck('router.identity'),
            'routerValues' => $sessions->pluck('total'),
            'genderLabels' => $genderLabels,
            'genderValues' => $genders->pluck('total'),
            'ageLabels'    => $ages->pluck('label'),
            'ageValues'    => $ages->pluck('total'),
        ]);

        return view('livewire.mikrotik.data.show-chart', [
            'aliados' => $aliados,
            'routers' => $routers,
            'totalConexiones' => $sessions->sum('total'),
        ]);
    }
}
