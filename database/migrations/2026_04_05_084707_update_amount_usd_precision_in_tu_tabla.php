<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAmountUsdPrecisionInSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Cambiamos amount_usd a 4 decimales
            // Usamos 12, 4 para mantener un rango amplio de números
            $table->decimal('amount_usd', 12, 4)->default(0.0000)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            // Revertimos a la precisión original de tu migración anterior
            $table->decimal('amount_usd', 10, 2)->default(0.00)->change();
        });
    }
}