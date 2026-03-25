<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\Router;
use App\Models\TicketLog;
use App\Models\AntennaMapping;
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
        $this->fromDate = now()->startOfMonth()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
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

        // Generar array de fechas en el rango
        $start = Carbon::parse($this->fromDate);
        $end = Carbon::parse($this->toDate);
        $this->dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $this->dates[] = $date->format('Y-m-d');
        }

        // Consulta Base
        $query = TicketLog::where('router_id', $this->selectedRouter)
            ->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);

        // Filtro por Zona (Basado en el segmento IP guardado en mac_address)
        if ($this->selectedZona) {
            $mapping = AntennaMapping::find($this->selectedZona);
            if ($mapping) {
                $segmento = implode('.', array_slice(explode('.', $mapping->ip_address), 0, 3)) . '.';
                $query->where('mac_address', 'LIKE', $segmento . '%');
            }
        }

        /**
         * NOTA: Los filtros de edad y género asumen una relación con una tabla 'hotspot_users'
         * vinculada por el campo 'username'. Ajusta el nombre de la tabla según tu DB.
         */
        if ($this->selectedEdad || $this->selectedGenero) {
            $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('hotspot_users') // Cambiar por tu tabla de perfiles
                    ->whereColumn('hotspot_users.username', 'ticket_logs.username');
                
                if ($this->selectedGenero) {
                    $q->where('gender', $this->selectedGenero);
                }

                if ($this->selectedEdad) {
                    switch ($this->selectedEdad) {
                        case 'menor18': $q->where('age', '<', 18); break;
                        case '18-24': $q->whereBetween('age', [18, 24]); break;
                        case '25-35': $q->whereBetween('age', [25, 35]); break;
                        case 'mayor35': $q->where('age', '>', 35); break;
                    }
                }
            });
        }

        // Agrupación por Día y Hora
        $results = $query->select([
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('HOUR(created_at) as hora'),
                DB::raw('COUNT(*) as total')
            ])
            ->groupBy('fecha', 'hora')
            ->get();

        // Mapear a matriz [fecha][hora]
        $matrix = [];
        foreach ($results as $row) {
            $matrix[$row->fecha][$row->hora] = $row->total;
        }

        $this->reportData = $matrix;

        // Notificar al JS que los datos cambiaron para cualquier efecto visual
        $this->dispatchBrowserEvent('reportUpdated');
    }

    public function render()
    {
        return view('livewire.mikrotik.data.hour-analysis');
    }
}