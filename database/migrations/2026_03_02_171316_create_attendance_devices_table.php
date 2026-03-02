<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_attendance_devices_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('ip', 45);             // IPv4/IPv6
            $table->unsignedInteger('port')->default(4370);
            $table->string('serial', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['ip','port']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('attendance_devices');
    }
};