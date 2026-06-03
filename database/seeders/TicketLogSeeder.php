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

        // 1. Creamos un pool de 400 Usuarios de prueba
        $users = [];
        $generos = ['F', 'M'];
        $nombresF = ['Maria', 'Ana', 'Carmen', 'Elena', 'Laura', 'Rosa'];
        $nombresM = ['Jose', 'Juan', 'Pedro', 'Luis', 'Carlos', 'Miguel'];
        $prefijosVzla = ['0412', '0414', '0416', '0424', '0426'];
        $segmentosIp = ['10.0.0.', '192.168.10.', '192.168.88.', '172.16.0.'];

        $this->command->info("Creando pool de 400 usuarios...");

        for ($i = 0; $i < 400; $i++) {
            $gender = $generos[array_rand($generos)];
            $firstName = ($gender == 'F') ? $nombresF[array_rand($nombresF)] : $nombresM[array_rand($nombresM)];
            
            // Generamos número de teléfono venezolano
            $phoneCode = $prefijosVzla[array_rand($prefijosVzla)];
            $phoneNumber = $phoneCode . rand(1000000, 9999999);
            
            // Importante: macaddress debe contener una IP para que funcione el filtro por Zona en HourAnalysis
            $fakeIp = $segmentosIp[array_rand($segmentosIp)] . rand(2, 254);

            $users[] = UserMikrotik::create([
                'router_id'   => $routers->random()->id,
                'name'        => $phoneNumber, // Nombre es el teléfono
                'full_name'   => $firstName . " " . "User " . $i,
                'gender'      => $gender,
                'birthday'    => Carbon::now()->subYears(rand(15, 60))->format('Y-m-d'),
                'email'       => "usuario_{$i}@wifiexpres.com",
                'macaddress'  => $fakeIp,
                'cellphonecode' => $phoneCode,
                'cellphone'   => substr($phoneNumber, 4),
                'active'      => true,
            ]);
        }

        // 2. Generamos logs para MAYO (80-100 usuarios por día)
        $this->command->info("Generando logs para Mayo (Promedio 90/día)...");
        for ($day = 1; $day <= 31; $day++) {
            $logsPerDay = rand(80, 100);
            for ($i = 0; $i < $logsPerDay; $i++) {
                $this->createRandomLog($users, $routers, 2026, 5, $day);
            }
        }

        // 3. Generamos logs para JUNIO (50-100 usuarios por día para el 1 y 2)
        $this->command->info("Generando logs para Junio 01 y 02 (50-100/día)...");
        for ($day = 1; $day <= 2; $day++) {
            $logsPerDay = rand(50, 100);
            for ($i = 0; $i < $logsPerDay; $i++) {
                $this->createRandomLog($users, $routers, 2026, 6, $day);
            }
        }

        $this->command->info("¡Seeder completado con éxito!");
    }

    /**
     * Helper para crear un log individual
     */
    private function createRandomLog($users, $routers, $year, $month, $day)
    {
        $user = $users[array_rand($users)];
        $router = $routers->random();
        
        $fechaLog = Carbon::create($year, $month, $day, rand(0, 23), rand(0, 59), rand(0, 59));
        $fechaString = $fechaLog->toDateTimeString();

        $duracion = rand(600, 3600); // Entre 10 min y 1 hora
        $fechaDesconexion = $fechaLog->copy()->addSeconds($duracion)->toDateTimeString();

        TicketLog::create([
            'router_id'        => $router->id,
            'username'         => $user->name,
            'mac_address'      => $user->macaddress,
            'duration_seconds' => $duracion,
            'disconnected_at'  => $fechaDesconexion,
            'created_at'       => $fechaString,
            'updated_at'       => $fechaString,
        ]);
    }
}