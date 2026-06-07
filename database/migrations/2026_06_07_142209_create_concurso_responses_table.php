<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConcursoResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('concurso_responses', function (Blueprint $table) {
            $table->id();
            // Referencia a la concurso (Nullable para persistencia al borrar origen)
            $table->foreignId('concurso_id')->nullable()
                ->constrained('advertising_concursos')
                ->onDelete('set null');

            // Campos duplicados de la concurso (Denormalización para histórico)
            $table->string('concurso_name')->nullable();
            $table->string('concurso_etapa')->nullable();
            $table->text('concurso_description')->nullable();
            $table->string('concurso_target_gender')->nullable();
            $table->unsignedBigInteger('concurso_age_range_id')->nullable();
            $table->string('concurso_media_type')->nullable();
            $table->string('concurso_media_path')->nullable();
            $table->string('concurso_question_text')->nullable();
            $table->string('concurso_question_type')->nullable();
            $table->json('concurso_options')->nullable();

            // Referencia al modelo UserMikrotik que definimos (tabla user_mikrotiks)
            $table->foreignId('user_mikrotik_id')
                ->constrained('user_mikrotiks')
                ->onDelete('cascade');
            
            $table->string('mac_address')->nullable();
            $table->string('router_identity')->nullable();

            // Guardamos la respuesta abierta o el ID/Texto de la opción seleccionada
            $table->text('answer');
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
        Schema::dropIfExists('concurso_responses');
    }
}
