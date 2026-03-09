<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagesTable extends Migration
{
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotspot_version_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('service_type')->default('cortesia'); 
            
            $table->decimal('commission_aliado', 5, 2)->default(70.00);
            $table->decimal('commission_system', 5, 2)->default(30.00);

            $table->decimal('cost', 10, 2)->default(0.00); 
            $table->integer('duration_months')->default(1);
            $table->integer('limit_routers')->default(1);
            $table->boolean('is_offer')->default(false);
            $table->decimal('offer_cost', 10, 2)->nullable();
            
            $table->boolean('is_active')->default(true);
            // NUEVO CAMPO: Para mostrar en el Dashboard del Aliado
            $table->boolean('is_visible')->default(true); 
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('packages');
    }
}