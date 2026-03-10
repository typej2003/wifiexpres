<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Router; 
use App\Models\UserMikrotik;

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
    public function getRouterInfo(Request $request) {
        $identity = $request->query('identity');
        $router = Router::where('identity', $identity)->with('hotspotSetting')->first();

        if (!$router) return response()->json(['success' => false], 404);

        $banner = "banner/WIFIEXPRES_banner_01.jpg"; 
        if ($router->hotspotSetting && !empty($router->hotspotSetting->carousel_images)) {
            $imagenes = $router->hotspotSetting->carousel_images;
            if (is_array($imagenes) && count($imagenes) > 0) {
                $fotoAleatoria = $imagenes[array_rand($imagenes)];
                $banner = 'https://wifiexpres.com/storage/carruselhotspot/' . $fotoAleatoria;
            }
        } elseif ($router->comercio_banner) {
            $banner = 'https://wifiexpres.com/storage/bannerrouter/' . $router->comercio_banner;
        }

        return response()->json([
            'success' => true,
            'router' => [
                'name' => $router->comercio_nombre ?? "WIFI EXPRES",
                'banner' => $banner,
                'store' => $router->store ?? "Sucursal",
                'address' => $router->address ?? "",
                'show_carousel' => $router->hotspotSetting->show_carousel ?? false 
            ]
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * TRIAL LEAD (UN SOLO PASO - IGUAL AL QUE FUNCIONABA)
     */
    public function trialLead(Request $request)
    {
        try {
            $name = $request->input('name');
            $macCliente = strtoupper($request->input('mac_cliente')); 
            $identity = $request->input('identity');
            $macRouter = $this->getMacByIdentity($identity);

            if (!$macRouter) return response()->json(['success' => false], 404);
            
            $tid = "LEAD" . time();
            $cmd = ":local id [/ip hotspot user find name=\"$macCliente\"]; :if ([:len \$id]>0) do={/ip hotspot user set \$id profile=\"cortesia-20min\" password=\"123456\"} else={/ip hotspot user add name=\"$macCliente\" password=\"123456\" profile=\"cortesia-20min\"}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no";

            if ($this->emitirAlSocket($cmd, $macRouter, $tid)) {
                if ($this->esperarConfirmacion($macRouter, $tid)) {
                    return response()->json(['success' => true, 'password' => '123456'])->header('Access-Control-Allow-Origin', '*');
                }
            }
            return response()->json(['success' => false, 'message' => 'Router no respondio']);
        } catch (\Exception $e) { return response()->json(['success' => false], 500); }
    }

    /**
     * PRE-ADD (VUELVE A LA LÓGICA DE DOBLE PASO PERO SIN LIMPIEZA AGRESIVA)
     */
    public function preAdd(Request $request) {
        try {
            $username = $request->input('username');
            $password = $request->input('password');
            $identity = $request->input('identity'); 
            $macRouter = $this->getMacByIdentity($identity);

            if (!$macRouter) return response()->json(['success' => false], 404);

            // PASO 1: Check
            $tidCheck = "CHK" . time();
            $cmdCheck = ":local id [/ip hotspot user find name=\"$username\"]; :if ([:len \$id]>0) do={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidCheck\" http-method=post http-data=\"EXISTE\" keep-result=no} else={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidCheck\" http-method=post http-data=\"NO_EXISTE\" keep-result=no}";

            $this->emitirAlSocket($cmdCheck, $macRouter, $tidCheck);
            $resCheck = $this->obtenerDatoRaw($macRouter, $tidCheck);

            // PASO 2: Add o Set
            $tidFinal = "PRE" . time();
            if ($resCheck === "EXISTE") {
                $cmdFinal = "/ip hotspot user set [find name=\"$username\"] password=\"$password\" profile=\"neutro\" limit-uptime=0s; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no";
            } else {
                $cmdFinal = "/ip hotspot user add name=\"$username\" password=\"$password\" profile=\"neutro\"; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no";
            }

            $this->emitirAlSocket($cmdFinal, $macRouter, $tidFinal);

            if ($this->esperarConfirmacion($macRouter, $tidFinal)) {
                return response()->json(['success' => true])->header('Access-Control-Allow-Origin', '*');
            }

            return response()->json(['success' => false, 'message' => 'Timeout']);
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
            $cmd = "/ip hotspot user set [find name=\"$username\"] profile=\"$profile\" limit-uptime=0s; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no";

            $this->emitirAlSocket($cmd, $mac, $tid);
            if ($this->esperarConfirmacion($mac, $tid)) return response()->json(['success' => true])->header('Access-Control-Allow-Origin', '*');
            
            return response()->json(['success' => false]);
        } catch (\Exception $e) { return response()->json(['success' => false], 500); }
    }

    /**
     * SOPORTE DE COMUNICACIÓN
     */
    protected function emitirAlSocket($comando, $mac, $tid) {
        return Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
            ->withBody($comando, 'text/plain')
            ->post("{$this->bridgeUrl}/set-command")->successful();
    }

    protected function obtenerDatoRaw($mac, $tid) {
        for ($i = 0; $i < 40; $i++) {
            sleep(1);
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
            if ($res->successful() && $res->json('status') === 'ready') {
                return trim($res->json('data'));
            }
        }
        return null;
    }

    protected function esperarConfirmacion($mac, $tid) {
        $res = $this->obtenerDatoRaw($mac, $tid);
        return ($res === 'OK' || $res === 'SUCCESS');
    }
}