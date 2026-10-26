<?php

namespace App\Services\Mobile\Equipos;

use App\Models\Mantenimiento;
use App\Models\Maquina;
use App\Models\ObraMaquina;
use App\Models\Seguro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaquinasConsultaService
{
    public function index(Request $request)
    {
        $q = Maquina::query()
            ->select([
                'id',
                'codigo',
                'nombre',
                'placas',
                'horometro_base',
                'estado',
                'marca',
                'modelo',
                'tipo',
            ])
            ->orderBy('nombre');

        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($x) use ($term) {
                $x->where('nombre', 'like', "%{$term}%")
                    ->orWhere('codigo', 'like', "%{$term}%")
                    ->orWhere('modelo', 'like', "%{$term}%")
                    ->orWhere('placas', 'like', "%{$term}%");
            });
        }

        if ($request->filled('estatus')) {
            $q->where('estado', $request->estatus);
        }

        $maquinasAsignadasQuery = ObraMaquina::query()
            ->where('estado', 'activa')
            ->whereNull('fecha_fin')
            ->select('maquina_id');

        if ($request->filled('asignada')) {
            if ((int) $request->asignada === 1) {
                $q->whereIn('id', clone $maquinasAsignadasQuery);
            } elseif ((int) $request->asignada === 0) {
                $q->whereNotIn('id', clone $maquinasAsignadasQuery);
            }
        }

        if ($request->boolean('en_uso')) {
            $q->whereIn('id', clone $maquinasAsignadasQuery);
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);
        $rows = $q->paginate($perPage)->withQueryString();

        $maquinaIds = $rows->getCollection()->pluck('id')->filter()->values();
        $asignacionesActivas = $maquinaIds->isEmpty()
            ? collect()
            : ObraMaquina::query()
                ->with('obra:id,nombre,clave_obra,estatus_nuevo')
                ->whereIn('maquina_id', $maquinaIds)
                ->where('estado', 'activa')
                ->whereNull('fecha_fin')
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id')
                ->get()
                ->unique('maquina_id')
                ->keyBy('maquina_id');

        return [
            'ok' => true,
            'data' => $rows->through(function ($m) use ($asignacionesActivas) {
                $asig = $asignacionesActivas->get($m->id);

                return [
                    'id' => (int) $m->id,
                    'codigo' => $m->codigo ?? null,
                    'nombre' => $m->nombre ?? null,
                    'tipo' => $m->tipo ?? null,
                    'marca' => $m->marca ?? null,
                    'modelo' => $m->modelo ?? null,
                    'placas' => $m->placas ?? null,
                    'horometro_base' => $m->horometro_base ?? null,
                    'estado' => $m->estado ?? null,
                    'asignada' => (bool) $asig,
                    'obra_activa' => $asig && $asig->obra ? [
                        'id' => (int) $asig->obra->id,
                        'nombre' => $asig->obra->nombre,
                        'clave_obra' => $asig->obra->clave_obra,
                        'estatus_nuevo' => $asig->obra->estatus_nuevo,
                    ] : null,
                ];
            }),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ];
    }

    public function show(Request $request, Maquina $maquina)
    {
        // Cargar asignación activa + obra
        $maquina->load([
            'asignacionActiva.obra:id,nombre,clave_obra,estatus_nuevo,ubicacion',
            'seguros',
        ]);

        // Historial corto de asignaciones (últimas 10)
        $asignaciones = ObraMaquina::query()
            ->select([
                'id',
                'obra_id',
                'maquina_id',
                'fecha_inicio',
                'fecha_fin',
                'horometro_inicio',
                'horometro_fin',
                'estado',
            ])
            ->with(['obra:id,nombre,clave_obra,estatus_nuevo'])
            ->where('maquina_id', $maquina->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($om) {
                return [
                    'obra_maquina_id' => (int) $om->id,
                    'fecha_inicio' => optional($om->fecha_inicio)->toDateString(),
                    'fecha_fin' => optional($om->fecha_fin)->toDateString(),
                    'estado' => $om->estado,
                    'horometro_inicio' => $om->horometro_inicio,
                    'horometro_fin' => $om->horometro_fin,
                    'obra' => $om->obra ? [
                        'id' => (int) $om->obra->id,
                        'nombre' => $om->obra->nombre,
                        'clave_obra' => $om->obra->clave_obra,
                        'estatus_nuevo' => $om->obra->estatus_nuevo,
                    ] : null,
                ];
            })
            ->values();

        $asig = $maquina->asignacionActiva;

        $seguros = $maquina->seguros()
            ->orderByDesc('vigencia_hasta')
            ->limit(10)
            ->get();

        $mantenimientos = $maquina->mantenimientos()
            ->with('mecanico:id_Empleado,Nombre,Apellidos')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return [
            'ok' => true,
            'data' => [
                'maquina' => [
                    'id' => (int) $maquina->id,
                    'codigo' => $maquina->codigo ?? null,
                    'nombre' => $maquina->nombre ?? null,
                    'tipo' => $maquina->tipo ?? null,
                    'marca' => $maquina->marca ?? null,
                    'modelo' => $maquina->modelo ?? null,
                    'numero_serie' => $maquina->numero_serie ?? null,
                    'placas' => $maquina->placas ?? null,
                    'color' => $maquina->color ?? null,
                    'horometro_base' => $maquina->horometro_base ?? null,
                    'estado' => $maquina->estado ?? null,
                    'ubicacion' => $maquina->ubicacion ?? null,
                    'notas' => $maquina->notas ?? null,
                ],
                'asignacion_activa' => $asig ? [
                    'obra_maquina_id' => (int) $asig->id,
                    'fecha_inicio' => optional($asig->fecha_inicio)->toDateString(),
                    'horometro_inicio' => $asig->horometro_inicio,
                    'estado' => $asig->estado,
                    'obra' => $asig->obra ? [
                        'id' => (int) $asig->obra->id,
                        'nombre' => $asig->obra->nombre,
                        'clave_obra' => $asig->obra->clave_obra,
                        'estatus_nuevo' => $asig->obra->estatus_nuevo,
                        'ubicacion' => $asig->obra->ubicacion ?? null,
                    ] : null,
                ] : null,
                'asignaciones_recientes' => $asignaciones,
                'seguro_vigente' => $this->resolverSeguroVigente($seguros),
                'seguros_recientes' => $seguros->map(fn (Seguro $seguro) => $this->mapSeguro($seguro))->values(),
                'mantenimientos_recientes' => $mantenimientos->map(fn (Mantenimiento $mantenimiento) => $this->mapMantenimiento($mantenimiento))->values(),
                'mantenimientos_resumen' => $this->mapMantenimientosResumen($maquina),
            ],
        ];
    }

    private function mapSeguro(Seguro $seguro): array
    {
        return [
            'id' => (int) $seguro->id,
            'aseguradora' => $seguro->aseguradora,
            'poliza_numero' => $seguro->poliza_numero,
            'tipo_seguro' => $seguro->tipo_seguro,
            'metodo_pago' => $seguro->metodo_pago,
            'frecuencia_pago' => $seguro->frecuencia_pago,
            'costo' => $seguro->costo !== null ? (float) $seguro->costo : null,
            'moneda' => $seguro->moneda,
            'fecha_compra' => optional($seguro->fecha_compra)->format('Y-m-d'),
            'vigencia_desde' => optional($seguro->vigencia_desde)->format('Y-m-d'),
            'vigencia_hasta' => optional($seguro->vigencia_hasta)->format('Y-m-d'),
            'suma_asegurada' => $seguro->suma_asegurada !== null ? (float) $seguro->suma_asegurada : null,
            'deducible' => $seguro->deducible !== null ? (float) $seguro->deducible : null,
            'cobertura' => $seguro->cobertura,
            'estatus' => $this->estatusSeguro($seguro),
            'alerta_vencimiento_activa' => (bool) $seguro->alerta_vencimiento_activa,
            'dias_preaviso' => $seguro->dias_preaviso,
            'observaciones' => $seguro->observaciones,
            'documento_url' => $this->storageUrl($seguro->documento_path),
            'comprobante_url' => $this->storageUrl($seguro->comprobante_path),
        ];
    }

    private function mapMantenimiento(Mantenimiento $mantenimiento): array
    {
        $mecanico = $mantenimiento->mecanico;

        return [
            'id' => (int) $mantenimiento->id,
            'tipo' => $mantenimiento->tipo,
            'categoria_mantenimiento' => $mantenimiento->categoria_mantenimiento,
            'descripcion' => $mantenimiento->descripcion,
            'km_actuales' => $mantenimiento->km_actuales,
            'km_proximo_servicio' => $mantenimiento->km_proximo_servicio,
            'horometro' => $mantenimiento->horometro,
            'fecha_programada' => optional($mantenimiento->fecha_programada)->format('Y-m-d'),
            'fecha_inicio' => optional($mantenimiento->fecha_inicio)->format('Y-m-d H:i:s'),
            'fecha_fin' => optional($mantenimiento->fecha_fin)->format('Y-m-d H:i:s'),
            'estatus' => $mantenimiento->estatus,
            'costo_total' => $mantenimiento->costo_total !== null ? (float) $mantenimiento->costo_total : null,
            'notas' => $mantenimiento->notas,
            'mecanico' => $mecanico ? [
                'id' => (int) $mecanico->id_Empleado,
                'nombre' => trim(($mecanico->Nombre ?? '').' '.($mecanico->Apellidos ?? '')),
            ] : null,
        ];
    }

    private function resolverSeguroVigente($seguros): ?array
    {
        $seguros = collect($seguros);
        $hoy = now();

        $vigente = $seguros->first(function (Seguro $seguro) use ($hoy) {
            return (string) $seguro->estatus !== 'cancelada'
                && $seguro->vigencia_desde
                && $seguro->vigencia_hasta
                && $seguro->vigencia_desde->lte($hoy)
                && $seguro->vigencia_hasta->gte($hoy);
        });

        return $vigente ? $this->mapSeguro($vigente) : null;
    }

    private function estatusSeguro(Seguro $seguro): string
    {
        if ($seguro->estatus === 'cancelada') {
            return 'cancelada';
        }

        $hoy = now();

        if ($seguro->vigencia_desde && $seguro->vigencia_desde->gt($hoy)) {
            return 'futura';
        }

        if ($seguro->vigencia_hasta && $seguro->vigencia_hasta->lt($hoy)) {
            return 'vencida';
        }

        return 'vigente';
    }

    private function mapMantenimientosResumen(Maquina $maquina): array
    {
        $query = $maquina->mantenimientos();

        return [
            'total' => (clone $query)->count(),
            'pendiente' => (clone $query)->where('estatus', 'pendiente')->count(),
            'en_proceso' => (clone $query)->where('estatus', 'en_proceso')->count(),
            'completado' => (clone $query)->where('estatus', 'completado')->count(),
            'cancelado' => (clone $query)->where('estatus', 'cancelado')->count(),
        ];
    }

    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }
}
