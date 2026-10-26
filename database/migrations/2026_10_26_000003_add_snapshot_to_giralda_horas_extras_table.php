<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('giralda_horas_extras', function (Blueprint $table) {
            $table->foreignId('empleado_hora_extra_tarifa_id')->nullable()
                ->constrained('empleado_hora_extra_tarifas', 'id', 'ghe_snapshot_tarifa_fk')->restrictOnDelete();
            $table->decimal('costo_hora_extra', 12, 4)->nullable();
            $table->decimal('importe', 14, 2)->nullable();
            $table->decimal('sueldo_base_snapshot', 14, 2)->nullable();
            $table->string('origen_tarifa', 20)->nullable();
            $table->string('snapshot_fuente', 40)->nullable();
            $table->timestamp('snapshot_at')->nullable();
            $table->foreignId('snapshot_por')->nullable()->constrained('users', 'id', 'ghe_snapshot_usuario_fk')->nullOnDelete();
            $table->index(['snapshot_at', 'id'], 'ghe_snapshot_pendientes_idx');
        });
    }

    public function down(): void
    {
        Schema::table('giralda_horas_extras', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('ghe_snapshot_tarifa_fk');
                $table->dropForeign('ghe_snapshot_usuario_fk');
            }
            $table->dropIndex('ghe_snapshot_pendientes_idx');
            $table->dropColumn([
                'empleado_hora_extra_tarifa_id', 'costo_hora_extra', 'importe', 'sueldo_base_snapshot',
                'origen_tarifa', 'snapshot_fuente', 'snapshot_at', 'snapshot_por',
            ]);
        });
    }
};
