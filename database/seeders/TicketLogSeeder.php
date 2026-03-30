<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketLog;
use App\Models\Router;
use App\Models\UserMikrotik;
use Carbon\Carbon;

class TicketLogSeeder extends Seeder
{
    public function run()
    {
        $routers = Router::all();
        if ($routers->isEmpty()) {
            $this->command->error("No hay routers en la base de datos.");
            return;
        }

        // Limpiamos logs antiguos
        TicketLog::truncate();

        // 1. Creamos 50 Usuarios de prueba
        $users = [];
        $generos = ['F', 'M'];
        $nombresF = ['Maria', 'Ana', 'Carmen', 'Elena', 'Laura', 'Rosa'];
        $nombresM = ['Jose', 'Juan', 'Pedro', 'Luis', 'Carlos', 'Miguel'];

        $this->command->info("Creando 50 usuarios de prueba...");

        for ($i = 0; $i < 50; $i++) {
            $gender = $generos[array_rand($generos)];
            $firstName = ($gender == 'F') ? $nombresF[array_rand($nombresF)] : $nombresM[array_rand($nombresM)];
            
            $users[] = UserMikrotik::create([
                'router_id'   => $routers->random()->id,
                'name'        => 'user' . rand(1000, 9999) . $i, // Agregamos $i para evitar duplicados
                'full_name'   => $firstName . " " . "Prueba " . $i,
                'gender'      => $gender,
                'birthday'    => Carbon::now()->subYears(rand(15, 60))->format('Y-m-d'),
                'email'       => "prueba{$i}@ejemplo.com",
                'active'      => true,
            ]);
        }

        // 2. Generamos logs hasta el 29-03-2026 12:00:00
        $this->command->info("Generando logs hasta el 29 de Marzo a las 12:00 PM...");

        for ($i = 0; $i < 500; $i++) {
            $router = $routers->random();
            $user = collect($users)->random();
            $segmento = collect(['10', '20', '30'])->random();
            $ipSimulada = "192.168.{$segmento}." . rand(2, 254);

            // LÓGICA DE FECHA LIMITADA
            $dia = rand(1, 29);
            
            if ($dia === 29) {
                $hora = rand(0, 11); // Solo hasta las 11 AM para que al sumar minutos no pase de las 12
            } else {
                $hora = rand(0, 23);
            }
            
            $minuto = rand(0, 59);
            
            // Creamos la fecha y la formateamos explícitamente para evitar el error 1292
            $fechaLog = Carbon::create(2026, 3, $dia, $hora, $minuto, 0);
            $fechaString = $fechaLog->format('Y-m-d H:i:s');

            $duracion = rand(600, 3600); // 10 min a 1 hora (reducido para no saltar de día)
            $fechaDesconexion = $fechaLog->copy()->addSeconds($duracion)->format('Y-m-d H:i:s');

            TicketLog::create([
                'router_id'        => $router->id,
                'username'         => $user->name,
                'mac_address'      => $ipSimulada,
                'duration_seconds' => $duracion,
                'disconnected_at'  => $fechaDesconexion,
                'created_at'       => $fechaString,
                'updated_at'       => $fechaString,
            ]);
        }

        $this->command->info("¡Seeder completado con éxito!");
    }
}