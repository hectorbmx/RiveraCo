<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sat_factura_borradores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sat_empresa_id')->nullable()->constrained('sat_empresas')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('obra_id')->nullable()->constrained('obras')->nullOnDelete();
            $table->foreignId('obra_factura_borrador_id')->nullable()->constrained('obra_factura_borradores')->nullOnDelete();
            $table->foreignId('sat_factura_id')->nullable()->constrained('sat_facturas')->nullOnDelete();
            $table->string('titulo')->nullable();
            $table->json('payload');
            $table->string('estado', 30)->default('borrador')->index();
            $table->timestamps();

            $table->index(['user_id', 'estado']);
            $table->index(['cliente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_factura_borradores');
    }
};