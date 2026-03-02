<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_attendance_logs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_device_id')->constrained('attendance_devices')->cascadeOnDelete();

            $table->unsignedInteger('device_uid');   // attendance uid
            $table->unsignedInteger('enroll_id');    // attendance id (userid)
            $table->tinyInteger('state')->nullable();
            $table->tinyInteger('type')->nullable();
            $table->dateTime('checked_at');

            $table->timestamps();

            // anti-duplicados (muy importante)
            $table->unique(['attendance_device_id','enroll_id','checked_at','type','state'], 'attendance_logs_uniq');
            $table->index(['attendance_device_id','checked_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('attendance_logs');
    }
};