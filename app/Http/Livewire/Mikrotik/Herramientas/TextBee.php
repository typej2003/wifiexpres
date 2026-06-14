<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Services\TextBeeService;

class TextBee extends Component
{
    public $phone;
    public $message;

    protected $rules = [
        'phone' => 'required',
        'message' => 'required|max:160',
    ];

    public function sendSms(TextBeeService $textBeeService)
    {
        $this->validate();

        $response = $textBeeService->sendSms($this->phone, $this->message);

        if ($response['success']) {
            session()->flash('message', $response['message']);
            $this->reset(['phone', 'message']);
        } else {
            session()->flash('error', $response['message']);
        }
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.text-bee')->layout('layouts.app');
    }
}
