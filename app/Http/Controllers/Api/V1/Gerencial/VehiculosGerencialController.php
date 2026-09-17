<?php

namespace App\Http\Controllers\Api\V1\Gerencial;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\Seguro;
use App\Models\Vehiculo;
use App\Models\VehiculoEmpleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VehiculosGerencialController extends Controller
{
    public function index(Request $request)
    {
        $query = Vehiculo::query()
            ->with([
                'asignacionActual.empleado:id_Empleado,Nombre,Apellidos,Puesto,Celular',
                'seguros',
                'documentoTarjetaCirculacionVigente',
            ])
            ->orderBy('marca')
            ->orderBy('modelo')
            ->orderBy('placas');

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $query->where(function ($vehiculo) use ($term) {
                $vehiculo->where('marca', 'like', "%{$term}%")
                    ->orWhere('modelo', 'like', "%{$term}%")
                    ->orWhere('placas', 'like', "%{$term}%")
                    ->orWhere('serie', 'like', "%{$term}%")
                    ->orWhere('tipo', 'like', "%{$term}%")
                    ->orWhereHas('asignacionActual.empleado', function ($empleado) use ($term) {
                        $empleado->where('Nombre', 'like', "%{$term}%")
                            ->orWhere('Apellidos', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->filled('asignado')) {
            if ((int) $request->asignado === 1) {
                $query->whereHas('asignacionActual');
            } elseif ((int) $request->asignado === 0) {
                $query->whereDoesntHave('asignacionActual');
            }
        }

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);
        $rows = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'ok' => true,
            'data' => $rows->through(fn (Vehiculo $vehiculo) => $this->mapVehiculoListItem($vehiculo)),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function show(Request $request, Vehiculo $vehiculo)
    {
        $vehiculo->load([
            'asignacionActual.empleado:id_Empleado,Nombre,Apellidos,Puesto,Celular',
            'seguros',
            'documentos',
        ]);

        $asignaciones = $vehiculo->asignaciones()
            ->with([
                'empleado:id_Empleado,Nombre,Apellidos,Puesto,Celular',
                'fotos',
            ])
            ->orderByDesc('fecha_asignacion')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (VehiculoEmpleado $asignacion) => $this->mapAsignacion($asignacion, true))
            ->values();

        $seguros = $vehiculo->seguros()
            ->orderByDesc('vigencia_hasta')
            ->limit(10)
            ->get();

        $mantenimientos = $vehiculo->mantenimientos()
            ->with('mecanico:id_Empleado,Nombre,Apellidos')
            ->orderByDesc('fecha_programada')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $documentos = $vehiculo->documentos()
            ->orderByDesc('vigente')
            ->orderByDesc('fecha_vencimiento')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return response()->json([
            'ok' => true,
            'data' => [
                'vehiculo' => $this->mapVehiculo($vehiculo),
                'asignacion_actual' => $vehiculo->asignacionActual
                    ? $this->mapAsignacion($vehiculo->asignacionActual, true)
                    : null,
                'asignaciones_recientes' => $asignaciones,
                'seguro_vigente' => $this->resolverSeguroVigente($seguros),
                'seguros_recientes' => $seguros->map(fn (Seguro $seguro) => $this->mapSeguro($seguro))->values(),
                'mantenimientos_recientes' => $mantenimientos->map(fn (Mantenimiento $mantenimiento) => $this->mapMantenimiento($mantenimiento))->values(),
                'mantenimientos_resumen' => $this->mapMantenimientosResumen($vehiculo),
                'documentos' => $documentos->map(fn ($documento) => $this->mapDocumento($documento))->values(),
                'documentos_resumen' => $this->mapDocumentosResumen($vehiculo),
            ],
        ]);
    }

    private function mapVehiculoListItem(Vehiculo $vehiculo): array
    {
        $seguroVigente = $this->resolverSeguroVigente($vehiculo->seguros);
        $tarjeta = $vehiculo->documentoTarjetaCirculacionVigente;

        return array_merge($this->mapVehiculo($vehiculo), [
            'asignado' => (bool) $vehiculo->asignacionActual,
            'asignacion_actual' => $vehiculo->asignacionActual ? $this->mapAsignacion($vehiculo->asignacionActual) : null,
            'seguro_vigente' => $seguroVigente,
            'tarjeta_circulacion' => $tarjeta ? $this->mapDocumento($tarjeta) : null,
        ]);
    }

    private function mapVehiculo(Vehiculo $vehiculo): array
    {
        return [
            'id' => (int) $vehiculo->id,
            'marca' => $vehiculo->marca,
            'modelo' => $vehiculo->modelo,
            'anio' => $vehiculo->anio,
            'color' => $vehiculo->color,
            'placas' => $vehiculo->placas,
            'serie' => $vehiculo->serie,
            'tipo' => $vehiculo->tipo,
            'estatus' => $vehiculo->estatus,
            'fecha_registro' => $this->dateString($vehiculo->fecha_registro),
            'foto_url' => $this->storageUrl($vehiculo->foto_principal),
        ];
    }

    private function mapAsignacion(VehiculoEmpleado $asignacion, bool $withFotos = false): array
    {
        $empleado = $asignacion->empleado;
        $data = [
            'id' => (int) $asignacion->id,
            'vehiculo_id' => (int) $asignacion->vehiculo_id,
            'empleado_id' => $asignacion->empleado_id ? (int) $asignacion->empleado_id : null,
            'fecha_asignacion' => optional($asignacion->fecha_asignacion)->format('Y-m-d'),
            'fecha_fin' => optional($asignacion->fecha_fin)->format('Y-m-d'),
            'km_inicial' => $asignacion->km_inicial,
            'km_final' => $asignacion->km_final,
            'notas' => $asignacion->notas,
            'empleado' => $empleado ? [
                'id' => (int) $empleado->id_Empleado,
                'nombre' => trim(($empleado->Nombre ?? '') . ' ' . ($empleado->Apellidos ?? '')),
                'puesto' => $empleado->Puesto,
                'celular' => $empleado->Celular,
            ] : null,
        ];

        if ($withFotos) {
            $asignacion->loadMissing('fotos');
            $data['fotos'] = $asignacion->fotos
                ->map(fn ($foto) => [
                    'id' => (int) $foto->id,
                    'orden' => $foto->orden,
                    'url' => $this->storageUrl($foto->url),
                ])
                ->values();
        }

        return $data;
    }

    private function mapSeguro(Seguro $seguro): array
    {
        $estatus = $this->estatusSeguro($seguro);

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
            'estatus' => $estatus,
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
                'nombre' => trim(($mecanico->Nombre ?? '') . ' ' . ($mecanico->Apellidos ?? '')),
            ] : null,
        ];
    }

    private function mapDocumento($documento): array
    {
        return [
            'id' => (int) $documento->id,
            'tipo' => $documento->tipo,
            'nombre_original' => $documento->nombre_original,
            'archivo_url' => $this->storageUrl($documento->archivo_path),
            'mime_type' => $documento->mime_type,
            'tamano' => $documento->tamano,
            'fecha_documento' => optional($documento->fecha_documento)->format('Y-m-d'),
            'fecha_vencimiento' => optional($documento->fecha_vencimiento)->format('Y-m-d'),
            'vigente' => (bool) $documento->vigente,
            'observaciones' => $documento->observaciones,
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

    private function mapMantenimientosResumen(Vehiculo $vehiculo): array
    {
        $query = $vehiculo->mantenimientos();

        return [
            'total' => (clone $query)->count(),
            'pendiente' => (clone $query)->where('estatus', 'pendiente')->count(),
            'en_proceso' => (clone $query)->where('estatus', 'en_proceso')->count(),
            'completado' => (clone $query)->where('estatus', 'completado')->count(),
            'cancelado' => (clone $query)->where('estatus', 'cancelado')->count(),
        ];
    }

    private function mapDocumentosResumen(Vehiculo $vehiculo): array
    {
        $hoy = now()->startOfDay();
        $limite = $hoy->copy()->addDays(30);
        $docs = $vehiculo->documentos;

        return [
            'total' => $docs->count(),
            'vigentes' => $docs->where('vigente', true)->count(),
            'vencidos' => $docs->filter(fn ($doc) => $doc->fecha_vencimiento && $doc->fecha_vencimiento->lt($hoy))->count(),
            'por_vencer' => $docs->filter(fn ($doc) => $doc->fecha_vencimiento && $doc->fecha_vencimiento->between($hoy, $limite))->count(),
        ];
    }

    private function storageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }
    private function dateString($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}

