<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\Router;
use App\Models\TicketLog;
use App\Models\AntennaMapping;
use App\Models\UserMikrotik;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HourAnalysis extends Component
{
    // Filtros
    public $fromDate;
    public $toDate;
    public $selectedRouter = null;
    public $selectedZona = null;
    public $selectedEdad = null;
    public $selectedGenero = null;

    // Datos para selectores
    public $routers = [];
    public $zonas = [];

    // Resultados estructurados
    public $reports = []; // Matriz de matrices [segmento][fecha][hora]
    public $dates = [];
    public $summaries = []; // Totales por segmento

    public function mount()
    {
        // 1. Inicializar con el día actual
        $this->fromDate = Carbon::now()->format('Y-m-d');
        $this->toDate = Carbon::now()->format('Y-m-d');
        $this->routers = Router::where('is_active', true)->get();

        // Cargar datos automáticamente si existen routers para mejorar la experiencia de usuario
        if ($this->routers->isNotEmpty()) {
            $this->selectedRouter = $this->routers->first()->id;
            $this->updatedSelectedRouter($this->selectedRouter);
            $this->consultar();
        }
    }

    public function updatedSelectedRouter($value)
    {
        $this->selectedZona = null;
        if ($value) {
            $this->zonas = AntennaMapping::where('router_id', $value)->get();
        } else {
            $this->zonas = [];
        }
    }

    public function consultar()
    {
        $this->validate([
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after_or_equal:fromDate',
            'selectedRouter' => 'required'
        ]);

        // Generar array de fechas en el rango para la cabecera de filas
        $start = Carbon::parse($this->fromDate);
        $end = Carbon::parse($this->toDate);
        $this->dates = [];
        
        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            $this->dates[] = $tempDate->format('Y-m-d');
            $tempDate->addDay();
        }

        $this->reports = [];
        $this->summaries = [];

        // 1. Query Base: Solo Router y Rango de Fechas (El punto de partida "General")
        $baseQuery = TicketLog::where('router_id', $this->selectedRouter)
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);

        // 2. Definir qué tablas (segmentos) vamos a generar
        $segmentsToProcess = [
            'General' => clone $baseQuery
        ];

        // Si se seleccionó Zona, creamos un segmento específico
        if ($this->selectedZona) {
            $mapping = AntennaMapping::find($this->selectedZona);
            if ($mapping) {
                $ipParts = explode('.', $mapping->ip_address);
                if (count($ipParts) >= 3) {
                    $segmento = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.';
                    $segmentsToProcess['Zona: ' . $mapping->location_name] = (clone $baseQuery)
                        ->where('mac_address', 'LIKE', $segmento . '%');
                }
            }
        }

        // Si se seleccionó Edad, creamos un segmento específico
        if ($this->selectedEdad) {
            $labels = ['menor18' => '< 18', '18-24' => '18-24', '25-35' => '25-35', 'mayor35' => '> 35'];
            $segmentsToProcess['Edad: ' . $labels[$this->selectedEdad]] = (clone $baseQuery)
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('user_mikrotiks')
                        ->whereColumn('user_mikrotiks.name', 'ticket_logs.username')
                        ->whereColumn('user_mikrotiks.router_id', 'ticket_logs.router_id');
                    switch ($this->selectedEdad) {
                        case 'menor18': $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) < 18'); break;
                        case '18-24': $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 18 AND 24'); break;
                        case '25-35': $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 25 AND 35'); break;
                        case 'mayor35': $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) > 35'); break;
                    }
                });
        }

        // Si se seleccionó Género, creamos un segmento específico
        if ($this->selectedGenero) {
            $genLabel = $this->selectedGenero == 'F' ? 'Femenino' : 'Masculino';
            $segmentsToProcess['Género: ' . $genLabel] = (clone $baseQuery)
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('user_mikrotiks')
                        ->whereColumn('user_mikrotiks.name', 'ticket_logs.username')
                        ->whereColumn('user_mikrotiks.router_id', 'ticket_logs.router_id')
                        ->where('gender', $this->selectedGenero);
                });
        }

        // 3. Ejecutar y mapear cada segmento
        foreach ($segmentsToProcess as $label => $segmentQuery) {
            $totalC = (clone $segmentQuery)->count();
            $uniqueU = (clone $segmentQuery)->distinct('username')->count('username');

            // Guardamos resumen para los badges de las tablas
            $this->summaries[$label] = [
                'conexiones' => $totalC,
                'usuarios' => $uniqueU,
                'porcentaje' => 100 // No necesario para tabla de impacto pero útil para lógica interna
            ];

            $results = $segmentQuery->select([
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('HOUR(created_at) as hora'),
                DB::raw('COUNT(*) as total')
            ])
            ->groupBy('fecha', 'hora')
            ->get();

            $matrix = [];
            foreach ($results as $row) {
                $matrix[$row->fecha][$row->hora] = $row->total;
            }
            $this->reports[$label] = $matrix;
        }

        $this->dispatchBrowserEvent('reportUpdated');
    }

    public function render()
    {
        return view('livewire.mikrotik.data.hour-analysis');
    }
}