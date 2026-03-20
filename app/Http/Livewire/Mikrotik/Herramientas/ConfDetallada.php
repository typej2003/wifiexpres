<?php

namespace App\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Aliado; // Asumiendo que existe el modelo Aliado
use App\Models\Router;

class ConfDetallada extends Component
{
    // Filtros
    public $aliados = [];
    public $routers = [];
    public $aliado_id = null;
    public $router_id = null;

    // Datos del MikroTik seleccionado
    public $interfaces = [];
    public $status = [];
    public $logs = [];
    public $identity = "MikroTik";

    public function mount()
    {
        // Cargamos los aliados al iniciar
        $this->aliados = Aliado::orderBy('nombre', 'asc')->get();
    }

    // Se ejecuta automáticamente cuando cambia aliado_id
    public function updatedAliadoId($value)
    {
        $this->routers = Router::where('aliado_id', $value)->get();
        $this->reset(['router_id', 'interfaces', 'status', 'logs']);
    }

    // Se ejecuta automáticamente cuando cambia router_id
    public function updatedRouterId($value)
    {
        if ($value) {
            $this->conectarMikrotik();
        } else {
            $this->reset(['interfaces', 'status', 'logs']);
        }
    }

    public function conectarMikrotik()
    {
        $this->validate(['router_id' => 'required']);
        $router = Router::find($this->router_id);
        $this->identity = $router->identity ?? 'MikroTik';

        try {
            // AQUÍ: Lógica real para obtener interfaces vía API/SSH
            // Simulación de respuesta del MikroTik:
            $this->interfaces = ['ether2', 'ether3', 'ether4', 'ether5'];
            
            foreach ($this->interfaces as $iface) {
                $this->status[$iface] = 'idle';
                $this->logs[$iface] = 'Equipo conectado. Esperando comandos...';
            }
            $this->status['profiles'] = 'idle';
            $this->logs['profiles'] = 'Pendiente de configuración.';
            
        } catch (\Exception $e) {
            $this->logs['global'] = "Error de conexión: " . $e->getMessage();
        }
    }

    public function configurarPuerto($interface, $index)
    {
        $this->status[$interface] = 'loading';
        $this->logs[$interface] = "Enviando secuencia a $interface...";

        // Lógica de segmentos (192.168.20.1, 192.168.30.1, etc.)
        $segmento = ($index + 2) * 10;
        
        // Simulación de comandos secuenciales
        sleep(1); 

        $this->status[$interface] = 'success';
        $this->logs[$interface] = "✅ Configurado: IP 192.168.$segmento.1/24, Bridge, Pool y Hotspot activos.";
    }

    public function configurarPerfiles()
    {
        $this->status['profiles'] = 'loading';
        sleep(1);
        $this->status['profiles'] = 'success';
        $this->logs['profiles'] = "✅ Perfiles: Neutro, ConexionGratis y hsprof1 configurados.";
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.conf-detallada');
    }
}