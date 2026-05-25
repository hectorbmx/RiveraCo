<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\ObraPlaneacionGasto;
use App\Models\ObraReposicionGasto;
use App\Models\ObraReposicionGastoDetalle;
use App\Models\SatCfdi;
use App\Models\UsuarioApp;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResidenteReposicionGastoController extends Controller
{
    public function index(Request $request)
    {
        $obra = $this->obraActivaResidente($request);

        $reposiciones = ObraReposicionGasto::query()
            ->with([
                'partida',
                'detalles.partida',
                'solicitadoPor:id,name,email',
                'revisadoPor:id,name,email',
                'aprobadoPor:id,name,email',
                'pagadoPor:id,name,email',
                'aprovisionadoPor:id,name,email',
            ])
            ->where('obra_id', $obra->id)
            ->latest()
            ->get();

        return response()->json([
            'ok' => true,
            'obra' => $this->mapObra($obra),
            'stats' => $this->stats($reposiciones),
            'montos' => $this->montos($reposiciones),
            'data' => $reposiciones->map(fn ($reposicion) => $this->mapReposicion($reposicion, true))->values(),
        ]);
    }

    public function catalogo(Request $request)
    {
        $obra = $this->obraActivaResidente($request);

        return response()->json([
            'ok' => true,
            'obra' => $this->mapObra($obra),
            'data' => $this->partidasBase($obra)->map(fn ($partida) => $this->mapPartida($partida))->values(),
        ]);
    }

    public function buscarCfdis(Request $request)
    {
        $this->obraActivaResidente($request);

        $request->validate([
            'rfc_emisor' => ['nullable', 'string', 'max:20'],
            'fecha' => ['nullable', 'date'],
            'monto' => ['nullable', 'numeric', 'min:0'],
            'uuid4' => ['nullable', 'string', 'max:4'],
        ]);

        $rfcEmpresa = 'RCO820921T66';

        $cfdis = SatCfdi::query()
            ->where('rfc_emisor', '!=', $rfcEmpresa)
            ->where('rfc_receptor', $rfcEmpresa)
            ->when($request->rfc_emisor, function ($query, $rfcEmisor) {
                $query->where('rfc_emisor', 'like', '%' . trim($rfcEmisor) . '%');
            })
            ->when($request->fecha, function ($query, $fecha) {
                $query->whereDate('fecha_emision', $fecha);
            })
            ->when($request->monto, function ($query, $monto) {
                $query->where('total', (float) $monto);
            })
            ->when($request->uuid4, function ($query, $uuid4) {
                $query->where('uuid', 'like', '%' . trim($uuid4));
            })
            ->latest('fecha_emision')
            ->limit(20)
            ->get([
                'id',
                'uuid',
                'fecha_emision',
                'rfc_emisor',
                'emisor_nombre',
                'rfc_receptor',
                'receptor_nombre',
                'subtotal',
                'total',
                'moneda',
                'metodo_pago',
                'forma_pago',
            ]);

        return response()->json([
            'ok' => true,
            'data' => $cfdis->map(fn ($cfdi) => $this->mapCfdi($cfdi))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $obra = $this->obraActivaResidente($request);
        $partidaIds = $this->partidasBase($obra)->pluck('id')->values()->all();

        $data = $request->validate([
            'tipo_reposicion' => ['required', Rule::in(['caja_chica', 'viaticos', 'gastos_varios'])],
            'partida_id' => ['required', 'integer', Rule::in($partidaIds)],
            'semana' => ['required', 'string', 'max:20'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'conceptos' => ['required', 'array', 'min:1'],
            'conceptos.*.tipo' => ['required', 'string', 'max:120'],
            'conceptos.*.descripcion' => ['nullable', 'string', 'max:500'],
            'conceptos.*.proveedor' => ['nullable', 'string', 'max:255'],
            'conceptos.*.rfc' => ['nullable', 'string', 'max:20'],
            'conceptos.*.uuid' => ['nullable', 'string', 'max:80'],
            'conceptos.*.fecha' => ['nullable', 'date'],
            'conceptos.*.monto' => ['required', 'numeric', 'min:0.01'],
            'conceptos.*.sat_cfdi_id' => ['nullable', 'integer', 'exists:sat_cfdis,id'],
            'conceptos.*.partida_id' => ['nullable', 'integer', Rule::in($partidaIds)],
        ]);

        if ($data['tipo_reposicion'] === 'caja_chica') {
            $sinCfdi = collect($data['conceptos'])->contains(fn ($concepto) => empty($concepto['sat_cfdi_id']));

            if ($sinCfdi) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Caja chica requiere CFDI relacionado en cada concepto.',
                    'errors' => [
                        'conceptos' => ['Caja chica requiere CFDI relacionado en cada concepto.'],
                    ],
                ], 422);
            }
        }

        $reposicion = DB::transaction(function () use ($request, $obra, $data) {
            $reposicion = ObraReposicionGasto::create([
                'obra_id' => $obra->id,
                'tipo_reposicion' => $data['tipo_reposicion'],
                'partida_id' => $data['partida_id'],
                'semana' => $data['semana'],
                'estatus' => 'solicitado',
                'observaciones' => $data['observaciones'] ?? null,
                'solicitado_por' => $request->user()->id,
                'solicitado_at' => now(),
                'total' => collect($data['conceptos'])->sum('monto'),
            ]);

            foreach ($data['conceptos'] as $concepto) {
                ObraReposicionGastoDetalle::create([
                    'obra_reposicion_gasto_id' => $reposicion->id,
                    'tipo' => $concepto['tipo'] ?? null,
                    'descripcion' => $concepto['descripcion'] ?? null,
                    'proveedor' => $concepto['proveedor'] ?? null,
                    'rfc' => $concepto['rfc'] ?? null,
                    'uuid' => $concepto['uuid'] ?? null,
                    'fecha' => $concepto['fecha'] ?? null,
                    'monto' => $concepto['monto'] ?? 0,
                    'sat_cfdi_id' => $concepto['sat_cfdi_id'] ?? null,
                    'partida_id' => $concepto['partida_id'] ?? $data['partida_id'],
                ]);
            }

            return $reposicion->load(['partida', 'detalles.partida', 'solicitadoPor:id,name,email']);
        });

        return response()->json([
            'ok' => true,
            'message' => 'Reposicion registrada correctamente.',
            'data' => $this->mapReposicion($reposicion, true),
        ], 201);
    }

    public function show(Request $request, ObraReposicionGasto $reposicion)
    {
        $obra = $this->obraActivaResidente($request);

        abort_if((int) $reposicion->obra_id !== (int) $obra->id, 404);

        $reposicion->load([
            'partida',
            'detalles.partida',
            'detalles.cfdi',
            'solicitadoPor:id,name,email',
            'revisadoPor:id,name,email',
            'aprobadoPor:id,name,email',
            'pagadoPor:id,name,email',
            'aprovisionadoPor:id,name,email',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->mapReposicion($reposicion, true),
        ]);
    }

    private function obraActivaResidente(Request $request): Obra
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('residente')) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Solo el perfil residente puede usar este modulo.',
            ], 403));
        }

        $usuarioApp = UsuarioApp::where('user_id', $user->id)->first();

        if (!$usuarioApp || !$usuarioApp->is_active) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'Este usuario no esta habilitado para la app.',
            ], 403));
        }

        $asignacion = ObraEmpleado::query()
            ->select('id', 'obra_id', 'empleado_id', 'rol_id')
            ->where('empleado_id', $usuarioApp->empleado_id)
            ->where('activo', 1)
            ->whereNull('fecha_baja')
            ->latest('id')
            ->first();

        if (!$asignacion) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'No tienes una obra activa asignada.',
            ], 403));
        }

        $obra = Obra::query()
            ->with(['cliente:id,nombre_comercial'])
            ->where('id', $asignacion->obra_id)
            ->first();

        if (!$obra) {
            throw new HttpResponseException(response()->json([
                'ok' => false,
                'message' => 'No tienes una obra activa asignada.',
            ], 403));
        }

        return $obra;
    }

    private function partidasBase(Obra $obra)
    {
        $presupuestoIds = $obra->presupuestos_vinculados()->pluck('presupuestos.id');

        return ObraPlaneacionGasto::query()
            ->where(function ($query) use ($obra, $presupuestoIds) {
                $query->where('obra_id', $obra->id)
                    ->orWhereIn('presupuesto_id', $presupuestoIds);
            })
            ->where('numero_semana', 0)
            ->orderBy('partida')
            ->orderBy('concepto')
            ->get()
            ->unique(function ($partida) {
                return mb_strtoupper(trim((string) $partida->partida)) . '|' . mb_strtoupper(trim((string) $partida->concepto));
            })
            ->values();
    }

    private function stats($reposiciones): array
    {
        return [
            'total' => $reposiciones->count(),
            'solicitadas' => $reposiciones->where('estatus', 'solicitado')->count(),
            'en_revision' => $reposiciones->whereIn('estatus', [
                'en_revision_area',
                'programado_area',
                'en_revision_admin',
                'pendiente_autorizacion',
            ])->count(),
            'autorizadas' => $reposiciones->whereIn('estatus', [
                'autorizado',
                'pagado',
                'cerrado',
            ])->count(),
        ];
    }

    private function montos($reposiciones): array
    {
        return [
            'solicitado' => (float) $reposiciones->whereIn('estatus', [
                'solicitado',
                'en_revision_area',
                'programado_area',
                'en_revision_admin',
                'pendiente_autorizacion',
            ])->sum('total'),
            'autorizado' => (float) $reposiciones->whereIn('estatus', [
                'autorizado',
                'pagado',
                'cerrado',
            ])->sum('total'),
            'pagado' => (float) $reposiciones->whereIn('estatus', [
                'pagado',
                'cerrado',
            ])->sum('total'),
        ];
    }

    private function mapReposicion(ObraReposicionGasto $reposicion, bool $includeDetalles = false): array
    {
        $data = [
            'id' => $reposicion->id,
            'folio' => 'REP-' . str_pad((string) $reposicion->id, 5, '0', STR_PAD_LEFT),
            'tipo_reposicion' => $reposicion->tipo_reposicion,
            'tipo_label' => $this->tipoLabel($reposicion->tipo_reposicion),
            'semana' => $reposicion->semana,
            'estatus' => $reposicion->estatus,
            'estatus_label' => $this->estatusLabel($reposicion->estatus),
            'observaciones' => $reposicion->observaciones,
            'total' => (float) $reposicion->total,
            'detalles_count' => $reposicion->detalles_count ?? $reposicion->detalles->count(),
            'partida' => $reposicion->partida ? $this->mapPartida($reposicion->partida) : null,
            'solicitado_at' => optional($reposicion->solicitado_at)->toDateTimeString(),
            'revisado_at' => optional($reposicion->revisado_at)->toDateTimeString(),
            'aprovisionado_at' => optional($reposicion->aprovisionado_at)->toDateTimeString(),
            'aprobado_at' => optional($reposicion->aprobado_at)->toDateTimeString(),
            'pagado_at' => optional($reposicion->pagado_at)->toDateTimeString(),
            'fecha_programada_pago' => optional($reposicion->fecha_programada_pago)->toDateString(),
            'fecha_salida_programada' => optional($reposicion->fecha_salida_programada)->toDateString(),
            'solicitado_por' => $this->mapUser($reposicion->solicitadoPor),
            'revisado_por' => $this->mapUser($reposicion->revisadoPor),
            'aprovisionado_por' => $this->mapUser($reposicion->aprovisionadoPor),
            'aprobado_por' => $this->mapUser($reposicion->aprobadoPor),
            'pagado_por' => $this->mapUser($reposicion->pagadoPor),
        ];

        if ($includeDetalles) {
            $data['detalles'] = $reposicion->detalles->map(fn ($detalle) => [
                'id' => $detalle->id,
                'tipo' => $detalle->tipo,
                'descripcion' => $detalle->descripcion,
                'proveedor' => $detalle->proveedor,
                'rfc' => $detalle->rfc,
                'uuid' => $detalle->uuid,
                'fecha' => optional($detalle->fecha)->toDateString(),
                'monto' => (float) $detalle->monto,
                'sat_cfdi_id' => $detalle->sat_cfdi_id,
                'partida' => $detalle->partida ? $this->mapPartida($detalle->partida) : null,
            ])->values();
        }

        return $data;
    }

    private function mapPartida(ObraPlaneacionGasto $partida): array
    {
        return [
            'id' => $partida->id,
            'partida' => $partida->partida,
            'concepto' => $partida->concepto,
            'unidad' => $partida->unidad,
            'cantidad' => $partida->cantidad !== null ? (float) $partida->cantidad : null,
            'precio_unitario' => $partida->precio_unitario !== null ? (float) $partida->precio_unitario : null,
            'monto_programado' => $partida->monto_programado !== null ? (float) $partida->monto_programado : null,
            'numero_semana' => $partida->numero_semana,
        ];
    }

    private function mapCfdi(SatCfdi $cfdi): array
    {
        return [
            'id' => $cfdi->id,
            'uuid' => $cfdi->uuid,
            'uuid_corto' => $cfdi->uuid ? substr($cfdi->uuid, -4) : null,
            'fecha' => optional($cfdi->fecha_emision)->format('Y-m-d'),
            'fecha_formateada' => optional($cfdi->fecha_emision)->format('d/m/Y'),
            'rfc_emisor' => $cfdi->rfc_emisor,
            'emisor_nombre' => $cfdi->emisor_nombre,
            'rfc_receptor' => $cfdi->rfc_receptor,
            'receptor_nombre' => $cfdi->receptor_nombre,
            'subtotal' => (float) $cfdi->subtotal,
            'total' => (float) $cfdi->total,
            'moneda' => $cfdi->moneda ?? 'MXN',
            'metodo_pago' => $cfdi->metodo_pago,
            'forma_pago' => $cfdi->forma_pago,
        ];
    }

    private function mapObra(Obra $obra): array
    {
        return [
            'id' => $obra->id,
            'nombre' => $obra->nombre,
            'clave_obra' => $obra->clave_obra,
            'cliente_nombre' => $obra->cliente?->nombre_comercial,
        ];
    }

    private function mapUser($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    private function tipoLabel(?string $tipo): string
    {
        return match ($tipo) {
            'caja_chica' => 'Caja chica',
            'viaticos' => 'Viaticos',
            'gastos_varios' => 'Gastos varios',
            default => 'Reposicion',
        };
    }

    private function estatusLabel(?string $estatus): string
    {
        return match ($estatus) {
            'solicitado' => 'Solicitado',
            'en_revision_area' => 'Revision area',
            'programado_area' => 'Programado area',
            'en_revision_admin' => 'Revision admin',
            'pendiente_autorizacion' => 'Pendiente autorizacion',
            'autorizado' => 'Autorizado',
            'pagado' => 'Pagado',
            'cerrado' => 'Cerrado',
            'rechazado' => 'Rechazado',
            default => ucfirst((string) $estatus),
        };
    }
}
