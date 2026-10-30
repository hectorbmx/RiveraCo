<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maquina_movimientos', function (Blueprint $table) {
            $table->string('solicitud_hash', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('maquina_movimientos', function (Blueprint $table) {
            $table->dropUnique(['solicitud_hash']);
            $table->dropColumn('solicitud_hash');
        });
    }
};
