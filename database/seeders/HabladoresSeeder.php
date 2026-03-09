<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pantalla;
use App\Models\Hablador; // Modelo en singular
use Illuminate\Support\Facades\DB;

class HabladoresSeeder extends Seeder
{
    public function run()
    {
        // 1. Buscamos al usuario ID 6
        $aliado = User::find(6);

        if (!$aliado) {
            $this->command->error("No se encontró al usuario con ID 6.");
            return;
        }

        // 2. Crear/Actualizar Pantallas
        $pantallas = [
            ['nombre' => 'TV Barra Principal', 'slug' => 'barra-principal'],
            ['nombre' => 'TV Terraza Exterior', 'slug' => 'terraza-ext'],
            ['nombre' => 'Pantalla Entrada', 'slug' => 'entrada-local'],
        ];

        foreach ($pantallas as $p) {
            Pantalla::updateOrCreate(
                ['slug_pantalla' => $p['slug']],
                [
                    'user_id' => $aliado->id,
                    'nombre' => $p['nombre'],
                ]
            );
        }

        // 3. Crear Habladores de prueba
        $habladores = [
            [
                'nombre' => 'Promo Cerveza 2x1',
                'tipo' => 'imagen',
                'recursos' => ['https://images.unsplash.com/photo-1535958636474-b021ee887b13?q=80&w=1000&auto=format&fit=crop'],
            ],
            [
                'nombre' => 'Menú del Día - Hamburguesa',
                'tipo' => 'imagen',
                'recursos' => ['https://images.unsplash.com/photo-1568901346375-23c9450c58cd?q=80&w=1000&auto=format&fit=crop'],
            ],
            [
                'nombre' => 'Bienvenidos a Wifiexprés',
                'tipo' => 'imagen',
                'recursos' => ['https://via.placeholder.com/1920x1080/0056b3/FFFFFF?text=Bienvenidos+a+Wifiexpres'],
            ]
        ];

        foreach ($habladores as $h) {
            Hablador::updateOrCreate(
                ['nombre' => $h['nombre'], 'user_id' => $aliado->id],
                [
                    'tipo' => $h['tipo'],
                    'recursos' => $h['recursos'],
                    'audio_url' => null,
                    'activo' => false
                ]
            );
        }

        $this->command->info("Datos cargados correctamente para el Aliado 6 usando el modelo Hablador.");
    }
}