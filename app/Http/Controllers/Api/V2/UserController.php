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

    /**
     * 3. REGISTRO DE CORTESÍA (TRIAL)
     */
    public function trialLead(Request $request)
    {
        try {
            $name        = $request->input('name');
            $macCliente  = strtoupper($request->input('mac_cliente')); 
            $identity    = $request->input('identity');
            $router      = $this->findRouter($identity);

            if (!$router) return response()->json(['success' => false, 'message' => 'Router no encontrado'], 404);
            
            $macRouter = strtoupper(trim($router->macAddress));
            $tid = "LEAD" . time();
            $password = "123456"; 
            $profile  = "cortesia 20min-0"; 

            // Se agregan comillas \"\$pr\" para manejar los espacios en el nombre del perfil
            $cmd = ":local m \"$macRouter\"; :local t \"$tid\"; :local u \"$macCliente\"; :local p \"$password\"; :local pr \"$profile\"; " .
                   ":do { " .
                   "  :local id [/ip hotspot user find name=\$u]; " .
                   "  :if ([:len \$id]>0) do={ " .
                   "    /ip hotspot user set \$id profile=\"\$pr\" password=\$p limit-uptime=0s; " .
                   "  } else={ " .
                   "    /ip hotspot user add name=\$u password=\$p profile=\"\$pr\" limit-uptime=0s; " .
                   "  }; " .
                   "  /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no; " .
                   "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            $this->emitirAlSocket($cmd, $macRouter, $tid);

            if ($this->esperarConfirmacion($macRouter, $tid)) {
                UserMikrotik::updateOrCreate(
                    ['name' => $macCliente, 'router_id' => $router->id],
                    ['password' => $password, 'profile' => $profile, 'full_name' => $name, 'active' => true]
                );
                return response()->json(['success' => true, 'password' => $password]);
            }

            return response()->json(['success' => false, 'message' => 'El router no confirmó la cortesía (Timeout)']);
        } catch (Exception $e) { 
            Log::error("Error en trialLead: " . $e->getMessage());
            return response()->json(['success' => false], 500); 
        }
    }

    /**
     * 4. PRE-REGISTRO (Fase 1: Creación Neutra)
     */
    public function preAdd(Request $request) {
        try {
            $username = $request->input('username');
            $password = $request->input('password');
            $identity = $request->input('identity'); 
            $router = $this->findRouter($identity);
            
            if (!$router) return response()->json(['success' => false, 'message' => 'Router no encontrado'], 404);
            $mac = strtoupper(trim($router->macAddress));

            $tidFinal = "PRE" . time();
            
            $cmdFinal = ":local m \"$mac\"; :local t \"$tidFinal\"; :local u \"$username\"; :local p \"$password\"; " .
                        ":do { " .
                        "  :local id [/ip hotspot user find name=\$u]; " .
                        "  :if ([:len \$id]>0) do={ " .
                        "    /ip hotspot user set \$id password=\$p profile=\"neutro\"; " .
                        "  } else={ " .
                        "    /ip hotspot user add name=\$u password=\$p profile=\"neutro\"; " .
                        "  }; " .
                        "  /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no; " .
                        "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            $this->emitirAlSocket($cmdFinal, $mac, $tidFinal);
            
            return response()->json(['success' => $this->esperarConfirmacion($mac, $tidFinal)]);

        } catch (Exception $e) { 
            Log::error("Error en preAdd: " . $e->getMessage());
            return response()->json(['success' => false], 500); 
        }
    }

    /**
     * 5. ACTIVACIÓN FINAL
     */
    public function activate(Request $request) {
        try {
            $username = $request->input('username');
            $profile  = $request->input('profile'); 
            $identity = $request->input('identity');
            $router   = $this->findRouter($identity);
            
            if (!$router) return response()->json(['success' => false, 'message' => 'Router no encontrado'], 404);
            $mac = strtoupper(trim($router->macAddress));
            $tid = "ACT" . time();

            // Importante: profile=\"\$pr\" y remove active \$act para forzar el re-logueo con el perfil nuevo
            $cmd = ":local m \"$mac\"; :local t \"$tid\"; :local u \"$username\"; :local pr \"$profile\"; " .
                   ":do { " .
                   "  :local id [/ip hotspot user find name=\$u]; " .
                   "  :if ([:len \$id]>0) do={ " .
                   "    /ip hotspot user set \$id profile=\"\$pr\" limit-uptime=0s; " .
                   "    :local act [/ip hotspot active find user=\$u]; " .
                   "    :if ([:len \$act]>0) do={ /ip hotspot active remove \$act }; " .
                   "    /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no; " .
                   "  } else={ " .
                   "    /log error \"Bridge: Usuario \$u no encontrado\"; " .
                   "    /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; " .
                   "  }; " .
                   "} on-error={ " .
                   "  /log error \"Bridge: Error activando perfil \$pr para \$u\"; " .
                   "  /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; " .
                   "};";
            
            $this->emitirAlSocket($cmd, $mac, $tid);
            
            $resultado = $this->esperarConfirmacion($mac, $tid);

            if ($resultado) {
                UserMikrotik::where('name', $username)->where('router_id', $router->id)->update([
                    'profile' => $profile, 
                    'active' => true
                ]);
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => 'El router no confirmó la activación del perfil']);
        } catch (Exception $e) { 
            Log::error("Error en activación: " . $e->getMessage());
            return response()->json(['success' => false], 500); 
        }
    }

    protected function emitirAlSocket($comando, $mac, $tid) {
        $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
        return Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
            ->withBody($comandoLimpio, 'text/plain')
            ->post("{$this->bridgeUrl}/set-command")->successful();
    }

    protected function esperarConfirmacion($mac, $tid) {
        // Aumentado a 35 segundos para dar margen de respuesta al fetch del router
        for ($i = 0; $i < 35; $i++) {
            sleep(1);
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
            if ($res->successful() && $res->json('status') === 'ready') {
                return (trim($res->json('data')) === 'OK');
            }
        }
        return false;
    }
}