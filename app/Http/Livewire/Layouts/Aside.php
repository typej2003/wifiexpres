<?php

namespace App\Http\Livewire\Layouts;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\Cita;

class Aside extends Component
{
    public $totalUsuarios = 0;

    public function render()
    {
        $this->totalUsuarios = User::all()->count();

        $this->totalCitas = Cita::where('atendida', false)->count();

        $this->totalRouters = Router::all()->count();

        return view('livewire.layouts.aside');
    }
}
