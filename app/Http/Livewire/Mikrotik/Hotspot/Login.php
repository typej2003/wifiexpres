<?php

namespace App\Http\Livewire\Mikrotik\Hotspot;

use Livewire\Component;

class Login extends Component
{
    public function login()
    {
        dd('ok');
    }

    public function render()
    {
        return view('livewire.mikrotik.hotspot.login');
    }
}
