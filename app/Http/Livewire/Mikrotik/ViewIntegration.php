<?php

namespace App\Http\Livewire\Mikrotik;

use Livewire\Component;

use RouterOS\Client;
use RouterOS\Query;

use App\Models\Router;

class ViewIntegration extends Component
{
    public $usuarios;
    public $response = null;
    public $identity = null;

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

    public function showIntegracion($host='typej.ddns.net', $user = 'admin', $pass= 'admin123')
    {
        try {
            //prueba 1 typej.ddns.net
            $dominio = 'typej.ddns.net';
            $host = gethostbyname($dominio);

            $client = $this->configRouter();

            $query = new Query('/system/identity/getall');

            $identity = $client->query($query)->read();

            $this->identity = $identity[0]['name'];

            $query = new Query('/system/resource/print');

            $response = $client->query($query)->read();
            // La variable $response contendrá un array con la información de los usuarios

            $claves = array_keys($response[0]);

            //dd($response[0]);
            $texto = '';

            foreach ($response[0] as $key => $elemento) {
                $texto .= $key .': '. $elemento . '<br>'; //PHP_EOL;
            }

            $this->response = $texto;

        } catch (Throwable $th) {
            //throw $th;
            dd('Error: ' . $th);
            //transmision
        }        

    }

    public function verIdUser($host='typej.ddns.net', $user = 'admin', $pass= 'admin123')
    {
        $client = $this->configRouter();

        try {
            
            $query = (new Query('/ip/hotspot/user/print'))
                ->where('name', '04165800403');


            $response = $client->query($query)->read();

            dd($response[0]['.id']);
            

        } catch (\Exception $e) {
            dd('Operación fallida');
        }     

    }

    public function render()
    {
        $dominio = 'typej.ddns.net';
        $ip_del_dominio = gethostbyname($dominio);

        return view('livewire.mikrotik.view-integration', [
            'dominio'  => $dominio,
            'ip_del_dominio'  => $ip_del_dominio,
        ]);
    }
}
