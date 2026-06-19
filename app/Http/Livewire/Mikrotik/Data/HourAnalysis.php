<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\Router;
use App\Models\TicketLog;
use App\Models\AntennaMapping;
use App\Models\UserMikrotik;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
    public $routerStatus = [];

    // Resultados estructurados
    public $reports = []; // Matriz de matrices [segmento][fecha][hora]
    public $dates = [];
    public $summaries = []; // Totales por segmento

    // Datos para el gráfico de área
    public $chartLabels = [];
    public $chartData = [];

    public function mount()
    {
        // 1. Inicializar con el día actual
        $this->fromDate = Carbon::now()->format('Y-m-d');
        $this->toDate = Carbon::now()->format('Y-m-d');

        $user = auth()->user();
        $this->routers = Router::with('user')
            ->when($user->role !== 'admin' && $user->role !== 'root', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })->get();

        $this->refreshStatus();

        // Cargar datos automáticamente si existen routers para mejorar la experiencia de usuario
        if ($this->routers->isNotEmpty()) {
            $this->selectedRouter = $this->routers->first()->id;
            $this->updatedSelectedRouter($this->selectedRouter);
            $this->consultar();
        }
    }

    /**
     * Consulta el bridge para saber qué routers están conectados actualmente
     */
    public function refreshStatus()
    {
        try {
            $response = Http::timeout(2)->get('http://188.95.113.44:3000/api/routers-online');
            $activeMacs = $response->successful() ? collect($response->json())->pluck('mac')->toArray() : [];

            foreach ($this->routers as $r) {
                $this->routerStatus[$r->id] = in_array(strtoupper(trim($r->macAddress)), $activeMacs);
            }
        } catch (\Exception $e) {}
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

        $tableName = (new TicketLog)->getTable();

        // 1. Query Base: Solo Router y Rango de Fechas (El punto de partida "General")
        $baseQuery = TicketLog::where($tableName . '.router_id', $this->selectedRouter)
            ->whereBetween($tableName . '.created_at', [$start->startOfDay(), $end->endOfDay()]);

        // 2. Definir qué tablas (segmentos) vamos a generar
        $segmentsToProcess = ['General' => clone $baseQuery];

        // Si se seleccionó Zona, creamos un segmento específico
        if ($this->selectedZona) {
            $mapping = AntennaMapping::find($this->selectedZona);
            if ($mapping) {
                $ipParts = explode('.', $mapping->ip_address);
                if (count($ipParts) >= 3) {
                    $segmento = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.';
                    $segmentsToProcess['Zona: ' . $mapping->location_name] = (clone $baseQuery)
                        ->where($tableName . '.mac_address', 'LIKE', $segmento . '%');
                }
            }
        }

        // Si se seleccionó Edad, creamos un segmento específico
        if ($this->selectedEdad) {
            $labels = ['menor18' => '< 18', '18-24' => '18-24', '25-35' => '25-35', 'mayor35' => '> 35'];

            $segmentsToProcess['Edad: ' . $labels[$this->selectedEdad]] = (clone $baseQuery)
                ->whereExists(function ($q) use ($tableName) {
                    $q->select(DB::raw(1))
                        ->from('user_mikrotiks')
                        ->whereRaw("LOWER(TRIM(user_mikrotiks.name)) = REPLACE(LOWER(TRIM({$tableName}.username)), 't-', '')");
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
                ->whereExists(function ($q) use ($tableName) {
                    $q->select(DB::raw(1))
                        ->from('user_mikrotiks')
                        ->whereRaw("LOWER(TRIM(user_mikrotiks.name)) = REPLACE(LOWER(TRIM({$tableName}.username)), 't-', '')")
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

            $results = $segmentQuery->toBase()
                ->select([
                    DB::raw("DATE({$tableName}.created_at) as fecha"),
                    DB::raw("HOUR({$tableName}.created_at) as hora"),
                    DB::raw('COUNT(*) as total')
                ])
                ->groupBy(DB::raw("DATE({$tableName}.created_at)"), DB::raw("HOUR({$tableName}.created_at)"))
                ->get();

            $matrix = [];
            foreach ($results as $row) {
                $h = (int)$row->hora;
                $matrix[$row->fecha][$h] = $row->total;
            }
            $this->reports[$label] = $matrix;
        }

        // 4. Preparar datos para el gráfico de área (Basado en el segmento General)
        $this->chartLabels = [];
        $this->chartData = [];
        foreach ($this->dates as $date) {
            for ($h = 0; $h < 24; $h++) {
                // Etiqueta corta: "DD/MM 00h"
                $label = Carbon::parse($date)->format('d/m') . ' ' . str_pad($h, 2, '0', STR_PAD_LEFT) . 'h';
                $this->chartLabels[] = $label;
                $this->chartData[] = $this->reports['General'][$date][$h] ?? 0;
            }
        }

        $this->dispatchBrowserEvent('reportUpdated', ['labels' => $this->chartLabels, 'data' => $this->chartData]);
    }

    public function render()
    {
        return view('livewire.mikrotik.data.hour-analysis');
    }
}