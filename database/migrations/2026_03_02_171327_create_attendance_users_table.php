<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_attendance_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendance_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_device_id')->constrained('attendance_devices')->cascadeOnDelete();

            $table->unsignedInteger('device_uid');      // uid interno del reloj
            $table->unsignedInteger('enroll_id');       // userid del reloj (ej 629)
            $table->string('name', 150)->nullable();
            $table->string('cardno', 32)->nullable();

            $table->timestamps();

            $table->unique(['attendance_device_id','enroll_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('attendance_users');
    }
};