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

class CrearTicket extends Component
{
    public $nameshotspots = [];

    public $namesprofiles = [];

    public $state = [];

    public $router;

    public $usershotspot = [];

    public $showEditModal = false;

    public $ticketsUsers;

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

    protected $listeners = ['changeComment' => 'changeComment'];

    public function mount($nrorouter = 'R001')
    {        
        $this->router = Router::where('nrorouter', $nrorouter)->first();
        //todos los hotspots
        
        $this->state['server'] = 'all';
        $this->state['profile'] = 'all';
        $this->state['prefijo'] = 'all';
    }

    public function configRouter()
    {
        try{
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

        } catch (Exception $e) {
            $result = "Caught exception: " . $e->getMessage() . "\n";
            return view('livewire.error.show-error', [
                'error' => '144',
                'description' => $result,
            ]);
            
        } 
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
            'totalTicket' => 'required|not_in:0',
            'prefijo' => 'required|not_in:0',
            'monto' => 'required',
        ], $messages)->validate();

        $server = $validatedData['server'];
        $profile = $validatedData['profile'];
        $prefijo = $validatedData['prefijo'];
        try {
            $this->cuentas = null;
            $client = $this->configRouter();

            $evento = Evento::where('prefijo', $validatedData['prefijo'])->first();
            $nro = $evento->nrotickets;
            
            for ($i = 0; $i < intval($validatedData['totalTicket']); $i++) {
                // Genera un nombre de usuario único (puedes ajustarlo)
                $nro++;
                // $username = $validatedData['prefijo'] . str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                $username = $validatedData['prefijo'] . $nro;
                
                // Genera la contraseña de 8 dígitos
                $password = $this->randomPassword();
                // Genera el serial de 8 dígitos
                $serial = $this->randomSerial();

                //dd($validatedData['server']);
                
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

                $monto = explode('/', $validatedData['profile'])[1];
                TicketUser::create([
                    'nroTicket' => 	$serial,
                    'user_id' => auth()->user()->id,
                    'userMikrotik_id' => $userMikrotik->id,
                    'server' =>	$server,
                    'user' => $username,
                    'password' => $password,
                    'profile' => $profile,
                    'prefijo' => $prefijo,
                    'monto' => $validatedData['monto'],
                    'nrorouter' => $this->router->nrorouter,
                ]);

                // asignar limit uptime
			    $this->defineUptimeLimit($userMikrotik, $mikrotik_id, $profile, $newUptimeLimit = "00:00:15");

                // Puedes manejar la respuesta si es necesario
                // Por ejemplo, registrar en la base de datos de Laravel si el usuario se creó correctamente
            }

            $evento->update(['nrotickets' => $evento->nrotickets + $validatedData['totalTicket'] ]);

            

            $tickets = TicketUser::where('server', $server)
                                ->Where('prefijo', $prefijo)
                                ->get();

            foreach ($tickets as $key => $ticket) {
                $users[] = ['nroTicket'  => $ticket->nroTicket, 'name' => $ticket->user, 'password' => $ticket->password, 'monto' => $ticket->monto, 'status' => $ticket->status, 'comment' => $ticket->comment];
            }
            
            $this->usershotspot = $this->selectUsershotspots($users, $server, $prefijo);

            //$ticketUser = TicketUser::where('prefijo', $prefijo)->get();

            // foreach ($ticketUser as $key => $value) {
            //     $this->cuentas[] = ['name' => $value->user, 'password' => $value->password];
            // }

            //$evento = Evento::where('prefijo', $validatedData['prefijo'])->first();
            
            //llamar a graficar qr
            $this->dispatchBrowserEvent('crear-qr', ['usershotspot' => $this->usershotspot]);

            //return 'Se han creado 10 usuarios de Hotspot con éxito.';


        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
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

            $userMikrotik->update(['limitUptime' => $newUptimeLimit]);
            
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

    public function selectUsershotspots($users, $hotspot, $prefijo)
    {
        $usershotspots = [];
        $aliado = auth()->user()->name;

        foreach ($users as $elementos) {            
            if(isset($elementos['comment']))
            {
                if($elementos['comment']=='activo' && $elementos['name']!=='default-trial'){
                        $status = true;
                        $comment = 'activo';
                }else{
                    $status = false;
                    $comment = 'noactivo';
                }
            }else{
                $status = false;
                $comment = 'noactivo';
            }
            if(isset($elementos['nroTicket'])){
                $nroTicket = $elementos['nroTicket'];
            }else{
                $nroTicket = '';
            }
            if(isset($elementos['monto'])){
                $monto = $elementos['monto'];
            }else{
                $monto = '';
            }
            if($elementos['name']!= 'default-trial'){
                if(array_key_exists('server', $elementos))
                {
                    if($elementos['server'] == $hotspot){
                        //buscar si la cadena existe en el array
                        if (str_contains($elementos['name'], $prefijo)) {                            
                            $usershotspots[] = ['aliado' => $aliado, 'name' => $elementos['name'], 'password' => $elementos['password'], 'comment' => $comment,  'status' => $status, 'nroTicket' => $nroTicket, 'monto' => $monto];
                        }
                        
                    }                
                }else{
                    if($prefijo == 'all'){
                        $usershotspots[] = ['aliado' => $aliado, 'name' => $elementos['name'], 'password' => (isset($elementos['password'])? $elementos['password'] : '' ), 'comment' => $comment, 'status' => $status, 'nroTicket' => $nroTicket, 'monto' => $monto];
                    }else{
                        if (str_contains($elementos['name'], $prefijo)) {                         
                            $usershotspots[] = ['aliado' => $aliado, 'name' => $elementos['name'], 'password' => (isset($elementos['password'])? $elementos['password'] : '' ), 'comment' => $comment, 'status' => $status, 'nroTicket' => $nroTicket, 'monto' => $monto];
                        }
                    }                    
                }        
            }        
        }    
        
        return $usershotspots;
    }
    /**Genera una contraseña de 8 dígitos con un dígito y un carácter especial.*/
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

    public function showUsersHotspot()
    {
        $messages = [
                    'required' => 'El campo :attribute es requerido.',
                    'name.max' => 'The name cannot exceed 255 characters.',
                ];

        $validatedData = Validator::make($this->state, [
            'server' => 'required',
            'prefijo' => 'required|not_in:0',
        ], $messages)->validate();

        $prefijo = $validatedData['prefijo'];
        $server = $validatedData['server'];
        
        $client = $this->configRouter();
        $users = $this->exeQuery($this->datos, '/ip/hotspot/user/print');

        // $query = (new Query('/ip/hotspot/user/print'));
            // ->where('server', 'all');

        // Enviar la consulta y leer la respuesta
        // $users = $client->query($query)->read();

        // Construye la consulta
        // $query = (new Query('/ip/hotspot/user/print'));
            // ->where('name', 'user'); // Busca usuarios cuyo nombre contenga 'josé'
        // $users = $client->query($query)->read();
        // dd($users);
        //$server = 'hotspot1';

        $this->usershotspot = $this->selectUsershotspots($users, $server, $prefijo);

        $this->dispatchBrowserEvent('crear-qr', ['usershotspot' => $this->usershotspot]);

        
    }
    
    public function showTickets()
    {
        $messages = [
                    'required' => 'El campo :attribute es requerido.',
                    'name.max' => 'The name cannot exceed 255 characters.',
                ];
        $validatedData = Validator::make($this->state, [
            'server' => 'nullable',
            'profile' => 'nullable',
            'prefijo' => 'nullable',
        ], $messages)->validate();
        
        $server = $validatedData['server'];
        $profile = $validatedData['profile'];
        $prefijo = $validatedData['prefijo'];
        $usershotspots = [];
        $users = [];
        $aliado = auth()->user()->name;

        $tickets = TicketUser::query(); // jpor 

        if($validatedData['server'] !== 'all'){
            $tickets = $tickets->where('server', $validatedData['server']);
        }
        if($validatedData['prefijo'] !== 'all'){
            $tickets = $tickets->where('prefijo', $validatedData['prefijo']);
        }
        if(auth()->user()->role !== 'admin'){
            $tickets = $tickets->where('user_id', auth()->user()->id);
        }
                           
        $tickets = $tickets->where(function($q){
                                    $q->where('server', $this->state['server'])
                                        ->orWhere('prefijo', $this->state['prefijo']);
                                })->get();
        foreach ($tickets as $key => $ticket) {
            $users[] = ['nroTicket'  => $ticket->nroTicket, 'name' => $ticket->user, 'password' => $ticket->password, 'monto' => $ticket->monto, 'status' => $ticket->status, 'comment' => $ticket->comment];
        }
        
        $this->usershotspot = $this->selectUsershotspots($users, $server, $prefijo);

        $this->dispatchBrowserEvent('crear-qr', ['usershotspot' => $this->usershotspot]);

    }

    public function changeComment($data)
    {
        try {
            $userMikrotik = UserMikrotik::where('name', $data['name'])->first();
            $client = $this->configRouter();
            
            $query = (new Query('/ip/hotspot/user/set'))
                ->equal('comment', $data['comment'])
                ->equal('.id', $userMikrotik->mikrotik_id);

            // Enviar la consulta y leer la respuesta
            $users = $client->query($query)->read();

            $ticketUser = TicketUser::where('user', $data['name'])->first();
            $ticketUser->update(['comment' => $data['comment']]);
            $this->dispatchBrowserEvent('hide-formHotspot', ['message' => 'Operación terminada con éxito.']);

        } catch (Exception $e) {
            $result = "Caught exception: " . $e->getMessage() . "\n";
            $this->dispatchBrowserEvent('hide-formHotspot', ['message' => $result]);
        } 
        
        return true;

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

    public function render()
    {
        try {
            
            
            if(auth()->user()->role !== 'admin'){
                $eventos = Evento::where('user_id', auth()->user()->id)->get();
            }else{
                $eventos = Evento::all();
            }        

            $hotspots = $this->exeQuery($this->datos, '/ip/hotspot/print');
            $this->nameshotspots = [];
            foreach ($hotspots as $elemento) {
                $this->nameshotspots[] = $elemento['name'];
            }

            //todos los profiles
            $profiles = $this->exeQuery($this->datos, '/ip/hotspot/user/profile/print');
            $this->namesprofiles = [];
            foreach ($profiles as $elemento) {
                if($elemento['name'] !== 'default'){
                    if($elemento['name'] !== 'PLANNEUTRO/0'){
                        if (!str_contains($elemento['name'], 'aliado')) {
                            $this->namesprofiles[] = $elemento['name'];
                        } 
                        
                    }
                }            
            }

            $this->ticketsUsers = TicketUser::where('user_id', auth()->user()->id)
                                    ->where(function($q){
                                        $q->where('server', $this->state['server'])
                                            ->orWhere('prefijo', $this->state['prefijo']);
                                    })->get();

            return view('livewire.mikrotik.hotspot.crear-ticket', [
                'eventos' => $eventos
            ]);

        } catch (Exception $e) {
        return view('livewire.error.show-error', [
            'error' => '501',
            'description' => $e,
        ]);
        }
    }

}
