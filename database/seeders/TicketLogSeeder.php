<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketLog;
use App\Models\Router;
use App\Models\UserMikrotik;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketLogSeeder extends Seeder
{
    public function run()
    {
        $routers = Router::all();
        if ($routers->isEmpty()) {
            $this->command->error("No hay routers en la base de datos. Ejecuta primero RouterSeeder.");
            return;
        }

        // Limpiamos logs antiguos para que la prueba de marzo sea exacta
        TicketLog::truncate();

        // 1. Creamos 50 Usuarios de prueba en UserMikrotik
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
                'name'        => 'user' . rand(1000, 9999),
                'full_name'   => $firstName . " " . "Prueba " . $i,
                'gender'      => $gender,
                // Fechas de nacimiento para cubrir los rangos (15 a 60 años)
                'birthday'    => Carbon::now()->subYears(rand(15, 60))->subMonths(rand(1, 12))->format('Y-m-d'),
                'email'       => "prueba{$i}@ejemplo.com",
                'active'      => true,
            ]);
        }

        // 2. Generamos 500 Logs para el mes de Marzo 2026
        $this->command->info("Generando 500 logs para Marzo 2026...");

        for ($i = 0; $i < 500; $i++) {
            $router = $routers->random();
            $user = collect($users)->random();
            
            // Segmentos IP definidos en AntennaMappingSeeder (Zonas)
            $segmento = collect(['10', '20', '30'])->random();
            $ipSimulada = "192.168.{$segmento}." . rand(2, 254);

            // Generar fecha aleatoria entre 01-03-2026 y 30-03-2026
            $dia = rand(1, 30);
            $hora = rand(0, 23);
            $minuto = rand(0, 59);
            $fechaLog = Carbon::create(2026, 3, $dia, $hora, $minuto, 0);

            $duracion = rand(600, 10800); // Entre 10 min y 3 horas

            TicketLog::create([
                'router_id'        => $router->id,
                'username'         => $user->name, // Se vincula con UserMikrotik->name
                'mac_address'      => $ipSimulada, // Usamos mac_address para la IP del cliente
                'duration_seconds' => $duracion,
                'disconnected_at'  => $fechaLog->copy()->addSeconds($duracion),
                'created_at'       => $fechaLog,
                'updated_at'       => $fechaLog,
            ]);
        }

        $this->command->info("¡Seeder completado con éxito!");
    }
}