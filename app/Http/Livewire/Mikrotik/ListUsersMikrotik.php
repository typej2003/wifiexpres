<?php

namespace App\Http\Livewire\Mikrotik;

use Livewire\Component;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Router;

use RouterOS\Client;
use RouterOS\Query;

class ListUsersMikrotik extends Component
{
    public $state = [];

    public $showEditModal = false;

    public function mount($nrorouter="R001")
    {        
        $this->router = Router::where('nrorouter', $nrorouter)->first();
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

    public function addNew()
	{
		$this->reset();

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

                $datos = [
                    'host' => '192.168.2.1',
                    'user' => 'admin',
                    'pass' => 'admin123'
                ];

                $client = new Client($datos);

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

        return view('livewire.mikrotik.list-users-mikrotik', ['result' => $result]);
    }
}
