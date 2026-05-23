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

        // 1. Definir los segmentos a analizar
        $baseQuery = TicketLog::where('router_id', $this->selectedRouter)
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);

        // Filtro por Zona (Basado en el segmento IP guardado en mac_address)
        if ($this->selectedZona) {
            $mapping = AntennaMapping::find($this->selectedZona);
            if ($mapping) {
                // Extraemos los primeros 3 octetos de la IP de la zona
                $ipParts = explode('.', $mapping->ip_address);
                if (count($ipParts) >= 3) {
                    $segmento = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.';
                    $baseQuery->where('mac_address', 'LIKE', $segmento . '%');
                }
            }
        }

        // 2. Procesar Segmentos (General, Géneros, Edades)
        $segments = [
            'General' => null,
            'Femenino' => ['field' => 'gender', 'value' => 'F'],
            'Masculino' => ['field' => 'gender', 'value' => 'M'],
            'Edad: < 18' => ['field' => 'age', 'case' => 'menor18'],
            'Edad: 18-24' => ['field' => 'age', 'case' => '18-24'],
            'Edad: 25-35' => ['field' => 'age', 'case' => '25-35'],
            'Edad: > 35' => ['field' => 'age', 'case' => 'mayor35'],
        ];

        foreach ($segments as $label => $filter) {
            $segmentQuery = clone $baseQuery;

            if ($filter) {
                $segmentQuery->whereExists(function ($q) use ($filter) {
                    $q->select(DB::raw(1))
                        ->from('user_mikrotiks')
                        ->whereColumn('user_mikrotiks.name', 'ticket_logs.username')
                        ->whereColumn('user_mikrotiks.router_id', 'ticket_logs.router_id');
                    
                    if ($filter['field'] === 'gender') {
                        $q->where('gender', $filter['value']);
                    }
                    
                    if ($filter['field'] === 'age') {
                        switch ($filter['case']) {
                            case 'menor18': $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) < 18'); break;
                            case '18-24': $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 18 AND 24'); break;
                            case '25-35': $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 25 AND 35'); break;
                            case 'mayor35': $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) > 35'); break;
                        }
                    }
                });
            }

            // Obtener Totales
            $totalC = $segmentQuery->count();
            if ($totalC > 0 || $label === 'General') {
                $this->summaries[$label] = [
                    'conexiones' => $totalC,
                    'usuarios' => $segmentQuery->distinct('username')->count('username')
                ];

                // Obtener Matriz horaria
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
        }

        // Notificar al navegador para posibles actualizaciones de UI (JS)
        $this->dispatchBrowserEvent('reportUpdated');
    }

    public function render()
    {
        return view('livewire.mikrotik.data.hour-analysis');
    }
}