<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Comercio;
use App\Models\Transaccion;

class SearchAfiliado extends Controller
{
    public function __invoke(Request $request)
    {

        $existe = false;
        //->
        $peticion = explode('/', \Request::getRequestUri());

        if($peticion[1]=='pas'){

            $usuario = User::where('name', $peticion[1])->where('role', 'afiliado')->first();

            $comercio = Comercio::where('name', $peticion[2])->first();
            
            if($comercio){
                $existe = true;
            }

            return view('admin.search-afiliado', [
                'existe' => $existe,
                'comercio' => $comercio,
            ]);
        }
    }

    public function enviarData(Request $request){

        $comercioId = 0;

        $operacion = $request->get('datos');

        $comercioId = $operacion['comercio_id'];
        
        if($comercioId == 0)
            return response(['operacion'=>'false','comercio_id'=>$comercioId]);

        $comercio = Comercio::find($comercioId);
        $operacion['user_id'] = $comercio->user_id;

        if($operacion['codigo'])
            $operacion['banco'] = $this->buscarBanco($operacion['codigo']);
        
        $transaccion = Transaccion::create($operacion);
        
        return response($operacion);
    }

    public function buscarBanco($codigo) {
        $name = '';
        
        $bancos = array(
            [
                "name"=> "BANCO DE VENEZUELA",
                "codigo"=> "0102"
            ],
            [
                "name"=> "100% BANCO",
                "codigo"=> "0156"
            ],
            [
                "name"=> "BANCAMIGA BANCO MICROFINANCIERO CA",
                "codigo"=> "0172"
            ],
            [
                "name"=> "BANCARIBE",
                "codigo"=> "0114"
            ],
            [
                "name"=> "BANCO ACTIVO",
                "codigo"=> "0171"
            ],
            [
                "name"=> "BANCO AGRICOLA DE VENEZUELA",
                "codigo"=> "0166"
            ],
            [
                "name"=> "BANCO BICENTENARIO DEL PUEBLO",
                "codigo"=> "0175"
            ],
            [
                "name"=> "BANCO CARONI",
                "codigo"=> "0128"
            ],
            [
                "name"=> "BANCO DEL TESORO",
                "codigo"=> "0163"
            ],
            [
                "name"=> "BANCO EXTERIOR",
                "codigo"=> "0115"
            ],
            [
                "name"=> "BANCO FONDO COMUN",
                "codigo"=> "0151"
            ],
            [
                "name"=> "BANCO INTERNACIONAL DE DESARROLLO",
                "codigo"=> "0173"
            ],
            [
                "name"=> "BANCO MERCANTIL",
                "codigo"=> "0105"
            ],
            [
                "name"=> "BANCO NACIONAL DE CREDITO",
                "codigo"=> "0191"
            ],
            [
                "name"=> "BANCO PLAZA",
                "codigo"=> "0138"
            ],
            [
                "name"=> "BANCO SOFITASA",
                "codigo"=> "0137"
            ],
            [
                "name"=> "BANCO VENEZOLANO DE CREDITO",
                "codigo"=> "0104"
            ],
            [
                "name"=> "BANCRECER",
                "codigo"=> "0168"
            ],
            [
                "name"=> "BANESCO",
                "codigo"=> "0134"
            ],
            [
                "name"=> "BANFANB",
                "codigo"=> "0177"
            ],
            [
                "name"=> "BANGENTE",
                "codigo"=> "0146"
            ],
            [
                "name"=> "BANPLUS",
                "codigo"=> "0174"
            ],
            [
                "name"=> "BBVA PROVINCIAL",
                "codigo"=> "0108"
            ],
            [
                "name"=> "DELSUR BANCO UNIVERSAL",
                "codigo"=> "0157"
            ],
            [
                "name"=> "MI BANCO",
                "codigo"=> "0169"
            ],
            [
                "name"=> "N58 BANCO DIGITAL BANCO MICROFINANCIERO S A",
                "codigo"=> "0178"
            ]);

        
            
        //$banco = array_search($codigo, array_column($bancos, 'name'));

        $name = $this->searchName($codigo, 'codigo', $bancos);

        return $name;
    }

    function searchName($value, $key, $array) {
        foreach ($array as $k => $val) {
            if ($val[$key] == $value) {
                return $val['name'];
                //return $k;
            }
        }
        return null;
     }
    
}
