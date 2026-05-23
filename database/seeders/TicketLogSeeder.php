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

        // 1. Creamos 20 Usuarios de prueba con datos ficticios para Mayo 2026
        $users = [];
        $generos = ['F', 'M'];
        $nombresF = ['Maria', 'Ana', 'Carmen', 'Elena', 'Laura', 'Rosa'];
        $nombresM = ['Jose', 'Juan', 'Pedro', 'Luis', 'Carlos', 'Miguel'];
        $prefijosVzla = ['0412', '0414', '0416', '0424', '0426'];

        $this->command->info("Creando 20 usuarios con teléfonos de Venezuela...");

        for ($i = 0; $i < 20; $i++) {
            $gender = $generos[array_rand($generos)];
            $firstName = ($gender == 'F') ? $nombresF[array_rand($nombresF)] : $nombresM[array_rand($nombresM)];
            
            // Generamos número de teléfono venezolano y MAC ficticia
            $phoneCode = $prefijosVzla[array_rand($prefijosVzla)];
            $phoneNumber = $phoneCode . rand(1000000, 9999999);
            $macBase = sprintf('%02X:%02X:%02X:%02X:%02X:%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));

            $users[] = UserMikrotik::create([
                'router_id'   => $routers->random()->id,
                'name'        => $phoneNumber, // Nombre es el teléfono
                'full_name'   => $firstName . " " . "Prueba " . $i,
                'gender'      => $gender,
                'birthday'    => Carbon::now()->subYears(rand(15, 60))->format('Y-m-d'),
                'email'       => "usuario_mayo{$i}@wifiexpres.com",
                'macaddress'  => "T-" . $macBase,
                'cellphonecode' => $phoneCode,
                'cellphone'   => substr($phoneNumber, 4),
                'active'      => true,
            ]);
        }

        // 2. Generamos 20 logs desde el 01/05/2026 hasta el 23/05/2026 06:00 AM
        $this->command->info("Generando 20 logs para el periodo de Mayo...");

        $startDate = Carbon::create(2026, 5, 1, 0, 0, 0);
        $endDate = Carbon::create(2026, 5, 23, 6, 0, 0);
        $secondsDiff = $startDate->diffInSeconds($endDate);

        for ($i = 0; $i < 20; $i++) {
            $user = $users[$i]; // Usamos cada usuario creado
            $router = Router::find($user->router_id) ?: $routers->random();
            
            // Fecha aleatoria dentro del rango solicitado
            $fechaLog = $startDate->copy()->addSeconds(rand(0, $secondsDiff));
            $fechaString = $fechaLog->toDateTimeString(); // Evita error 1292

            $duracion = rand(900, 7200); // Entre 15 min y 2 horas
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

        $this->command->info("¡Seeder completado con éxito!");
    }
}