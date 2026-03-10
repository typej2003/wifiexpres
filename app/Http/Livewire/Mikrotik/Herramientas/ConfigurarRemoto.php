<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigurarRemoto extends Component
{
    public $router_id;
    public $logs = [];
    public $isConfiguring = false;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function emitirAlSocket($comando, $mac, $tid)
    {
        try {
            $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
            $response = Http::withHeaders([
                'x-mac' => $mac,
                'x-id'  => $tid
            ])
            ->withBody($comandoLimpio, 'text/plain')
            ->post("{$this->bridgeUrl}/set-command");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error Bridge: " . $e->getMessage());
            return false;
        }
    }

    public function esperarRespuesta($mac, $tid)
    {
        for ($i = 0; $i < 30; $i++) {
            sleep(1);
            try {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    return $res->json('data');
                }
            } catch (\Exception $e) { }
        }
        return null;
    }

    public function ejecutarConfiguracion()
    {
        $this->validate(['router_id' => 'required']);
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);
        $tid = "CONF" . time();

        $this->isConfiguring = true;
        $this->logs[] = "Iniciando configuración remota para MAC: $mac...";

        // SCRIPT DE AUTOMATIZACIÓN MIKROTIK
        $script = "
            :local m \"$mac\"; :local t \"$tid\";
            :do {
                # 1. Crear Usuario de Soporte
                /user add name=\"soporte\" password=\"123\" group=full comment=\"Acceso remoto automatizado\";

                # 2. Crear Bridge LAN
                /interface bridge add name=bridge-lan comment=\"Bridge principal creado desde Panel\";

                # 3. Asignar puertos al bridge (excepto ether1 que suele ser WAN)
                :foreach i in=[/interface ethernet find where name!=\"ether1\"] do={
                    :local ethName [/interface ethernet get \$i name];
                    /interface bridge port add bridge=bridge-lan interface=\$ethName;
                };

                # 4. Asignar IP Address al Bridge
                /ip address add address=192.168.88.1/24 interface=bridge-lan network=192.168.88.0;

                # 5. Configurar Pool y DHCP Server
                /ip pool add name=dhcp_pool1 ranges=192.168.88.10-192.168.88.254;
                /ip dhcp-server add address-pool=dhcp_pool1 disabled=no interface=bridge-lan name=dhcp-remoto;
                /ip dhcp-server network add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=8.8.8.8,8.8.4.4;

                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"SUCCESS\" keep-result=no;
            } on-error={
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no;
            };
        ";

        if ($this->emitirAlSocket($script, $mac, $tid)) {
            $this->logs[] = "Comando enviado. Esperando ejecución en MikroTik...";
            $resultado = $this->esperarRespuesta($mac, $tid);

            if ($resultado === "SUCCESS") {
                $this->logs[] = "✅ ¡Configuración aplicada con éxito!";
                session()->flash('message', 'Router configurado correctamente.');
            } else {
                $this->logs[] = "❌ Error: El router no pudo aplicar los cambios o hubo timeout.";
            }
        } else {
            $this->logs[] = "❌ Error: No se pudo contactar con el Bridge.";
        }

        $this->isConfiguring = false;
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.configurar-remoto', [
            'routers' => Router::where('user_id', Auth::id())->get()
        ]);
    }
}