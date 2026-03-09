<?php

namespace App\Http\Livewire\Mikrotik\Hotspot;

use Livewire\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use RouterOS\Client;
use RouterOS\Query;

use App\Models\Router;

class ListPlanes extends Component
{
    public $datos = [
            'host' => '192.168.2.1',
            'user' => 'jose',
            'pass' => '123'
        ];
    public $state = [];

    public $router;

    public $namesProfilesUser = [];

    public $addressPool = [];

    public $showEditModal = false;

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

    public function exeQuery($datos, $query)
    {
        try {

                $client = $this->configRouter();

                //$client = new Client($datos);

                $query = new Query($query);

                $result = $client->query($query)->read();

            } catch (Exception $e) {
                $result = "Caught exception: " . $e->getMessage() . "\n";
            } 
        return $result;
    }

    public function exeQueryListPlanes($datos, $query)
    {
        try {
             
                // Iniciar la conexión
                $client = new Client($datos);
                
                $query = new Query($query);

                $result = $client->query($query)->read();

            } catch (Exception $e) {
                $result = "Caught exception: " . $e->getMessage() . "\n";
            } 
        return $result;
    }

    public function listPlanes(Request $request)
    {
        try {

            $host = $request->post('dns');
            $router = $request->post('router');
            $admin = $request->post('admin');
            $password = $request->post('password');
            if(config('app.host') == 'ip'){
                $host = $request->post('ip');
            }else{
                $host = $request->post('dns');
            }        
            $datos = [
                'host' => $host,
                'user' => $admin,
                'pass' => $password,
                'port' => 8728,
            ];
            
            //todas los perfiles de hotspot
            $profiles = $this->exeQueryListPlanes($datos, '/ip/hotspot/user/profile/print');
            $namesProfiles = [];
            foreach ($profiles as $elemento) {
                $namesProfiles[] = $elemento['name'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Planes extraidos satisfactoriamente!.',
                'planes' => $profiles,
            ]);

        } catch (Exception $e) {
            // Manejar errores de conexión o de la API
            return response()->json([
                'success' => false,
                'message' => 'Escaneando... Espere, o refrescar la pagina!.',
            ]);
            // return response()->json(['success' => false, 'message' => 'Error de conexión con el router.', 'error' => $e->getMessage()], 500);
        }
    }

    public function addNew()
	{
        
     
        $namesProfilesUser = $this->namesProfilesUser;
        $addressPool = $this->addressPool;
        $router = $this->router;
        
		$this->reset();
        $this->namesProfilesUser = $namesProfilesUser;
        $this->addressPool = $addressPool;
        $this->router = $router;

		$this->showEditModal = false;

        $this->state['sharedUsers'] =  1;
        $this->state['sessionTimeout'] =  '00:01:00';
        $this->state['idleTimeout'] =  '01:00:00';
        $this->state['keepaliveTimeout'] =  '00:02:00';
        $this->state['statusAutorefresh'] =  '00:00:10';
        $this->state['rateLimitRxTx'] =  '1M/1M';
        $this->state['addressPool'] =  'none';

        $this->dispatchBrowserEvent('show-formProfileUser', ['addressPool' => $this->addressPool]);

	}

    public function createProfile()
    {
        try {
                $messages = [
                    'required' => 'El campo :attribute es requerido.',
                    'name.max' => 'The name cannot exceed 255 characters.',
                ];

                $validatedData = Validator::make($this->state, [
                    'name' => 'required',
                    'addressPool' => 'required|not_in:0',
                    'sharedUsers' => 'required',
                    'sessionTimeout' => 'nullable',
                    'idleTimeout' => 'nullable',
                    'keepaliveTimeout' => 'nullable',
                    'statusAutorefresh' => 'nullable',
                    'rateLimitRxTx' => 'required',
                    'costo' => 'required',

                ], $messages)->validate();

                $datos = [
                    'host' => '192.168.2.1',
                    'user' => 'admin',
                    'pass' => 'admin123'
                ];

                $client = new Client($datos);

                // $query = (new Query('/ip/hotspot/user/profile/add'))
                //      ->equal('name', $validatedData['name'])
                //      ->equal('address-pool', $validatedData['addressPool'])
                //      ->equal('shared-users', $validatedData['sharedUsers'])
                //      ->equal('session-timeout', $validatedData['sessionTimeout'])
                //      ->equal('idle-timeout', $validatedData['idleTimeout'])
                //      ->equal('keepalive-timeout', $validatedData['keepaliveTimeout'])
                //      ->equal('status-autorefresh', $validatedData['statusAutorefresh'])
                //      ->equal('rate-limit-rx-tx', $validatedData['rateLimitRxTx']);
                    //  ->equal('comment', $validatedData['comment']);
                
                 // 2. Definir los parámetros para el nuevo perfil
                $profileName = '10M 2';
                $downloadRate = '10M';
                $uploadRate = '10M';
                $sessionTimeout = '1h'; // 1 hora de sesión

                // 3. Construir la consulta de la API para crear el perfil
                $query = (new Query('/ip/hotspot/user/profile/add'))
                    ->equal('name', "{$validatedData['name']}/{$validatedData['costo']}")
                    ->equal('address-pool', $validatedData['addressPool'])
                    ->equal('rate-limit', $validatedData['rateLimitRxTx'])
                    // ->equal('rate-limit', "{$downloadRate}/{$uploadRate}")
                    // ->equal('address-list', $validatedData['addressList'])
                    ->equal('session-timeout', $sessionTimeout);
                    // ->equal('comment', 'Perfil creado desde Laravel');

                // Ejecutar la consulta
                $respuesta = $client->query($query)->read();
                // Tarea completada.

                $this->dispatchBrowserEvent('hide-formProfileUser', ['message' => 'Perfil agregado satisfactoriamente!']);

            } catch (Exception $e) {
                $this->dispatchBrowserEvent('hide-formProfileUser', ["Caught exception: " . $e->getMessage() . "\n"]);
                
            } 

		//$validatedData['password'] = bcrypt($validatedData['password']);
    }

    public function render()
    {
        
        //todos los perfiles usuarios de los hotspot
        $profilesUser = $this->exeQuery($this->datos, '/ip/hotspot/user/profile/print');
        $this->namesProfilesUser = [];
        foreach ($profilesUser as $elemento) {
            $this->namesProfilesUser[] = $elemento['name'];
        }

        //todos los perfiles usuarios de los hotspot
        $address = $this->exeQuery($this->datos, '/ip/pool/print');
        $this->addressPool = [];
        foreach ($address as $elemento) {
            $this->addressPool[] = $elemento['name'];
        }

        return view('livewire.mikrotik.hotspot.list-planes', ['profilesUser' => $profilesUser]);
    }

    public function saveComment(Request $request)
    {
        try {

            $host = $request->post('dns');
            $router = $request->post('router');
            $admin = $request->post('admin');
            $password = $request->post('password');
            $username = $request->post('username');

            
            if(config('app.host') == 'ip'){
                $host = $request->post('ip');
            }else{
                $host = $request->post('dns');
            }
            // Iniciar la conexión
            $client = new Client([
                'host' => $host,
                'user' => $admin,
                'pass' => $password,
                'port' => 8728,
            ]);
            
            $query = (new Query('/ip/hotspot/user/set'))
                ->equal('name', $username)
                ->equal('comment', 'activo');

            $response = $client->query($query)->read();
            
            return response()->json([
                'success' => true,
                'message' => 'Planes extraidos satisfactoriamente!.',
            ]);

        } catch (Exception $e) {
            // Manejar errores de conexión o de la API
            return response()->json([
                'success' => false,
                'message' => 'Fallo la asignación del status al user!.',
            ]);
            // return response()->json(['success' => false, 'message' => 'Error de conexión con el router.', 'error' => $e->getMessage()], 500);
        }
    }
}
