<?php

namespace App\Services\Mantenimiento;

use App\Models\Mantenimiento;

class MantenimientoManoObraService
{
    public function actualizar(Mantenimiento $mantenimiento): void
    {
        $datos = $this->calcular($mantenimiento);

        if (!$datos) {
            return;
        }

        $detalle = $mantenimiento->detalles()
            ->where('tipo', 'mano_obra')
            ->where('concepto', 'like', 'Mano de obra:%')
            ->first();

        if (!$detalle) {
            $detalle = $mantenimiento->detalles()->make(['tipo' => 'mano_obra']);
        }

        $detalle->fill([
            'concepto' => 'Mano de obra: ' . $datos['nombre'],
            'cantidad' => $datos['horas'],
            'costo_unitario' => $datos['costo_hora'],
            'costo_total' => $datos['costo_total'],
        ]);
        $mantenimiento->detalles()->save($detalle);

        $costoRefacciones = (float) $mantenimiento->detalles()
            ->whereIn('tipo', ['refaccion', 'consumible'])
            ->sum('costo_total');

        $mantenimiento->update([
            'costo_mano_obra' => $datos['costo_total'],
            'costo_refacciones' => round($costoRefacciones, 2),
            'costo_total' => round($datos['costo_total'] + $costoRefacciones, 2),
        ]);
    }

    public function calcular(Mantenimiento $mantenimiento): ?array
    {
        $mantenimiento->loadMissing('mecanico');

        $minutos = $mantenimiento->duracion_en_minutos;
        $mecanico = $mantenimiento->mecanico;
        if ($minutos === null || !$mecanico) {
            return null;
        }

        $sueldo = (float) ($mecanico->Sueldo_real ?: $mecanico->Sueldo ?: 0);
        $diasPeriodo = match ((int) ($mecanico->Sueldo_tipo ?: 0)) {
            1 => 7,
            2 => 15,
            3 => 30,
            default => 0,
        };
        if ($sueldo <= 0 || $diasPeriodo <= 0) {
            return null;
        }

        $horas = round($minutos / 60, 2);
        $costoHora = round($sueldo / $diasPeriodo / 8, 2);

        return [
            'nombre' => trim(($mecanico->Nombre ?? '') . ' ' . ($mecanico->Apellidos ?? '')) ?: 'Mecánico',
            'horas' => $horas,
            'costo_hora' => $costoHora,
            'costo_total' => round($horas * $costoHora, 2),
        ];
    }

}
