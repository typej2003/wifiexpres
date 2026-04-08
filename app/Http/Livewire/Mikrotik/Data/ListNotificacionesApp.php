<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\NotificationApp;

class ListNotificacionesApp extends Component
{
    use WithPagination;

    public $search = '';

    // Método para eliminar un registro individual
    public function delete($id)
    {
        $notificacion = NotificationApp::find($id);
        if ($notificacion) {
            $notificacion->delete();
            session()->flash('message', 'Registro eliminado correctamente.');
        }
    }

    // Método para limpiar todo el historial si lo deseas
    public function clearAll()
    {
        NotificationApp::truncate();
        session()->flash('message', 'Historial vaciado.');
    }

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