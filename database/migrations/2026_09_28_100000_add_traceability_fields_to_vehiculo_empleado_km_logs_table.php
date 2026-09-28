<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculo_empleado_km_logs', function (Blueprint $table) {
            $table->foreignId('capturado_por_user_id')
                ->nullable()
                ->after('notas')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('origen', 20)
                ->nullable()
                ->after('capturado_por_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehiculo_empleado_km_logs', function (Blueprint $table) {
            $table->dropForeign(['capturado_por_user_id']);
            $table->dropColumn([
                'capturado_por_user_id',
                'origen',
            ]);
        });
    }
};