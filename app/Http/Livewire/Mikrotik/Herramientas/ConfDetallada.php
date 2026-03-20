<?php

namespace App\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use Illuminate\Support\Facades\Log;

class ConfDetallada extends Component
{
    public $router_id;
    public $interfaces = [];
    public $loading_interfaces = false;
    
    // Estados de carga por sección
    public $status = []; // Almacena 'loading', 'success', 'error'
    public $logs = [];   // Almacena el output de cada comando

    public function mount($router_id)
    {
        $this->router_id = $router_id;
        $this->cargarInterfaces();
    }

    public function cargarInterfaces()
    {
        $this->loading_interfaces = true;
        try {
            $router = Router::findOrFail($this->router_id);
            // Simulación de comando para obtener interfaces físicas (no ether1)
            // $this->interfaces = $router->sendRaw('/interface ethernet find where name~"ether" and name!="ether1"');
            
            // Ejemplo estático para desarrollo de la vista (esto vendría del MikroTik)
            $this->interfaces = ['ether2', 'ether3', 'ether4', 'ether5'];
            
            foreach ($this->interfaces as $iface) {
                $this->status[$iface] = 'idle';
                $this->logs[$iface] = 'Esperando acción...';
            }
            $this->status['profiles'] = 'idle';
            $this->status['walled_garden'] = 'idle';

        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo conectar con el MikroTik: ' . $e->getMessage());
        }
        $this->loading_interfaces = false;
    }

    public function configurarPuerto($interface, $index)
    {
        $this->status[$interface] = 'loading';
        $this->logs[$interface] = "Iniciando configuración para $interface...";

        $ip_base = "192.168." . (($index + 2) * 10);
        $cmds = [
            "/interface bridge add name=bridge-$interface",
            "/interface bridge port add bridge=bridge-$interface interface=$interface",
            "/ip address add address=$ip_base.1/24 interface=bridge-$interface",
            "/ip pool add name=pool-$interface ranges=$ip_base.10-$ip_base.250",
            "/ip dhcp-server add name=srv-$interface interface=bridge-$interface address-pool=pool-$interface disabled=no",
            "/ip hotspot add name=hotspot-$interface interface=bridge-$interface address-pool=pool-$interface profile=hsprof1 disabled=no"
        ];

        // Aquí ejecutarías cada comando vía tu API/SSH
        // foreach($cmds as $cmd) { $router->execute($cmd); }

        $this->status[$interface] = 'success';
        $this->logs[$interface] = "✅ Configurado: Bridge, IP($ip_base.1), Pool y Hotspot creados.";
    }

    public function configurarPerfiles()
    {
        $this->status['profiles'] = 'loading';
        
        $cmds = [
            '/ip hotspot user profile add name="neutro" session-timeout=1s shared-users=1',
            '/ip hotspot user profile add name="conexiongratis" shared-users=1 status-autorefresh=1m rate-limit="2M/2M"',
            '/ip hotspot profile add dns-name=wifi.login name=hsprof1 login-by=http-chap,http-pap,trial trial-user-profile=conexiongratis'
        ];

        $this->status['profiles'] = 'success';
        $this->logs['profiles'] = "✅ Perfiles de usuario y servidor actualizados.";
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.conf-detallada');
    }
}