<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDuracionToTicketLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ticket_logs', function (Blueprint $table) {
            $table->timestamp('disconnected_at')->nullable();
            $table->integer('duration_seconds')->nullable(); // Guardamos segundos para fácil reporte
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ticket_logs', function (Blueprint $table) {
            //
        });
    }
}
