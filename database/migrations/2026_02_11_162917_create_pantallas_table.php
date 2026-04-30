<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePantallasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Migration: create_pantallas_table
        Schema::create('pantallas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // El Aliado
            $table->string('nombre'); // Ej: "Pantalla Barra", "Pantalla Terraza"
            $table->string('slug_pantalla')->unique(); // Identificador único para la URL
            $table->bigInteger('hablador_id')->nullable();
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
        Schema::dropIfExists('pantallas');
    }
}
