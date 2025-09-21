<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_id')->unique();
            $table->string('health_center_id');
            $table->string('service_name');
            $table->text('description');
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('schedule')->nullable();
            $table->timestamps();
            
            $table->foreign('health_center_id')->references('health_center_id')->on('health_centers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
