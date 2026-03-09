<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagomovilsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagomovils', function (Blueprint $table) {
            $table->id();
            $table->string('referencia');
            $table->string('telefono');
            $table->string('banco');
            $table->string('plan')->nullable();
            $table->string('user')->nullable();
            $table->string('identity')->nullable();            
            $table->date('fecha_pago')->nullable();
            $table->decimal('monto', 12,2);
            $table->string('status');
            $table->boolean('active')->default(true);
            $table->text('externalcomment')->nullable();
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
        Schema::dropIfExists('pagomovils');
    }
}
