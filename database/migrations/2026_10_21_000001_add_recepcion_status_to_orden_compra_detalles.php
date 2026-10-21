<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orden_compra_detalles')) {
            return;
        }

        if (! Schema::hasColumn('orden_compra_detalles', 'estado_recepcion')) {
            Schema::table('orden_compra_detalles', function (Blueprint $table) {
                $table->string('estado_recepcion', 20)
                    ->default('pendiente')
                    ->after('cantidad_recibida')
                    ->index('oc_detalle_estado_recepcion_idx');
            });
        }

        $detalles = DB::table('orden_compra_detalles')
            ->select(['id', 'orden_compra_id', 'cantidad', 'cantidad_recibida'])
            ->get();

        foreach ($detalles as $detalle) {
            $cantidad = (float) ($detalle->cantidad ?? 0);
            $recibida = (float) ($detalle->cantidad_recibida ?? 0);
            $estado = $recibida <= 0
                ? 'pendiente'
                : ($recibida + 0.0005 >= $cantidad ? 'recibida' : 'parcial');

            DB::table('orden_compra_detalles')
                ->where('id', $detalle->id)
                ->update(['estado_recepcion' => $estado]);
        }

        if (Schema::hasTable('ordenes_compra') && Schema::hasColumn('ordenes_compra', 'estado_recepcion')) {
            $ordenes = DB::table('orden_compra_detalles')
                ->select(['orden_compra_id', 'cantidad', 'cantidad_recibida'])
                ->get()
                ->groupBy('orden_compra_id');

            foreach ($ordenes as $ordenCompraId => $partidas) {
                $ordenada = (float) $partidas->sum(fn ($partida) => (float) ($partida->cantidad ?? 0));
                $recibida = (float) $partidas->sum(fn ($partida) => (float) ($partida->cantidad_recibida ?? 0));
                $estado = $recibida <= 0
                    ? 'pendiente'
                    : ($recibida + 0.0005 >= $ordenada ? 'recibida' : 'parcial');

                DB::table('ordenes_compra')
                    ->where('id', $ordenCompraId)
                    ->update(['estado_recepcion' => $estado]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('orden_compra_detalles') || ! Schema::hasColumn('orden_compra_detalles', 'estado_recepcion')) {
            return;
        }

        Schema::table('orden_compra_detalles', function (Blueprint $table) {
            $table->dropIndex('oc_detalle_estado_recepcion_idx');
            $table->dropColumn('estado_recepcion');
        });
    }
};
