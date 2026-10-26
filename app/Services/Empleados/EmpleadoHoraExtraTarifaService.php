<?php

namespace App\Services\Empleados;

use App\Models\Empleado;
use App\Models\EmpleadoHoraExtraTarifa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmpleadoHoraExtraTarifaService
{
    public function costoAutomatico(Empleado $empleado): ?float
    {
        $base = (float) $empleado->Sueldo_real;
        if ($base <= 0) {
            $base = (float) $empleado->Sueldo + (float) $empleado->Complemento;
        }

        return $base > 0 ? round(($base / 7 / 8) * 2, 4) : null;
    }

    public function paraCaptura(Empleado $empleado): array
    {
        $empleado->loadMissing('horaExtraTarifas');

        return [
            'automatica' => $this->costoAutomatico($empleado),
            'vigencias' => $empleado->horaExtraTarifas->map(fn ($tarifa) => [
                'desde' => $tarifa->vigente_desde->toDateString(),
                'hasta' => $tarifa->vigente_hasta?->toDateString(),
                'modalidad' => $tarifa->modalidad,
                'costo' => $tarifa->costo_hora_extra === null ? null : (float) $tarifa->costo_hora_extra,
            ])->values()->all(),
        ];
    }

    public function costoParaFecha(Empleado $empleado, string $fecha): ?float
    {
        $datos = $this->paraCaptura($empleado);
        foreach ($datos['vigencias'] as $vigencia) {
            if ($vigencia['desde'] <= $fecha && ($vigencia['hasta'] === null || $vigencia['hasta'] >= $fecha)) {
                return $vigencia['modalidad'] === EmpleadoHoraExtraTarifa::MODALIDAD_CUOTA_FIJA
                    ? $vigencia['costo'] : $datos['automatica'];
            }
        }

        return $datos['automatica'];
    }

    public function importeAcumulado(Empleado $empleado, iterable $registros): ?float
    {
        return app(GiraldaHoraExtraSnapshotService::class)->resumen($registros)['monto'];
    }

    public function registrar(Empleado $empleado, array $data, int $usuarioId): EmpleadoHoraExtraTarifa
    {
        return DB::transaction(function () use ($empleado, $data, $usuarioId) {
            // Serializar cambios aun cuando el empleado no tenga tarifas previas.
            $empleado = Empleado::whereKey($empleado->getKey())->lockForUpdate()->firstOrFail();
            $ultima = $empleado->horaExtraTarifas()->lockForUpdate()->first();
            $desde = CarbonImmutable::parse($data['vigente_desde'])->startOfDay();

            if ($ultima && $desde->lessThanOrEqualTo($ultima->vigente_desde)) {
                throw ValidationException::withMessages([
                    'vigente_desde' => 'La nueva vigencia debe iniciar después del '.
                        $ultima->vigente_desde->format('d/m/Y').'. El historial anterior se conserva.',
                ])->errorBag('horaExtraTarifa');
            }

            if ($ultima && (! $ultima->vigente_hasta || $ultima->vigente_hasta->greaterThanOrEqualTo($desde))) {
                $ultima->update(['vigente_hasta' => $desde->subDay()->toDateString()]);
            }

            return $empleado->horaExtraTarifas()->create([
                'modalidad' => $data['modalidad'],
                'costo_hora_extra' => $data['modalidad'] === EmpleadoHoraExtraTarifa::MODALIDAD_CUOTA_FIJA
                    ? $data['costo_hora_extra'] : null,
                'vigente_desde' => $desde->toDateString(),
                'motivo' => $data['motivo'],
                'registrado_por' => $usuarioId,
            ]);
        });
    }
}
