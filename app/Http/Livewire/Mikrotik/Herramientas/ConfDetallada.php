<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use App\Models\HotspotVersion;
use Illuminate\Support\Facades\Http;

class ConfDetallada extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    public $version_id = null; 
    public $interfaces = []; 
    public $isWaitingResponse = false; 
    public $currentTid = null;
    public $intentos = 0;
    
    public $taskStatus = []; 
    public $taskResult = []; 
    public $activeTask = null; 
    public $queue = [];
    public $isProcessing = false; 

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function updatedSelectedAliado()
    {
        $this->reset(['router_id', 'interfaces', 'taskStatus', 'taskResult', 'isWaitingResponse', 'queue', 'isProcessing']);
    }

    public function updatedRouterId($value)
    {
        if ($value) $this->iniciarDescubrimiento();
    }

    public function iniciarDescubrimiento()
    {
        if (!$this->router_id) return;
        
        $this->reset(['interfaces', 'taskStatus', 'taskResult', 'intentos', 'queue']);
        $this->isWaitingResponse = true;
        $this->isProcessing = true;
        $this->currentTid = "DISC" . time();
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        $script = ":local ifs \"\"; :foreach i in=[/interface find where type~\"ether|wlan|wifi\"] do={ :set ifs (\$ifs . [/interface get \$i name] . \"|\") }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid={$this->currentTid}\" http-method=post http-data=\$ifs keep-result=no";
        
        $this->emitirAlBridge($script, $mac, $this->currentTid);
    }

    public function scanearInterfaz($iface, $index)
    {
        if ($this->isProcessing) return;
        $this->isProcessing = true;
        $this->queue = [];
        
        // SECUENCIA LÓGICA
        $tareas = ['limpiar_interfaz', 'bridge', 'address', 'pool', 'dhcp', 'hotspot'];
        foreach ($tareas as $t) {
            $this->queue[] = ['iface' => $iface, 'index' => $index, 'tarea' => $t];
        }
        $this->procesarSiguienteEnCola();
    }

    public function scanGlobal()
    {
        if (!$this->version_id || $this->isProcessing) return;
        $this->isProcessing = true;
        $this->queue = [];
        $tareas = ['wg_servidor', 'wg_bdv', 'wg_push', 'portal', 'reboot'];
        foreach ($tareas as $t) {
            $this->queue[] = ['iface' => 'global', 'index' => 0, 'tarea' => $t];
        }
        $this->procesarSiguienteEnCola();
    }

    public function procesarSiguienteEnCola()
    {
        if (count($this->queue) > 0) {
            $next = array_shift($this->queue);
            $this->ejecutarTarea($next['iface'], $next['index'], $next['tarea']);
        } else {
            // FIN DE PROCESO: Apagamos flags para detener el poll
            $this->isProcessing = false;
            $this->isWaitingResponse = false;
            $this->activeTask = null;
        }
    }

    public function ejecutarTarea($iface, $index, $tarea)
    {
        $this->taskStatus[$iface][$tarea] = 'loading';
        $this->taskResult[$iface][$tarea] = 'Enviando...';
        $this->activeTask = ['iface' => $iface, 'tarea' => $tarea, 'index' => $index];
        $this->isProcessing = true;
        $this->isWaitingResponse = true;
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $segmento = ($index + 1) * 10;
        
        $vCode = 1;
        if($this->version_id) {
            $versionObj = HotspotVersion::find($this->version_id);
            $vCode = $versionObj ? $versionObj->code : 1;
        }
        $downloadUrl = "https://wifiexpres.com/api/portal-download/" . $vCode;

        $cmds = [
            'limpiar_interfaz' => "
                :do { /ip hotspot remove [find where interface=\"bridge-$iface\"] } on-error={};
                :do { /ip hotspot profile remove [find where name=\"hsprof-$iface\" or name=\"hsprof1\"] } on-error={};
                :do { /ip dhcp-server remove [find where interface=\"bridge-$iface\"] } on-error={};
                :do { /ip dhcp-server network remove [find where gateway=\"192.168.$segmento.1\"] } on-error={};
                :do { /ip pool remove [find where name=\"pool-$iface\"] } on-error={};
                :do { /ip address remove [find where interface=\"bridge-$iface\"] } on-error={};
                :do { /interface bridge port remove [find where interface=\"$iface\"] } on-error={};
                :do { /interface bridge remove [find where name=\"bridge-$iface\"] } on-error={};
            ",
            'bridge'  => "/interface bridge add name=\"bridge-$iface\"; /interface bridge port add bridge=\"bridge-$iface\" interface=\"$iface\"",
            'address' => "/ip address add address=192.168.$segmento.1/24 interface=\"bridge-$iface\"",
            'pool'    => "/ip pool add name=\"pool-$iface\" ranges=192.168.$segmento.10-192.168.$segmento.250",
            'dhcp'    => "/ip dhcp-server add address-pool=\"pool-$iface\" interface=\"bridge-$iface\" name=\"srv-$iface\" disabled=no; /ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8",
            'hotspot' => "
                :do { /ip hotspot user profile remove [find where name~\"neutro|cortesia|conexion\"] } on-error={};
                /ip hotspot user profile add name=\"neutro\" shared-users=1 session-timeout=1s;
                /ip hotspot user profile add name=\"cortesia 20min-0\" shared-users=1 session-timeout=20m;
                /ip hotspot user profile add name=\"conexiongratis\" shared-users=1 rate-limit=\"2M/2M\";
                /ip hotspot profile add dns-name=wifi.login name=\"hsprof-$iface\" login-by=http-chap,http-pap,trial trial-user-profile=conexiongratis;
                /ip hotspot add address-pool=\"pool-$iface\" interface=\"bridge-$iface\" name=\"hotspot-$iface\" profile=\"hsprof-$iface\" disabled=no
            ",
            'wg_servidor' => "/ip hotspot walled-garden remove [find dst-host=\"wifiexpres.com\" or dst-host=\"*.wifiexpres.com\" or dst-host=\"188.95.113.44\"]; /ip hotspot walled-garden add dst-host=wifiexpres.com; /ip hotspot walled-garden add dst-host=*.wifiexpres.com; /ip hotspot walled-garden add dst-host=188.95.113.44; :do { /ip hotspot walled-garden ip remove [find dst-address=188.95.113.44] } on-error={}; /ip hotspot walled-garden ip add dst-address=188.95.113.44 dst-port=3000 protocol=tcp comment=\"Acceso Bridge Nodejs\"",
            'wg_bdv' => "/ip hotspot walled-garden remove [find comment=\"Pasarela BDV\"]; /ip hotspot walled-garden add dst-host=*.biopagobdv.com action=allow comment=\"Pasarela BDV\"; /ip hotspot walled-garden add dst-host=*.banvenez.com action=allow comment=\"Pasarela BDV\"; /ip hotspot walled-garden add dst-host=biopago.banvenez.com action=allow comment=\"Pasarela BDV\"; /ip hotspot walled-garden ip remove [find comment=\"Pasarela BDV\"]; /ip hotspot walled-garden ip add dst-address=190.217.7.106 action=accept comment=\"Pasarela BDV\"; /ip hotspot walled-garden ip add dst-address=190.217.7.229 action=accept comment=\"Pasarela BDV\"; /ip hotspot walled-garden ip add dst-address=200.11.243.174 action=accept comment=\"Pasarela BDV\"; /ip hotspot walled-garden ip add dst-address=190.202.148.187 action=accept comment=\"Pasarela BDV\"",
            'wg_push' => "/ip hotspot walled-garden remove [find comment=\"Notificaciones Push\"]; /ip hotspot walled-garden add dst-host=fcm.googleapis.com action=allow comment=\"Notificaciones Push\"; /ip hotspot walled-garden add dst-host=fcm-xmpp.googleapis.com action=allow comment=\"Notificaciones Push\"; /ip hotspot walled-garden add dst-host=mtalk.google.com action=allow comment=\"Notificaciones Push\"; /ip hotspot walled-garden add dst-host=*.push.apple.com action=allow comment=\"Notificaciones Push\"; /ip hotspot walled-garden add dst-host=*.push.apple.com.akadns.net action=allow comment=\"Notificaciones Push\"; /ip hotspot walled-garden add dst-host=appleid.apple.com action=allow comment=\"Notificaciones Push\"; /ip hotspot walled-garden ip remove [find comment=\"Notificaciones Push\"]; /ip hotspot walled-garden ip add dst-port=5228-5230 protocol=tcp action=accept comment=\"Notificaciones Push\"; /ip hotspot walled-garden ip add dst-port=5223 protocol=tcp action=accept comment=\"Notificaciones Push\"",
            'portal' => "/ip hotspot profile set [find name=\"hsprof1\" or name=\"hsprof-$iface\"] html-directory=hotspot; /tool fetch url=\"$downloadUrl\" dst-path=\"hotspot/login.html\" check-certificate=no",
            'reboot' => "/system reboot"
        ];

        $this->currentTid = "CFG" . rand(10,99) . time();
        $script = $cmds[$tarea] . "; :delay 2s; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid={$this->currentTid}\" http-method=post http-data=\"OK\" keep-result=no";
        
        $this->emitirAlBridge($script, $mac, $this->currentTid);
    }

    protected function emitirAlBridge($script, $mac, $tid)
    {
        $comandoLimpio = trim(preg_replace('/\s+/', ' ', $script));
        Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comandoLimpio, 'text/plain')->post("{$this->bridgeUrl}/set-command");
        $this->isWaitingResponse = true;
    }

    public function checkStatus()
    {
        if (!$this->isWaitingResponse) return;
        
        $router = Router::find($this->router_id);
        if (!$router) return;
        $mac = strtoupper(trim($router->macAddress));
        $this->intentos++;

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);
            if ($res->successful()) {
                $status = $res->json('status');
                $data = trim($res->json('data'));

                if ($status === 'ready') {
                    if ($this->activeTask) {
                        $this->finalizarTareaActual($data === "OK" ? 'success' : 'error', $data === "OK" ? 'OK' : 'Error');
                    } else {
                        $this->interfaces = array_values(array_filter(explode('|', $data)));
                        $this->isWaitingResponse = $this->isProcessing = false;
                        $this->intentos = 0;
                    }
                }
            }
            if ($this->intentos >= 60) $this->finalizarTareaActual('error', 'Expirado');
        } catch (\Exception $e) { }
    }

    private function finalizarTareaActual($status, $mensaje)
    {
        if ($this->activeTask) {
            $iface = $this->activeTask['iface'];
            $tarea = $this->activeTask['tarea'];
            $this->taskStatus[$iface][$tarea] = $status;
            $this->taskResult[$iface][$tarea] = $mensaje;
            
            // Apagamos la espera momentáneamente
            $this->isWaitingResponse = false;
            $this->activeTask = null;
            $this->intentos = 0;

            if ($status === 'success') {
                usleep(800000); 
                $this->procesarSiguienteEnCola();
            } else {
                $this->queue = [];
                $this->isProcessing = false;
            }
        }
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.conf-detallada', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::where('user_id', $this->selectedAliado)->get(),
            'hotspot_versions' => HotspotVersion::all()
        ]);
    }
}