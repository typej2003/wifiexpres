<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertisingCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advertising_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // El Aliado dueño
            $table->string('name');
            $table->text('description')->nullable();
            
            // Segmentación
            $table->string('target_gender')->default('todos'); // masculino, femenino, todos
            $table->foreignId('age_range_id')->nullable()->constrained('age_ranges')->onDelete('set null');
            
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
        Schema::dropIfExists('advertising_campaigns');
    }
}
