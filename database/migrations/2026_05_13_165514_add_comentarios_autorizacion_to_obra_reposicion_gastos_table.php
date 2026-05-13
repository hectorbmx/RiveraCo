<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_reposicion_gastos', function (Blueprint $table) {
            $table->text('comentarios_autorizacion')
                ->nullable()
                ->after('aprobado_at');
        });
    }

    public function down(): void
    {
        Schema::table('obra_reposicion_gastos', function (Blueprint $table) {
            $table->dropColumn('comentarios_autorizacion');
        });
    }
};