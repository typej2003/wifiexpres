<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Router;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Buscamos los routers configurados previamente
        $router01 = Router::where('identity', 'Router01')->first();
        $routerSencillo = Router::where('identity', 'RouterSencillo')->first();

        // Plan de 1 Hora por 1 Bs para Router01
        if ($router01) {
            Plan::create([
                'router_id'         => $router01->id,
                'name'              => '1 Hora-1', // Estructura Texto-Costo
                'mikrotik_profile'  => '1 Hora-1', // Estructura Texto-Costo
                'price'             => 1.00,
                
                // Parámetros técnicos MikroTik
                'session_timeout'   => '01:00:00',
                'idle_timeout'      => '00:05:00',
                'keepalive_timeout' => '00:02:00',
                'status_autorefresh'=> '00:01:00',
                'shared_users'      => 1,
                'rate_limit'        => '2M/2M',
                'is_active'         => true,
            ]);
        }

        // Plan de 1 Hora por 1 Bs para RouterSencillo
        if ($routerSencillo) {
            Plan::create([
                'router_id'         => $routerSencillo->id,
                'name'              => '1 Hora-1',
                'mikrotik_profile'  => '1 Hora-1',
                'price'             => 1.00,
                
                // Parámetros técnicos MikroTik
                'session_timeout'   => '01:00:00',
                'idle_timeout'      => '00:02:00',
                'keepalive_timeout' => '00:02:00',
                'status_autorefresh'=> '00:01:00',
                'shared_users'      => 1,
                'rate_limit'        => '1M/1M',
                'is_active'         => true,
            ]);
        }
    }
}