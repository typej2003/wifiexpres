<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutersTable extends Migration
{
    public function up()
    {
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Relación con el plan (NUEVO)
            $table->foreignId('package_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('identity');
            $table->string('ip');
            $table->string('macAddress')->nullable();
            $table->string('dns')->nullable();
            $table->integer('api_port')->default(8728);
            $table->string('admin');
            $table->string('password');
            $table->string('location')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->longText('login_source')->nullable();
            $table->string('status')->default('Habilitado');
            $table->foreignId('hotspot_version_id')->nullable()->constrained('hotspot_versions')->onDelete('set null');

            $table->string('comercio_nombre')->default('WIFI EXPRES');
            $table->string('comercio_logo')->nullable();
            $table->string('comercio_banner')->nullable();
            $table->string('hotspot_url')->nullable();
            
            $table->boolean('is_store')->default(false);
            $table->string('store')->nullable(); 
            $table->text('address')->nullable(); 
            
            $table->boolean('is_trial')->default(false);

            $table->boolean('is_promotion')->default(false);
            $table->string('path_imgs')->nullable(); 

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('routers');
    }
}