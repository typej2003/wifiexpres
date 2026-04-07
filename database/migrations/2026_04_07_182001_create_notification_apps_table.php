<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationAppsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_apps', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('device_id')->nullable(); // Para saber qué teléfono envió la data
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
        Schema::dropIfExists('notification_apps');
    }
}
