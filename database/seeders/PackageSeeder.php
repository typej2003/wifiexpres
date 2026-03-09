<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;
use App\Models\HotspotVersion;

class PackageSeeder extends Seeder
{
    public function run()
    {
        // Buscamos las versiones creadas en el sistema
        $vAutonoma = HotspotVersion::where('name', 'Autónomo V1')->first();
        $vTarificada = HotspotVersion::where('name', 'Tarificado V2')->first();
        $vCortesia = HotspotVersion::where('name', 'Cortesía V3')->first();

        $packages = [
            // --- MODELO CORTESÍA (Ideal para Restaurantes/Hoteles que dan WiFi Gratis) ---
            [
                'name' => 'Cortesía Emprendedor',
                'description' => 'Perfecto para un local pequeño. Gestión de 1 router con portal cautivo de bienvenida gratuita.',
                'hotspot_version_id' => $vCortesia->id ?? null,
                'service_type' => 'cortesia',
                'cost' => 25.00,
                'duration_months' => 1,
                'limit_routers' => 1, // Límite estricto 1
                'is_offer' => false,
                'offer_cost' => null,
                'commission_aliado' => 0,
                'commission_system' => 0,
                'is_active' => true,
                'is_visible' => true,
            ],
            [
                'name' => 'Cortesía Corporativo (Pack 5)',
                'description' => 'Para franquicias o negocios con varias áreas. Permite conectar hasta 5 routers bajo una misma membresía.',
                'hotspot_version_id' => $vCortesia->id ?? null,
                'service_type' => 'cortesia',
                'cost' => 100.00,
                'duration_months' => 1,
                'limit_routers' => 5, // Límite para 5 nodos
                'is_offer' => true,
                'offer_cost' => 85.00,
                'commission_aliado' => 0,
                'commission_system' => 0,
                'is_active' => true,
                'is_visible' => true,
            ],

            // --- MODELO REPARTO (V2 - Venta de Tickets/Fichas) ---
            [
                'name' => 'Reparto Social 70/30',
                'description' => 'Plan de entrada para venta de internet. El aliado se queda con el 70% de las ventas. Soporta 2 routers.',
                'hotspot_version_id' => $vTarificada->id ?? null,
                'service_type' => 'reparto',
                'cost' => 15.00, 
                'duration_months' => 1,
                'limit_routers' => 2, // Límite para 2 nodos
                'is_offer' => false,
                'offer_cost' => null,
                'commission_aliado' => 70.00,
                'commission_system' => 30.00,
                'is_active' => true,
                'is_visible' => true,
            ],
            [
                'name' => 'Reparto WISP Pro 85/15',
                'description' => 'Plan avanzado para distribuidores. Gran margen de ganancia (85%) y capacidad para 10 routers.',
                'hotspot_version_id' => $vTarificada->id ?? null,
                'service_type' => 'reparto',
                'cost' => 40.00,
                'duration_months' => 1,
                'limit_routers' => 10, // Límite para 10 nodos
                'is_offer' => true,
                'offer_cost' => 30.00,
                'commission_aliado' => 85.00,
                'commission_system' => 15.00,
                'is_active' => true,
                'is_visible' => true,
            ],

            // --- MODELO AUTÓNOMO (V1 - El aliado tiene su propio recaudo) ---
            [
                'name' => 'Autónomo Global V1',
                'description' => 'Uso exclusivo de plataforma sin comisiones por venta. El aliado gestiona todo el recaudo. Soporta 3 routers.',
                'hotspot_version_id' => $vAutonoma->id ?? null,
                'service_type' => 'reparto', // Se marca reparto pero con 100% aliado
                'cost' => 60.00,
                'duration_months' => 1,
                'limit_routers' => 3, // Límite para 3 nodos
                'is_offer' => false,
                'offer_cost' => null,
                'commission_aliado' => 100.00,
                'commission_system' => 0,
                'is_active' => true,
                'is_visible' => true,
            ],
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(['name' => $package['name']], $package);
        }
    }
}