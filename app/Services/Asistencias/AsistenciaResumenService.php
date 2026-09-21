<?php

namespace App\Services\Asistencias;

use App\Models\ObraAsistencia;
use App\Models\ObraEmpleado;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AsistenciaResumenService
{
    private const TZ = 'America/Mexico_City';

    public function resumenSemanalEmpleado(int $empleadoId, CarbonInterface|string|null $weekStart = null, ?int $obraId = null): array
    {
        $inicio = $this->inicioSemana($weekStart);
        $fin = $inicio->copy()->endOfWeek(Carbon::SUNDAY);

        $asistencias = $this->asistenciasQuery($inicio, $fin)
            ->where('empleado_id', $empleadoId)
            ->when($obraId, fn ($query) => $query->where('obra_id', $obraId))
            ->with('registradoPor:id,name')
            ->orderBy('checked_at')
            ->get();

        $porDia = $asistencias->groupBy(fn (ObraAsistencia $asistencia) => $asistencia->checked_date?->toDateString());

        return [
            'empleado_id' => $empleadoId,
            'obra_id' => $obraId,
            'week_start' => $inicio->toDateString(),
            'week_end' => $fin->toDateString(),
            'promedio_entrada' => $this->promedioEntrada($asistencias),
            'days' => $this->diasSemana($inicio)
                ->map(fn (Carbon $dia) => $this->mapDiaEmpleado($dia, $porDia->get($dia->toDateString(), collect())))
                ->values(),
        ];
    }

    public function resumenDiarioObra(int $obraId, CarbonInterface|string|null $date = null, bool $incluirResidentes = false): array
    {
        $fecha = $this->fechaLocal($date);

        $asignaciones = ObraEmpleado::query()
            ->with([
                'empleado:id_Empleado,Nombre,Apellidos,Puesto',
                'rol:id,rol_key,nombre',
            ])
            ->where('obra_id', $obraId)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->orderBy('id')
            ->get()
            ->filter(fn (ObraEmpleado $asignacion) => $incluirResidentes || ! $this->esResidente($asignacion))
            ->values();

        $empleadoIds = $asignaciones
            ->pluck('empleado_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $asistencias = ObraAsistencia::query()
            ->where('obra_id', $obraId)
            ->whereDate('checked_date', $fecha->toDateString())
            ->whereIn('empleado_id', $empleadoIds)
            ->with('registradoPor:id,name')
            ->orderBy('checked_at')
            ->get()
            ->groupBy('empleado_id');

        $empleados = $asignaciones
            ->map(fn (ObraEmpleado $asignacion) => $this->mapEmpleadoObraDia(
                $asignacion,
                $asistencias->get($asignacion->empleado_id, collect())
            ))
            ->values();

        $total = $empleados->count();
        $conEntrada = $empleados->whereNotNull('entrada')->count();
        $conFoto = $empleados->where('status', 'completa')->count();
        $sinEntrada = $empleados->where('status', 'sin_entrada')->count();
        $entradaSinFoto = $empleados->where('status', 'entrada_sin_foto')->count();

        return [
            'obra_id' => $obraId,
            'date' => $fecha->toDateString(),
            'incluir_residentes' => $incluirResidentes,
            'completa' => $total > 0 && $conFoto === $total,
            'resumen' => [
                'esperados' => $total,
                'con_entrada' => $conEntrada,
                'con_entrada_y_foto' => $conFoto,
                'sin_entrada' => $sinEntrada,
                'entrada_sin_foto' => $entradaSinFoto,
            ],
            'empleados' => $empleados,
            'faltantes' => $empleados
                ->filter(fn (array $empleado) => $empleado['status'] !== 'completa')
                ->values(),
        ];
    }

    public function promedioEntradaEmpleado(
        int $empleadoId,
        CarbonInterface|string|null $desde = null,
        CarbonInterface|string|null $hasta = null,
        ?int $obraId = null
    ): ?array {
        $inicio = $desde ? $this->fechaLocal($desde) : now(self::TZ)->startOfMonth();
        $fin = $hasta ? $this->fechaLocal($hasta) : now(self::TZ);

        $asistencias = $this->asistenciasQuery($inicio, $fin)
            ->where('empleado_id', $empleadoId)
            ->where('tipo', 'entrada')
            ->when($obraId, fn ($query) => $query->where('obra_id', $obraId))
            ->get();

        return $this->promedioEntrada($asistencias);
    }

    private function asistenciasQuery(Carbon $inicio, Carbon $fin)
    {
        return ObraAsistencia::query()
            ->whereDate('checked_date', '>=', $inicio->toDateString())
            ->whereDate('checked_date', '<=', $fin->toDateString());
    }

    private function mapDiaEmpleado(Carbon $dia, Collection $asistencias): array
    {
        $entrada = $asistencias->firstWhere('tipo', 'entrada');
        $salida = $asistencias->firstWhere('tipo', 'salida');
        $status = 'sin_entrada';

        if ($entrada && $entrada->photo_path) {
            $status = 'completa';
        } elseif ($entrada) {
            $status = 'entrada_sin_foto';
        }

        return [
            'date' => $dia->toDateString(),
            'label' => $this->labelDia($dia),
            'is_today' => $dia->isSameDay(now(self::TZ)),
            'status' => $status,
            'entrada' => $this->mapAsistencia($entrada),
            'salida' => $this->mapAsistencia($salida),
        ];
    }

    private function mapEmpleadoObraDia(ObraEmpleado $asignacion, Collection $asistencias): array
    {
        $entrada = $asistencias->firstWhere('tipo', 'entrada');
        $salida = $asistencias->firstWhere('tipo', 'salida');
        $status = 'sin_entrada';

        if ($entrada && $entrada->photo_path) {
            $status = 'completa';
        } elseif ($entrada) {
            $status = 'entrada_sin_foto';
        }

        return [
            'obra_empleado_id' => (int) $asignacion->id,
            'empleado_id' => (int) $asignacion->empleado_id,
            'empleado_nombre' => $this->nombreEmpleado($asignacion),
            'rol_id' => $asignacion->rol_id ? (int) $asignacion->rol_id : null,
            'rol_nombre' => $asignacion->rol?->nombre,
            'status' => $status,
            'entrada' => $this->mapAsistencia($entrada),
            'salida' => $this->mapAsistencia($salida),
        ];
    }

    private function mapAsistencia(?ObraAsistencia $asistencia): ?array
    {
        if (! $asistencia) {
            return null;
        }

        $checkedAt = $asistencia->checked_at?->copy()->timezone(self::TZ);

        return [
            'id' => (int) $asistencia->id,
            'tipo' => $asistencia->tipo,
            'checked_date' => $asistencia->checked_date?->toDateString(),
            'checked_at' => $checkedAt?->toDateTimeString(),
            'hora' => $checkedAt?->format('H:i'),
            'tiene_foto' => (bool) $asistencia->photo_path,
            'photo_path' => $asistencia->photo_path,
            'ubicacion_texto' => $asistencia->ubicacion_texto,
            'registrado_por' => $asistencia->registradoPor?->name,
        ];
    }

    private function promedioEntrada(Collection $asistencias): ?array
    {
        $entradas = $asistencias
            ->filter(fn (ObraAsistencia $asistencia) => $asistencia->tipo === 'entrada' && $asistencia->checked_at)
            ->values();

        if ($entradas->isEmpty()) {
            return null;
        }

        $minutos = (int) round($entradas->avg(function (ObraAsistencia $asistencia) {
            $local = $asistencia->checked_at->copy()->timezone(self::TZ);

            return ($local->hour * 60) + $local->minute;
        }));

        return [
            'hora' => sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60),
            'minutos_desde_medianoche' => $minutos,
            'muestras' => $entradas->count(),
        ];
    }

    private function diasSemana(Carbon $inicio): Collection
    {
        return collect(range(0, 6))
            ->map(fn (int $offset) => $inicio->copy()->addDays($offset));
    }

    private function inicioSemana(CarbonInterface|string|null $weekStart): Carbon
    {
        return $this->fechaLocal($weekStart)->startOfWeek(Carbon::MONDAY);
    }

    private function fechaLocal(CarbonInterface|string|null $date): Carbon
    {
        if ($date instanceof CarbonInterface) {
            return Carbon::instance($date)->timezone(self::TZ)->startOfDay();
        }

        return $date
            ? Carbon::parse($date, self::TZ)->startOfDay()
            : now(self::TZ)->startOfDay();
    }

    private function labelDia(Carbon $dia): string
    {
        return match ((int) $dia->dayOfWeekIso) {
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mie',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sab',
            7 => 'Dom',
        };
    }

    private function nombreEmpleado(ObraEmpleado $asignacion): ?string
    {
        $empleado = $asignacion->empleado;

        if (! $empleado) {
            return null;
        }

        return trim(($empleado->Nombre ?? '') . ' ' . ($empleado->Apellidos ?? '')) ?: null;
    }

    private function esResidente(ObraEmpleado $asignacion): bool
    {
        $campos = [
            $asignacion->empleado?->Puesto,
            $asignacion->puesto_en_obra,
            $asignacion->rol?->rol_key,
            $asignacion->rol?->nombre,
        ];

        foreach ($campos as $campo) {
            if (str_contains(strtoupper((string) $campo), 'RESIDENTE')) {
                return true;
            }
        }

        return false;
    }
}