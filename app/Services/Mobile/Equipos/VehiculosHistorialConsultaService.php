<?php

namespace App\Services\Mobile\Equipos;

use App\Models\InventarioDocumento;
use App\Models\InventarioDocumentoDetalle;
use App\Models\Mantenimiento;
use App\Models\Seguro;
use App\Models\Vehiculo;
use App\Models\VehiculoEmpleado;
use App\Models\VehiculoEmpleadoKmLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VehiculosHistorialConsultaService
{
    public function __construct(private VehiculosConsultaService $consulta)
    {
    }

    public function asignaciones(Request $request, Vehiculo $vehiculo): array
    {
        $filtros = $this->filtros($request);
        $query = $vehiculo->asignaciones()
            ->with(['empleado:id_Empleado,Nombre,Apellidos,Puesto,Celular', 'fotos'])
            ->orderByDesc('fecha_asignacion')->orderByDesc('id');
        $this->fechas($query, 'fecha_asignacion', $filtros);

        return $this->pagina($query, $vehiculo, $filtros, function (VehiculoEmpleado $asignacion) {
            $data = $this->consulta->mapAsignacion($asignacion, true);
            $data['km_inicial'] = $this->numero($asignacion->km_inicial);
            $data['km_final'] = $this->numero($asignacion->km_final);
            $data['fotos'] = $data['fotos']->map(fn (array $foto) => [
                ...$foto,
                'id' => (int) $foto['id'],
                'orden' => $foto['orden'] === null ? null : (int) $foto['orden'],
            ])->all();

            return $data;
        });
    }

    public function servicios(Request $request, Vehiculo $vehiculo): array
    {
        $filtros = $this->filtros($request, [
            'estatus' => ['sometimes', 'required', Rule::in([
                'borrador', 'orden_generada', 'pendiente', 'en_proceso', 'completado', 'cancelado',
            ])],
        ]);
        $query = $vehiculo->mantenimientos()->with('mecanico:id_Empleado,Nombre,Apellidos')
            ->orderByDesc('fecha_programada')->orderByDesc('id');
        $this->fechas($query, 'fecha_programada', $filtros);
        if (isset($filtros['estatus'])) {
            $query->where('estatus', $filtros['estatus']);
        }

        return $this->pagina($query, $vehiculo, $filtros, function (Mantenimiento $servicio) {
            $data = $this->consulta->mapMantenimiento($servicio);
            foreach (['km_actuales', 'km_proximo_servicio', 'horometro', 'costo_total'] as $campo) {
                $data[$campo] = $this->numero($servicio->{$campo});
            }
            $data['fecha_inicio'] = $servicio->fecha_inicio?->toIso8601String();
            $data['fecha_fin'] = $servicio->fecha_fin?->toIso8601String();

            return $data;
        });
    }

    public function seguros(Request $request, Vehiculo $vehiculo): array
    {
        $filtros = $this->filtros($request, [
            'estatus' => ['sometimes', 'required', Rule::in(['futura', 'vigente', 'vencida', 'cancelada'])],
        ]);
        $query = $vehiculo->seguros()->reorder()->orderByDesc('vigencia_hasta')->orderByDesc('id');
        $this->fechas($query, 'vigencia_hasta', $filtros);
        if (isset($filtros['estatus'])) {
            // Same priority as the existing mapper: cancellation, future, expired, current.
            // Filtering happens before pagination, including policies with missing dates.
            $ahora = now()->toDateTimeString();
            $query->whereRaw("(CASE WHEN estatus = ? THEN ?
                WHEN vigencia_desde > ? THEN ?
                WHEN vigencia_hasta < ? THEN ? ELSE ? END) = ?", [
                'cancelada', 'cancelada', $ahora, 'futura', $ahora, 'vencida', 'vigente', $filtros['estatus'],
            ]);
        }

        return $this->pagina($query, $vehiculo, $filtros,
            fn (Seguro $seguro) => $this->consulta->mapSeguro($seguro));
    }

    public function combustible(Request $request, Vehiculo $vehiculo): array
    {
        $filtros = $this->filtros($request, ['fuente' => ['required', Rule::in(['registros', 'almacen'])]]);

        return $filtros['fuente'] === 'registros'
            ? $this->registrosCombustible($vehiculo, $filtros)
            : $this->salidasAlmacen($vehiculo, $filtros);
    }

    private function registrosCombustible(Vehiculo $vehiculo, array $filtros): array
    {
        $query = VehiculoEmpleadoKmLog::query()
            ->with(['asignacion.empleado:id_Empleado,Nombre,Apellidos', 'obra:id,nombre,clave_obra', 'capturadoPor:id,name'])
            ->where(function ($query) use ($vehiculo) {
                $query->where('vehiculo_id', $vehiculo->id)
                    ->orWhere(function ($legados) use ($vehiculo) {
                        $legados->whereNull('vehiculo_id')
                            ->whereHas('asignacion', fn ($asignacion) => $asignacion->where('vehiculo_id', $vehiculo->id));
                    });
            })
            ->orderByDesc('fecha')->orderByDesc('id');
        $this->fechas($query, 'fecha', $filtros);

        return $this->pagina($query, $vehiculo, $filtros, function (VehiculoEmpleadoKmLog $log) use ($vehiculo) {
            // A conflicting legacy assignment must not expose another vehicle's employee.
            $asignacion = $log->asignacion;
            $empleado = $asignacion && (int) $asignacion->vehiculo_id === (int) $vehiculo->id
                ? $asignacion->empleado : null;

            return [
                'id' => (int) $log->id,
                'vehiculo_id' => (int) $vehiculo->id,
                'vehiculo_empleado_id' => $log->vehiculo_empleado_id === null ? null : (int) $log->vehiculo_empleado_id,
                'origen' => $log->origen,
                'fecha' => $log->fecha?->toIso8601String(),
                'km' => $this->numero($log->km),
                'monto_gasolina' => $this->numero($log->monto_gasolina),
                'notas' => $log->notas,
                'obra' => $this->obra($log->obra),
                'empleado' => $empleado ? [
                    'id' => (int) $empleado->id_Empleado,
                    'nombre' => trim(($empleado->Nombre ?? '').' '.($empleado->Apellidos ?? '')),
                ] : null,
                'capturado_por' => $log->capturadoPor ? [
                    'id' => (int) $log->capturadoPor->id,
                    'nombre' => $log->capturadoPor->name,
                ] : null,
                'foto_url' => $this->storageUrl($log->foto),
                'foto_ticket_gasolina_url' => $this->storageUrl($log->foto_ticket_gasolina),
            ];
        }, ['fuente' => 'registros']);
    }

    private function salidasAlmacen(Vehiculo $vehiculo, array $filtros): array
    {
        $query = InventarioDocumentoDetalle::query()
            ->with(['documento.obra:id,nombre,clave_obra', 'producto:id,nombre,sku'])
            ->where('vehiculo_id', $vehiculo->id)
            ->whereHas('documento', function ($documento) use ($filtros) {
                $documento->where('tipo', 'salida')->where('estado', 'aplicado');
                $this->fechas($documento, 'fecha', $filtros);
            })
            ->orderByDesc(InventarioDocumento::query()->select('fecha')
                ->whereColumn('inventario_documentos.id', 'inventario_documento_detalles.documento_id')->limit(1))
            ->orderByDesc('documento_id')->orderByDesc('id');

        return $this->pagina($query, $vehiculo, $filtros, function (InventarioDocumentoDetalle $detalle) {
            $documento = $detalle->documento;

            return [
                'id' => (int) $detalle->id,
                'vehiculo_id' => (int) $detalle->vehiculo_id,
                'documento' => [
                    'id' => (int) $documento->id,
                    'folio' => $documento->folio,
                    'fecha' => $documento->fecha?->toDateString(),
                    'tipo' => $documento->tipo,
                    'estado' => $documento->estado,
                ],
                'producto' => $detalle->producto ? [
                    'id' => (int) $detalle->producto->id,
                    'nombre' => $detalle->producto->nombre,
                    'sku' => $detalle->producto->sku,
                ] : null,
                'cantidad' => $this->numero($detalle->cantidad),
                'costo_unitario' => $this->numero($detalle->costo_unitario),
                'obra' => $this->obra($documento->obra),
                'notas' => $detalle->notas,
            ];
        }, ['fuente' => 'almacen']);
    }

    private function filtros(Request $request, array $extras = []): array
    {
        $reglas = [
            'page' => ['sometimes', 'required', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:50'],
            'desde' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'hasta' => ['sometimes', 'required', 'date_format:Y-m-d'],
        ];
        if ($request->query('desde') !== null) {
            $reglas['hasta'][] = 'after_or_equal:desde';
        }

        return Validator::make($request->query(), [...$reglas, ...$extras])->validate();
    }

    private function fechas($query, string $columna, array $filtros): void
    {
        if (isset($filtros['desde'])) {
            $query->whereDate($columna, '>=', $filtros['desde']);
        }
        if (isset($filtros['hasta'])) {
            $query->whereDate($columna, '<=', $filtros['hasta']);
        }
    }

    private function pagina($query, Vehiculo $vehiculo, array $filtros, callable $mapper, array $meta = []): array
    {
        $pagina = $query->paginate((int) ($filtros['per_page'] ?? 20), ['*'], 'page', (int) ($filtros['page'] ?? 1));

        return [
            'ok' => true,
            'data' => $pagina->getCollection()->map($mapper)->values()->all(),
            'meta' => [
                'vehiculo_id' => (int) $vehiculo->id,
                'current_page' => $pagina->currentPage(),
                'last_page' => $pagina->lastPage(),
                'per_page' => $pagina->perPage(),
                'total' => $pagina->total(),
                ...$meta,
            ],
        ];
    }

    private function numero($valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }

    private function obra($obra): ?array
    {
        return $obra ? ['id' => (int) $obra->id, 'nombre' => $obra->nombre, 'clave_obra' => $obra->clave_obra] : null;
    }

    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path : Storage::disk('public')->url(ltrim($path, '/'));
    }
}
