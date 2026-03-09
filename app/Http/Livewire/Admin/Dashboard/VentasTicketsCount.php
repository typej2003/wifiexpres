<?php

namespace App\Http\Livewire\Admin\Dashboard;

use App\Models\User;
use App\Models\TicketUser;
use Livewire\Component;

class VentasTicketsCount extends Component
{
    public $usersCount;

    public function mount()
    {
        $this->getVentasTicketsCount();
    }

    public function getVentasTicketsCount($option = 'TODAY')
    {   
        
        $this->usersCount = TicketUser::query();
        $this->usersCount = $this->usersCount->where('comment', 'activo');
        if(auth()->user()->role !== 'admin'){
            $this->usersCount = $this->usersCount->where('user_id', auth()->user()->id);
        }
        $this->usersCount = $this->usersCount->whereBetween('created_at', $this->getDateRange($option))
            ->count();

    }

    public function getDateRange($option)
    {
        if ($option == 'TODAY') {
            return [now()->today(), now()];
        }

        if ($option == 'MTD') {
            return [now()->firstOfMonth(), now()];
        }

        if ($option == 'YTD') {
            return [now()->firstOfYear(), now()];
        }

        return [now()->subDays($option), now()];
    }

    public function render()
    {
        return view('livewire.admin.dashboard.ventas-tickets-count');
    }
}
