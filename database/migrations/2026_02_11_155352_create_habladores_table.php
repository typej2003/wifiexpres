<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHabladoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('habladors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id'); // El aliado
            $table->string('nombre');
            $table->enum('tipo', ['imagen', 'carrusel', 'video']);
            $table->json('recursos'); // Rutas de fotos/videos
            $table->string('audio_url')->nullable();
            $table->json('caracteristicas')->nullable(); // Precio, descripción, etc.
            $table->boolean('activo')->default(false);
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
        Schema::dropIfExists('habladors');
    }
}
