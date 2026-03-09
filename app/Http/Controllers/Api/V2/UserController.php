<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Router; 
use App\Models\UserMikrotik;
use App\Models\HotspotSetting; // Asegúrate de que esté importado

class UserController extends Controller
{
    protected $bridgeUrl = "http://188.95.113.44:3000";

    protected function getMacByIdentity($identity)
    {
        $router = Router::where('identity', $identity)->first();
        return $router ? strtoupper(trim($router->macAddress)) : null;
    }

    /**
     * INFO VISUAL PARA EL PORTAL
     */
    /**
     * INFO VISUAL PARA EL PORTAL
     */
    public function getRouterInfo(Request $request) {
        $identity = $request->query('identity');
        
        // Cargamos el router con su configuración de hotspot asociada
        $router = Router::where('identity', $identity)->with('hotspotSetting')->first();

        if (!$router) {
            return response()->json(['success' => false, 'message' => 'Router no encontrado'], 404);
        }

        // --- LÓGICA DE SELECCIÓN DE BANNER ---
        $banner = "banner/WIFIEXPRES_banner_01.jpg"; // Imagen por defecto

        /**
         * Prioridad 1: Si tiene HotspotSetting y el carrusel tiene imágenes, 
         * seleccionamos una al azar para el portal.
         */
        if ($router->hotspotSetting && !empty($router->hotspotSetting->carousel_images)) {
            $imagenes = $router->hotspotSetting->carousel_images;
            
            if (is_array($imagenes) && count($imagenes) > 0) {
                $fotoAleatoria = $imagenes[array_rand($imagenes)];
                $banner = 'https://wifiexpres.com/storage/carruselhotspot/' . $fotoAleatoria;
            }
        } 
        /**
         * Prioridad 2: Si no hay carrusel pero el router tiene un banner de comercio único.
         */
        elseif ($router->comercio_banner) {
            $banner = 'https://wifiexpres.com/storage/bannerrouter/' . $router->comercio_banner;
        }

        return response()->json([
            'success' => true,
            'router' => [
                'name'    => $router->comercio_nombre ?? "WIFI EXPRES",
                'banner'  => $banner,
                'store'   => $router->store ?? "Sucursal",
                'address' => $router->address ?? "",
                // Enviamos flags adicionales por si el portal necesita saber si el carrusel está activo
                'show_carousel' => $router->hotspotSetting->show_carousel ?? false 
            ]
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * CAPTURA DE LEAD Y REGISTRO EN MIKROTIK USANDO MAC COMO USERNAME
     */
    public function trialLead(Request $request)
    {
        try {
            $name          = $request->input('name');
            $macCliente    = strtoupper($request->input('mac_cliente')); 
            $cellphone     = $request->input('cellphone');
            $cellphonecode = $request->input('cellphonecode');
            $identity      = $request->input('identity');
            
            $password = "123456"; 
            $profile  = "cortesia-20min"; 

            $router = Router::where('identity', $identity)->first();
            if (!$router) return response()->json(['success' => false, 'message' => 'Establecimiento no identificado'], 404);
            
            $macRouter = strtoupper(trim($router->macAddress));
            $tid = "LEAD" . time();
            $comment = "Lead: $name | Tel: $cellphonecode$cellphone";

            $cmd = ":local id [/ip hotspot user find name=\"$macCliente\"]; " .
                   ":if ([:len \$id]>0) do {" .
                   "/ip hotspot user set \$id profile=\"$profile\" password=\"$password\" comment=\"$comment\";" .
                   "} else {" .
                   "/ip hotspot user add name=\"$macCliente\" password=\"$password\" profile=\"$profile\" comment=\"$comment\";" .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no";

            $sent = Http::withHeaders(['x-mac' => $macRouter, 'x-id' => $tid])
                        ->withBody($cmd, 'text/plain')
                        ->post("{$this->bridgeUrl}/set-command");

            if (!$sent->successful()) {
                return response()->json(['success' => false, 'message' => 'Bridge fuera de línea']);
            }

            $confirmado = false;
            for ($i = 0; $i < 30; $i++) {
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $macRouter, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $confirmado = (trim($res->json('data')) === 'OK');
                    break;
                }
            }

            if ($confirmado) {
                UserMikrotik::updateOrCreate(
                    ['name' => $macCliente, 'router_id' => $router->id],
                    [
                        'password' => $password,
                        'profile' => $profile,
                        'full_name' => $name,
                        'cellphone' => $cellphone,
                        'cellphonecode' => $cellphonecode,
                        'active' => true
                    ]
                );
                return response()->json(['success' => true, 'password' => $password])
                                 ->header('Access-Control-Allow-Origin', '*');
            }

            return response()->json(['success' => false, 'message' => 'El Router no respondió a tiempo.']);

        } catch (\Exception $e) {
            Log::error("Error en trialLead: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno del servidor.'], 500);
        }
    }

    /**
     * OTROS MÉTODOS DE SOPORTE
     */
    public function trialAdd(Request $request) {
        try {
            $username = $request->input('username');
            $identity = $request->input('identity'); 
            $email = $request->input('email') ?? 'sin email';
            $password = "123456"; $profile = "cortesia-20min"; 
            $mac = $this->getMacByIdentity($identity);
            if (!$mac) return response()->json(['success' => false], 404);
            $tidCheck = "TRC" . time();
            $cmdCheck = ":local id [/ip hotspot user find name=\"$username\"];:if ([:len \$id]>0) do={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidCheck\" http-method=post http-data=\"EXISTE\" keep-result=no} else={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidCheck\" http-method=post http-data=\"NO_EXISTE\" keep-result=no}";
            $this->emitirAlSocket($cmdCheck, $mac, $tidCheck);
            $existe = false;
            for ($i = 0; $i < 60; $i++) {
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tidCheck]);
                if ($res->successful() && $res->json('status') === 'ready') { $existe = (trim($res->json('data')) === 'EXISTE'); break; }
            }
            if ($existe) return response()->json(['success' => false, 'message' => 'Prueba ya utilizada.']);
            $tidFinal = "TRA" . time();
            $cmdFinal = ":do {/ip hotspot user add name=\"$username\" password=\"$password\" profile=\"$profile\" comment=\"Trial: $email\";/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"ERROR\" keep-result=no}";
            $this->emitirAlSocket($cmdFinal, $mac, $tidFinal);
            if ($this->esperarConfirmacion($mac, $tidFinal)) return response()->json(['success' => true, 'password' => $password]);
            return response()->json(['success' => false]);
        } catch (\Exception $e) { return response()->json(['success' => false], 500); }
    }

    public function preAdd(Request $request) {
        try {
            $username = $request->input('username');
            $password = $request->input('password');
            $identity = $request->input('identity'); 
            $mac = $this->getMacByIdentity($identity);
            if (!$mac) return response()->json(['success' => false], 404);
            $tidCheck = "CHK" . time();
            $cmdCheck = ":local id [/ip hotspot user find name=\"$username\"];:if ([:len \$id]>0) do={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidCheck\" http-method=post http-data=\"EXISTE\" keep-result=no} else={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidCheck\" http-method=post http-data=\"NO_EXISTE\" keep-result=no}";
            $this->emitirAlSocket($cmdCheck, $mac, $tidCheck);
            $existe = false;
            for ($i = 0; $i < 60; $i++) {
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tidCheck]);
                if ($res->successful() && $res->json('status') === 'ready') { $existe = (trim($res->json('data')) === 'EXISTE'); break; }
            }
            $tidFinal = "PRE" . time();
            if ($existe) {
                $cmdFinal = ":do {/ip hotspot user set [find name=\"$username\"] password=\"$password\" profile=\"neutro\";/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"ERROR\" keep-result=no}";
            } else {
                $cmdFinal = ":do {/ip hotspot user add name=\"$username\" password=\"$password\" profile=\"neutro\";/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"ERROR\" keep-result=no}";
            }
            $this->emitirAlSocket($cmdFinal, $mac, $tidFinal);
            if ($this->esperarConfirmacion($mac, $tidFinal)) return response()->json(['success' => true]);
            return response()->json(['success' => false]);
        } catch (\Exception $e) { return response()->json(['success' => false], 500); }
    }

    public function activate(Request $request) {
        try {
            $username = $request->input('username');
            $profile = $request->input('profile'); 
            $identity = $request->input('identity');
            $mac = $this->getMacByIdentity($identity);
            if (!$mac) return response()->json(['success' => false], 404);
            $tid = "ACT" . time();
            $cmd = ":do {/ip hotspot user set [find name=\"$username\"] profile=\"$profile\" limit-uptime=0s;/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no}";
            $this->emitirAlSocket($cmd, $mac, $tid);
            if ($this->esperarConfirmacion($mac, $tid)) return response()->json(['success' => true]);
            return response()->json(['success' => false]);
        } catch (\Exception $e) { return response()->json(['success' => false], 500); }
    }

    protected function emitirAlSocket($comando, $mac, $tid) {
        $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
        return Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
            ->withBody($comandoLimpio, 'text/plain')
            ->post("{$this->bridgeUrl}/set-command")->successful();
    }

    protected function esperarConfirmacion($mac, $tid) {
        for ($i = 0; $i < 60; $i++) {
            sleep(1);
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
            if ($res->successful() && $res->json('status') === 'ready') {
                $data = trim($res->json('data'));
                return ($data === 'OK' || $data === 'EXISTE');
            }
        }
        return false;
    }
}