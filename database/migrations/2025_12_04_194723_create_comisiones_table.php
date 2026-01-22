<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('obra_id')
                  ->constrained('obras')
                  ->cascadeOnDelete();

            // pila_id empata con obras_pilas.id (BIGINT UNSIGNED)
            $table->unsignedBigInteger('pila_id');

            $table->date('fecha');

            // 🔧 residente_id SIN foreign key (solo guardamos el ID legacy)
            $table->unsignedInteger('residente_id')->nullable();

            $table->string('numero_formato', 50)->nullable();
            $table->string('cliente_nombre', 150)->nullable();
            $table->text('observaciones')->nullable();

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('updated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index(['obra_id', 'fecha']);
        });

        // 👇 Solo agregamos FK para pila_id (que sí controlamos)
        Schema::table('comisiones', function (Blueprint $table) {
            $table->foreign('pila_id')
                  ->references('id')
                  ->on('obras_pilas')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones');
    }
};
