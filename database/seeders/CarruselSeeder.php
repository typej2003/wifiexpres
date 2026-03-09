<?php

namespace Database\Seeders;

use App\Models\Carrusel;
use Illuminate\Database\Seeder;

class CarruselSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Definimos la cantidad de banners que tienes por cada tipo
        $totalBanners = 6;

        for ($i = 1; $i <= $totalBanners; $i++) {
            $suffix = str_pad($i, 2, '0', STR_PAD_LEFT); // Convierte 1 en 01, 2 en 02, etc.

            // 1. MODO ESCRITORIO (d)
            Carrusel::create([
                'title'      => "Banner Desktop $suffix",
                'avatar'     => "WIFIEXPRES_banner_$suffix.jpg",
                'order'      => $i,
                'active'     => 'active',
                'bannerside' => 1,
                'device'     => 'd',
            ]);

            // 2. MODO TABLET (t)
            Carrusel::create([
                'title'      => "Banner Tablet $suffix",
                'avatar'     => "WIFIEXPRES_banner_1536_h_$suffix.jpg",
                'order'      => $i,
                'active'     => 'active',
                'bannerside' => 1,
                'device'     => 't',
            ]);

            // 3. MODO MÓVIL (m)
            Carrusel::create([
                'title'      => "Banner Movil $suffix",
                'avatar'     => "WIFIEXPRES_banner_1536_v_$suffix.jpg",
                'order'      => $i,
                'active'     => 'active',
                'bannerside' => 1,
                'device'     => 'm',
            ]);
        }
    }
}