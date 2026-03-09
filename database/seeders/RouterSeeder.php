<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Router;
use App\Models\Package;

class RouterSeeder extends Seeder
{
    public function run()
    {
        // Obtenemos planes de ejemplo para asignar
        $planPro = Package::where('name', 'like', '%Pro%')->first();
        $planStandard = Package::where('name', 'like', '%Estándar%')->first();

        // Router 1: Configuración de Negocio
        Router::create([
            'user_id'            => 6,
            'package_id'         => $planPro->id ?? null, // Asignado
            'ip'                 => '192.168.88.1',
            'macAddress'         => '04:F4:1C:5C:7A:34',
            'dns'                => 'hjy0an3fz8s.sn.mynetname.net',
            'api_port'           => '49153',
            'identity'           => 'Router01',
            'admin'              => 'userapi',
            'password'           => 'userapi123',
            'location'           => 'Cumana, Estado Sucre',
            'is_active'          => true,
            'status'             => 'Habilitado',
            'hotspot_version_id' => 1,
            'comercio_nombre'    => 'WIFI EXPRES',
            'comercio_logo'      => 'logos/logo_chamo.png',
            'comercio_banner'    => 'banners/banner_principal_chamo.jpg',
            'hotspot_url'        => 'http://wifiexpres.hotspot',
            'is_store'           => true,
            'store'              => 'Inversiones El Chamo C.A.',
            'address'            => 'Av. Universidad, C.C. San Vicente, PB, Local 4. Cumaná, Sucre.',
            'is_promotion'       => true,
            'path_imgs'          => 'promociones/chamo/' 
        ]);

        // Router 2: Configuración Estándar
        Router::create([
            'user_id'            => 6,
            'package_id'         => $planStandard->id ?? null, // Asignado
            'ip'                 => '192.168.10.1',
            'macAddress'         => '48:A9:8A:91:CD:AE',
            'dns'                => 'he908z92z4h.sn.mynetname.net',
            'api_port'           => '49152',
            'identity'           => 'RouterCangrejoAzul',
            'admin'              => 'userapi',
            'password'           => 'userapi123',
            'location'           => 'Cumaná, Playa San Luis',
            'is_active'          => true,
            'status'             => 'Habilitado',
            'hotspot_version_id' => 1,
            'comercio_nombre'    => 'Cangrejo Azul',
            'hotspot_url'        => 'http://hotspot.bendicion',
            'is_store'           => true,
            'is_trial'           => true,
            'store'              => 'Cangrejo Azul',
            'address'            => 'A 200 mts de Cangrejo Azul. Cumaná.',
            'is_promotion'       => false,
        ]);

        // Router 2: Configuración Estándar
        Router::create([
            'user_id'            => 6,
            'package_id'         => $planStandard->id ?? null, // Asignado
            'ip'                 => '192.168.10.1',
            'macAddress'         => '48:A9:8A:91:CD:AE',
            'dns'                => 'he908z92z4h.sn.mynetname.net',
            'api_port'           => '49152',
            'identity'           => 'MikrotikLaVega',
            'admin'              => 'userapi',
            'password'           => 'userapi123',
            'location'           => 'Caracas, La Vega',
            'is_active'          => true,
            'status'             => 'Habilitado',
            'hotspot_version_id' => 1,
            'comercio_nombre'    => 'Comercio La Vega',
            'hotspot_url'        => 'http://hotspot.bendicion',
            'is_store'           => true,
            'is_trial'           => true,
            'store'              => 'Comercio La Vega',
            'address'            => 'A 200 mts de las casistas. Caracas.',
            'is_promotion'       => false,
        ]);
    }
}