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
            $response = Http::timeout(4)->get($this->bridgeUrl . '/api/routers-online');
            
            if ($response->successful()) {
                $this->routersOnline = $response->json();
            } else {
                $this->error = "Bridge respondió con error: " . $response->status();
            }
        } catch (\Exception $e) {
            $this->error = "Error de conexión con el Bridge en " . $this->bridgeUrl;
            $this->routersOnline = [];
        }
    }

    public function sendTestCommand($mac)
    {
        $tid = "TEST" . Str::upper(Str::random(5));
        $script = ':log info "Prueba de Auditoria Bridge"; /tool fetch url="' . $this->bridgeUrl . '/post-result?mac=' . $mac . '&tid=' . $tid . '" http-method=post http-data="TEST_OK" keep-result=no';

        try {
            $response = Http::withHeaders([
                'x-mac' => $mac,
                'x-id' => $tid
            ])->withBody($script, 'text/plain')->post($this->bridgeUrl . '/set-command');

            if ($response->successful()) {
                session()->flash('message', "Comando [$tid] enviado a la cola.");
            }
        } catch (\Exception $e) {
            $this->error = "No se pudo enviar el comando de prueba.";
        }
        
        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.router-auditor');
    }
}