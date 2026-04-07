<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\NotificationApp;

class ListNotificacionesApp extends Component
{
    use WithPagination;

    public $search = '';

    // Polling de 5 segundos para que la lista se actualice sola si llegan notificaciones
    public function render()
    {
        $query = NotificationApp::query();

        if ($this->search) {
            $query->where('app_name', 'like', '%' . $this->search . '%')
                  ->orWhere('title', 'like', '%' . $this->search . '%');
        }

        return view('livewire.mikrotik.data.list-notificaciones-app', [
            'notificaciones' => $query->latest()->paginate(15)
        ])->layout('layouts.app');
    }
}