<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserMikrotik;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\DB;

class UsersVisits extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $selectedUserId = null;
    public $from = null;

    public function mount($userId = null)
    {
        if ($userId) {
            $this->selectedUserId = $userId;
        }
        $this->from = request()->query('from');
    }

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
        if ($this->from === 'permanencia') {
            return redirect()->route('smartdata.permanencia');
        }
        $this->selectedUserId = null;
    }

    public function render()
    {
        $selectedUser = $this->selectedUserId ? UserMikrotik::find($this->selectedUserId) : null;
        
        $users = UserMikrotik::query()->where(function($query) {
            $term = '%' . $this->search . '%';
            $query->where('full_name', 'like', $term)
                  ->orWhere('name', 'like', $term)
                  ->orWhere('server', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere(DB::raw("CONCAT(COALESCE(cellphonecode,''), COALESCE(cellphone,''))"), 'like', $term);
        })
        ->latest()
        ->paginate(15);

        // Corregimos la consulta para que coincida con el formato 'T-MAC' de TicketLog
        $visits = $selectedUser 
            ? TicketLog::where('username', 'T-' . $selectedUser->name)->orderBy('created_at', 'desc')->get() 
            : collect();
            
        $totalVisits = $visits->count();

        return view('livewire.mikrotik.smartdata.users-visits', compact('users', 'selectedUser', 'visits', 'totalVisits'));
    }
}
