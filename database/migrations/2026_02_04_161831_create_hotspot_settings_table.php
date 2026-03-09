<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotspotSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotspot_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('portal_name')->default('WIFI EXPRES');
            $table->boolean('has_shop')->default(false);
            $table->string('shop_name')->nullable();
            $table->string('shop_location')->nullable();
            $table->json('carousel_images')->nullable(); // Guardará las URLs de las fotos
            $table->boolean('show_carousel')->default(true);
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
        Schema::dropIfExists('hotspot_settings');
    }
}
