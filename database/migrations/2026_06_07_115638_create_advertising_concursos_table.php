<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertisingConcursosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advertising_concursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // El Aliado dueño
            $table->string('router_identity')->nullable(); // Para asociar la campaña a un router específico
            $table->string('name');
            $table->text('description')->nullable();
            
            // Segmentación
            $table->string('target_gender')->default('todos'); // masculino, femenino, todos
            $table->unsignedBigInteger('age_range_id')->default(0);
            
            // Contenido Multimedia
            $table->string('media_type'); // imagen, video
            $table->string('media_path');
            
            // Estructura de la Encuesta
            $table->string('question_text');
            $table->enum('question_type', ['simple', 'multiple_choice', 'single_choice'])->default('simple');
            $table->json('options')->nullable(); // Guardará las opciones en caso de ser selección
            
            $table->boolean('active')->default(true);
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
        Schema::dropIfExists('advertising_concursos');
    }
}
