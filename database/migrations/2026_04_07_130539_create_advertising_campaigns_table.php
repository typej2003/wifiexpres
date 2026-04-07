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
            $table->integer('age_min')->default(0);
            $table->integer('age_max')->default(100);
            
            // Contenido Multimedia
            $table->string('media_type'); // imagen, video
            $table->string('media_path');
            
            // La Pregunta
            $table->string('question');
            
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
