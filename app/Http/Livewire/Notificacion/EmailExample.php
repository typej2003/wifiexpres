<?php

namespace App\Http\Livewire\Notificacion;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserPayment;

class EmailExample extends Component
{

    public function sendEmail()
    {
        $user = Auth()->user();

        //$email = Mail::to($user->email)->send(new UserPayment($user));        

        $email = Mail::to('typej2003@gmail.com')->from('ventas@panexpres.com')->send(new UserPayment($user));        

        $this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario agregado satisfactoriamente!']);

    }

    public function render()
    {
        return view('livewire.notificacion.email-example');
    }
}
