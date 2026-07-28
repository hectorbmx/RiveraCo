<?php

use App\Models\Obra;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehiculo_empleado_km_logs', function (Blueprint $table) {
            $table->foreignId('obra_id')
                ->nullable()
                ->after('vehiculo_empleado_id')
                ->constrained('obras')
                ->nullOnDelete();
            $table->string('foto_ticket_gasolina')->nullable()->after('foto');
            $table->decimal('monto_gasolina', 12, 2)->nullable()->after('foto_ticket_gasolina');

            $table->index(['obra_id', 'fecha']);
        });

        $logs = DB::table('vehiculo_empleado_km_logs as logs')
            ->join('vehiculo_empleado as asignaciones', 'asignaciones.id', '=', 'logs.vehiculo_empleado_id')
            ->whereNull('logs.obra_id')
            ->select([
                'logs.id',
                'logs.fecha',
                'asignaciones.empleado_id',
            ])
            ->orderBy('logs.id')
            ->get();

        foreach ($logs as $log) {
            $obraId = DB::table('obra_empleado as asignaciones_obra')
                ->join('obras', 'obras.id', '=', 'asignaciones_obra.obra_id')
                ->where('asignaciones_obra.empleado_id', $log->empleado_id)
                ->whereDate('asignaciones_obra.fecha_alta', '<=', $log->fecha)
                ->where(function ($query) use ($log) {
                    $query->whereNull('asignaciones_obra.fecha_baja')
                        ->orWhereDate('asignaciones_obra.fecha_baja', '>=', $log->fecha);
                })
                ->whereNotIn('obras.estatus_nuevo', [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA])
                ->orderByDesc('asignaciones_obra.fecha_alta')
                ->orderByDesc('asignaciones_obra.id')
                ->value('asignaciones_obra.obra_id');

            if ($obraId) {
                DB::table('vehiculo_empleado_km_logs')
                    ->where('id', $log->id)
                    ->update(['obra_id' => $obraId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('vehiculo_empleado_km_logs', function (Blueprint $table) {
            $table->dropForeign(['obra_id']);
            $table->dropIndex(['obra_id', 'fecha']);
            $table->dropColumn([
                'obra_id',
                'foto_ticket_gasolina',
                'monto_gasolina',
            ]);
        });
    }
};
