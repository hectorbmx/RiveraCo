<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nomina_corridas', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('tipo_pago', 20);              // semanal | quincenal
            $table->string('subtipo', 50)->nullable();    // normal | extra | aguinaldo | etc
            $table->string('periodo_label', 100)->nullable();

            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_pago')->nullable();

            $table->string('status', 20)->default('draft'); // draft | closed | paid
            $table->string('notas', 255)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['tipo_pago', 'fecha_inicio', 'fecha_fin']);
            $table->index('status');

            // Si tu tabla users es estándar (id BIGINT), puedes activar FKs:
            // $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('paid_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_corridas');
    }
};
