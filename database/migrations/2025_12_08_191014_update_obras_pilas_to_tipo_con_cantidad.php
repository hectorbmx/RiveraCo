<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obras_pilas', function (Blueprint $table) {
            // 👇 Solo agregamos el nuevo campo
            $table->unsignedSmallInteger('cantidad_programada')
                  ->default(1)
                  ->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('obras_pilas', function (Blueprint $table) {
            $table->dropColumn('cantidad_programada');
        });
    }
};
