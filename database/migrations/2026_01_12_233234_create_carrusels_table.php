<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarruselsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carrusels', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('bannerside')->default(1);
            // d=desktop, t=tablet, m=mobile
            $table->char('device', 1)->default('d');
            $table->string('title')->nullable();
            $table->string('avatar')->nullable();
            $table->integer('order')->nullable();
            $table->string('active')->nullable();
            $table->string('responsive')->nullable();
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
        Schema::dropIfExists('carrusels');
    }
}
