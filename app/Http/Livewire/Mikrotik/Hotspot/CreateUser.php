<?php

namespace App\Http\Livewire\Mikrotik\Hotspot;

use Livewire\Component;
use Illuminate\Http\Request;

use RouterOS\Client;
use RouterOS\Query;

use App\Models\Router;

class CreateUser extends Component
{
    public $datos = [
            'host' => '192.168.2.1',
            'user' => 'admin',
            'pass' => 'admin123'
        ];
    public $router;

    public function mount($nrorouter = 'R001')
    {
        $this->router = Router::where('nrorouter', $nrorouter)->first();
    }

    public function configRouter()
    {
        if(config('app.host') == 'ip'){
            $host = $this->router->ip;
        }else{
            $host = $this->router->dns;
            //$host = 'typej.ddns.net';
            //$host = '192.168.1.6';
        }        
        
        // Iniciar la conexión
        $client = new Client([
            'host' => $host,
            'user' => $this->router->admin,
            'pass' => $this->router->password,
            'port' => 8728,
        ]);

        return $client;
    }
        
    public function index()
    {
        //
        $data = ['valor' => "Operacion exitosa a traves de api! con index", ];
        return response()->json($data);
    }
    
    public function addNew()
	{
        //esto es un ejemplo
        
        $server = 'hotspot1';
        $username = 'usuarioPrueba';
        $password = '12345';
        $profile = '1hora';

        $datos = [
                'host' => '192.168.2.1',
                'user' => 'admin',
                'pass' => 'admin123'
            ];

        // $data = ['valor' => "Operacion exitosa a traves de api! con addNew", ];
        // return response()->json($data);

        $response = $this->crearUsuarioHotspot($server, $username, $password, $profile = 'default');

        if ( $response['status'] ){

            $response = $this->login($username, $password);

            if ( $response['status'] ){
                $data = $response;
                return response()->json($data);
                //return response()->json($data)->header('Access-Control-Allow-Origin', 'http://192.168.2.1');
            }else{
                $data = $response;
                return response()->json($data);
            }

        }else{
            $data = ['valor' => "Usuario no creado!", 'status' => false, ];
            return response()->json($data);
        }
        

	}

    public function crearUsuarioHotspot($server = 'all', $username, $password, $profile = 'default')
    {
        $datos = [
                'host' => '192.168.2.1',
                'user' => 'admin',
                'pass' => 'admin123'
            ];

        $macAddress = '1A-2B-3C-4D-5E';
        // $data = ['valor' => "Operacion exitosa a traves de api! con addNew", ];
        // return response()->json($data);

        try {

            $client = new Client($datos);

            // Crear la consulta para añadir el usuario
            $query = (new Query('/ip/hotspot/user/add'))
                ->equal('server', $server)
                ->equal('name', $username)
                ->equal('password', $password)
                ->equal('profile', $profile);
                //->equal('mac-address', $macAddress);
            
            // Ejecutar la consulta
            $client->query($query)->read();
            // Tarea completada.
                
            $data = ['valor' => "Usuario creado!", 'status' => true, ];

            return $data;

        } catch (Exception $e) {

            $data = ['valor' => $e, 'status' => false, ];
            
            return $data;
            
        } 
    }
    
    // Función para autenticar al usuario y darle acceso
    public function iniciarSesionHotspot($username, $password)
    {
        // Conexión al dispositivo MikroTik
        $conexion = [
            'host' => '192.168.2.1',
            'user' => 'admin',
            'pass' => 'admin123'
        ];
        $client = new Client($conexion);

        // Crear la consulta para iniciar sesión
        $query = (new Query('/login'))
            ->equal('name', $username)
            ->equal('password', $password);

        try {
            // Ejecutar la consulta de login
            $response = $client->query($query)->read();
            // Verificar la respuesta para confirmar el éxito
            if (!empty($response) && isset($response['!trap'])) {
                $msj = 'Error de autenticación: ' . $response['!trap'][0]['message'];
                return [
                    'valor' => $msj,
                    'status' => false,
                ];
                
            }
            return [
                    'valor' => 'Session iniciada satisfactoriamente!',
                    'status' => true,
                ];;
        } catch (Exception $e) {
            return [
                    'valor' => $e,
                    'status' => false,
                ];
        }
    }

    public function login($username, $password)
    {
        try {

            $client = new Client([
                'host' => '192.168.2.1', // Reemplaza con la IP de tu router
                'user' => 'admin',      // Usuario API
                'pass' => 'admin123', // Contraseña API
                //'port' => 8729,            // Puerto de la API
            ]);

            $macAddress = '1A-2B-3C-4D-5E';

            // Se utiliza el comando /ip/hotspot/active/login
            $query = new Query('/ip/hotspot/active/login');
            $query->set('user', $username);
            $query->set('password', $password);
            
            // La API de MikroTik requiere la dirección MAC para la autenticación
            // Asegúrate de enviar la MAC desde tu formulario HTML
            $query->set('mac-address', $macAddress);

            // $query = (new Query('/ip/hotspot/active/login'))
            //     ->equal('user', $username)
            //     ->equal('password', $password)
            //     ->equal('mac-address', $macAddress);

            $response = $client->query($query)->read();

            return ['valor' => "Operacion exitosa a traves de api!", 'status' => true];

            // Verificar si el login fue exitoso según la respuesta de MikroTik
            // if (empty($response)) {
            //     return response()->json([
            //         'status' => true,
            //         'valor' => 'Usuario autenticado exitosamente.',
            //         // Aquí puedes redirigir al usuario
            //         // El login.html debe manejar la redirección del navegador con JS
            //     ]);
            // } else {
            //     return response()->json([
            //         'status' => false,
            //         'valor' => 'Error de autenticación con MikroTik.',
            //         'response' => $response
            //     ]);
            // }

        } catch (Exception $e) {
            // Manejar errores de conexión o de la API
            return response()->json(['status' => false, 'valor' => 'Error de conexión con el router.', 'error' => $e->getMessage()], 500);
        }
    }

    public function render()
    {
        return view('livewire.mikrotik.hotspot.create-user');
    }

    public function login1(Request $request)
    {
        
        // 1. Obtener los datos del formulario de login
        $username = $request->input('username');
        $password = $request->input('password');

        // Asumimos que también recibes la dirección MAC del usuario para la autenticación
        $macAddress = $request->input('mac'); // Asegúrate de que tu formulario login.html envíe este dato

        // 2. Validar las credenciales del usuario con tu lógica de Laravel
        //    (e.g., verificar en una base de datos, servicio, etc.)
        //    Si las credenciales no son válidas, retornas un error.
        //    if (!Auth::attempt(['username' => $username, 'password' => $password])) {
        //        return response()->json(['success' => false, 'message' => 'Credenciales inválidas.']);
        //    }
        
        // Simulación de validación exitosa:
        $credentialsAreValid = true;

        if (!$credentialsAreValid) {
             return response()->json(['success' => false, 'message' => 'Credenciales inválidas.']);
        }
        
        // 3. Si las credenciales son válidas, usar la API de RouterOS para autenticar al usuario
        try {
            $client = new Client([
                'host' => '192.168.2.1', // Reemplaza con la IP de tu router
                'user' => 'admin',      // Usuario API
                'pass' => 'admin123', // Contraseña API
                'port' => 8728,            // Puerto de la API
            ]);
            
            // Se utiliza el comando /ip/hotspot/active/login
            // $query = new Query('/ip/hotspot/active/login');
            // $query->set('user', $username);
            // $query->set('password', $password);

            
            // La API de MikroTik requiere la dirección MAC para la autenticación
            // Asegúrate de enviar la MAC desde tu formulario HTML
            // $query->set('mac-address', $macAddress);

            //$query = (new Query('/ip/hotspot/active/login'))
            $query = (new Query('/login'))
                ->equal('name', $username)
                ->equal('password', $password)
                ->equal('mac-address', $macAddress);
                

            $response = $client->query($query)->read();

            // Verificar si el login fue exitoso según la respuesta de MikroTik
            if (empty($response)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Usuario autenticado exitosamente.',
                    'url' => 'https://panexpres.com',
                    // Aquí puedes redirigir al usuario
                    // El login.html debe manejar la redirección del navegador con JS
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de autenticación con MikroTik.',
                    'response' => $response
                ]);
            }

        } catch (Exception $e) {
            // Manejar errores de conexión o de la API
            return response()->json(['success' => false, 'message' => 'Error de conexión con el router.', 'error' => $e->getMessage()], 500);
        }
    }
}
