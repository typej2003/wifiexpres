<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            // Relaciones
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Dueño del router/ganancia
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            
            // Atributos de la venta
            $table->string('type'); // 'ticket_fisico' o 'pasarela'
            $table->string('reference_id'); // ID del Ticket o de la transaccion PagoMovil
            $table->string('description'); // Ej: "Activación Ticket: XYZ" o "Pago Pasarela: Plan 1H"
            
            // Bimoneda
            $table->decimal('amount_usd', 15, 4)->default(0.00);
            $table->decimal('amount_bs', 15, 4)->default(0.00);
            $table->decimal('rate', 15, 4)->nullable(); // Tasa de cambio (BCV u otra) al momento
            
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
        Schema::dropIfExists('sales');
    }
}