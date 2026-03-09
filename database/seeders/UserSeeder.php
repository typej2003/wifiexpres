<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'identificationNac' => 'V',
            'identificationNumber' => '12966576',
            'name' => 'alex',
            'names' => 'Alexander',
            'surnames' => 'Diaz',
            'email' => 'ddrsistemas@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => 'root',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('personal_information')->insert([
            'user_id' => 1,
            'cellphonecode' => '0414',
            'cellphone' => '1899016',
            'msgcontact'  => 'Hola, te asesoramos por  whatsapp gestiona tu compra por este canal.',
            'address' => 'Caracas, San Bernardino',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('users')->insert([
            'identificationNac' => 'V',
            'identificationNumber' => '123456789',
            'name' => 'admin',
            'names' => 'admin',
            'surnames' => 'ADMIN',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('personal_information')->insert([
            'user_id' => 1,
            'cellphonecode' => '0416',
            'cellphone' => '5800403',
            'address' => 'Caracas, San Bernardino',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('users')->insert([
            'identificationNac' => 'V',
            'identificationNumber' => '22222222',
            'name' => 'lidernegocio',
            'names' => 'lidernegocio',
            'surnames' => 'LIDERNEGOCIO',
            'email' => 'lidernegocio@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => 'lidernegocio',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('personal_information')->insert([
            'user_id' => 1,
            'cellphonecode' => '0416',
            'cellphone' => '5800403',
            'address' => 'Caracas, San Bernardino',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);
        
        DB::table('users')->insert([
            'identificationNac' => 'V',
            'identificationNumber' => '33333333',
            'name' => 'vendedor',
            'names' => 'vendedor',
            'surnames' => 'VENDEDOR',
            'email' => 'vendedor@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => 'vendedor',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('personal_information')->insert([
            'user_id' => 1,
            'cellphonecode' => '0416',
            'cellphone' => '5800403',
            'address' => 'Caracas, San Bernardino',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('users')->insert([
            'identificationNac' => 'V',
            'identificationNumber' => '44444444',
            'name' => 'cliente',
            'names' => 'cliente',
            'surnames' => 'CLIENTE',
            'email' => 'cliente@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => 'cliente',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('personal_information')->insert([
            'user_id' => 1,
            'cellphonecode' => '0416',
            'cellphone' => '5800403',
            'address' => 'Caracas, San Bernardino',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('users')->insert([
            'identificationNac' => 'V',
            'identificationNumber' => '11222333',
            'name' => 'Pepe',
            'names' => 'jose',
            'surnames' => 'Perez',
            'email' => 'aliado@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => 'aliado',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

        DB::table('personal_information')->insert([
            'user_id' => 1,
            'cellphonecode' => '0416',
            'cellphone' => '5800403',
            'address' => 'Caracas, San Bernardino',
            'created_at' => '2022-05-16 12:20:36',
            'updated_at' => '2022-05-16 12:20:36'
        ]);

    }
}
