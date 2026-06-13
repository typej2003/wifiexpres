<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserMikrotik;
use App\Models\TicketLog;
use App\Models\Router;

class UsersVisits extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $selectedUserId = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectUser($id)
    {
        $this->selectedUserId = $id;
    }

    public function deselectUser()
    {
        $this->selectedUserId = null;
    }

    public function render()
    {
        $selectedUser = $this->selectedUserId ? UserMikrotik::find($this->selectedUserId) : null;
        
        $users = UserMikrotik::where(function($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
        })->paginate(15);

        $visits = $selectedUser ? TicketLog::where('username', $selectedUser->name)->orderBy('created_at', 'desc')->get() : collect();
        $totalVisits = $visits->count();

        return view('livewire.mikrotik.smartdata.users-visits', compact('users', 'selectedUser', 'visits', 'totalVisits'));
    }
}
