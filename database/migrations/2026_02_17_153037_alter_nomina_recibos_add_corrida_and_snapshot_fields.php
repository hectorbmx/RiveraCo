<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nomina_recibos', function (Blueprint $table) {
            // Corrida
            if (!Schema::hasColumn('nomina_recibos', 'corrida_id')) {
                $table->unsignedBigInteger('corrida_id')->nullable()->after('id');
                $table->index('corrida_id');
            }

            // Snapshots
            if (!Schema::hasColumn('nomina_recibos', 'sueldo_imss_snapshot')) {
                $table->decimal('sueldo_imss_snapshot', 12, 2)->default(0)->after('fecha_pago');
            }
            if (!Schema::hasColumn('nomina_recibos', 'complemento_snapshot')) {
                $table->decimal('complemento_snapshot', 12, 2)->default(0)->after('sueldo_imss_snapshot');
            }
            if (!Schema::hasColumn('nomina_recibos', 'infonavit_snapshot')) {
                $table->decimal('infonavit_snapshot', 12, 2)->default(0)->after('complemento_snapshot');
            }

            // Campos de captura “limpios” (si hoy tienes *_legacy, puedes mantener ambos)
            if (!Schema::hasColumn('nomina_recibos', 'descuentos')) {
                $table->decimal('descuentos', 12, 2)->default(0)->after('faltas');
            }
            if (!Schema::hasColumn('nomina_recibos', 'comisiones_lock')) {
                $table->tinyInteger('comisiones_lock')->default(0)->after('comisiones_monto');
            }
            if (!Schema::hasColumn('nomina_recibos', 'comisiones_cargadas_at')) {
                $table->timestamp('comisiones_cargadas_at')->nullable()->after('comisiones_lock');
            }
            if (!Schema::hasColumn('nomina_recibos', 'comisiones_cargadas_by')) {
                $table->unsignedBigInteger('comisiones_cargadas_by')->nullable()->after('comisiones_cargadas_at');
            }

            // Campo opcional informativo (si lo quieres guardar como cantidad)
            if (!Schema::hasColumn('nomina_recibos', 'metros_lineales')) {
                $table->decimal('metros_lineales', 12, 2)->default(0)->after('horas_extra');
            }

            // Unique por corrida+empleado
            // OJO: empleado_id ya existe en tu tabla según screenshot.
            // Si tu tabla ya tiene un unique similar, esto fallará: revisa antes.
        });

        // Agregar unique fuera del closure para poder checar existencia manualmente (Laravel no tiene hasIndex nativo)
        // Si tu tabla está vacía, puedes correrlo directo; si no, asegúrate que no haya duplicados.
        Schema::table('nomina_recibos', function (Blueprint $table) {
            // Esto creará índice unique con nombre explícito (para poder bajarlo fácil)
            $table->unique(['corrida_id', 'empleado_id'], 'nomina_recibos_corrida_empleado_unique');
        });

        // FK opcional a nomina_corridas (recomendado)
        Schema::table('nomina_recibos', function (Blueprint $table) {
            // Si quieres FK estricta:
            // $table->foreign('corrida_id')->references('id')->on('nomina_corridas')->cascadeOnDelete();

            // Si prefieres mantenerlo flexible por ahora, déjalo sin FK.
        });
    }

    public function down(): void
    {
        // Primero quitar FK/unique si existiera
        Schema::table('nomina_recibos', function (Blueprint $table) {
            // Si activaste FK, descomenta:
            // try { $table->dropForeign(['corrida_id']); } catch (\Throwable $e) {}

            try { $table->dropUnique('nomina_recibos_corrida_empleado_unique'); } catch (\Throwable $e) {}
        });

        Schema::table('nomina_recibos', function (Blueprint $table) {
            $cols = [
                'corrida_id',
                'sueldo_imss_snapshot',
                'complemento_snapshot',
                'infonavit_snapshot',
                'descuentos',
                'metros_lineales',
                'comisiones_lock',
                'comisiones_cargadas_at',
                'comisiones_cargadas_by',
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('nomina_recibos', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
