<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgeRangesTable extends Migration
{
    public function up()
    {
        Schema::create('age_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: "Jóvenes", "Adultos", "Tercera Edad"
            $table->integer('min_age');
            $table->integer('max_age');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('age_ranges');
    }
}