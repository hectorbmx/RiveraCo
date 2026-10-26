<?php

namespace App\Services\Empleados;

use App\Models\Empleado;
use App\Models\GiraldaHoraExtra;
use App\Models\NominaRecibo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GiraldaHoraExtraSnapshotService
{
    public const CAMPOS = [
        'empleado_hora_extra_tarifa_id', 'costo_hora_extra', 'importe', 'sueldo_base_snapshot',
        'origen_tarifa', 'snapshot_fuente', 'snapshot_at', 'snapshot_por',
    ];

    public function datos(Empleado $empleado, string $fecha, float $horas, string $fuente, ?int $usuarioId = null): array
    {
        $fecha = CarbonImmutable::parse($fecha)->toDateString();
        if ($horas < 0 || $horas > 24) {
            throw ValidationException::withMessages(['total_horas' => 'Las horas extra deben estar entre 0 y 24.']);
        }
        $empleado->loadMissing('horaExtraTarifas');
        $tarifa = $empleado->horaExtraTarifas->first(fn ($item) =>
            $item->vigente_desde->toDateString() <= $fecha &&
            (! $item->vigente_hasta || $item->vigente_hasta->toDateString() >= $fecha)
        );
        $fija = $tarifa?->modalidad === 'cuota_fija';
        $base = (float) $empleado->Sueldo_real;
        if ($base <= 0) {
            $base = (float) $empleado->Sueldo + (float) $empleado->Complemento;
        }
        $costo = $fija ? ($tarifa->costo_hora_extra === null ? null : (float) $tarifa->costo_hora_extra)
            : app(EmpleadoHoraExtraTarifaService::class)->costoAutomatico($empleado);
        if ($costo === null || $costo <= 0) {
            throw ValidationException::withMessages(['total_horas' => 'El empleado no tiene sueldo o cuota fija válida para calcular el importe de horas extra.']);
        }

        return [
            'empleado_hora_extra_tarifa_id' => $tarifa?->id,
            'costo_hora_extra' => $costo,
            'importe' => round($horas * $costo, 2),
            'sueldo_base_snapshot' => $fija ? null : $base,
            'origen_tarifa' => $fija ? 'cuota_fija' : 'automatica',
            'snapshot_fuente' => $fuente,
            'snapshot_at' => now(),
            'snapshot_por' => $usuarioId,
        ];
    }

    public function crear(array $data, ?int $usuarioId): GiraldaHoraExtra
    {
        return DB::transaction(function () use ($data, $usuarioId) {
            $empleado = Empleado::whereKey($data['empleado_id'])->lockForUpdate()->firstOrFail();
            $data['fecha'] = CarbonImmutable::parse($data['fecha'])->toDateString();
            // El importe se determina en el servidor; no aceptar snapshots del formulario.
            $data = array_diff_key($data, array_flip(self::CAMPOS));
            return GiraldaHoraExtra::create(array_merge($data, $this->datos(
                $empleado, $data['fecha'], (float) $data['total_horas'], 'captura', $usuarioId
            )));
        });
    }

    public function actualizar(GiraldaHoraExtra $registro, array $data): void
    {
        DB::transaction(function () use ($registro, $data) {
            $registro = GiraldaHoraExtra::whereKey($registro->id)->lockForUpdate()->firstOrFail();
            if ($this->tieneNominaProtegida($registro)) {
                throw ValidationException::withMessages(['total_horas' => 'El periodo tiene nómina cerrada o pagada; requiere revisión antes de modificar horas extra.']);
            }
            if (! $registro->tieneSnapshot()) {
                throw ValidationException::withMessages(['total_horas' => 'Este registro requiere completar su snapshot histórico antes de editarlo.']);
            }
            $data['fecha'] = CarbonImmutable::parse($data['fecha'])->toDateString();
            if ($data['fecha'] !== $registro->fecha->toDateString()) {
                throw ValidationException::withMessages(['fecha' => 'La fecha de un registro con snapshot se conserva para mantener la tarifa aplicada.']);
            }
            $data = array_diff_key($data, array_flip(self::CAMPOS));
            $data['importe'] = round((float) $data['total_horas'] * (float) $registro->costo_hora_extra, 2);
            $registro->update($data);
        });
    }

    public function resumen(iterable $registros): array
    {
        $horas = 0;
        $centavos = 0;
        $pendientes = 0;
        $estimados = 0;
        $tarifas = [];
        foreach ($registros as $registro) {
            $horas += (float) $registro->total_horas;
            if (! $registro->tieneSnapshot()) {
                $pendientes++;
                continue;
            }
            $centavos += (int) round((float) $registro->importe * 100);
            $tarifas[$registro->costo_hora_extra] = (float) $registro->costo_hora_extra;
            $estimados += $registro->snapshot_fuente === 'estimado_sueldo_actual' ? 1 : 0;
        }

        return [
            'total_horas' => round($horas, 2), 'monto' => $pendientes > 0 ? null : round($centavos / 100, 2),
            'pendientes' => $pendientes, 'estimados' => $estimados,
            'tarifa_hora_extra' => count($tarifas) === 1 && $pendientes === 0 ? reset($tarifas) : null,
            'tarifas_multiples' => count($tarifas) > 1,
        ];
    }

    public function tieneNominaProtegida(GiraldaHoraExtra $registro): bool
    {
        // No hay vinculo individual: revisar conservadoramente el periodo del empleado.
        return NominaRecibo::where('empleado_id', $registro->empleado_id)
            ->where(fn ($q) => $q->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', $registro->fecha->toDateString()))
            ->where(fn ($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $registro->fecha->toDateString()))
            ->where(function ($q) {
                $estados = ['cerrada', 'cerrado', 'pagada', 'pagado'];
                $q->whereIn('status', $estados)->orWhereHas('corrida', fn ($corrida) => $corrida->whereIn('status', $estados));
            })->exists();
    }

    public function completar(GiraldaHoraExtra $registro, bool $aplicar, bool $estimar): string
    {
        return DB::transaction(function () use ($registro, $aplicar, $estimar) {
            $registro = GiraldaHoraExtra::whereKey($registro->id)->lockForUpdate()->firstOrFail();
            if ($registro->tieneSnapshot()) {
                return 'existente';
            }
            foreach (self::CAMPOS as $campo) {
                if ($registro->getAttribute($campo) !== null) {
                    return 'parcial_revisar';
                }
            }
            if ($this->tieneNominaProtegida($registro)) {
                return 'nomina_protegida';
            }
            $empleado = Empleado::whereKey($registro->empleado_id)->lockForUpdate()->first();
            if (! $empleado) {
                return 'sin_empleado';
            }
            if ((float) $registro->total_horas < 0 || (float) $registro->total_horas > 24) {
                return 'horas_invalidas';
            }
            try {
                $datos = $this->datos($empleado, $registro->fecha->toDateString(), (float) $registro->total_horas, 'reconstruido_cuota_fija');
            } catch (ValidationException $exception) {
                return 'sin_tarifa';
            }
            if ($datos['origen_tarifa'] === 'automatica') {
                if (! $estimar) {
                    return 'requiere_sueldo_historico';
                }
                $datos['snapshot_fuente'] = 'estimado_sueldo_actual';
            }
            if ($aplicar) {
                // No tocar horarios, autorizacion, updated_at ni recibos de nomina.
                DB::table('giralda_horas_extras')->where('id', $registro->id)->update($datos);
            }
            return $datos['origen_tarifa'] === 'cuota_fija' ? 'cuota_fija' : 'estimado';
        });
    }
}
