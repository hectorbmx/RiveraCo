<?php

namespace App\Services\Obras;

use App\Models\Obra;
use App\Models\ObraDevolucion;

/** Vista de conciliación: no cierra la obra ni determina consumo. */
class FiniquitoMaterialConsultaService
{
    public function consultar(Obra $obra): array
    {
        $materiales = app(MaterialDevolucionSaldoService::class)->porObra((int) $obra->id);
        $solicitudes = ObraDevolucion::where('obra_id', $obra->id)
            ->with(['detalles.recepcionDirectaDetalle.recepcion', 'detalles.movimientoOrigen.documento', 'detalles.salidaHuentitanDetalle.salida', 'recepciones.detalles', 'recepciones.almacen'])
            ->orderByDesc('id')->get();
        app(ObraDevolucionDiferenciasConsultaService::class)->anexar($solicitudes);
        $campos = ['recepcion_directa' => 'recepcion_directa_detalle_id', 'salida_general' => 'inventario_movimiento_id', 'salida_huentitan' => 'huentitan_salida_detalle_id'];
        $relacionadas = []; $origenesFaltantes = [];
        $claves = $materiales['entregas']->mapWithKeys(fn ($e) => [$e->origen_tipo.':'.$e->origen_id => true]);
        foreach ($solicitudes as $solicitud) {
            if (! in_array($solicitud->estado, ['solicitada', 'recepcion_parcial', 'recibida'], true)) continue;
            foreach ($solicitud->detalles as $partida) {
                $clave = null;
                foreach ($campos as $tipo => $campo) if ($partida->$campo !== null) { $clave = $tipo.':'.$partida->$campo; break; }
                if (! $clave || ! $claves->has($clave)) { $origenesFaltantes[] = ['folio' => $solicitud->folio, 'partida' => $partida->descripcion]; continue; }
                $relacionadas[$clave][] = ['solicitud' => $solicitud, 'partida' => $partida];
            }
        }
        $entregas = $materiales['entregas']->map(function ($entrega) use ($relacionadas) {
            $referencias = $relacionadas[$entrega->origen_tipo.':'.$entrega->origen_id] ?? [];
            $fisicoPendiente = 0; $pendienteRecibir = 0; $revisar = (bool) $entrega->requiere_revision; $folios = [];
            foreach ($referencias as $ref) {
                $resumen = $ref['partida']->resumen_devolucion;
                if ($resumen['errores'] || $ref['solicitud']->resumen_diferencias['errores']) $revisar = true;
                $fisicoPendiente += (int) round($resumen['por_aplicar'] * 1000);
                $pendienteRecibir += (int) round(($resumen['pendiente'] ?? 0) * 1000);
                $folios[] = $ref['solicitud']->folio ?: 'Solicitud #'.$ref['solicitud']->id;
            }
            $entregado = (int) round((float) $entrega->cantidad * 1000);
            $aplicado = (int) round($entrega->cantidad_devuelta_confirmada * 1000);
            $sinDevolucion = $entregado - $aplicado - $fisicoPendiente;
            if ($sinDevolucion < 0) $revisar = true;
            return ['entrega' => $entrega, 'solicitudes' => array_values(array_unique($folios)), 'requiere_revision' => $revisar,
                'fisico_pendiente_aplicar' => $revisar ? null : $fisicoPendiente / 1000.0,
                'pendiente_recibir' => $revisar ? null : $pendienteRecibir / 1000.0,
                'sin_devolucion_fisica' => $revisar ? null : $sinDevolucion / 1000.0];
        });
        return ['materiales' => $materiales, 'entregas' => $entregas, 'solicitudes' => $solicitudes,
            'origenes_faltantes' => $origenesFaltantes,
            'solicitudes_pendientes' => $solicitudes->whereIn('estado', ['borrador', 'solicitada', 'recepcion_parcial'])->count(),
            'condiciones_diferentes' => $solicitudes->whereIn('estado', ['solicitada', 'recepcion_parcial', 'recibida'])->sum(fn ($s) => $s->resumen_diferencias['condiciones_diferentes'])];
    }
}
