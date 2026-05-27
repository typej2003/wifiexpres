<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Router;
use App\Models\Plan;
use App\Models\User;
use App\Models\Setting;
use App\Models\Ticket;
use RouterOS\Client;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Http; 
use App\Http\Livewire\Notificacion\EmailController;

class HotspotController extends Controller
{
    // URL del Bridge Node.js
    private $bridgeUrl = "http://188.95.113.44:3000";

    /**
     * Busca un router de forma flexible: por MAC o por Identity.
     * Esto corrige el error donde $(mac) en MikroTik devuelve la MAC del celular.
     */
    private function findRouter($mac, $identity = null)
    {
        $query = Router::query();

        if ($identity && $mac) {
            $query->where('identity', $identity)->orWhere('macAddress', $mac);
        } elseif ($identity) {
            $query->where('identity', $identity);
        } elseif ($mac) {
            $query->where('macAddress', $mac);
        }
        return $query->first();
    }

    /**
     * Envía comandos al Bridge Node.js
     */
    private function sendToBridge($mac, $script)
    {
        try {
            $response = Http::withBody($script, 'text/plain')
                            ->post($this->bridgeUrl . "/set-command?mac=" . $mac);
            
            return $response->successful();
        } catch (Exception $e) {
            \Log::error("Error conectando al Bridge: " . $e->getMessage());
            return false;
        }
    }

    private function notifyUserCredentials($email, $username, $password, $name = 'Cliente')
    {
        if (empty($email)) return;

        try {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = new User();
                $user->email = $email;
                $user->names = $name;
                $user->surnames = "";
            }
            $user->username = $username;
            $user->password_plain = $password;

            $emailController = new EmailController();
            $emailController->sendEmailCliente('welcomeComercio', $user);
        } catch (Exception $e) {
            \Log::error("Error enviando email de hotspot: " . $e->getMessage());
        }
    }

    public function v2AddUserNeutral(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $email    = $request->input('email');
        $router_mac = $request->input('router_mac');
        $identity   = $request->input('identity'); // Recibido desde el portal
        $profile_neutral = "neutro"; 

        if (!$username || !$password || (!$router_mac && !$identity)) {
            return response()->json(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        $router = $this->findRouter($router_mac, $identity);
        if (!$router) {
            return response()->json(['success' => false, 'message' => 'Router no hallado'], 404);
        }

        $mac_real = $router->macAddress; // Usamos la MAC real de la DB para el Bridge

        if ($router->connection_type === 'bridge' || !$router->ip) {
            $script = "/ip hotspot user add name=\"$username\" password=\"$password\" profile=\"$profile_neutral\" comment=\"V2 Bridge Neutro: " . now() . "\"";
            if ($this->sendToBridge($mac_real, $script)) {
                if ($email) $this->notifyUserCredentials($email, $username, $password);
                return response()->json(['success' => true, 'message' => 'Comando enviado al Bridge'], 200);
            }
        }

        try {
            $userAdmin = User::where('role', 'admin')->first();
            $setting = Setting::where('user_id', $userAdmin->id)->first();
            $mode = $setting ? (int)$setting->mikrotik_connection_mode : 0; 
            $host = ($mode === 1 && !empty($router->dns)) ? $router->dns : $router->ip;

            $client = new Client([
                'host' => $host, 'user' => $router->admin, 'pass' => $router->password,
                'port' => (int) ($router->api_port ?? 49152), 'timeout' => 5,
            ]);

            $check = $client->query((new Query('/ip/hotspot/user/print'))->where('name', $username))->read();

            if (empty($check)) {
                $client->query((new Query('/ip/hotspot/user/add'))
                    ->equal('name', $username)
                    ->equal('password', $password)
                    ->equal('profile', $profile_neutral)
                    ->equal('comment', 'V2 Pre-registro Neutro: ' . now()))->read();
            } else {
                $client->query((new Query('/ip/hotspot/user/set'))
                    ->equal('.id', $check[0]['.id'])
                    ->equal('password', $password)
                    ->equal('profile', $profile_neutral)
                    ->equal('comment', 'V2 Actualizado a Neutro: ' . now()))->read();
            }

            if ($email) $this->notifyUserCredentials($email, $username, $password);
            return response()->json(['success' => true, 'message' => 'Usuario neutro listo'], 200);

        } catch (Exception $e) {
            $script = "/ip hotspot user add name=\"$username\" password=\"$password\" profile=\"$profile_neutral\"";
            if ($this->sendToBridge($mac_real, $script)) {
                return response()->json(['success' => true, 'message' => 'API falló, enviado vía Bridge'], 200);
            }
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function v2UpgradeUserPlan(Request $request)
    {
        $username = $request->input('username');
        $profile_real = $request->input('profile');

        $plan = Plan::where('mikrotik_profile', $profile_real)->first();
        if (!$plan) return response()->json(['success' => false, 'message' => 'Plan no existe'], 404);

        $router = Router::find($plan->router_id);
        $router_mac = $router->macAddress;

        $script = "/ip hotspot user set [find name=\"$username\"] profile=\"$profile_real\" comment=\"V2 Upgrade Bridge: " . now() . "\"; /ip hotspot active remove [find user=\"$username\"]";
        
        try {
            $userAdmin = User::where('role', 'admin')->first();
            $setting = Setting::where('user_id', $userAdmin->id)->first();
            $mode = $setting ? (int)$setting->mikrotik_connection_mode : 0; 
            $host = ($mode === 1 && !empty($router->dns)) ? $router->dns : $router->ip;

            $client = new Client([
                'host' => $host, 'user' => $router->admin, 'pass' => $router->password,
                'port' => (int) ($router->api_port ?? 49152), 'timeout' => 5,
            ]);

            $userMk = $client->query((new Query('/ip/hotspot/user/print'))->where('name', $username))->read();
            if (!empty($userMk)) {
                $client->query((new Query('/ip/hotspot/user/set'))->equal('.id', $userMk[0]['.id'])->equal('profile', $profile_real))->read();
                $active = $client->query((new Query('/ip/hotspot/active/print'))->where('user', $username))->read();
                foreach($active as $s) {
                    $client->query((new Query('/ip/hotspot/active/remove'))->equal('.id', $s['.id']))->read();
                }
            }
        } catch (Exception $e) {
            $this->sendToBridge($router_mac, $script);
        }

        Ticket::updateOrCreate(
            ['username' => $username, 'router_id' => $router->id],
            [
                'identity' => $router->identity, 'plan' => $plan->name, 'costo' => $plan->price,
                'tiempo_uso' => $profile_real, 'activado' => true, 'fecha_uso' => now(), 'estado' => 'en_uso'
            ]
        );

        return response()->json(['success' => true, 'message' => 'Upgrade procesado'], 200);
    }

    public function addUser(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $profile  = $request->input('profile');
        $email    = $request->input('email');

        $plan = Plan::where('mikrotik_profile', $profile)->first();
        if (!$plan) return response()->json(['success' => false, 'message' => 'Perfil no encontrado'], 404);

        $router = Router::find($plan->router_id);

        try {
            $userAdmin = User::where('role', 'admin')->first();
            $setting = Setting::where('user_id', $userAdmin->id)->first();
            $mode = $setting ? (int)$setting->mikrotik_connection_mode : 0; 
            $host = ($mode === 1 && !empty($router->dns)) ? $router->dns : $router->ip;

            $client = new Client([
                'host' => $host, 'user' => $router->admin, 'pass' => $router->password,
                'port' => (int) ($router->api_port ?? 49152), 'timeout' => 5,
            ]);

            $userExists = $client->query((new Query('/ip/hotspot/user/print'))->where('name', $username))->read();

            if (!empty($userExists)) {
                $client->query((new Query('/ip/hotspot/user/set'))->equal('.id', $userExists[0]['.id'])->equal('profile', $profile)->equal('password', $password))->read();
            } else {
                $client->query((new Query('/ip/hotspot/user/add'))->equal('name', $username)->equal('password', $password)->equal('profile', $profile))->read();
            }
        } catch (Exception $e) {
            $script = "/ip hotspot user add name=\"$username\" password=\"$password\" profile=\"$profile\"";
            $this->sendToBridge($router->macAddress, $script);
        }

        Ticket::updateOrCreate(['username' => $username, 'router_id' => $router->id], [
            'password' => $password, 'identity' => $router->identity, 'plan' => $plan->name,
            'costo' => $plan->price, 'tiempo_uso' => $profile, 'activado' => true, 'fecha_uso' => now(), 'estado' => 'en_uso'
        ]);

        if ($email) $this->notifyUserCredentials($email, $username, $password);
        return response()->json(['success' => true, 'message' => 'Usuario V1 procesado'], 200);
    }

    public function getPlans(Request $request)
    {
        $request->validate([
            'mac' => 'nullable|string',
            'identity' => 'required_without:mac|string',
        ]);

        $mac = $request->query('mac');
        $identity = $request->query('identity');

        $router = $this->findRouter($mac, $identity);

        if (!$router) {
            return response()->json(['success' => false, 'message' => 'Router no registrado'], 404)
                             ->header('Access-Control-Allow-Origin', '*');
        }

        $routerData = $router->toArray();
        $routerData['comercio_nombre'] = $router->comercio_nombre; // Aseguramos que se envíe
        $routerData['comercio_banner'] = $router->comercio_banner ? 'https://wifiexpres.com/storage/bannerrouter/' . $router->comercio_banner : asset('storage/bannerrouter/WIFIEXPRES_banner_01.jpg'); 
        
        $imgsUrls = [];
        $pathImgs = is_string($router->path_imgs) ? json_decode($router->path_imgs, true) : $router->path_imgs;

        if (is_array($pathImgs)) {
            foreach ($pathImgs as $img) {
                if ($img) {
                    $imgsUrls[] = asset('storage/carruselhotspot/' . $img);
                }
            }
        }
        $routerData['path_imgs'] = $imgsUrls;

        try {
            $userAdmin = User::where('role', 'admin')->first();
            $setting = Setting::where('user_id', $userAdmin->id)->first();
            $mode = $setting ? (int)$setting->mikrotik_connection_mode : 0; 
            $host = ($mode === 1 && !empty($router->dns)) ? $router->dns : $router->ip;

            $client = new Client([
                'host' => $host, 'user' => $router->admin, 'pass' => $router->password, 
                'port' => (int) ($router->api_port ?? 49152), 'timeout' => 3
            ]);
            
            $profilesMk = $client->query(new Query('/ip/hotspot/user/profile/print'))->read();
            $plansDb = Plan::where('router_id', $router->id)->get()->keyBy('mikrotik_profile');

            $finalPlans = [];
            foreach ($profilesMk as $profile) {
                $name = $profile['name'];
                if (isset($plansDb[$name])) {
                    $finalPlans[] = [
                        'name' => $plansDb[$name]->name,
                        'price' => $plansDb[$name]->price,
                        'mikrotik_profile' => $name,
                        'uptime' => $profile['session-timeout'] ?? 'Ilimitado'
                    ];
                }
            }
            return response()->json(['success' => true, 'router' => $routerData, 'plans' => $finalPlans], 200)
                             ->header('Access-Control-Allow-Origin', '*');
            
        } catch (Exception $e) {
            // Fallback a Base de Datos si MikroTik está offline
            $plansBackup = Plan::where('router_id', $router->id)
                ->get(['name', 'price', 'mikrotik_profile'])
                ->map(function($plan) {
                    return [
                        'name' => $plan->name,
                        'price' => $plan->price,
                        'mikrotik_profile' => $plan->mikrotik_profile,
                        'uptime' => 'Consultar al conectar'
                    ];
                });

            return response()->json([
                'success' => true, 
                'router' => $routerData, 
                'plans' => $plansBackup, 
                'status' => 'offline_db'
            ], 200)
            ->header('Access-Control-Allow-Origin', '*');
        }
    }


    public function getInfoRouter(Request $request)
    {
        $mac = $request->query('mac'); 
        $identity = $request->query('identity'); // Soportamos buscar por nombre de router

        if (!$mac && !$identity) {
            return response()->json(['success' => false, 'message' => 'Identificador no recibido'], 400);
        }

        $router = $this->findRouter($mac, $identity);

        if (!$router) {
            return response()->json(['success' => false, 'message' => 'Router no registrado'], 404)
                             ->header('Access-Control-Allow-Origin', '*');
        }

        $routerData = $router->toArray();
        $routerData['comercio_nombre'] = $router->comercio_nombre;
        $routerData['comercio_banner'] = $router->comercio_banner ? 'https://wifiexpres.com/storage/bannerrouter/' . $router->comercio_banner : asset('storage/bannerrouter/WIFIEXPRES_banner_01.jpg'); 
        
        $imgsUrls = [];
        $pathImgs = is_string($router->path_imgs) ? json_decode($router->path_imgs, true) : $router->path_imgs;

        if (is_array($pathImgs)) {
            foreach ($pathImgs as $img) {
                if ($img) {
                    $imgsUrls[] = asset('storage/carruselhotspot/' . $img);
                }
            }
        }
        $routerData['path_imgs'] = $imgsUrls;

        try {
            $userAdmin = User::where('role', 'admin')->first();
            $setting = Setting::where('user_id', $userAdmin->id)->first();
            $mode = $setting ? (int)$setting->mikrotik_connection_mode : 0; 
            $host = ($mode === 1 && !empty($router->dns)) ? $router->dns : $router->ip;

            $client = new Client([
                'host' => $host, 'user' => $router->admin, 'pass' => $router->password, 
                'port' => (int) ($router->api_port ?? 49152), 'timeout' => 3
            ]);
            
            $profilesMk = $client->query(new Query('/ip/hotspot/user/profile/print'))->read();
            $plansDb = Plan::where('router_id', $router->id)->get()->keyBy('mikrotik_profile');

            $finalPlans = [];
            foreach ($profilesMk as $profile) {
                $name = $profile['name'];
                if (isset($plansDb[$name])) {
                    $finalPlans[] = [
                        'name' => $plansDb[$name]->name,
                        'price' => $plansDb[$name]->price,
                        'mikrotik_profile' => $name,
                        'uptime' => $profile['session-timeout'] ?? 'Ilimitado'
                    ];
                }
            }
            return response()->json(['success' => true, 'router' => $routerData, 'plans' => $finalPlans], 200)
                             ->header('Access-Control-Allow-Origin', '*');
            
        } catch (Exception $e) {
            // Fallback a Base de Datos si MikroTik está offline
            $plansBackup = Plan::where('router_id', $router->id)
                ->get(['name', 'price', 'mikrotik_profile'])
                ->map(function($plan) {
                    return [
                        'name' => $plan->name,
                        'price' => $plan->price,
                        'mikrotik_profile' => $plan->mikrotik_profile,
                        'uptime' => 'Consultar al conectar'
                    ];
                });

            return response()->json([
                'success' => true, 
                'router' => $routerData, 
                'plans' => $plansBackup, 
                'status' => 'offline_db'
            ], 200)
            ->header('Access-Control-Allow-Origin', '*');
        }
    }

    public function v3RegisterLead(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $router_mac = $request->input('router_mac');
        $identity   = $request->input('identity');
        
        $profile_free = "cortesia"; 
        $password_free = "wifi123";

        if (!$name || !$email || !$phone || (!$router_mac && !$identity)) {
            return response()->json(['success' => false, 'message' => 'Faltan datos'], 400);
        }

        $router = $this->findRouter($router_mac, $identity);
        if (!$router) return response()->json(['success' => false, 'message' => 'Router no hallado'], 404);

        try {
            $userAdmin = User::where('role', 'admin')->first();
            $setting = Setting::where('user_id', $userAdmin->id)->first();
            $mode = $setting ? (int)$setting->mikrotik_connection_mode : 0; 
            $host = ($mode === 1 && !empty($router->dns)) ? $router->dns : $router->ip;

            $client = new Client([
                'host' => $host, 'user' => $router->admin, 'pass' => $router->password,
                'port' => (int) ($router->api_port ?? 49152), 'timeout' => 5,
            ]);

            $client->query((new Query('/ip/hotspot/user/add'))
                ->equal('name', $phone)
                ->equal('password', $password_free)
                ->equal('profile', $profile_free)
                ->equal('comment', "V3 Lead: $name | $email"))->read();

        } catch (Exception $e) {
            $script = "/ip hotspot user add name=\"$phone\" password=\"$password_free\" profile=\"$profile_free\" comment=\"V3 Lead Bridge: $name\"";
            $this->sendToBridge($router->macAddress, $script);
        }

        Ticket::updateOrCreate(['username' => $phone, 'router_id' => $router->id], [
            'identity' => $router->identity, 'plan' => $profile_free, 'costo' => 0,
            'tiempo_uso' => $profile_free, 'activado' => true, 'fecha_uso' => now(), 'estado' => 'en_uso'
        ]);

        return response()->json(['success' => true, 'message' => 'Acceso concedido'], 200);
    }
}