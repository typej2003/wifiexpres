<?php

namespace App\Exports;

use App\Models\UserMikrotik;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UsersMikrotikExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    protected $search, $selectedAliado, $selectedRouter, $isAdmin, $periodo, $fecha_desde, $fecha_hasta;

    public function __construct($search, $selectedAliado, $selectedRouter, $isAdmin, $periodo, $fecha_desde, $fecha_hasta)
    {
        $this->search = $search;
        $this->selectedAliado = $selectedAliado;
        $this->selectedRouter = $selectedRouter;
        $this->isAdmin = $isAdmin;
        $this->periodo = $periodo;
        $this->fecha_desde = $fecha_desde;
        $this->fecha_hasta = $fecha_hasta;
    }

    public function query()
    {
        $query = UserMikrotik::query()->with(['router.user']);

        // Aplicamos la misma lógica de filtrado que el componente
        if ($this->isAdmin) {
            if ($this->selectedAliado) {
                $query->whereHas('router', function ($q) {
                    $q->where('user_id', $this->selectedAliado);
                });
            }
        } else {
            $query->whereHas('router', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        if ($this->selectedRouter) {
            $query->where('router_id', $this->selectedRouter);
        }

        if ($this->periodo === 'ultimos_50') {
            $query->limit(50);
        } else {
            if ($this->fecha_desde) {
                $query->whereDate('created_at', '>=', $this->fecha_desde);
            }
            if ($this->fecha_hasta) {
                $query->whereDate('created_at', '<=', $this->fecha_hasta);
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('full_name', 'like', '%' . $this->search . '%')
                  ->orWhere('cellphone', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Fecha Registro',
            'Nombre Completo',
            'Server',
            'Cod. Teléfono',
            'Teléfono',
            'Router Identity',
            'Aliado Propietario',
            'Email'
        ];
    }

    public function map($user): array
    {
        return [
            $user->created_at->format('d/m/Y H:i'),
            $user->full_name ?? 'N/A',
            $user->server,
            $user->cellphonecode,
            $user->cellphone,
            $user->router->identity ?? 'N/A',
            $user->router->user->name ?? 'Sistema',
            $user->email ?? '-'
        ];
    }
}