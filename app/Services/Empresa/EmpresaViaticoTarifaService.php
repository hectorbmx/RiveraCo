<?php

namespace App\Services\Empresa;

use App\Models\EmpresaViaticoTarifa;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmpresaViaticoTarifaService
{
    public function registrarNuevaTarifa(float $importeDiario, CarbonInterface|string $vigenciaDesde, ?int $creadoPor = null, ?string $notas = null): EmpresaViaticoTarifa
    {
        $vigenciaDesde = $this->normalizarFecha($vigenciaDesde);

        return DB::transaction(function () use ($importeDiario, $vigenciaDesde, $creadoPor, $notas) {
            $this->cerrarTarifaVigente($vigenciaDesde);

            return EmpresaViaticoTarifa::create([
                'importe_diario' => $importeDiario,
                'vigencia_desde' => $vigenciaDesde->toDateString(),
                'vigencia_hasta' => null,
                'activo' => true,
                'creado_por' => $creadoPor,
                'notas' => $notas,
            ]);
        });
    }

    private function cerrarTarifaVigente(CarbonInterface $nuevaVigenciaDesde): void
    {
        $vigenciaHasta = Carbon::instance($nuevaVigenciaDesde->toDateTime())
            ->subDay()
            ->toDateString();

        EmpresaViaticoTarifa::vigentes()
            ->update([
                'vigencia_hasta' => $vigenciaHasta,
                'activo' => false,
            ]);
    }

    private function normalizarFecha(CarbonInterface|string $fecha): CarbonInterface
    {
        if ($fecha instanceof CarbonInterface) {
            return $fecha->copy()->startOfDay();
        }

        return Carbon::parse($fecha)->startOfDay();
    }
}