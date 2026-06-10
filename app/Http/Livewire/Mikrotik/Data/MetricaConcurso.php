<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\Router;
use App\Models\AdvertisingConcurso;
use App\Models\ConcursoResponse;
use App\Models\UserMikrotik;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MetricaConcurso extends Component
{
    // Filtros
    public $fromDate;
    public $toDate;
    public $selectedRouter = null;
    public $selectedConcurso = null;

    // Datos para selectores
    public $routers = [];
    public $concursos = [];

    // Resultados
    public $stats = [];
    public $chartData = [];
    public $tableData = [];

    public function mount()
    {
        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->format('Y-m-d');

        $user = auth()->user();
        
        // Obtener routers accesibles
        $this->routers = Router::query()
            ->when($user->role !== 'admin' && $user->role !== 'root', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })->get();

        // Obtener concursos creados
        $this->concursos = AdvertisingConcurso::query()
            ->when($user->role !== 'admin' && $user->role !== 'root', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })->get();

        if ($this->concursos->isNotEmpty()) {
            $this->selectedConcurso = $this->concursos->first()->id;
            $this->consultar();
        }
    }

    public function consultar()
    {
        $this->validate([
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after_or_equal:fromDate',
        ]);

        $query = ConcursoResponse::query()
            ->whereBetween('created_at', [
                Carbon::parse($this->fromDate)->startOfDay(), 
                Carbon::parse($this->toDate)->endOfDay()
            ]);

        if ($this->selectedRouter) {
            $router = Router::find($this->selectedRouter);
            if ($router) {
                $query->where('router_identity', $router->identity);
            }
        }

        if ($this->selectedConcurso) {
            $query->where('concurso_id', $this->selectedConcurso);
        }

        // 1. Estadísticas Generales
        $responses = $query->get();
        $this->stats = [
            'total_participantes' => $responses->count(),
            'usuarios_unicos' => $responses->unique('cellphone')->count(),
            'hombres' => $responses->where('concurso_target_gender', 'M')->count(), // Basado en campo denormalizado
            'mujeres' => $responses->where('concurso_target_gender', 'F')->count(),
        ];

        // 2. Distribución de Respuestas (para el gráfico)
        $distribution = $responses->groupBy('answer')
            ->map(fn($group) => $group->count());

        $this->chartData = [
            'labels' => $distribution->keys()->toArray(),
            'values' => $distribution->values()->toArray(),
        ];

        // 3. Datos de la tabla
        $this->tableData = $responses->take(50); // Limitamos para rendimiento

        $this->dispatchBrowserEvent('updateConcursoChart', $this->chartData);
    }

    public function render()
    {
        return view('livewire.mikrotik.data.metrica-concurso')->layout('layouts.app');
    }
}