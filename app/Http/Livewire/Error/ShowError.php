<?php

namespace App\Http\Livewire\Error;

use Livewire\Component;

class ShowError extends Component
{
    public $error = 0;
    public $description = "";

    public function mount($error)
    {
        $this->error = $error;
        switch ($error) {
            case '10':
                $this->description = "Comercio no existe";
                break;
            
            case '11':
                $this->description = "Comercio no pertenece al usuario";
                break;

            case '12':
                $this->description = "Pedido no pertenece a este Comercio";
                break;
        }
    }

    public function render()
    {
        return view('livewire.error.show-error');
    }
}
