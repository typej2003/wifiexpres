<?php

namespace App\Http\Livewire\Mikrotik\Router;

use Livewire\Component;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Router;

use RouterOS\Client;
use RouterOS\Query;

class RouterPlanesAfiliado extends Component
{
    public $router;

    public $state = [];

    public $showEditModal = false;

    public $namesProfilesUser = [];

    public $addressPool = [];

    public $profileIdBeingRemoved = null;

    public function mount($router_id)
    {        
        $this->router = Router::find($router_id);
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
                $client = new Client($datos);

                $query = new Query($query);

                $result = $client->query($query)->read();

            } catch (Exception $e) {
                $result = "Caught exception: " . $e->getMessage() . "\n";
                return response()->json([
                    'success' => false,
                    'message' => $result,
                ]);
            } 
        return $result;
    }

    public function listPlanes()
    {
        try {
            $datos = [
                'host' => '192.168.2.1', // Reemplaza con la IP de tu router
                'user' => 'admin',      // Usuario API
                'pass' => 'admin123', // Contraseña API
                'port' => 8728,            // Puerto de la API
            ];

            //todas los perfiles de hotspot
            $profiles = $this->exeQuery($datos, '/ip/hotspot/user/profile/print');
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
            return response()->json(['success' => false, 'message' => 'Error de conexión con el router.', 'error' => $e->getMessage()], 500);
        }
    }

    public function addNew()
	{
        $router = $this->router;
        $namesProfilesUser = $this->namesProfilesUser;
        $addressPool = $this->addressPool;        
		$this->reset();
        $this->router = $router;
        $this->namesProfilesUser = $namesProfilesUser;
        $this->addressPool = $addressPool;

		$this->showEditModal = false;

        $this->state['sharedUsers'] =  1;
        $this->state['sessionTimeout'] =  '00:10:00';
        $this->state['idleTimeout'] =  '00:00:10';
        $this->state['keepaliveTimeout'] =  '00:01:00';
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
                    'sessionTimeout' => 'nullable', //tiempo máximo que un usuario permanece conectado y autorizado
                    'idleTimeout' => 'nullable', //Detectar y cerrar la sesión de un usuario que ha dejado de usar activamente la red
                    'keepaliveTimeout' => 'nullable', //Sirve para verificar si un usuario aún está conectado al router
                    'statusAutorefresh' => 'nullable',
                    'downloadRate' => 'required',
                    'uploadRate' => 'required',
                    'costo' => 'required',

                ], $messages)->validate();

                $client = $this->configRouter();
                
                // 3. Construir la consulta de la API para crear el perfil
                $query = (new Query('/ip/hotspot/user/profile/add'))
                    ->equal('name', "{$validatedData['name']}/{$validatedData['costo']}/aliado")
                    ->equal('shared-users', $validatedData['sharedUsers'])
                    ->equal('address-pool', $validatedData['addressPool'])
                    ->equal('rate-limit', $validatedData['downloadRate'].'/'.$validatedData['uploadRate'])
                    // ->equal('address-list', $validatedData['addressList'])
                    ->equal('session-timeout', $validatedData['sessionTimeout']);
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

    public function confirmProfileRemoval($id)
	{
		$this->profileIdBeingRemoved = $id;

		$this->dispatchBrowserEvent('show-delete-modal');
	}

    public function deleteProfile()
    {
        try {
            
            $client = $this->configRouter();

            $query = (new Query('/ip/hotspot/user/profile/remove'))
            ->equal('.id', $this->profileIdBeingRemoved);
            
            $delete = $client->query($query)->read();

            $this->dispatchBrowserEvent('hide-delete-modal');

            return true;
                
        } catch (\Exception $e) {
            // Manejar el error
            return null;
        }
    }

    public function render()
    {
        //todos los perfiles usuarios de los hotspot

        if(config('app.host') == 'ip'){
            $host = $this->router->ip;
        }else{
            $host = $this->router->dns;
            //$host = 'typej.ddns.net';
            //$host = '192.168.1.6';
        }        
        
        // Iniciar la conexión
        $datos = [
            'host' => $host,
            'user' => $this->router->admin,
            'pass' => $this->router->password,
            'port' => 8728,
        ];

        $profilesUser = $this->exeQuery($datos, '/ip/hotspot/user/profile/print');
        $this->namesProfilesUser = [];
        foreach ($profilesUser as $elemento) {
            $this->namesProfilesUser[] = $elemento['name'];
            
        }

        //todos los perfiles usuarios de los hotspot
        $address = $this->exeQuery($datos, '/ip/pool/print');
        $this->addressPool = [];
        foreach ($address as $elemento) {
            $this->addressPool[] = $elemento['name'];
        }

        return view('livewire.mikrotik.router.router-planes-afiliado', ['profilesUser' => $profilesUser]);
    }
}
