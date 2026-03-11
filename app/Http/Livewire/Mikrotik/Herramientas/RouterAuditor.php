<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RouterAuditor extends Component
{
    public $routersOnline = [];
    public $bridgeUrl = "http://188.95.113.44:3000"; 
    public $error = null;

    public function mount()
    {
        $this->refreshData();
    }

    public function refreshData()
    {
        try {
            $this->error = null;
            // Subimos a 10 segundos el timeout para auditoría pesada
            $response = Http::timeout(10)->get($this->bridgeUrl . '/api/routers-online');
            
            if ($response->successful()) {
                $this->routersOnline = $response->json();
            } 
        } catch (\Exception $e) {
            $this->error = "Bridge desconectado o lento.";
        }
    }

    public function sendTestCommand($mac)
    {
        $tid = "TEST" . Str::upper(Str::random(5));
        // Pasamos mac y tid en la URL, y el data en el cuerpo
        $script = ':log info "Prueba Bridge"; /tool fetch url="' . $this->bridgeUrl . '/post-result?mac=' . $mac . '&tid=' . $tid . '" http-method=post http-data="TEST_OK" keep-result=no';

        try {
            $response = Http::withHeaders([
                'x-mac' => $mac,
                'x-id' => $tid
            ])->withBody($script, 'text/plain')->post($this->bridgeUrl . '/set-command');

            if ($response->successful()) {
                session()->flash('message', "Comando [$tid] enviado.");
            }
        } catch (\Exception $e) {
            $this->error = "Error al enviar comando.";
        }
        
        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.router-auditor');
    }
}