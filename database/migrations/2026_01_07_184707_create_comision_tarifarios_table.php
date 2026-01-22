<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comision_tarifarios', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();

            // borrador | vigente | archivado
            $table->string('estado', 20)->default('borrador')->index();

            $table->timestamp('vigente_desde')->nullable();
            $table->timestamp('vigente_hasta')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('published_by')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Si quieres FKs a users:
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comision_tarifarios');
    }
};
