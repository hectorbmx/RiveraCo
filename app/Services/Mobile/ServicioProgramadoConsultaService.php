<?php

namespace App\Services\Mobile;

use App\Models\Mantenimiento;
use App\Models\OrdenCompra;
use App\Services\Mantenimiento\MantenimientoAbastecimientoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ServicioProgramadoConsultaService
{
    public const ESTADOS = ['borrador', 'orden_generada', 'pendiente', 'en_proceso', 'completado', 'cancelado'];

    public function __construct(private MantenimientoAbastecimientoService $abastecimiento) {}

    public function consulta(): Builder
    {
        return Mantenimiento::query()->where('tipo', 'programado')
            ->with(['maquina:id,nombre,codigo', 'vehiculo:id,marca,modelo,placas',
                'obra:id,nombre', 'mecanico:id_Empleado,Nombre,Apellidos', 'servicioPreventivoTipo:id,nombre,ambito,activo',
                'materialesOrden:id,mantenimiento_id,producto_id,cantidad_requerida,incluido',
                'abastecimientos:id,mantenimiento_id,tipo,estado',
                'abastecimientos.detalles:id,abastecimiento_id,producto_id,cantidad_atendida'])
            ->withExists(['materialesOrden', 'abastecimientos'])->withCount('fotos');
    }

    public function cabecera(Mantenimiento $servicio): array
    {
        $data = $servicio->only(['id', 'tipo', 'maquina_id', 'vehiculo_id', 'obra_id', 'mecanico_id',
            'categoria_mantenimiento', 'servicio_preventivo_tipo_id', 'descripcion', 'km_actuales',
            'km_proximo_servicio', 'horometro', 'fecha_programada', 'fecha_inicio', 'fecha_fin', 'notas', 'estatus',
            'abastecimiento_estado', 'abastecimiento_tipo', 'orden_servicio_folio',
            'costo_mano_obra', 'costo_refacciones', 'costo_total', 'created_at', 'updated_at']);
        foreach (['id', 'maquina_id', 'vehiculo_id', 'obra_id', 'mecanico_id', 'servicio_preventivo_tipo_id', 'km_actuales', 'km_proximo_servicio'] as $campo) {
            $data[$campo] = $data[$campo] !== null ? (int) $data[$campo] : null;
        }
        foreach (['horometro', 'costo_mano_obra', 'costo_refacciones', 'costo_total'] as $campo) {
            $data[$campo] = $data[$campo] !== null ? (float) $data[$campo] : null;
        }

        return $data;
    }

    public function resumen(Mantenimiento $servicio, ?int $empleadoId): array
    {
        $propio = $empleadoId !== null && (int) $servicio->mecanico_id === $empleadoId;
        $editable = $propio && $servicio->estatus === 'borrador' && ! $servicio->orden_servicio_folio
            && ! $servicio->materiales_orden_exists && ! $servicio->abastecimientos_exists;
        $ambito = $servicio->maquina_id ? 'maquinaria' : 'vehiculo';
        $generable = $editable && ((bool) $servicio->maquina_id !== (bool) $servicio->vehiculo_id)
            && (bool) $servicio->servicioPreventivoTipo?->activo && $servicio->servicioPreventivoTipo?->ambito === $ambito;
        $surtidos = $servicio->abastecimientos->flatMap(fn ($s) => $s->detalles)->groupBy('producto_id')
            ->map(fn ($filas) => (float) $filas->sum('cantidad_atendida'));
        $requeridos = $servicio->materialesOrden->where('incluido', true)->groupBy('producto_id')
            ->map(fn ($filas) => (float) $filas->sum('cantidad_requerida'));
        $pendientes = $servicio->materialesOrden->filter(fn ($m) => $m->incluido
            && (float) $requeridos->get($m->producto_id, 0) - (float) $surtidos->get($m->producto_id, 0) > 0.0005);
        $solicitable = $propio && $servicio->maquina_id && $servicio->orden_servicio_folio
            && in_array($servicio->estatus, ['orden_generada', 'pendiente', 'en_proceso'], true)
            && $pendientes->isNotEmpty() && $pendientes->every(fn ($m) => (bool) $m->producto_id);
        $activas = $servicio->abastecimientos->whereNotIn('estado', ['surtida', 'cancelada']);

        return array_merge($this->cabecera($servicio), [
            'maquina' => $servicio->maquina?->only(['id', 'nombre', 'codigo']),
            'vehiculo' => $servicio->vehiculo?->only(['id', 'marca', 'modelo', 'placas']),
            'obra' => $servicio->obra?->only(['id', 'nombre']),
            'mecanico' => $servicio->mecanico ? ['id' => (int) $servicio->mecanico->id_Empleado,
                'nombre' => trim($servicio->mecanico->Nombre.' '.$servicio->mecanico->Apellidos)] : null,
            'servicio_preventivo' => $servicio->servicioPreventivoTipo?->only(['id', 'nombre', 'ambito']),
            'es_propio' => $propio,
            'acciones_permitidas' => ['editar_borrador' => $editable, 'generar_orden' => $generable,
                'ajustar_materiales' => $propio && $servicio->estatus === 'orden_generada' && (bool) $servicio->orden_servicio_folio
                    && (bool) $servicio->materiales_orden_exists && ! $servicio->abastecimientos_exists,
                'solicitar_almacen' => $solicitable && ! $activas->contains('tipo', 'almacen'),
                'solicitar_compra' => $solicitable && ! $activas->contains('tipo', 'compra'),
                'agregar_evidencias' => $propio && $servicio->fotos_count < 8,
                'iniciar' => $propio && (bool) $servicio->orden_servicio_folio && in_array($servicio->estatus, ['orden_generada', 'pendiente'], true),
                'completar' => $propio && (bool) $servicio->orden_servicio_folio && $servicio->estatus === 'en_proceso'
                    && $servicio->fecha_inicio !== null && app(\App\Services\Mantenimiento\MantenimientoEjecucionService::class)->surtidoCompleto($servicio),
                'cancelar' => false],
        ]);
    }

    public function detalle(Mantenimiento $servicio, ?int $empleadoId): array
    {
        $servicio->load(['materialesOrden', 'detalles', 'fotos', 'abastecimientos.detalles', 'abastecimientos.inventarioDocumentos']);
        $compras = $servicio->abastecimientos->isEmpty() ? collect() : OrdenCompra::query()
            ->whereIn('mantenimiento_abastecimiento_id', $servicio->abastecimientos->modelKeys())
            ->orderBy('id')->get(['id', 'mantenimiento_abastecimiento_id', 'folio', 'estado', 'estado_recepcion', 'fecha'])
            ->groupBy('mantenimiento_abastecimiento_id');

        return array_merge($this->resumen($servicio, $empleadoId), [
            'orden' => ['folio' => $servicio->orden_servicio_folio, 'generada_at' => $servicio->orden_servicio_generada_at,
                'tiene_snapshot' => (bool) $servicio->materiales_orden_exists],
            'materiales' => $servicio->materialesOrden->map(fn ($m) => [
                'id' => (int) $m->id, 'producto_id' => $m->producto_id === null ? null : (int) $m->producto_id,
                'material_preventivo_id' => $m->material_preventivo_id === null ? null : (int) $m->material_preventivo_id,
                'producto' => $m->producto_nombre_snapshot, 'sku' => $m->producto_sku_snapshot,
                'unidad' => $m->unidad, 'cantidad_requerida' => (float) $m->cantidad_requerida,
                'incluido' => (bool) $m->incluido, 'motivo_exclusion' => $m->motivo_exclusion,
            ])->values(),
            'avance_materiales' => $this->abastecimiento->calcular($servicio)->values(),
            'solicitudes' => $servicio->abastecimientos->sortBy('id')->values()->map(fn ($s) => [
                'id' => (int) $s->id, 'folio' => $s->folio, 'tipo' => $s->tipo, 'estado' => $s->estado,
                'created_at' => $s->created_at,
                'materiales' => $s->detalles->sortBy('id')->values()->map(fn ($d) => [
                    'id' => (int) $d->id, 'producto_id' => $d->producto_id === null ? null : (int) $d->producto_id,
                    'producto' => $d->producto_nombre_snapshot, 'sku' => $d->producto_sku_snapshot, 'unidad' => $d->unidad,
                    'requerido' => (float) $d->cantidad_requerida, 'solicitado' => (float) $d->cantidad_solicitada,
                    'atendido' => (float) $d->cantidad_atendida, 'estado' => $d->estado,
                ]),
                'documentos_inventario' => $s->inventarioDocumentos->sortBy('id')->values()
                    ->map(fn ($d) => $d->only(['id', 'folio', 'tipo', 'estado', 'fecha', 'orden_compra_id'])),
                'ordenes_compra' => $compras->get($s->id, collect())->map(fn ($c) => $c->only(['id', 'folio', 'estado', 'estado_recepcion', 'fecha']))->values(),
            ]),
            'conceptos' => $servicio->detalles->sortBy('id')->values()->map(fn ($d) => [
                'id' => (int) $d->id, 'concepto' => $d->concepto, 'tipo' => $d->tipo,
                'cantidad' => (float) $d->cantidad, 'costo_unitario' => (float) $d->costo_unitario, 'costo_total' => (float) $d->costo_total,
            ]),
            'evidencias' => $servicio->fotos->sortBy('id')->values()->map(fn ($f) => [
                'id' => (int) $f->id, 'descripcion' => $f->descripcion,
                'url' => $f->ruta ? url(Storage::disk('public')->url($f->ruta)) : null,
            ]),
        ]);
    }
}
