<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Router; 
use App\Models\UserMikrotik;
use App\Models\HotspotSetting;
use App\Models\Plan;
use Exception;

class UserController extends Controller
{
    protected $bridgeUrl = "http://188.95.113.44:3000";

    /**
     * Helper para buscar router por MAC o Identity
     */
    protected function findRouter($identity)
    {
        return Router::where('identity', $identity)
                     ->orWhere('macAddress', $identity)
                     ->with('hotspotSetting')
                     ->first();
    }

    /**
     * 1. INFO VISUAL (BRANDING) - Carga instantánea
     */
    public function getRouterInfo(Request $request) {
        $identity = $request->query('identity');
        $router = $this->findRouter($identity);

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
                'is_store' => $router->is_store ?? 0,
                'is_trial' => $router->is_trial ?? 0,
                'show_carousel' => $router->hotspotSetting->show_carousel ?? false 
            ]
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * 2. LISTADO DE PLANES (Desde Base de Datos)
     * Optimizamos para no consultar el MikroTik aquí.
     */
    public function getPlans(Request $request)
    {
        $identity = $request->query('identity');
        $router = $this->findRouter($identity);

        if (!$router) {
            return response()->json(['success' => false, 'message' => 'Router no identificado'], 404);
        }

        // Consultamos los planes activos asociados a este router en la DB
        $plans = Plan::where('router_id', $router->id)
                     ->where('is_active', true)
                     ->get(['name', 'price', 'mikrotik_profile', 'session_timeout'])
                     ->map(function($p) {
                        return [
                            'name' => $p->name,
                            'price' => $p->price,
                            'mikrotik_profile' => $p->mikrotik_profile,
                            'uptime' => $p->session_timeout ?? 'Ilimitado'
                        ];
                     });

        return response()->json([
            'success' => true, 
            'plans' => $plans
        ])->header('Access-Control-Allow-Origin', '*');
    }

    /**
     * 3. REGISTRO DE CORTESÍA (TRIAL) - Vía Socket
     */
    public function trialLead(Request $request)
    {
        try {
            $name          = $request->input('name');
            $macCliente    = strtoupper($request->input('mac_cliente')); 
            $identity      = $request->input('identity');
            $router        = $this->findRouter($identity);

            if (!$router) return response()->json(['success' => false, 'message' => 'Router no encontrado'], 404);
            
            $macRouter = strtoupper(trim($router->macAddress));
            $tid = "LEAD" . time();
            $password = "123456"; 
            $profile  = "cortesia 20min-0"; 

            // Comando optimizado para el script del MikroTik
            $cmd = ":local id [/ip hotspot user find name=\"$macCliente\"]; " .
                   ":if ([:len \$id]>0) do {" .
                   "/ip hotspot user set \$id profile=\"$profile\" password=\"$password\";" .
                   "} else {" .
                   "/ip hotspot user add name=\"$macCliente\" password=\"$password\" profile=\"$profile\";" .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no";

            $this->emitirAlSocket($cmd, $macRouter, $tid);

            if ($this->esperarConfirmacion($macRouter, $tid)) {
                UserMikrotik::updateOrCreate(
                    ['name' => $macCliente, 'router_id' => $router->id],
                    ['password' => $password, 'profile' => $profile, 'full_name' => $name, 'active' => true]
                );
                return response()->json(['success' => true, 'password' => $password]);
            }

            return response()->json(['success' => false, 'message' => 'El Router no respondió.']);
        } catch (Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * 4. PRE-REGISTRO (Fase 1: Verificación y Creación Neutra) - Vía Socket
     */
    public function preAdd(Request $request) {
        try {
            $username = $request->input('username');
            $password = $request->input('password');
            $identity = $request->input('identity'); 
            $router = $this->findRouter($identity);
            
            if (!$router) return response()->json(['success' => false], 404);
            $mac = strtoupper(trim($router->macAddress));

            $tidCheck = "CHK" . time();
            $cmdCheck = ":local id [/ip hotspot user find name=\"$username\"]; :if ([:len \$id]>0) do={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidCheck\" http-method=post http-data=\"EXISTE\" keep-result=no} else={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidCheck\" http-method=post http-data=\"NO_EXISTE\" keep-result=no}";
            
            $this->emitirAlSocket($cmdCheck, $mac, $tidCheck);
            
            $existe = false;
            for ($i = 0; $i < 40; $i++) {
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tidCheck]);
                if ($res->successful() && $res->json('status') === 'ready') { 
                    $existe = (trim($res->json('data')) === 'EXISTE'); 
                    break; 
                }
            }

            $tidFinal = "PRE" . time();
            $cmdFinal = $existe 
                ? ":do {/ip hotspot user set [find name=\"$username\"] password=\"$password\" profile=\"neutro\";/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"ERROR\" keep-result=no}"
                : ":do {/ip hotspot user add name=\"$username\" password=\"$password\" profile=\"neutro\";/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tidFinal\" http-method=post http-data=\"ERROR\" keep-result=no}";

            $this->emitirAlSocket($cmdFinal, $mac, $tidFinal);
            return response()->json(['success' => $this->esperarConfirmacion($mac, $tidFinal)]);
        } catch (Exception $e) { return response()->json(['success' => false], 500); }
    }

    /**
     * 5. ACTIVACIÓN FINAL (Fase 2: Cambio de Profile tras pago exitoso) - Vía Socket
     */
    public function activate(Request $request) {
        try {
            $username = $request->input('username');
            $profile = $request->input('profile'); 
            $identity = $request->input('identity');
            $router = $this->findRouter($identity);
            
            if (!$router) return response()->json(['success' => false], 404);
            $mac = strtoupper(trim($router->macAddress));

            $tid = "ACT" . time();
            $cmd = ":do {/ip hotspot user set [find name=\"$username\"] profile=\"$profile\" limit-uptime=0s;/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no} on-error={/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR\" keep-result=no}";
            
            $this->emitirAlSocket($cmd, $mac, $tid);
            return response()->json(['success' => $this->esperarConfirmacion($mac, $tid)]);
        } catch (Exception $e) { return response()->json(['success' => false], 500); }
    }

    /**
     * COMUNICACIÓN CON EL BRIDGE (SOCKET)
     */
    protected function emitirAlSocket($comando, $mac, $tid) {
        $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
        return Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
            ->withBody($comandoLimpio, 'text/plain')
            ->post("{$this->bridgeUrl}/set-command")->successful();
    }

    protected function esperarConfirmacion($mac, $tid) {
        for ($i = 0; $i < 45; $i++) {
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