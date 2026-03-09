<?php

namespace App\Http\Livewire\Mikrotik\Hotspot;

use Livewire\Component;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use RouterOS\Client;
use RouterOS\Query;

use App\Models\Router;
use App\Models\Evento;
use App\Models\TicketUser;
use App\Models\UserMikrotik;

use Illuminate\Http\Request;
//use \RouterOS; // Asegúrate de que este 'use' apunte al namespace correcto

class CrearTicketPhone extends Component
{
    public $nameshotspots = [];

    public $namesprofiles = [];

    public $state = [];

    public $router;

    public $usershotspot = [];

    public $showEditModal = false;

    public $datos = [
        'host' => '192.168.2.1',
        'user' => 'admin',
        'pass' => 'admin123'
    ];

    public $cuenta = [
        'id' => '',
        'name' => '',
        'password' => '',
    ];
    public $cuentas = [];

    public function mount($nrorouter = 'R001')
    {        
        $this->router = Router::where('nrorouter', $nrorouter)->first();
        //todos los hotspots
        $hotspots = $this->exeQuery($this->datos, '/ip/hotspot/print');
        $this->nameshotspots = [];
        foreach ($hotspots as $elemento) {
            $this->nameshotspots[] = $elemento['name'];
        }

        //todos los profiles
        $profiles = $this->exeQuery($this->datos, '/ip/hotspot/user/profile/print');
        $this->namesprofiles = [];
        foreach ($profiles as $elemento) {
            $this->namesprofiles[] = $elemento['name'];
        }
        
        $this->state['server'] = 'all';
        $this->state['prefijo'] = 'all';

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
    
    public function createHotspotUsers()
    {
        
        $messages = [
                    'required' => 'El campo :attribute es requerido.',
                    'name.max' => 'The name cannot exceed 255 characters.',
                ];

        $validatedData = Validator::make($this->state, [
            'server' => 'required|not_in:0',
            'profile' => 'required|not_in:0',
            'cellphone' => 'required',
            'prefijo' => 'required',
            'monto' => 'required',
        ], $messages)->validate();
        
        try {

            $client = $this->configRouter();
            
            $username = $validatedData['cellphone'];

            $userMikrotik = UserMikrotik::where('name', $username)->first();

            $nrorouter = $this->router->nrorouter;
            $server = $validatedData['server'];
            $profile = $validatedData['profile'];
            $prefijo = $validatedData['prefijo'];
            $monto = $validatedData['monto'];
            
            if(!$userMikrotik)
			{
                // Genera la contraseña de 8 dígitos
                $password = $this->randomPassword();
                // Genera el serial de 8 dígitos
                $serial = $this->randomSerial();

                $query = (new Query('/ip/hotspot/user/add'))
                    ->equal('server', $server)
                    ->equal('name', $username)
                    ->equal('password', $password)
                    ->equal('comment', 'noactivo')
                    ->equal('profile', $profile);
                // Ejecutar la consulta
                $response = $client->query($query)->read();
                // Tarea completada.

                $mikrotik_id = $this->searchId_mikrotik($client, $username);
				
                $userMikrotik = UserMikrotik::create([
					'server' => $server,
					'name' => $username,
					'password' => $password,
					'profile' => $profile,
                    'routes' => $this->router->nrorouter,
                    'mikrotik_id'  => $mikrotik_id,
				]);

                // TicketUser::create([
                //     'nroTicket' => 	$serial,
                //     'user_id' => auth()->user()->id,
                //     'userMikrotik_id' => $userMikrotik->id,
                //     'server' =>	$server,
                //     'user' => $username,
                //     'password' => $password,
                //     'profile' => $profile,
                //     'prefijo' => $prefijo,
                //     'monto' => $monto,	
                //     'nrorouter' => $this->router->nrorouter,
                // ]);
                
                $this->createTicketUser($userMikrotik, $nrorouter, $server, $username, $password, $profile, $prefijo, $monto);
                
                $this->cuentas[] = ['name' => $username, 'password' => $password];

                $this->dispatchBrowserEvent('hide-form', ['message' => 'Se han creado el usuario ' . $username . ' de Hotspot con éxito.']);

                $newUser = [
					'user' => $username,
					'password' => $password,
					'status' => true,
				];

            }else{

                $password = $this->randomPassword();

                $newUser = [
                        'user' => $username,
                        'password' => $password,
                        'status' => true,
                    ];

                $userMikrotik->update(['profile'=>$profile, 'password'=>$password]);
				$mikrotik_id = $userMikrotik->mikrotik_id;
				//$password = $userMikrotik->password;

                if(!$mikrotik_id){
					$mikrotik_id = $this->searchId_mikrotik($client, $username);
					$userMikrotik->update(['mikrotik_id'=>$mikrotik_id]);
				}
                
				//si se borro del mikrotik pero esta en bd, ultimo codigo en ser agregado
				if(!$this->searchId_mikrotik($client, $username)){
                    //crear en mikrotik
					// Crear la consulta para añadir el usuario
					$query = (new Query('/ip/hotspot/user/add'))
						->equal('server', $server)
                        ->equal('name', $username)
                        ->equal('password', $password)
                        ->equal('profile', $profile);
					
					// Ejecutar la consulta
					$client->query($query)->read();
                    // Tarea completada.
					
					$mikrotik_id = $this->searchId_mikrotik($client, $username);
					
                    $userMikrotik->update(['mikrotik_id' => $mikrotik_id]);

				}

                $this->createTicketUser($userMikrotik, $nrorouter, $server, $username, $password, $profile, $prefijo, $monto);

				// Modificar profile
				$query = (new Query('/ip/hotspot/user/set'))
					->equal('.id', $mikrotik_id)
					->equal('password', $password)
					->equal('profile', $profile);

				$response = $client->query($query)->read();

                $this->cleanUptime($mikrotik_id, $newUptime = "00:00:00");

                $this->dispatchBrowserEvent('hide-form', ['message' => 'Se han actualizo el usuario ' . $username . ' de Hotspot con éxito.']);
			}

            // asignar limit uptime
			$this->defineUptimeLimit($userMikrotik, $mikrotik_id, $profile, $newUptimeLimit = "00:00:15");
            // Puedes manejar la respuesta si es necesario
            // Por ejemplo, registrar en la base de datos de Laravel si el usuario se creó correctamente   
            $this->cuentas[] = ['name' => $username, 'password' => $password];         
            
            //llamar a graficar qr
            $this->dispatchBrowserEvent('crear-qr', ['usershotspot' => $this->cuentas]);

            //return 'Se han creado 10 usuarios de Hotspot con éxito.';

        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function createTicketUser($userMikrotik, $nrorouter, $server, $username, $password, $profile, $prefijo, $monto){

        TicketUser::create([
            'nroTicket' => 	$this->randomSerial(),
            'user_id' => auth()->user()->id,
            'userMikrotik_id' => $userMikrotik->id,
            'server' =>	$server,
            'user' => $username,
            'password' => $password,
            'profile' => $profile,
            'prefijo' => $prefijo,
            'monto' => $monto,
            'nrorouter' => $nrorouter,
        ]);

        return 0;
    }

    private function randomSerial() {
        $seguir = true;
        $x = 0;
        while ($seguir) {
            $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
            //$alphabet = '1234567890';
            $pass = array(); //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < 8; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }

            if($x > 1000 )
            {
                $seguir = false;
            }else{
                $x = $x + 1;
                $serial = TicketUser::where('nroTicket', implode($pass))->first();
                if (!$serial){
                    $seguir = false;
                }
            }
                
        }
		
		return implode($pass); //turn the array into a string
	}

    public function searchId_mikrotik($client, $user)
	{
		try {
            
			// buscar id
			$query = (new Query('/ip/hotspot/user/print'))
				->where('name', $user);
			$response = $client->query($query)->read();

			return  $response[0]['.id'];


		} catch (\Throwable $th) {

			return false;
		}
		
	}

    public function defineUptimeLimit(UserMikrotik $userMikrotik, $id, $profile, $newUptimeLimit = "00:00:15")
    {

        $client = $this->configRouter();

        try {
            //buscar tiempo del perfil de user
            $newUptimeLimit = $this->timeProfileUser($profile);
            
            $query = (new Query('/ip/hotspot/user/set'))
                ->equal('.id', $id)
                ->equal('limit-uptime', $newUptimeLimit);

            $response = $client->query($query)->read();

			$userMikrotik->update(['limitUptime' => $newUptimeLimit ]);
            
            return true;            

        } catch (\Exception $e) {
            return false;
        }        
    }

	public function timeProfileUser($name)
    {
        $client = $this->configRouter();
        
        // Buscar el usuario
        $query = (new Query('/ip/hotspot/user/profile/print'))
            ->where('name', $name);
            
        // Ejecutar la consulta
        $time = $client->query($query)->read();

        if (isset($time[0]['session-timeout'])) {
            return $time[0]['session-timeout'];
        }else{
            return '';
        }
    }

    public function cleanUptime($id, $newUptime = "00:00:00")
    {
        $client = $this->configRouter();

        $userName = "user"; // El nombre del usuario a modificar
        
        try {
            
            $query = (new Query('/ip/hotspot/user/reset-counters'))
                ->equal('.id', $id);


            $response = $client->query($query)->read();
            
            return true;
            

        } catch (\Exception $e) {
            return false;
        }

        
    }

    public function selectUsershotspots($users, $hotspot, $prefijo)
    {
        $usershotspots = [];
        $aliado = auth()->user()->name;
        
        foreach ($users as $elementos) { 
            if(isset($elementos['comment']))
            {
                if($elementos['comment']=='activo' && $elementos['name']!=='default-trial'){
                        $status = true;
                }else{
                    $status = false;
                }
            }else{
                $status = false;
            }
            if($elementos['name']!= 'default-trial'){
                if(array_key_exists('server', $elementos))
                {
                    if($elementos['server'] == $hotspot){
                        //buscar si la cadena existe en el array
                        if (str_contains($elementos['name'], $prefijo)) {                            
                            $usershotspots[] = ['aliado' => $aliado, 'name' => $elementos['name'], 'password' => $elementos['password'], 'status' => $status];
                        }
                        
                    }                
                }else{
                    if($prefijo == 'all'){
                        $usershotspots[] = ['aliado' => $aliado, 'name' => $elementos['name'], 'password' => $elementos['password'], 'status' => $status];
                    }else{
                        if (str_contains($elementos['name'], $prefijo)) {                         
                            $usershotspots[] = ['aliado' => $aliado, 'name' => $elementos['name'], 'password' => $elementos['password'], 'status' => $status];
                        }
                    }
                    
                }        
            }            
        }        
        
        return $usershotspots;
    }

    /**
     * Genera una contraseña de 8 dígitos con un dígito y un carácter especial.
     */
    private function generatePassword()
    {
        $chars = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        $specialChar = $chars[rand(0, strlen($chars) - 1)];

        // Genera 7 caracteres aleatorios
        $randomChars = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 7);

        // Inserta un dígito en una posición aleatoria
        $position = rand(0, 7);
        $password = substr_replace($randomChars, rand(0, 9), $position, 0);

        // Inserta el carácter especial en una posición aleatoria
        $position = rand(0, 8);
        $password = substr_replace($password, $specialChar, $position, 0);

        return $password;
    }

    private function randomPassword() {
		// $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$alphabet = '1234567890';
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}

    private function randomNroTicket() {
		// $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$alphabet = '1234567890';
		$nroTicket = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$nroTicket[] = $alphabet[$n];
		}

        $resp = TicketUser::where('nroTicket', implode($nroTicket))->first();        

        if($resp != null){
            $nroTicket = $this->randomNroTicket();
        }
        return implode($nroTicket); //turn the array into a string
	}

    public function showUsersHotspot()
    {
        $messages = [
                    'required' => 'El campo :attribute es requerido.',
                    'name.max' => 'The name cannot exceed 255 characters.',
                ];

        $validatedData = Validator::make($this->state, [
            'server' => 'required|not_in:0',
            'prefijo' => 'required|not_in:0',
        ], $messages)->validate();

        $server = $validatedData['server'];
        $prefijo = $validatedData['prefijo'];

        $users = $this->exeQuery($this->datos, '/ip/hotspot/user/print');

        $this->usershotspot = $this->selectUsershotspots($users, $server, $prefijo);

        $this->dispatchBrowserEvent('crear-qr', ['usershotspot' => $this->usershotspot]);

        
    }

    public function render()
    {
        if(auth()->user()->role !== 'admin'){
            $eventos = Evento::where('user_id', auth()->user()->id)->get();
        }else{
            $eventos = Evento::all();
        }        

        return view('livewire.mikrotik.hotspot.crear-ticket-phone', ['eventos' => $eventos]);
    }

}
