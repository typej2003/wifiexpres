<?php

namespace App\Http\Livewire\Notificacion;

use Livewire\Component;
use App\Http\Controllers\SendMailController;

class EmailFile extends Component
{

    public function sendFile()
    {
        $user = Auth()->user();

        $sendFile = new  SendMailController();
        
        $sendFile->sendMailWithAttachment($user);

        $this->dispatchBrowserEvent('hide-form', ['message' => 'Email enviado satisfactoriamente!']);
    }

    public function render()
    {
        return view('livewire.notificacion.email-file');
    }
}
