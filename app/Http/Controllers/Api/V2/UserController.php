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
    public function trialLead1(Request $request)
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

    public function trialLead(Request $request)
    {
        try {
            // Capturamos los datos que vienen del fetch en login.html
            // Nota: El JS envía 'username', 'email', 'phone', 'identity' y 'router_mac'
            $macCliente = strtoupper($request->input('username')); 
            $identity   = $request->input('identity');
            $email      = $request->input('email');
            $phone      = $request->input('phone');
            
            $password   = "123456"; 
            $profile    = "cortesia 20min-0"; 

            // 1. Buscamos el router por su identidad
            $router = \App\Models\Router::where('identity', $identity)->first();

            if (!$router) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Router no encontrado'
                ], 404)->header('Access-Control-Allow-Origin', '*');
            }

            // 3. Retornamos éxito inmediato con CORS habilitado
            // Esto permite que el JS en login.html ejecute loginSystem() sin errores
            return response()->json([
                'success'  => true, 
                'password' => $password,
                'message'  => 'Cortesía autorizada correctamente'
            ])->header('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            \Log::error("Error en trialLead: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Error interno al procesar la cortesía'
            ], 500)->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * 4. PRE-REGISTRO (Fase 1: Creación Neutra)
     */
    public function preAdd1(Request $request) {
        try {
            $username = $request->input('username');
            $password = $request->input('password');
            $identity = $request->input('identity');
            $profile = $request->input('planSelected'); 
            $router = $this->findRouter($identity);
            
            if (!$router) return response()->json(['success' => false, 'message' => 'Router no encontrado'], 404);
            $mac = strtoupper(trim($router->macAddress));
            $tidFinal = "PRE" . time();
            
            // Simplificamos: Usamos find directo sin variables intermedias de ID
            // $cmdFinal = ":local m \"$mac\"; :local t \"$tidFinal\"; :local u \"$username\"; :local p \"$password\"; " .
            //             ":do { " .
            //             "  :if ([:len [/ip hotspot user find name=\$u]] > 0) do={ " .
            //             "    /ip hotspot user set [find name=\$u] password=\$p profile=\"$profile\"; " .
            //             "  } else={ " .
            //             "    /ip hotspot user add name=\$u password=\$p profile=\"$profile\"; " .
            //             "  }; " .
            //             "  /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no; " .
            //             "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            //$cmdFinal = ":local m \"$mac\"; :local t \"$tidFinal\"; :local u \"$username\"; :local p \"$password\"; :local pr \"$profile\"; :do { :local id [/ip hotspot user find name=\$u]; :if ([:len \$id] > 0) do={ /ip hotspot user set \$id password=\$p profile=\$pr; } else={ /ip hotspot user add name=\$u password=\$p profile=\$pr; }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            $cmdFinal = ":local m \"$mac\"; :local t \"$tidFinal\"; :local u \"$username\"; :local p \"$password\"; :local pr \"$profile\"; :do { /ip hotspot user add name=\$u password=\$p profile=\$pr; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            $this->emitirAlSocket($cmdFinal, $mac, $tidFinal);
            
            // IMPORTANTE: Asegúrate de que el método esperarConfirmacion tenga un timeout de al menos 15-20 segundos
            $confirmado = $this->esperarConfirmacion($mac, $tidFinal);

            return response()->json(['success' => $confirmado]);

        } catch (Exception $e) { 
            Log::error("Error en preAdd: " . $e->getMessage());
            return response()->json(['success' => false], 500); 
        }
    }

    public function preAdd(Request $request) {
        try {
            $username = $request->input('username');
            $password = $request->input('password');
            $identity = $request->input('identity');
            $profile = $request->input('planSelected'); 
            $router = $this->findRouter($identity);
            
            if (!$router) return response()->json(['success' => false, 'message' => 'Router no encontrado'], 404);
            
            $mac = strtoupper(trim($router->macAddress));
            $tidFinal = "PRE" . time();
            
            // Comando optimizado estilo "Profile"
            //$cmdFinal = ":local m \"$mac\"; :local t \"$tidFinal\"; :local u \"$username\"; :local p \"$password\"; :local pr \"$profile\"; :do { /ip hotspot user add name=\$u password=\$p profile=\$pr; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            $cmdFinal = ":local m \"$mac\"; :local t \"$tidFinal\"; :local u \"$username\"; :local p \"$password\"; :local pr \"$profile\"; :do { :if ([:len [/ip hotspot user find name=\$u]] > 0) do={ /ip hotspot user set [find name=\$u] password=\$p profile=\$pr; } else={ /ip hotspot user add name=\$u password=\$p profile=\$pr; }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            $this->emitirAlSocket($cmdFinal, $mac, $tidFinal);
            
            $confirmado = $this->esperarConfirmacion($mac, $tidFinal);

            // Agregamos los valores a la respuesta para depuración
            return response()->json([
                'success' => $confirmado,
                'debug' => [
                    'user' => $username,
                    'pass' => $password,
                    'profile' => $profile,
                    'mac' => $mac
                ]
            ]);

        } catch (Exception $e) { 
            \Log::error("Error en preAdd: " . $e->getMessage());
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

            // Comando en una sola línea (One-Liner)
            $cmd = "/ip hotspot user {:local u \"$username\"; :local pr \"$profile\"; :local m \"$mac\"; :local t \"$tid\"; :if ([:len [find where name=\$u]] > 0) do={set [find where name=\$u] profile=\$pr limit-uptime=0s; /log info (\"Bridge: OK \" . \$u); /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no;} else={/log error (\"Bridge: FAIL \" . \$u); /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no;}}";
            
            $this->emitirAlSocket($cmd, $mac, $tid);
            
            $resultado = $this->esperarConfirmacion($mac, $tid);

            if ($resultado) {
                UserMikrotik::where('name', $username)
                    ->where('router_id', $router->id)
                    ->update(['profile' => $profile, 'active' => true]);
                return response()->json(['success' => true]);
            }
            
            return response()->json(['success' => false, 'message' => 'El router no confirmó la activación']);
            
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

    public function freeConnection(Request $request) 
    {
        try {
            $macCliente = strtoupper($request->input('mac_cliente'));
            $identity   = $request->input('identity');
            $password   = "12345"; // Contraseña genérica para el login posterior
            $profile    = "conexiongratis";

            // Buscamos el router para asociar el registro
            $router = \App\Models\Router::where('identity', $identity)->first();
            if (!$router) {
                return response()->json(['success' => false, 'message' => 'Router no hallado'], 404);
            }

            // Guardar los datos del formulario para Marketing
            \App\Models\UserMikrotik::updateOrCreate(
                ['name' => $macCliente, 'router_id' => $router->id],
                [
                    'server'    => $identity,
                    'macaddress'    => $macCliente,
                    'password'      => $password,
                    'full_name'     => $request->input('full_name'),
                    'gender'        => $request->input('gender'),
                    'birthday'      => $request->input('birthday'),
                    'email'         => $request->input('email'),
                    'cellphonecode' => $request->input('cellphonecode'),
                    'cellphone'     => $request->input('cellphone'),
                    'profile'       => $profile,
                    'active'        => true
                ]
            );

            // Retornamos éxito de inmediato para que el login.html proceda
            return response()->json([
                'success' => true, 
                'password' => $password,
                'message' => 'Registro guardado exitosamente'
            ])->header('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}