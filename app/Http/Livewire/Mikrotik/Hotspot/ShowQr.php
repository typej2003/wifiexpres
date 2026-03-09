<?php

namespace App\Http\Livewire\Mikrotik\Hotspot;

use RouterOS\Client;
use RouterOS\Query;

use Livewire\Component;
use App\Models\Router;
use App\Models\TicketUser;
use App\Models\UserMikrotik;

class ShowQr extends Component
{
    public $ticket;
    public $router;

    protected $listeners = ['changeComment' => 'changeComment', 'verQr' => 'verQr'];

    public function mount($ticket_id)
    {
        $this->ticket_id = $ticket_id;
        // $this->ticket = Ticket::find($ticket_id);
    }

    public function verQr()
	{
        
        $ticket = TicketUser::find($this->ticket_id);
        $this->router = Router::where('nrorouter', $ticket->nrorouter)->first();
		$this->showEditModal = false;        
        
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

            $this->verQr();

        } catch (Exception $e) {
            $result = "Caught exception: " . $e->getMessage() . "\n";
            $this->dispatchBrowserEvent('hide-formHotspot', ['message' => $result]);
        } 
        
        return true;

    }

    public function render()
    {

        return view('livewire.mikrotik.hotspot.show-qr');
    }
}
