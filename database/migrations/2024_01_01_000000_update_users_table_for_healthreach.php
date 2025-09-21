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
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_id')->unique()->after('id');
            $table->enum('role', ['patient', 'health_worker', 'admin'])->default('patient')->after('email');
            $table->string('contact_number')->nullable()->after('role');
            $table->text('address')->nullable()->after('contact_number');
            $table->string('fcm_token')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'role', 'contact_number', 'address', 'fcm_token']);
        });
    }
};
