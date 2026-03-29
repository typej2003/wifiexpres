<?php

namespace App\Http\Livewire\Dashboards;

use Livewire\Component;
use App\Models\Router;
use App\Models\TicketLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AliadoDashboard extends Component
{
    public $period = 'today';

    public function setPeriod($value)
    {
        $this->period = $value;
        $this->emit('updateChart');
    }

    public function render()
    {
        $user = Auth::user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');

        [$start, $end] = match($this->period) {
            'weekly' => [now()->startOfWeek(), now()],
            'month'  => [now()->startOfMonth(), now()],
            default  => [now()->startOfDay(), now()],
        };

        $data = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$start, $end])
            ->select('router_id', DB::raw('count(*) as total'))
            ->groupBy('router_id')
            ->with('router:id,identity')
            ->get();

        return view('livewire.dashboards.aliado-dashboard', [
            'labels' => $data->map(fn($i) => $i->router->identity ?? 'MikroTik'),
            'values' => $data->pluck('total'),
        ])->layout('layouts.app');
    }
}