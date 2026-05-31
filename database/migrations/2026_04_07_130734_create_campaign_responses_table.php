<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_responses', function (Blueprint $table) {
            $table->id();
            
            // Referencia a la campaña (Nullable para persistencia al borrar origen)
            $table->foreignId('campaign_id')->nullable()
                ->constrained('advertising_campaigns')
                ->onDelete('set null');

            // Campos duplicados de la campaña (Denormalización para histórico)
            $table->string('campaign_name')->nullable();
            $table->text('campaign_description')->nullable();
            $table->string('campaign_target_gender')->nullable();
            $table->unsignedBigInteger('campaign_age_range_id')->nullable();
            $table->string('campaign_media_type')->nullable();
            $table->string('campaign_media_path')->nullable();
            $table->string('campaign_question_text')->nullable();
            $table->string('campaign_question_type')->nullable();
            $table->json('campaign_options')->nullable();

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
        Schema::dropIfExists('campaign_responses');
    }
}
