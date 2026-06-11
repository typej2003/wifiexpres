<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concurso_id')->constrained('advertising_concursos')->onDelete('cascade');
            $table->string('etapa');
            $table->json('results'); // Almacenará los ganadores, ej: {"A": "Brasil", "B": "Francia"}
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
        Schema::dropIfExists('event_results');
    }
}
