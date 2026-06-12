<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePuntuacionConcursosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('puntuacion_concursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concurso_id')->constrained('advertising_concursos')->onDelete('cascade');
            $table->string('full_name')->nullable();
            $table->string('cellphonecode')->nullable();
            $table->string('cellphone')->nullable();
            $table->integer('puntaje')->default(0);
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
        Schema::dropIfExists('puntuacion_concursos');
    }
}
