<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserMikrotiksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_mikrotiks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('router_id')->nullable(); // Relación con routers
            $table->string('mikrotik_id')->nullable();
            $table->string('server')->nullable();
            $table->string('name')->nullable(); // Este es el Username (teléfono)
            $table->string('password')->nullable();
            
            // Campos de Lead (Portal)
            $table->string('full_name')->nullable(); // Nombre real del cliente
            $table->string('gender')->nullable();
            $table->date('birthday')->nullable();
            
            $table->string('address')->nullable();
            $table->string('macaddress')->nullable();
            $table->string('profile')->nullable();
            $table->string('routes')->nullable();
            $table->string('email')->nullable();
            $table->string('cellphonecode')->nullable();
            $table->string('cellphone')->nullable();            
            $table->boolean('active')->default(true);
            $table->string('limitUptime')->nullable();
            $table->string('limitBytesIn')->nullable();
            $table->string('limitBytesOut')->nullable();
            $table->string('limitBytesTotal')->nullable();
            $table->string('uptime')->nullable();
            $table->string('bytesIn')->nullable();
            $table->string('packetsIn')->nullable();
            $table->string('bytesOut')->nullable();
            $table->string('packetsOut')->nullable();
            $table->timestamps();

            // Index para búsquedas rápidas
            $table->index('name');
            $table->index('router_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_mikrotiks');
    }
}