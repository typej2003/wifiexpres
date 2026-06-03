<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            CarruselSeeder::class,
            HotspotVersionSeeder::class,
            RouterSeeder::class,
            PlanSeeder::class,
            PackageSeeder::class,
            HabladoresSeeder::class,
            //TicketLogSeeder::class,
        ]);
    }
}
