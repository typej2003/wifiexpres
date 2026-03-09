<?php

namespace App\Http\Livewire\Mikrotik\Hotspot;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\User;
use App\Models\UserMikrotik;
use App\Models\DatosBasicos;
use App\Models\Router;
use App\Models\TicketUser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use RouterOS\Client;
use RouterOS\Query;

class ListUsersAliados extends AdminComponent
{
	use WithFileUploads;

	public $state = [];

	public $user;

	public $showEditModal = false;

	public $userIdBeingRemoved = null;

	public $searchTerm = null;

    protected $queryString = ['searchTerm' => ['except' => '']];

	public $photo;

    public $sortColumnName = 'created_at';

    public $sortDirection = 'desc';

	public function changeRole(User $user, $role)
	{
		Validator::make(['role' => $role], [
			'role' => [
				'required',
				Rule::in(User::ROLE_ADMIN, User::ROLE_USER, User::ROLE_CLIENTE, User::ROLE_ALIADO),
			],
		])->validate();

		$user->update(['role' => $role]);

		$this->dispatchBrowserEvent('updated', ['message' => "Rol cambió a {$role} satisfactoriamente."]);
	}

	public function changeStatus(User $user, $status)
	{
		Validator::make(['status' => $status], [
			'status' => [
				'required',
				Rule::in(User::ACTIVO, User::SUSPENDIDO),
			],
		])->validate();

		if($status==='activo'){
			if( $this->activar($user) )
			{
				// $user->update(['status' => $status, 'active' => 1,]);
				$user->update(['status' => $status]);
				$this->dispatchBrowserEvent('updated', ['message' => "Status cambió a {$status} satisfactoriamente."]);

			}else{

				$this->dispatchBrowserEvent('alert', 
						['type' => 'error',  'message' => 'Usuario no fue activado!']);		
			}
			
		}else{
			// Modificar profile
			$userMikrotik = UserMikrotik::where('user_id', $user->id)->first();
			$this->router = Router::where('nrorouter', $user->nrorouter)->first();
			$client = $this->configRouter();
			$query = (new Query('/ip/hotspot/user/set'))
				->equal('.id', $userMikrotik->mikrotik_id)
				->equal('profile', 'PLANNEUTRO/0');

			$response = $client->query($query)->read();
			// $user->update(['status' => $status, 'active' => false]);
			$user->update(['status' => $status]);

			$this->dispatchBrowserEvent('updated', ['message' => "Status cambió a {$status} satisfactoriamente."]);
		}		

		
	}

	public function addNew()
	{
		$this->reset();

		$this->showEditModal = false;

		$this->dispatchBrowserEvent('show-form');
	}

	public function createUser()
	{
		$validatedData = Validator::make($this->state, [
			'name' => 'required',
			'email' => 'required|email|unique:users',
			'password' => 'required|confirmed',
			'role' => 'required',
		])->validate();

		$validatedData['password'] = bcrypt($validatedData['password']);

		if ($this->photo) {
			$validatedData['avatar'] = $this->photo->store('/', 'avatars');
		}

		User::create($validatedData);

		// session()->flash('message', 'User added successfully!');

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario agregado satisfactoriamente!']);
	}

	public function edit(User $user)
	{
		$this->reset();

		$this->showEditModal = true;

		$this->user = $user;

		$this->state = $user->toArray();

		$this->dispatchBrowserEvent('show-form');
	}

	public function updateUser()
	{
		$validatedData = Validator::make($this->state, [
			'name' => 'required',
			'email' => 'required|email|unique:users,email,'.$this->user->id,
			'password' => 'sometimes|confirmed',
			'role' => 'required',
			'identificationNac' => 'required',
			'identificationNumber' => 'required',
		])->validate();

		if(!empty($validatedData['password'])) {
			$validatedData['password'] = bcrypt($validatedData['password']);
		}

		if ($this->photo) {
			Storage::disk('avatars')->delete($this->user->avatar);
			$validatedData['avatar'] = $this->photo->store('/', 'avatars');
		}

		$this->user->update($validatedData);

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario actualizado satisfactoriamente!']);
	}

	public function confirmUserRemoval($userId)
	{
		$this->userIdBeingRemoved = $userId;

		$this->dispatchBrowserEvent('show-delete-modal');
	}

	public function deleteUser()
	{
		$user = User::findOrFail($this->userIdBeingRemoved);

		$user->delete();

		$this->dispatchBrowserEvent('hide-delete-modal', ['message' => 'Usuario eliminado satisfactoriamente!']);
	}

    public function sortBy($columnName)
    {
        if ($this->sortColumnName === $columnName) {
            $this->sortDirection = $this->swapSortDirection();
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortColumnName = $columnName;
    }

    public function swapSortDirection()
    {
        return $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function render()
    {

		$users = User::query();

		$users = $users->where('role', 'aliado');
    	$users = $users->where(function($q){
			$q->where('name', 'like', '%'.$this->searchTerm.'%')
			->orWhere('email', 'like', '%'.$this->searchTerm.'%');
		});
        $users = $users->orderBy($this->sortColumnName, $this->sortDirection)
            ->paginate(15);

        return view('livewire.mikrotik.hotspot.list-users-aliados', [
        	'users' => $users,
        ]);
    }

	public function activar(User $user)
	{
		try {
				
				$this->user = $user;

				$datosbasicos = DatosBasicos::where('user_id', $user->id)->first();

				if($datosbasicos){
					
					$userNew = $this->createUserHotspot($user->nrorouter, $datosbasicos->cellphonecode . $datosbasicos->cellphone, $user->profile, $user->password);

					if($userNew['status'] == true){
						$this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario activado satisfactoriamente!']);
						return true;
					}else{
						$this->dispatchBrowserEvent('alert', 
						['type' => 'error',  'message' => 'Usuario no fue activado!']);		
						return false;
					}			

				}else{
					$this->dispatchBrowserEvent('alert', 
						['type' => 'error',  'message' => 'El usuario no posee datos básicos!']);
					return false;
					
				}	
			
			
		} catch (Exception $e) {
			$this->dispatchBrowserEvent('alert', 
                    ['type' => 'error',  'message' => 'Ocurrio un error en la activación del usuario!']);
			return false;
		}        		

	}

	public function activarUser(User $user)
	{
		try {
			dd('activarUser');
			if($user->active){
				$this->user = $user;

				$datosbasicos = DatosBasicos::where('user_id', $user->id)->first();

				if($datosbasicos){

					$cellphone = $datosbasicos->cellphonecode . $datosbasicos->cellphone;

					$userNew = $this->createUserHotspot($user->nrorouter, $cellphone, $user->profile, $user->password);

					if($userNew['status'] == true){
						$this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario activado satisfactoriamente!']);
						return true;
					}else{
						$this->dispatchBrowserEvent('alert', 
						['type' => 'error',  'message' => 'Usuario no fue activado!']);		
						return false;
					}			

				}else{
					$this->dispatchBrowserEvent('alert', 
						['type' => 'error',  'message' => 'El usuario no posee datos básicos!']);
					return false;
					
				}	
			}else{

				$this->dispatchBrowserEvent('alert', 
							['type' => 'error',  'message' => 'El usuario se encuentra suspendido!']);
				return false;
			}
			
		} catch (Exception $e) {
			$this->dispatchBrowserEvent('alert', 
                    ['type' => 'error',  'message' => 'Ocurrio un error en la activación del usuario!']);
			return false;
		}        		

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
			'port' => $this->router->api_port,
        ]);

        return $client;
    }

	public function createUserHotspot($nrorouter, $user, $profile)
    {        
        try {
				
                $this->router = Router::where('nrorouter', $nrorouter)->first();

                $client = $this->configRouter();

                $userMikrotik = UserMikrotik::where('name', $user)->first();
                $server = 'all';

				$password = $this->user->password_mikrotik;

				if(!$userMikrotik)
                {
					
                    // Crear la consulta para añadir el usuario
                    $query = (new Query('/ip/hotspot/user/add'))
                        ->equal('server', $server)
                        ->equal('name', $user)
                        ->equal('password', $password)
						->equal('comment', 'noactivo')
                        ->equal('profile', $profile);
                    
                    // Ejecutar la consulta
                    $result = $client->query($query)->read();
					//dd('server: ' . $server . ' name: ' . $user .' password: ' . $password . ' comment: noactivo' . ' profile: ' . $profile);
					
                    // Tarea completada.
                    // buscar id
                    // $query = (new Query('/ip/hotspot/user/print'))
                    //     ->where('name', $user);
                    // $response = $client->query($query)->read();
                    $mikrotik_id = $this->searchId_mikrotik($client, $user);

                    $userMikrotik = UserMikrotik::create([
                        'mikrotik_id' => $mikrotik_id, 
                        'name'=>$user,
						'password'=>$password,
                        'server'=>$server,
                        'profile'=>$profile,
						'user_id'=>$this->user->id,
                    ]);

					$serial = $this->randomSerial();
					$monto = explode('/', $profile)[1];

					TicketUser::create([
						'nroTicket' => 	$serial,
						'user_id' => $this->user->id,
						'userMikrotik_id' => $userMikrotik->id,
						'server' =>	$server,
						'user' => $user,
						'password' => $password,
						'profile' => $profile,
						'prefijo' => '',
						'monto' => $monto,
						'nrorouter' => $this->user->nrorouter,
					]);
				
				    $this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario del Hotspot agregado satisfactoriamente!']);

                }else{
                    $newUser = [
                        'user' => $user,
                        'password' => $userMikrotik->password,
                        'status' => false,
                    ];
                    $userMikrotik->update(['profile'=>$profile]);
                    $mikrotik_id = $userMikrotik->mikrotik_id;
    
                    
                    //si se borro del mikrotik pero esta en bd
					if(!$this->searchId_mikrotik($client, $user)){
						
						//crear en mikrotik
						// Crear la consulta para añadir el usuario
						$query = (new Query('/ip/hotspot/user/add'))
							->equal('server', $server)
							->equal('name', $user)
							->equal('password', $password)
							->equal('comment', 'noactivo')
							->equal('profile', $profile);
						
						// Ejecutar la consulta
						$result = $client->query($query)->read();

						// Tarea completada.
						// buscar id
						// $query = (new Query('/ip/hotspot/user/print'))
						//     ->where('name', $user);
						// $response = $client->query($query)->read();
						$mikrotik_id = $this->searchId_mikrotik($client, $user);
						
						$userMikrotik = UserMikrotik::where('user_id', $this->user->id)->first();
						$userMikrotik->update(['mikrotik_id' => $mikrotik_id]);

					}

                    // Modificar profile
                    $query = (new Query('/ip/hotspot/user/set'))
                        ->equal('.id', $mikrotik_id)
                        ->equal('profile', $profile);

                    $response = $client->query($query)->read();

                    $this->cleanUptime($mikrotik_id, $newUptime = "00:00:00");
                    
                }

                // asignar limit uptime
			    $this->defineUptimeLimit($userMikrotik, $mikrotik_id, $profile, $newUptimeLimit = "00:00:15");

                //Enviar sms con el user y la contraseña
                //$this->sendSms($user, $password);

                $this->dispatchBrowserEvent('hide-form', ['message' => 'Limit Uptime del Usuario cambiado satisfactoriamente!']);
				
				return [
                        'user' => $user,
                        'password' => $userMikrotik->password,
                        'status' => true,
                    ];

            } catch (Exception $e) {

                $this->dispatchBrowserEvent('alert', 
                    ['type' => 'error',  'message' => "Caught exception: " . $e->getMessage() . "\n"]);

				return [
                        'user' => $user,
                        'password' => $userMikrotik->password,
                        'status' => false,
                    ];

                
            } 

		//$validatedData['password'] = bcrypt($validatedData['password']);
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
	
}
