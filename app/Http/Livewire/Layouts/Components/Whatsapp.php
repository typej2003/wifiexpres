<?php

namespace App\Http\Livewire\Layouts\Components;

use Livewire\Component;
use App\Models\User;

class Whatsapp extends Component
{
    public function render()
    {
        $user = User::where('role', 'root')->first();
        
        $contactcellphone = $user->datosbasicos->cellphonecode. $user->datosbasicos->cellphone;
        $msgcontact = $user->datosbasicos->msgcontact;

        return view('livewire.layouts.components.whatsapp', compact('contactcellphone', 'msgcontact'));
    }
}
