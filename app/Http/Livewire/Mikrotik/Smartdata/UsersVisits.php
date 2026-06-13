<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use App\Models\UserMikrotik;
use App\Models\TicketLog;
use App\Models\Router;

class UsersVisits extends Component
{
    public $search = '';
    public $selectedUserId = null;

    public function selectUser($id)
    {
        $this->selectedUserId = $id;
        $this->search = '';
    }

    public function render()
    {
        $usersSearch = [];
        if (strlen($this->search) > 2) {
            $usersSearch = UserMikrotik::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
                ->limit(5)
                ->get();
        }

        $selectedUser = $this->selectedUserId ? UserMikrotik::find($this->selectedUserId) : null;
        $visits = $selectedUser ? TicketLog::where('username', $selectedUser->name)->orderBy('created_at', 'desc')->get() : collect();
        $totalVisits = $visits->count();

        return view('livewire.mikrotik.smartdata.users-visits', compact('usersSearch', 'selectedUser', 'visits', 'totalVisits'));
    }
}
