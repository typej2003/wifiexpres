<?php

namespace App\Http\Livewire\Mikrotik\Hotspot;

use App\Http\Livewire\Admin\AdminComponent;

use App\Models\Router;
use App\Models\UserMikrotik;
use App\Models\TicketUser;

use RouterOS\Client;
use RouterOS\Query;

class ListTicketsVendidos extends AdminComponent
{
    public $showEditModal = false;
    public $searchTerm = null;
    public $sortColumnName = 'created_at';
    public $sortDirection = 'desc';
    public $comment = 'all';
    public $router;
    public $desde;
    public $hasta;

    public function mount($nrorouter = 'R001')
    {        
        $this->router = Router::where('nrorouter', $nrorouter)->first();
        //todos los hotspots
        
    }

    public function updatedDesde($value)
    {
        $this->hasta = $value;
    }

    public function activar(TicketUser $ticket, $comment)
	{
		try {
            $userMikrotik = UserMikrotik::find($ticket->userMikrotik_id);

            $data = [
                'name' => $userMikrotik->name,
                'mikrotik_id' => $userMikrotik->mikrotik_id,
                'comment' => $comment,
            ];
            
            $userNew = $this->changeComment($data);

            if($userNew['status'] == true){
                switch ($comment) {
                    case 'activo':
                        $this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario activado satisfactoriamente!']);
                        break;
                    case 'noactivo':
                        $this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario desactivado satisfactoriamente!']);
                        break;
                    
                    case 'suspendido':
                        $this->dispatchBrowserEvent('hide-form', ['message' => 'Usuario suspendido satisfactoriamente!']);
                        break;
                    
                }
                
                return true;
            }else{
                $this->dispatchBrowserEvent('alert', 
                ['type' => 'error',  'message' => 'Usuario no fue activado!']);		
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
            'port' => 8728,
        ]);

        return $client;
    }

    public function changeComment($data)
    {
        try {
            
            $client = $this->configRouter();
            
            $query = (new Query('/ip/hotspot/user/set'))
                ->equal('comment', $data['comment'])
                ->equal('.id', $data['mikrotik_id']);

            // Enviar la consulta y leer la respuesta
            $users = $client->query($query)->read();

            $ticketUser = TicketUser::where('user', $data['name'])->first();
            $ticketUser->update(['comment' => $data['comment']]);
            $this->dispatchBrowserEvent('hide-formHotspot', ['message' => 'Operación terminada con éxito.']);
            return [
                'status' => 'true',
            ];

        } catch (Exception $e) {
            $result = "Caught exception: " . $e->getMessage() . "\n";
            $this->dispatchBrowserEvent('hide-formHotspot', ['message' => $result]);
            return [
                'status' => 'false',
            ];
        }         

    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function verQr($id)
	{
        $ticket = TicketUser::find($id);
		$this->showEditModal = false;

		$this->dispatchBrowserEvent('show-form');

        
        
        $users[] = ['nroTicket'  => $ticket->nroTicket, 'name' => $ticket->user, 'password' => $ticket->password, 'monto' => $ticket->monto, 'status' => $ticket->status, 'comment' => $ticket->comment];
            
        $this->usershotspot = $this->selectUsershotspots($users, $ticket->server, $ticket->prefijo);

        
        //llamar a graficar qr
        $this->dispatchBrowserEvent('crear-qr', ['usershotspot' => $this->usershotspot]);
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

    public function render()
    {
        $tickets = TicketUser::query();

        if($this->desde != null){         
            $tickets = $tickets->when($this->desde, function ($query) {
                    // Filtrar desde la fecha especificada (incluida)
                    $query->whereDate('created_at', '>=', $this->desde);
                })
                ->when($this->hasta, function ($query) {
                    // Filtrar hasta la fecha especificada (incluida)
                    $query->whereDate('created_at', '<=', $this->hasta);
                });
        }


        if($this->comment != 'all'){
            $tickets = $tickets->where('comment', $this->comment);
        }
        if(auth()->user()->role != 'admin'){
            $tickets = $tickets->where('user_id', auth()->user()->id);
        }

        $tickets = $tickets->where(function($q){
            $q->Where('user', 'like', '%'.$this->searchTerm.'%')
                ->orWhere('nroTicket', 'like', '%'.$this->searchTerm.'%');
                });

        $tickets = $tickets->paginate();

        return view('livewire.mikrotik.hotspot.list-tickets-vendidos', [
            'tickets' => $tickets,
        ]);
    }
}
