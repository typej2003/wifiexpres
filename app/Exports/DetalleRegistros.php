<?php

namespace App\Exports;

use App\Models\TicketLog;
use App\Models\Router;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DetalleRegistros implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $router_id, $desde, $hasta;

    public function __construct($router_id = null, $desde = null, $hasta = null)
    {
        $this->router_id = $router_id;
        $this->desde = $desde ? Carbon::parse($desde)->startOfDay() : now()->subDays(7)->startOfDay();
        $this->hasta = $hasta ? Carbon::parse($hasta)->endOfDay() : now()->endOfDay();
    }

    /**
     * Consulta los datos de la base de datos aplicando los filtros y el join con UserMikrotik.
     */
    public function query()
    {
        $user = Auth::user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');

        return TicketLog::query()
            ->whereIn('ticket_logs.router_id', $routerIds)
            ->whereBetween('ticket_logs.created_at', [$this->desde, $this->hasta])
            ->when(!empty($this->router_id), fn($q) => $q->where('ticket_logs.router_id', $this->router_id))
            ->leftJoin('user_mikrotiks', function($join) {
                $join->on('user_mikrotiks.router_id', '=', 'ticket_logs.router_id')
                     ->on('user_mikrotiks.name', '=', DB::raw("REPLACE(ticket_logs.username, 'T-', '')"));
            })
            ->with('router')
            ->select(
                'ticket_logs.*', 
                'user_mikrotiks.full_name as client_name', 
                'user_mikrotiks.gender', 
                'user_mikrotiks.birthday'
            )
            ->latest('ticket_logs.created_at');
    }

    /**
     * Define los encabezados de las columnas del archivo Excel.
     */
    public function headings(): array
    {
        return [
            'USUARIO',
            'CLIENTE',
            'GÉNERO',
            'EDAD',
            'DIRECCIÓN IP/MAC',
            'ROUTER',
            'FECHA DE REGISTRO',
            'DURACIÓN',
        ];
    }

    /**
     * Mapea los datos de cada fila para controlar exactamente qué se escribe.
     */
    public function map($log): array
    {
        return [
            $log->username,
            $log->cliente_nombre, // Accessor en TicketLog
            $log->cliente_genero, // Accessor en TicketLog
            $log->cliente_edad,   // Accessor en TicketLog (calcula edad automáticamente)
            $log->mac_address,
            $log->router->identity ?? 'N/A',
            $log->created_at->format('d/m/Y H:i'),
            $log->duracion_formateada,
        ];
    }

    /**
     * Aplica estilos a la hoja de cálculo (por ejemplo, poner la fila 1 en negrita).
     * * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la primera fila (los encabezados)
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'] // Color índigo
                ]
            ],
        ];
    }
}