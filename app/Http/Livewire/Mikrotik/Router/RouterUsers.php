<?php

namespace App\Http\Livewire\Mikrotik\Router;

use Livewire\Component;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Router;

use RouterOS\Client;
use RouterOS\Query;

class RouterUsers extends Component
{
    public $router;

    public $state = [];

    public $showEditModal = false;

    public function mount($nrorouter = "R001")
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

    public function addNew()
	{
        $router = $this->router;
		$this->reset();
        $this->router = $router;

		$this->showEditModal = false;

		$this->dispatchBrowserEvent('show-form');
	}

    public function createUser()
    {
        try {
                $validatedData = Validator::make($this->state, [
                    'name' => 'required',
                    'password' => 'required|confirmed',
                    'group' => 'required|not_in:0',
                ])->validate();
                
                $client = $this->configRouter();

                $query = (new Query('/user/add'))
                     ->equal('name', $validatedData['name'])
                     ->equal('password', $validatedData['password'])
                     ->equal('group', $validatedData['group']);
                
                // Ejecutar la consulta
                $client->query($query)->read();
                // Tarea completada.


                $this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario agregado satisfactoriamente!']);
            } catch (Exception $e) {
                $this->dispatchBrowserEvent('hide-form', ["Caught exception: " . $e->getMessage() . "\n"]);
                
            } 

		//$validatedData['password'] = bcrypt($validatedData['password']);
    }

    public function deleteUser($nameProfile)
    {
        try {
            $client = $this->configRouter();

            $query = (new Query('/user/remove'))
            ->equal('.id', $nameProfile);
            
            $delete = $client->query($query)->read();
            return true;
                
        } catch (\Exception $e) {
            // Manejar el error
            return null;
        }
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

        $query = '/user/print';

        $result = $this->exeQuery($datos, $query);

        return view('livewire.mikrotik.router.router-users', ['result' => $result ]);
    }
}
