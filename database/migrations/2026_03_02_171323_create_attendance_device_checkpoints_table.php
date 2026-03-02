<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_attendance_device_checkpoints_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendance_device_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_device_id')->constrained('attendance_devices')->cascadeOnDelete();
            $table->dateTime('last_timestamp')->nullable(); // hasta dónde ya sincronizamos
            $table->timestamps();

            $table->unique('attendance_device_id');
        });
    }
    public function down(): void {
        Schema::dropIfExists('attendance_device_checkpoints');
    }
};