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
            
            // Referencia explícita a la tabla advertising_campaigns
            $table->foreignId('campaign_id')
                ->constrained('advertising_campaigns') 
                ->onDelete('cascade');

            // Referencia al modelo UserMikrotik que definimos (tabla user_mikrotiks)
            $table->foreignId('user_mikrotik_id')
                ->constrained('user_mikrotiks')
                ->onDelete('cascade');

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
