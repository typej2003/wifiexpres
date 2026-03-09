<?php

namespace App\Http\Livewire\Mikrotik\Router;

use Livewire\Component;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;

class RouterHotspots extends Component
{
    public $router;

    public $state = [];

    public $showEditModal = false;

    public $namesInterfaces = [];
    public $namesProfiles = [];
    public $namesProfilesUser = [];
    public $usersHotspot = [];
    public $nameshotspots = [];
    public $namesUsershotspots = [];

    public function mount($router_id)
    {        
        $this->router = Router::find($router_id);
    }

    public function exeQuery($datos, $query)
    {
        try {
                
                $client = new Client($datos);

                $query = new Query($query);

                $result = $client->query($query)->read();

            } catch (Exception $e) {
                $result = "Caught exception: " . $e->getMessage() . "\n";
            } 
        return $result;
    }

    public function cantUsershotspots($hotspot)
    {
        $cant = 0;
        $cantTrial = 0;

        foreach ($this->namesUsershotspots as $elementos) {            
            if(array_key_exists('server', $elementos))
            {
                if($elementos['server'] == $hotspot){
                    $cant++;
                }                
            }
            else{
                $cantTrial++;
            }
        }
        return $cant;
    }

    public function cantUserstrial()
    {
        $cant = 0;
        $cantTrial = 0;

        foreach ($this->namesUsershotspots as $elementos) {            
            if(!array_key_exists('server', $elementos))
            {            
                $cant++;     
            }
        }
        return $cant;
    }

    public function addNew()
	{
        $router = $this->router; 
        $namesInterfacesT = $this->namesInterfaces; 
        $namesProfiles = $this->namesProfiles;
        $namesProfilesUser = $this->namesProfilesUser;
		$this->reset();
        $this->router = $router; 
        $this->namesInterfaces = $namesInterfacesT; 
        $this->namesProfiles = $namesProfiles;
        $this->namesProfilesUser = $namesProfilesUser;

		$this->showEditModal = false;

        $this->dispatchBrowserEvent('show-formHotspot', ['namesInterfaces' => $this->namesInterfaces, 'namesProfiles' => $this->namesProfiles]);

	}

    public function createHotspot()
    {
        try {
                $messages = [
                    'required' => 'El campo :attribute es requerido.',
                    'name.max' => 'The name cannot exceed 255 characters.',
                ];

                $validatedData = Validator::make($this->state, [
                    'name' => 'required',
                    'interfaceH' => 'required|not_in:0',
                    'profileH' => 'required|not_in:0',
                ], $messages)->validate();

                $datos = [
                    'host' => '192.168.2.1',
                    'user' => 'admin',
                    'pass' => 'admin123'
                ];

                $client = new Client($datos);

                $query = (new Query('/ip/hotspot/add'))
                     ->equal('name', $validatedData['name'])
                     ->equal('interface', $validatedData['interfaceH'])
                     ->equal('profile', $validatedData['profileH']);
                
                // Ejecutar la consulta
                $client->query($query)->read();
                // Tarea completada.

                $this->dispatchBrowserEvent('hide-formHotspot', ['message' => 'Hotspot agregado satisfactoriamente!']);
            } catch (Exception $e) {
                $this->dispatchBrowserEvent('hide-formHotspot', ["Caught exception: " . $e->getMessage() . "\n"]);
                
            } 

		//$validatedData['password'] = bcrypt($validatedData['password']);
    }

    public function addNewUserHotspot($nameserverUH)
	{
        $router = $this->router; 
        $namesInterfaces = $this->namesInterfaces; 
        $namesProfiles = $this->namesProfiles;
        $namesProfilesUser = $this->namesProfilesUser;
        $nameshotspots = $this->nameshotspots;
		$this->reset();
        $this->router = $router; 
        $this->namesInterfaces = $namesInterfaces; 
        $this->namesProfiles = $namesProfiles;
        $this->namesProfilesUser = $namesProfilesUser;
        $this->nameshotspots = $nameshotspots;

		$this->showEditModal = false;
        $this->state['serverUH'] = $nameserverUH;

        $this->dispatchBrowserEvent('show-formUserHotspot', ['hotspots' => $this->nameshotspots, 'namesProfilesUser' => $this->namesProfilesUser, 'nameserverUH' => $nameserverUH ]);

	}

    public function createUserHotspot()
    {
        try {
                $messages = [
                    'required' => 'El campo :attribute es requerido.',
                    'name.max' => 'The name cannot exceed 255 characters.',
                ];

                $validatedData = Validator::make($this->state, [
                    'serverUH' => 'required',
                    'nameUH' => 'required',
                    'password' => 'required',
                    'address' => 'nullable',
                    'macaddress' => 'nullable',
                    'profileUH' => 'required|not_in:0',
                    'routes' => 'nullable',
                    'email' => 'nullable',                    
                ], $messages)->validate();

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

                // Crear la consulta para añadir el usuario
                $query = (new Query('/ip/hotspot/user/add'))
                    ->equal('server', $validatedData['serverUH'])
                    ->equal('name', $validatedData['nameUH'])
                    ->equal('password', $validatedData['password'])
                    ->equal('profile', $validatedData['profileUH']);
                
                // Ejecutar la consulta .
                $result = $client->query($query)->read();
                // Tarea completada.
                if($result['after']['message'] == 'failure: already have user with this name for this server')
                {
                    // Buscar el usuario
                    $query = (new Query('/ip/hotspot/user/print'))
                        ->where('name', $validatedData['nameUH']);
                    
                    // // Ejecutar la consulta
                    $user = $client->query($query)->read();
                    //dd($user[0]['.id']);

                    // Modificar datos
                    $query = (new Query('/ip/hotspot/user/set'))
                        ->where('.id', '*2B')
                        ->equal('server', $validatedData['serverUH'])
                        ->equal('password', $validatedData['password'])
                        ->equal('profile', $validatedData['profileUH']);
                    
                    // Ejecutar la consulta
                    $result = $client->query($query)->read();
                    dd($result);
                }

                $this->dispatchBrowserEvent('hide-formUserHotspot', ['message' => 'Usuario del Hotspot agregado satisfactoriamente!']);
            } catch (Exception $e) {
                $this->dispatchBrowserEvent('hide-formUserHotspot', ["Caught exception: " . $e->getMessage() . "\n"]);
                
            } 

		//$validatedData['password'] = bcrypt($validatedData['password']);
    }

    public function render()
    {
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
        

        // todas las interfaces
        $interfaces = $this->exeQuery($datos, '/interface/print');
        $this->namesInterfaces = [];
        foreach ($interfaces as $elemento) {
            $this->namesInterfaces[] = $elemento['name'];
        }

        //todas los perfiles de hotspot
        $profiles = $this->exeQuery($datos, '/ip/hotspot/profile/print');
        $this->namesProfiles = [];
        foreach ($profiles as $elemento) {
            $this->namesProfiles[] = $elemento['name'];
        }

        //todos los usuarios de los hotspot
        $users = $this->exeQuery($datos, '/ip/hotspot/user/print');

        $this->namesUsershotspots = $users;

        //dd($this->cantUsershotspots('hotspot1'));
        //dd($this->cantUserstrial());        
        //dd($users);

        //todos los hotspot
        $hotspots = $this->exeQuery($datos, '/ip/hotspot/print');
        $this->nameshotspots = [];
        foreach ($hotspots as $elemento) {
            $this->nameshotspots[] = $elemento['name'];
        }

        //todos los perfiles usuarios de los hotspot
        $profilesUser = $this->exeQuery($datos, '/ip/hotspot/user/profile/print');
        $this->namesProfilesUser = [];
        foreach ($profilesUser as $elemento) {
            $this->namesProfilesUser[] = $elemento['name'];
        }

        // foreach ($users as $elemento) {
        //     $this->usersHotspot[] = $elemento['name'];
        // ..

        return view('livewire.mikrotik.router.router-hotspots', ['result' => $hotspots ]);
    }
}
