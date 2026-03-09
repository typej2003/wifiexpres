<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('site_name')->nullable();
            $table->string('site_email')->nullable();
            $table->string('site_title')->nullable();
            $table->string('currency', 5)->default('$');
            $table->string('api_bcv', 5)->default('NO');
            $table->decimal('dollar_rate', 12, 2)->default(36.00); // <--- Nueva columna

            $table->boolean('mikrotik_connection_mode')->default(1); 
            $table->boolean('sidebar_collapse')->default(0);
            $table->boolean('in_cellphonecontact')->default(0);
            $table->boolean('in_sliderprincipal')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};