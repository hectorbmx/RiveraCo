<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->string('origen', 12)->default('excel');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('nombre_obra')->nullable();
            $table->string('equipo_propuesto', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('presupuestos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropColumn(['origen', 'nombre_obra', 'equipo_propuesto']);
        });
    }
};
