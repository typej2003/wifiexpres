<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained('routers')->onDelete('cascade');
            $table->string('username');
            $table->string('password');
            $table->string('identity')->nullable();
            $table->decimal('costo', 12,2)->default(0);
            $table->string('plan'); // Ej: "5MB", "Premium"
            $table->string('tiempo_uso')->default('00:00:00')->change();
            $table->boolean('activado')->default(false);
            $table->timestamp('fecha_uso')->nullable();
            $table->boolean('anulado')->default(false);
            $table->boolean('sincronizado')->default(false);
            $table->string('estado')->default('disponible'); // disponible, en_uso, agotado, anulado
            $table->string('tiempo_consumido')->nullable();
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
        Schema::dropIfExists('tickets');
    }
}
