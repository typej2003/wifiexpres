<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Router; 
use App\Models\UserMikrotik;
use App\Models\Plan;
use Exception;

class UserController extends Controller
{
    protected $bridgeUrl = "http://188.95.113.44:3000";

    protected function findRouter($identity) {
        return Router::where('identity', $identity)
                     ->orWhere('macAddress', $identity)
                     ->with('hotspotSetting')
                     ->first();
    }

    public function getRouterInfo(Request $request) {
        $identity = $request->query('identity');
        $router = $this->findRouter($identity);
        if (!$router) return response()->json(['success' => false], 404);

        $banner = "banner/WIFIEXPRES_banner_01.jpg"; 
        if ($router->hotspotSetting && !empty($router->hotspotSetting->carousel_images)) {
            $imagenes = $router->hotspotSetting->carousel_images;
            $fotoAleatoria = $imagenes[array_rand($imagenes)];
            $banner = 'https://wifiexpres.com/storage/carruselhotspot/' . $fotoAleatoria;
        }

        return response()->json([
            'success' => true,
            'router' => [
                'name'    => $router->comercio_nombre ?? "WIFI EXPRES",
                'banner'  => $banner,
                'show_carousel' => $router->hotspotSetting->show_carousel ?? false 
            ]
        ])->header('Access-Control-Allow-Origin', '*');
    }

    public function getPlans(Request $request) {
        $identity = $request->query('identity');
        $router = $this->findRouter($identity);
        if (!$router) return response()->json(['success' => false], 404);

        $plans = Plan::where('router_id', $router->id)
                     ->where('is_active', true)
                     ->where('mikrotik_profile', 'not like', '%cortesia%')
                     ->get(['name', 'price', 'mikrotik_profile', 'session_timeout'])
                     ->map(function($p) {
                        return [
                            'name' => $p->name,
                            'price' => $p->price,
                            'profile' => $p->mikrotik_profile,
                            'uptime' => $p->session_timeout ?? 'Ilimitado'
                        ];
                     });

        return response()->json(['success' => true, 'plans' => $plans])->header('Access-Control-Allow-Origin', '*');
    }

    public function trialLead(Request $request) {
        $name = $request->input('name');
        $macCliente = strtoupper($request->input('mac_cliente')); 
        $router = $this->findRouter($request->input('identity'));

        if (!$router) return response()->json(['success' => false], 404);
        
        $macRouter = strtoupper(trim($router->macAddress));
        $tid = "LEAD" . time();
        $profile = "cortesia 20min-0"; 

        $cmd = ":local u \"$macCliente\"; :local p \"123456\"; :local pr \"$profile\"; " .
               ":if ([:len [/ip hotspot user find name=\$u]]>0) do={ /ip hotspot user set [find name=\$u] profile=\$pr password=\$p } else={ /ip hotspot user add name=\$u password=\$p profile=\$pr }; " .
               "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macRouter&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no";

        $this->emitirAlSocket($cmd, $macRouter, $tid);
        
        // No hay sleep(). Retornamos el TID para que el JS verifique.
        return response()->json(['success' => true, 'tid' => $tid, 'password' => '123456']);
    }

    public function preAdd(Request $request) {
        $username = $request->input('username');
        $password = $request->input('password');
        $router = $this->findRouter($request->input('identity'));
        if (!$router) return response()->json(['success' => false], 404);

        $mac = strtoupper(trim($router->macAddress));
        $tid = "PRE" . time();

        $cmd = ":local u \"$username\"; :local p \"$password\"; " .
               ":if ([:len [/ip hotspot user find name=\$u]]>0) do={ /ip hotspot user set [find name=\$u] password=\$p profile=\"neutro\" } else={ /ip hotspot user add name=\$u password=\$p profile=\"neutro\" }; " .
               "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"OK\" keep-result=no";

        $this->emitirAlSocket($cmd, $mac, $tid);
        return response()->json(['success' => true, 'tid' => $tid]);
    }

    public function checkStatus(Request $request) {
        $mac = strtoupper($request->query('mac'));
        $tid = $request->query('tid');
        $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
        return response()->json($res->json());
    }

    protected function emitirAlSocket($comando, $mac, $tid) {
        $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
        Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
            ->withBody($comandoLimpio, 'text/plain')
            ->post("{$this->bridgeUrl}/set-command");
    }
}