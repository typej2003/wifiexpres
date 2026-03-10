<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Router; 
use App\Models\UserMikrotik;
use App\Models\HotspotSetting;

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

        if (!$router) {
            return response()->json(['success' => false, 'message' => 'Router no encontrado'], 404);
        }

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
                'name'    => $router->comercio_nombre ?? "WIFI EXPRES",
                'banner'  => $banner,
                'store'   => $router->store ?? "Sucursal",
                'address' => $router->address ?? "",
                'show_carousel' => $router->hotspotSetting->show_carousel ?? false 
            ]
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * CAPTURA DE LEAD (TRIAL RÁPIDO)
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

            if ($this->emitirAlSocket($cmd, $macRouter, $tid)) {
                if ($this->esperarConfirmacion($macRouter, $tid)) {
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
            }

            return response()->json(['success' => false, 'message' => 'El Router no respondió a tiempo.']);

        } catch (\Exception $e) {
            Log::error("Error en trialLead: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno.'], 500);
        }
    }

    /**
     * PRE-REGISTRO (DOBLE PASO: CHECK -> ADD/SET)
     */
    public function preAdd(Request $request) {
        try {
            $username = $request->input('username');
            $password = $request->input('password');
            $identity = $request->input('identity'); 
            $macRouter = $this->getMacByIdentity($identity);

            if (!$macRouter) return response()->json(['success' => false, 'message' => 'Router no identificado'], 404);

            // PASO 1: Verificar existencia
            $tidCheck = "CHK" . time();
            $cmdCheck = ":local id [/ip hotspot user find name=\"$username\"]; " .
                        ":if ([:len \$id]>0) do={ " .
                        "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidCheck\" http-method=post http-data=\"EXISTE\" keep-result=no; " .
                        "} else={ " .
                        "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidCheck\" http-method=post http-data=\"NO_EXISTE\" keep-result=no; }";

            $this->emitirAlSocket($cmdCheck, $macRouter, $tidCheck);
            $resCheck = $this->obtenerRespuestaRaw($macRouter, $tidCheck);
            $existe = ($resCheck === "EXISTE");

            // PASO 2: Acción Final
            $tidFinal = "PRE" . time();
            if ($existe) {
                $cmdFinal = ":do { /ip hotspot user set [find name=\"$username\"] password=\"$password\" profile=\"neutro\" limit-uptime=0s; " .
                            "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no; " .
                            "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidFinal\" http-method=post http-data=\"ERROR\" keep-result=no; }";
            } else {
                $cmdFinal = ":do { /ip hotspot user add name=\"$username\" password=\"$password\" profile=\"neutro\"; " .
                            "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no; " .
                            "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tidFinal\" http-method=post http-data=\"ERROR\" keep-result=no; }";
            }

            $this->emitirAlSocket($cmdFinal, $macRouter, $tidFinal);

            if ($this->esperarConfirmacion($macRouter, $tidFinal)) {
                return response()->json(['success' => true])->header('Access-Control-Allow-Origin', '*');
            }

            return response()->json(['success' => false, 'message' => 'El Router no confirmó el registro.']);

        } catch (\Exception $e) { 
            Log::error("Error en preAdd: " . $e->getMessage());
            return response()->json(['success' => false], 500); 
        }
    }

    /**
     * ACTIVACIÓN DE PLAN
     */
    public function activate(Request $request) {
        try {
            $username = $request->input('username');
            $profile  = $request->input('profile'); 
            $identity = $request->input('identity');
            $mac      = $this->getMacByIdentity($identity);

            if (!$mac) return response()->json(['success' => false], 404);
            
            $tid = "ACT" . time();
            $cmd = ":do { /ip hotspot user set [find name=\"$username\"] profile=\"$profile\" limit-uptime=0s; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no; " .
                   "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no; }";

            $this->emitirAlSocket($cmd, $mac, $tid);

            if ($this->esperarConfirmacion($mac, $tid)) {
                return response()->json(['success' => true])->header('Access-Control-Allow-Origin', '*');
            }
            return response()->json(['success' => false]);
        } catch (\Exception $e) { return response()->json(['success' => false], 500); }
    }

    /**
     * MÉTODOS DE COMUNICACIÓN (LÓGICA PLANMANAGER)
     */
    protected function emitirAlSocket($comando, $mac, $tid) {
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
            Log::error("Bridge Error: " . $e->getMessage());
            return false;
        }
    }

    protected function obtenerRespuestaRaw($mac, $tid) {
        for ($i = 0; $i < 60; $i++) {
            sleep(1);
            try {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    return trim($res->json('data'));
                }
            } catch (\Exception $e) { }
        }
        return null;
    }

    protected function esperarConfirmacion($mac, $tid) {
        $data = $this->obtenerRespuestaRaw($mac, $tid);
        return ($data === 'OK' || $data === 'SUCCESS');
    }
}