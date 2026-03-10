<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ejemplo: 1h-2
            $table->string('mikrotik_profile'); 
            $table->decimal('price', 8, 2)->default(0);
            
            // Parámetros técnicos del perfil MikroTik
            $table->string('session_timeout')->default('01:00:00');
            $table->string('idle_timeout')->default('none');
            $table->string('keepalive_timeout')->default('00:02:00');
            $table->string('status_autorefresh')->default('00:01:00');
            
            // Parámetros de Cookies (Nuevos)
            $table->boolean('add_mac_cookie')->default(true);
            $table->string('mac_cookie_timeout')->default('03:00:00');

            $table->integer('shared_users')->default(1);
            $table->string('rate_limit')->nullable(); // Ejemplo: 1M/1M
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plans');
    }
}