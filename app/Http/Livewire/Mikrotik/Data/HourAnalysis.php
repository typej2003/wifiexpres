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

    // Resultados
    public $reportData = [];
    public $dates = [];

    public function mount()
    {
        // Inicializar con el mes actual de 2026 según el contexto
        $this->fromDate = Carbon::create(2026, 3, 1)->format('Y-m-d');
        $this->toDate = Carbon::create(2026, 3, 30)->format('Y-m-d');
        $this->routers = Router::where('is_active', true)->get();
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

        // Consulta Base sobre TicketLog
        $query = TicketLog::where('router_id', $this->selectedRouter)
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);

        // Filtro por Zona (Basado en el segmento IP guardado en mac_address)
        if ($this->selectedZona) {
            $mapping = AntennaMapping::find($this->selectedZona);
            if ($mapping) {
                // Extraemos los primeros 3 octetos de la IP de la zona
                $ipParts = explode('.', $mapping->ip_address);
                if (count($ipParts) >= 3) {
                    $segmento = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.';
                    $query->where('mac_address', 'LIKE', $segmento . '%');
                }
            }
        }

        // Filtros de Edad y Género cruzando con UserMikrotik
        if ($this->selectedEdad || $this->selectedGenero) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('user_mikrotiks')
                    ->whereColumn('user_mikrotiks.name', 'ticket_logs.username')
                    ->whereColumn('user_mikrotiks.router_id', 'ticket_logs.router_id');
                
                if ($this->selectedGenero) {
                    $q->where('gender', $this->selectedGenero);
                }

                if ($this->selectedEdad) {
                    // Cálculo de edad basado en el campo birthday
                    switch ($this->selectedEdad) {
                        case 'menor18': 
                            $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) < 18'); 
                            break;
                        case '18-24': 
                            $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 18 AND 24'); 
                            break;
                        case '25-35': 
                            $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 25 AND 35'); 
                            break;
                        case 'mayor35': 
                            $q->whereRaw('TIMESTAMPDIFF(YEAR, birthday, CURDATE()) > 35'); 
                            break;
                    }
                }
            });
        }

        // Agrupación por Día y Hora para la matriz
        $results = $query->select([
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('HOUR(created_at) as hora'),
                DB::raw('COUNT(*) as total')
            ])
            ->groupBy('fecha', 'hora')
            ->get();

        // Mapear los resultados a una matriz estructurada [fecha][hora]
        $matrix = [];
        foreach ($results as $row) {
            $matrix[$row->fecha][$row->hora] = $row->total;
        }

        $this->reportData = $matrix;

        // Notificar al navegador para posibles actualizaciones de UI (JS)
        $this->dispatchBrowserEvent('reportUpdated');
    }

    public function render()
    {
        return view('livewire.mikrotik.data.hour-analysis');
    }
}