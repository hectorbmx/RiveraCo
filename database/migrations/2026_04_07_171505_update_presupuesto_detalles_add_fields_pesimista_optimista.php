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
        // Es necesario envolver las columnas en Schema::table
        Schema::table('presupuesto_detalles', function (Blueprint $table) {
            $table->decimal('importe_optimista', 15, 2)->nullable()->after('importe');
            $table->decimal('importe_pesimista', 15, 2)->nullable()->after('importe_optimista');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presupuesto_detalles', function (Blueprint $table) {
            $table->dropColumn(['importe_optimista', 'importe_pesimista']);
        });
    }
};