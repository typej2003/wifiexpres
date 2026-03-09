<?php

namespace App\Http\Livewire\Mikrotik\Router;

use Livewire\Component;
use App\Models\Router;

class ConfigureRouter extends Component
{
    public $router;

    public $showEditModal = false;

    public function mount($router_id)
    {        
        $this->router = Router::find($router_id);
    }
    
    public function render()
    {
        return view('livewire.mikrotik.router.configure-router');
    }
}
