<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Aliado;
use App\Models\Router;

class ConfDetallada extends Component
{
    // Filtros de selección
    public $aliado_id = null;
    public $router_id = null;
    
    // Datos cargados
    public $interfaces = [];
    public $status = [];
    public $logs = [];
    public $identity = "MikroTik";

    public function mount()
    {
        // No cargamos nada inicialmente para que el usuario elija
    }

    // Al cambiar Aliado, reseteamos Router e interfaces
    public function updatedAliadoId($value)
    {
        $this->router_id = null;
        $this->interfaces = [];
    }

    // Al cambiar Router, intentamos conectar y obtener interfaces
    public function updatedRouterId($value)
    {
        if ($value) {
            $this->conectarMikrotik();
        }
    }

    public function conectarMikrotik()
    {
        $router = Router::find($this->router_id);
        if ($router) {
            $this->identity = $router->identity;
            // Aquí iría la lógica real de obtención de interfaces (ej. ether2, ether3...)
            // Por ahora simulamos para armar la vista
            $this->interfaces = ['ether2', 'ether3', 'ether4', 'ether5'];
            
            foreach ($this->interfaces as $iface) {
                $this->status[$iface] = 'idle';
                $this->logs[$iface] = 'Equipo conectado. Listo para configurar.';
            }
            $this->status['profiles'] = 'idle';
            $this->logs['profiles'] = 'Esperando configuración global...';
        }
    }

    public function configurarPuerto($interface, $index)
    {
        $this->status[$interface] = 'loading';
        $this->logs[$interface] = "Enviando comandos a $interface...";

        // Simulación de proceso
        sleep(1); 

        $this->status[$interface] = 'success';
        $this->logs[$interface] = "✅ Configuración aplicada con éxito en $interface.";
    }

    public function configurarPerfiles()
    {
        $this->status['profiles'] = 'loading';
        sleep(1);
        $this->status['profiles'] = 'success';
        $this->logs['profiles'] = "✅ Perfiles de Hotspot y Walled Garden actualizados.";
    }

    public function render()
    {
        // Cargamos aliados aquí para que siempre estén disponibles
        $aliados = Aliado::orderBy('nombre', 'asc')->get();
        $routers = $this->aliado_id ? Router::where('aliado_id', $this->aliado_id)->get() : [];

        return view('livewire.mikrotik.herramientas.conf-detallada', [
            'lista_aliados' => $aliados,
            'lista_routers' => $routers
        ]);
    }
}