<?php

namespace App\Http\Livewire\Mikrotik\User;

use Livewire\Component;
use App\Models\Router;

use Illuminate\Support\Facades\Validator;
use RouterOS\Client;
use RouterOS\Query;

class TimeOut extends Component
{
    public $router;

    public $state = [];

    public $showEditModal = false;

    public $namesProfilesUser = [];

    public $addressPool = [];

    public $user = '04165800403';

    public $password = '60048209';

    public function mount($nrorouter="R001")
    {   
        $this->router = Router::where('nrorouter', $nrorouter)->first();

        //$this->state['tiempo'] = '1d2h4m45s';

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

    public function procesar()
    {
        try {
            //todos los perfiles usuarios de los hotspot

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

            //$profilesUser = $this->exeQuery($datos, '/ip/hotspot/user/profile/print');

            $nameProfile = '1 minuto/10';

            $nameProfile = '1 mes';
            
            $query = (new Query('/ip/hotspot/user/profile/print'))
            ->where('name', $nameProfile);        
            $result = $client->query($query)->read();
            
            //dd($result[0]['session-timeout']);

            //$usersHotspot = $this->exeQuery($datos, '/ip/hotspot/user/print');        
            $nameProfile = '04165800403';
            
            $query = (new Query('/ip/hotspot/user/print'))
            ->where('name', $nameProfile);        
            $result1 = $client->query($query)->read();

//            dd($this->convertir_a_seg($result1[0]['uptime']));
            //dd($result[0]['session-timeout']);
            dd($this->convertir_a_seg($result[0]['session-timeout']));

            dd('session-timeout: '. $result[0]['session-timeout'] . ' uptime: ' . $result1[0]['uptime']);
            
            return view('livewire.mikrotik.user.time-out', ['profilesUser' => $profilesUser, 'profilesUser' => $usersHotspot]);

        } catch (Exception $e) {
            $result = "Caught exception: " . $e->getMessage() . "\n";
            return response()->json([
                'success' => false,
                'message' => $result,
            ]);
            dd($result);
        } 
    }

    public function convertir()
    {
        $validatedData = Validator::make($this->state, [
			'tiempo' => 'required',
		])->validate();

        $arreglo = [];

        $result = '';
        for ($i=0; $i < strlen($validatedData['tiempo']); $i++) { 
            $caracter = $validatedData['tiempo'][$i];
            if (is_numeric($caracter))
            {
                $result .= $caracter;
            }
            else{
                $result .= $caracter;
                $arreglo[] = $result;
                $result = '';
            }
        }

        $segundos_por_dia = 86400; // 24 * 60 * 60
        $total_segundos = 0;
        
        foreach ($arreglo as $key => $value) {
            if( str_contains($value, 'd') )
            {
                $dias = preg_replace('/[d]/', '', $value);
                $total_segundos += intval($dias) * 24*60*60;
            }
            if( str_contains($value, 'h') )
            {
                $horas = preg_replace('/[h]/', '', $value);
                $total_segundos += intval($horas) * 3600;
            }
            if( str_contains($value, 'm') )
            {
                $min = preg_replace('/[m]/', '', $value);
                $total_segundos += intval($min) * 60;
            }
            if( str_contains($value, 's') )
            {
                $seg = preg_replace('/[s]/', '', $value);
                $total_segundos += intval($seg);
            }

        }
        dd($total_segundos);
        
    }

    public function convertir_a_seg($tiempo)
    {        
        $arreglo = [];
        $result = '';
        for ($i=0; $i < strlen($tiempo); $i++) { 
            $caracter = $tiempo[$i];
            if (is_numeric($caracter))
            {
                $result .= $caracter;
            }
            else{
                $result .= $caracter;
                $arreglo[] = $result;
                $result = '';
            }
        }

        $segundos_por_dia = 86400; // 24 * 60 * 60
        $total_segundos = 0;
        
        foreach ($arreglo as $key => $value) {

            if( str_contains($value, 'w') )
            {
                $semanas = preg_replace('/[w]/', '', $value);
                $total_segundos += intval($semanas) * 7*24*60*60;

            }

            if( str_contains($value, 'd') )
            {
                $dias = preg_replace('/[d]/', '', $value);
                $total_segundos += intval($dias) * 24*60*60;
            }
            if( str_contains($value, 'h') )
            {
                $horas = preg_replace('/[h]/', '', $value);
                $total_segundos += intval($horas) * 3600;
            }
            if( str_contains($value, 'm') )
            {
                $min = preg_replace('/[m]/', '', $value);
                $total_segundos += intval($min) * 60;
            }
            if( str_contains($value, 's') )
            {
                $seg = preg_replace('/[s]/', '', $value);
                $total_segundos += intval($seg);
            }

        }
        return $total_segundos;
        
    }

    public function render()
    {
        $dominio = 'typej.ddns.net';
        $ip_del_dominio = gethostbyname($dominio);

        return view('livewire.mikrotik.user.time-out', [
            'dominio'  => $dominio,
            'ip_del_dominio'  => $ip_del_dominio,
        ]);

        
    }
}
