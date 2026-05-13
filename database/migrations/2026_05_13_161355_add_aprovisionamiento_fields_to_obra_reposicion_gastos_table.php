<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obra_reposicion_gastos', function (Blueprint $table) {

            $table->foreignId('cuenta_banco_empresa_id')
                ->nullable()
                ->after('comentarios_revision')
                ->constrained('cuentas_banco_empresa');

            $table->foreignId('metodo_pago_empresa_id')
                ->nullable()
                ->after('cuenta_banco_empresa_id')
                ->constrained('metodos_pago_empresa');

            $table->date('fecha_salida_programada')
                ->nullable()
                ->after('metodo_pago_empresa_id');

            $table->text('comentarios_aprovisionamiento')
                ->nullable()
                ->after('fecha_salida_programada');

            $table->foreignId('aprovisionado_por')
                ->nullable()
                ->after('comentarios_aprovisionamiento')
                ->constrained('users');

            $table->timestamp('aprovisionado_at')
                ->nullable()
                ->after('aprovisionado_por');

        });
    }

    public function down(): void
    {
        Schema::table('obra_reposicion_gastos', function (Blueprint $table) {

            $table->dropForeign(['cuenta_banco_empresa_id']);
            $table->dropForeign(['metodo_pago_empresa_id']);
            $table->dropForeign(['aprovisionado_por']);

            $table->dropColumn([
                'cuenta_banco_empresa_id',
                'metodo_pago_empresa_id',
                'fecha_salida_programada',
                'comentarios_aprovisionamiento',
                'aprovisionado_por',
                'aprovisionado_at',
            ]);
        });
    }
};