<?php

namespace App\Services\Empleados;

use App\Models\Empleado;
use Illuminate\Support\Collection;

class EmpleadoKardexService
{
    public function build(Empleado $empleado): Collection
    {
        $items = collect();

        // 1) Nómina (incluye normales y extras si ya están en la misma tabla)
        foreach ($empleado->nominaRecibos ?? [] as $recibo) {

            // Fecha “principal” para ordenar (pago > fin > inicio)
            $fecha = $recibo->fecha_pago ?? $recibo->fecha_fin ?? $recibo->fecha_inicio ?? $recibo->created_at;

            $tipo    = $recibo->tipo_pago ?? 'nomina';
            $subtipo = $recibo->subtipo ?? 'nomina_normal';

            $label = 'Nómina';
            if ($tipo === 'extra') {
                $map = [
                    'aguinaldo'        => 'Aguinaldo',
                    'prima_vacacional' => 'Prima vacacional',
                    'ptu'              => 'PTU',
                    'bono'             => 'Bono',
                    'otro'             => 'Extra',
                ];
                $label = $map[$subtipo] ?? ucfirst(str_replace('_', ' ', $subtipo));
            }

            $obraLabel = null;
            if ($recibo->obra) {
                $obraLabel = $recibo->obra->nombre
                    ?? $recibo->obra->folio
                    ?? ('Obra #'.$recibo->obra->id);
            } elseif (!empty($recibo->obra_legacy)) {
                $obraLabel = $recibo->obra_legacy.' (legacy)';
            }

            $items->push([
                'fecha'       => $fecha,
                'grupo'       => 'nomina',
                'tipo'        => $tipo, // nomina|extra
                'titulo'      => $label,
                'subtitulo'   => $obraLabel ?: 'Sin obra',
                'status'      => $recibo->status ?? null,
                'monto'       => (float) ($recibo->sueldo_neto ?? 0),
                'percepciones'=> (float) ($recibo->total_percepciones ?? 0),
                'deducciones' => (float) ($recibo->total_deducciones ?? 0),
                'meta'        => [
                    'periodo' => $recibo->periodo_label ?? null,
                    'folio'   => $recibo->folio ?? null,
                    'rango'   => trim(
                        ($recibo->fecha_inicio?->format('d/m/Y') ?? '') .
                        ' - ' .
                        ($recibo->fecha_fin?->format('d/m/Y') ?? '')
                    ),
                    'pago'    => $recibo->fecha_pago?->format('d/m/Y') ?? null,
                ],
                'detalle' => [
                    'faltas'            => $recibo->faltas ?? null,
                    'horas_extra'       => $recibo->horas_extra ?? null,
                    'metros_lin_monto'  => $recibo->metros_lin_monto ?? null,
                    'comisiones_monto'  => $recibo->comisiones_monto ?? null,
                    'factura_monto'     => $recibo->factura_monto ?? null,
                    'descuentos_legacy' => ($recibo->descuentos_legacy ?? null),
                    'infonavit_legacy'  => ($recibo->infonavit_legacy ?? null),
                    'notas_legacy'      => $recibo->notas_legacy ?? null,
                ],
            ]);

            // 2) (Opcional) Si ya tienes tabla de pagos extra como hijos (nomina_pagos_extra)
            //    descomenta esto si existe la relación $recibo->pagosExtra
            /*
            foreach ($recibo->pagosExtra ?? [] as $extra) {
                $items->push([
                    'fecha'     => $extra->fecha ?? $fecha,
                    'grupo'     => 'extra',
                    'tipo'      => 'extra',
                    'titulo'    => 'Pago extra: ' . ($extra->concepto ?? 'Extra'),
                    'subtitulo' => $obraLabel ?: 'Sin obra',
                    'status'    => $recibo->status ?? null,
                    'monto'     => (float) ($extra->monto ?? 0),
                    'meta'      => [
                        'ref' => 'Recibo #' . ($recibo->id ?? ''),
                    ],
                    'detalle'   => [
                        'notas' => $extra->notas ?? null,
                    ],
                ]);
            }
            */
        }

        // Orden final (desc)
        return $items
            ->filter(fn($x) => !empty($x['fecha']))
            ->sortByDesc(fn($x) => $x['fecha'])
            ->values();
    }
}
