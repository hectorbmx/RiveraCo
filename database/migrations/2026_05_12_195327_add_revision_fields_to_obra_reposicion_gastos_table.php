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
        Schema::table('obra_reposicion_gastos', function (Blueprint $table) {

            $table->date('fecha_programada_pago')
                ->nullable()
                ->after('revisado_at');

            $table->text('comentarios_revision')
                ->nullable()
                ->after('fecha_programada_pago');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obra_reposicion_gastos', function (Blueprint $table) {
            //
        });
    }
};
