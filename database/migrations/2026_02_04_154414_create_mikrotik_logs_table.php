<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mikrotik_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->string('time_mikrotik'); 
            $table->string('category');      // success, danger, warning, info
            $table->string('type');          // Hotspot, Seguridad, Sistema
            $table->text('message');
            $table->string('hash')->unique(); // Evita duplicados exactos
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mikrotik_logs');
    }
};