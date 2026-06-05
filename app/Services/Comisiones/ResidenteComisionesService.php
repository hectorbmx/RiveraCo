<?php

namespace App\Services\Comisiones;

use App\Models\Comision;
use App\Models\ComisionEtapa;
use App\Models\ComisionEtapaFoto;
use App\Models\ComisionEtapaPersonal;
use App\Models\ComisionTarifario;
use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\ObraPila;
use App\Models\User;
use App\Models\UsuarioApp;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class ResidenteComisionesService
{
    private const ETAPAS = [
        ComisionEtapa::ETAPA_PERFORACION => 1,
        ComisionEtapa::ETAPA_BENTONITA => 2,
        ComisionEtapa::ETAPA_ADEME => 3,
        ComisionEtapa::ETAPA_ACERO => 4,
        ComisionEtapa::ETAPA_COLADO => 5,
    ];

    private const ETAPAS_OPCIONALES = [
        ComisionEtapa::ETAPA_BENTONITA,
        ComisionEtapa::ETAPA_ADEME,
    ];

    private const PRODUCCION_POR_ETAPA = [
        ComisionEtapa::ETAPA_PERFORACION => [
            'campo' => 'metros_comision',
            'detalle' => 'metros_sujetos_comision',
            'label' => 'Metros comisión',
            'unidad' => 'm lineales',
        ],
        ComisionEtapa::ETAPA_BENTONITA => [
            'campo' => 'vol_bentonita',
            'detalle' => 'vol_bentonita',
            'label' => 'Bentonita usada',
            'unidad' => 'm3',
        ],
        ComisionEtapa::ETAPA_ADEME => [
            'campo' => 'ml_ademe_bauer',
            'detalle' => 'ml_ademe_bauer',
            'label' => 'Ademe colocado',
            'unidad' => 'm lineales',
        ],
        ComisionEtapa::ETAPA_ACERO => [
            'campo' => 'kg_acero',
            'detalle' => 'kg_acero',
            'label' => 'Acero colocado',
            'unidad' => 'kg',
        ],
        ComisionEtapa::ETAPA_COLADO => [
            'campo' => 'vol_concreto',
            'detalle' => 'vol_concreto',
            'label' => 'Concreto colado',
            'unidad' => 'm3',
        ],
    ];

    public function indexForUser(User $user): array
    {
        $obra = $this->obraActiva($user);

        $comisiones = Comision::query()
            ->with([
                'pila',
                'etapas' => fn ($query) => $query
                    ->withCount(['fotos', 'personal'])
                    ->with(['fotos'])
                    ->orderBy('orden')
                    ->orderBy('id'),
            ])
            ->where('obra_id', $obra->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $pendientes = $comisiones
            ->filter(fn (Comision $comision) => ! in_array($comision->estado, ['cerrada', 'cancelada'], true))
            ->map(fn (Comision $comision) => $this->mapComision($comision))
            ->values();

        $cerradas = $comisiones
            ->filter(fn (Comision $comision) => $comision->estado === 'cerrada')
            ->map(fn (Comision $comision) => $this->mapComision($comision))
            ->values();

        $canceladas = $comisiones
            ->filter(fn (Comision $comision) => $comision->estado === 'cancelada')
            ->map(fn (Comision $comision) => $this->mapComision($comision))
            ->values();

        return [
            'obra' => $this->mapObra($obra),
            'resumen' => [
                'pendientes' => $pendientes->count(),
                'cerradas' => $cerradas->count(),
                'canceladas' => $canceladas->count(),
                'total' => $comisiones->count(),
            ],
            'pendientes' => $pendientes,
            'cerradas' => $cerradas,
            'canceladas' => $canceladas,
        ];
    }

    public function showForUser(User $user, Comision $comision): array
    {
        $obra = $this->obraActiva($user);

        if ((int) $comision->obra_id !== (int) $obra->id) {
            throw new AuthorizationException('La comision no pertenece a tu obra activa.');
        }

        $comision->load([
            'pila',
            'etapas' => fn ($query) => $query
                ->withCount(['fotos', 'personal'])
                ->with([
                    'fotos',
                    'personal.asignacionEmpleado.empleado',
                    'personal.rol',
                    'personal.actividad',
                ])
                ->orderBy('orden')
                ->orderBy('id'),
            'detalles',
        ]);

        return [
            'obra' => $this->mapObra($obra),
            'comision' => $this->mapComision($comision, true),
        ];
    }

    public function createForUser(User $user, array $data): array
    {
        $obra = $this->obraActiva($user);
        $this->validarPilaDeObra($obra, (int) $data['pila_id']);

        $comision = DB::transaction(function () use ($user, $obra, $data) {
            $tarifario = ComisionTarifario::query()
                ->where('estado', 'vigente')
                ->orderByDesc('vigente_desde')
                ->first()
                ?? ComisionTarifario::query()->orderByDesc('id')->first();

            $comision = Comision::create([
                'obra_id' => $obra->id,
                'pila_id' => (int) $data['pila_id'],
                'fecha' => $data['fecha'],
                'estado' => 'pendiente',
                'residente_id' => $this->empleadoIdApp($user),
                'numero_formato' => $data['numero_formato'] ?? null,
                'cliente_nombre' => $data['cliente_nombre'] ?? $obra->cliente?->nombre_comercial,
                'observaciones' => $data['observaciones'] ?? null,
                'trabajo_id' => $data['trabajo_id'] ?? null,
                'tarifario_id' => $tarifario?->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $opcionales = $data['opcionales'] ?? [];
            foreach (self::ETAPAS as $etapa => $orden) {
                $esOpcional = $this->esEtapaOpcional($etapa);
                $aplica = ! $esOpcional || (bool) ($opcionales[$etapa] ?? false);

                $comision->etapas()->create([
                    'obra_id' => $obra->id,
                    'pila_id' => (int) $data['pila_id'],
                    'etapa' => $etapa,
                    'estado' => $aplica ? ComisionEtapa::ESTADO_PENDIENTE : ComisionEtapa::ESTADO_NO_APLICA,
                    'orden' => $orden,
                    'requiere_foto' => $aplica,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            if (! empty($data['perforacion'])) {
                $etapa = $comision->etapas()
                    ->where('etapa', ComisionEtapa::ETAPA_PERFORACION)
                    ->firstOrFail();

                $this->guardarEtapa($user, $obra, $comision, $etapa, $data['perforacion']);
            }

            return $comision;
        });

        return $this->showForUser($user, $comision)['comision'];
    }

    public function updateEtapaForUser(User $user, Comision $comision, string $etapaKey, array $data): array
    {
        $obra = $this->obraActiva($user);
        $this->validarComisionDeObra($obra, $comision);

        if (! array_key_exists($etapaKey, self::ETAPAS)) {
            throw ValidationException::withMessages([
                'etapa' => ['La etapa indicada no es valida.'],
            ]);
        }

        $etapa = $comision->etapas()
            ->where('etapa', $etapaKey)
            ->firstOrFail();

        DB::transaction(function () use ($user, $obra, $comision, $etapa, $data) {
            $this->validarCambioEtapa($comision, $etapa, $data);
            $this->guardarEtapa($user, $obra, $comision, $etapa, $data);
            $this->actualizarEstadoComision($comision);
        });

        return $this->showForUser($user, $comision->fresh())['comision'];
    }

    public function storeFotoForUser(User $user, Comision $comision, string $etapaKey, UploadedFile $foto, ?string $comentario = null): array
    {
        $obra = $this->obraActiva($user);
        $this->validarComisionDeObra($obra, $comision);

        if (! array_key_exists($etapaKey, self::ETAPAS)) {
            throw ValidationException::withMessages([
                'etapa' => ['La etapa indicada no es valida.'],
            ]);
        }

        $etapa = $comision->etapas()
            ->where('etapa', $etapaKey)
            ->firstOrFail();

        $imageManager = ImageManager::gd();
        $imagen = $imageManager->read($foto->getRealPath())
            ->scaleDown(width: 1600)
            ->toWebp(quality: 72);

        $path = 'comisiones/'
            . $comision->id
            . '/'
            . $etapaKey
            . '/'
            . Str::uuid()
            . '.webp';

        Storage::disk('public')->put($path, $imagen);

        $fotoModel = ComisionEtapaFoto::create([
            'comision_etapa_id' => $etapa->id,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => 'image/webp',
            'size' => strlen((string) $imagen),
            'comentario' => $comentario,
            'uploaded_by' => $user->id,
        ]);

        return [
            'id' => (int) $fotoModel->id,
            'disk' => $fotoModel->disk,
            'path' => $fotoModel->path,
            'url' => Storage::disk($fotoModel->disk)->url($fotoModel->path),
            'mime_type' => $fotoModel->mime_type,
            'size' => $fotoModel->size,
            'comentario' => $fotoModel->comentario,
        ];
    }

    private function obraActiva(User $user): Obra
    {
        $usuarioApp = UsuarioApp::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $usuarioApp) {
            throw new AuthorizationException('Este usuario no esta habilitado para la app.');
        }

        $asignacion = ObraEmpleado::query()
            ->with('obra.cliente:id,nombre_comercial')
            ->where('empleado_id', $usuarioApp->empleado_id)
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->latest('id')
            ->first();

        if (! $asignacion || ! $asignacion->obra) {
            throw new AuthorizationException('No tienes una obra activa asignada.');
        }

        return $asignacion->obra;
    }

    private function empleadoIdApp(User $user): ?int
    {
        return UsuarioApp::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->value('empleado_id');
    }

    private function validarPilaDeObra(Obra $obra, int $pilaId): void
    {
        $existe = ObraPila::query()
            ->where('obra_id', $obra->id)
            ->where('id', $pilaId)
            ->exists();

        if (! $existe) {
            throw ValidationException::withMessages([
                'pila_id' => ['La pila no pertenece a tu obra activa.'],
            ]);
        }
    }

    private function validarComisionDeObra(Obra $obra, Comision $comision): void
    {
        if ((int) $comision->obra_id !== (int) $obra->id) {
            throw new AuthorizationException('La comision no pertenece a tu obra activa.');
        }
    }

    private function guardarEtapa(User $user, Obra $obra, Comision $comision, ComisionEtapa $etapa, array $data): void
    {
        $estado = $data['estado'] ?? null;

        if (! $estado) {
            $estado = ! empty($data['hora_fin'])
                ? ComisionEtapa::ESTADO_COMPLETADA
                : ComisionEtapa::ESTADO_EN_PROCESO;
        }

        $etapa->fill([
            'hora_inicio' => $data['hora_inicio'] ?? $etapa->hora_inicio,
            'hora_fin' => $data['hora_fin'] ?? $etapa->hora_fin,
            'observaciones' => array_key_exists('observaciones', $data) ? $data['observaciones'] : $etapa->observaciones,
            'estado' => $estado,
            'requiere_foto' => array_key_exists('requiere_foto', $data) ? (bool) $data['requiere_foto'] : $etapa->requiere_foto,
            'completada_at' => $estado === ComisionEtapa::ESTADO_COMPLETADA ? now() : null,
            'updated_by' => $user->id,
        ]);
        $etapa->save();

        if (array_key_exists('personal', $data)) {
            $this->syncPersonalEtapa($obra, $etapa, $data['personal'] ?? []);
        }

        $this->syncDetalleProduccion($comision, $data);

        $comision->forceFill([
            'estado' => 'pendiente',
            'updated_by' => $user->id,
        ])->save();
    }

    private function syncDetalleProduccion(Comision $comision, array $data): void
    {
        $camposDetalle = [
            'diametro',
            'cantidad',
            'profundidad',
            'metros_comision',
            'kg_acero',
            'vol_bentonita',
            'vol_concreto',
            'ml_ademe_bauer',
            'campana_pzas',
            'adicional',
        ];

        $tieneDetalle = collect($camposDetalle)->contains(fn ($campo) => array_key_exists($campo, $data));

        if (! $tieneDetalle) {
            return;
        }

        $detalle = $comision->detalles()->firstOrNew([]);
        $detalle->fill([
            'diametro' => array_key_exists('diametro', $data) ? (string) $data['diametro'] : $detalle->diametro,
            'cantidad' => array_key_exists('cantidad', $data) ? (int) ($data['cantidad'] ?? 0) : ($detalle->cantidad ?? 1),
            'profundidad' => array_key_exists('profundidad', $data) ? (float) ($data['profundidad'] ?? 0) : $detalle->profundidad,
            'metros_sujetos_comision' => array_key_exists('metros_comision', $data) ? (float) ($data['metros_comision'] ?? 0) : $detalle->metros_sujetos_comision,
            'kg_acero' => array_key_exists('kg_acero', $data) ? (float) ($data['kg_acero'] ?? 0) : $detalle->kg_acero,
            'vol_bentonita' => array_key_exists('vol_bentonita', $data) ? (float) ($data['vol_bentonita'] ?? 0) : $detalle->vol_bentonita,
            'vol_concreto' => array_key_exists('vol_concreto', $data) ? (float) ($data['vol_concreto'] ?? 0) : $detalle->vol_concreto,
            'ml_ademe_bauer' => array_key_exists('ml_ademe_bauer', $data) ? (float) ($data['ml_ademe_bauer'] ?? 0) : $detalle->ml_ademe_bauer,
            'campana_pzas' => array_key_exists('campana_pzas', $data) ? (int) ($data['campana_pzas'] ?? 0) : $detalle->campana_pzas,
            'adicional' => array_key_exists('adicional', $data) ? $data['adicional'] : $detalle->adicional,
        ]);
        $detalle->save();
    }

    private function syncPersonalEtapa(Obra $obra, ComisionEtapa $etapa, array $personal): void
    {
        $ids = collect($personal)
            ->pluck('obra_empleado_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $asignaciones = ObraEmpleado::query()
            ->where('obra_id', $obra->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $idsValidos = [];

        foreach ($personal as $row) {
            $obraEmpleadoId = (int) ($row['obra_empleado_id'] ?? 0);
            $asignacion = $asignaciones->get($obraEmpleadoId);

            if (! $asignacion) {
                continue;
            }

            $idsValidos[] = $obraEmpleadoId;

            ComisionEtapaPersonal::updateOrCreate(
                [
                    'comision_etapa_id' => $etapa->id,
                    'obra_empleado_id' => $obraEmpleadoId,
                ],
                [
                    'empleado_id' => $asignacion->empleado_id,
                    'rol_id' => $asignacion->rol_id,
                    'actividad_id' => $row['actividad_id'] ?? null,
                    'comisiona' => (bool) ($row['comisiona'] ?? true),
                    'importe_comision' => (float) ($row['importe_comision'] ?? 0),
                    'notas' => $row['notas'] ?? null,
                ]
            );
        }

        $etapa->personal()
            ->whereNotIn('obra_empleado_id', $idsValidos)
            ->delete();
    }

    private function actualizarEstadoComision(Comision $comision): void
    {
        $etapas = $comision->etapas()->get();
        $aplicables = $etapas->where('estado', '!=', ComisionEtapa::ESTADO_NO_APLICA);

        if ($aplicables->isNotEmpty() && $aplicables->every(fn ($etapa) => $etapa->estado === ComisionEtapa::ESTADO_COMPLETADA)) {
            $comision->forceFill([
                'estado' => 'cerrada',
                'cerrada_at' => now(),
            ])->save();
        }
    }

    private function mapComision(Comision $comision, bool $detallado = false): array
    {
        $etapas = $comision->etapas instanceof EloquentCollection
            ? $comision->etapas
            : collect();

        $etapas->each(fn (ComisionEtapa $etapa) => $etapa->setRelation('comision', $comision));

        $payload = [
            'id' => (int) $comision->id,
            'fecha' => optional($comision->fecha)->toDateString(),
            'estado' => $comision->estado ?? 'cerrada',
            'numero_formato' => $comision->numero_formato,
            'cliente_nombre' => $comision->cliente_nombre,
            'observaciones' => $comision->observaciones,
            'pila' => $this->mapPila($comision),
            'avance' => $this->avance($etapas),
            'siguiente_etapa' => $this->siguienteEtapa($etapas),
            'estatus_visual' => $this->estatusVisual($comision, $etapas),
            'etapas' => $etapas->map(fn (ComisionEtapa $etapa) => $this->mapEtapa($etapa, $detallado))->values(),
            'cerrada_at' => optional($comision->cerrada_at)->toIso8601String(),
            'cancelada_at' => optional($comision->cancelada_at)->toIso8601String(),
            'created_at' => optional($comision->created_at)->toIso8601String(),
            'updated_at' => optional($comision->updated_at)->toIso8601String(),
        ];

        if ($detallado) {
            $payload['personal_legacy_count'] = $comision->personales()->count();
            $payload['detalles_legacy_count'] = $comision->detalles()->count();
            $payload['perforaciones_legacy_count'] = $comision->perforaciones()->count();
        }

        return $payload;
    }

    private function mapObra(Obra $obra): array
    {
        return [
            'id' => (int) $obra->id,
            'nombre' => $obra->nombre,
            'clave_obra' => $obra->clave_obra,
            'ubicacion' => $obra->ubicacion,
            'cliente_nombre' => $obra->cliente?->nombre_comercial,
            'estatus_nuevo' => $obra->estatus_nuevo,
        ];
    }

    private function mapPila(Comision $comision): ?array
    {
        $pila = $comision->pila;

        if (! $pila) {
            return null;
        }

        return [
            'id' => (int) $pila->id,
            'numero_pila' => $pila->numero_pila ?? null,
            'tipo' => $pila->tipo ?? null,
            'diametro' => $pila->diametro_proyecto !== null ? (float) $pila->diametro_proyecto : null,
            'profundidad' => $pila->profundidad_proyecto !== null ? (float) $pila->profundidad_proyecto : null,
            'ubicacion' => $pila->ubicacion ?? null,
        ];
    }

    private function mapEtapa(ComisionEtapa $etapa, bool $detallado = false): array
    {
        $payload = [
            'id' => (int) $etapa->id,
            'etapa' => $etapa->etapa,
            'estado' => $etapa->estado,
            'orden' => (int) $etapa->orden,
            'es_opcional' => $this->esEtapaOpcional($etapa->etapa),
            'puede_activar' => $this->puedeActivarEtapa($etapa),
            'puede_registrar' => $this->puedeRegistrarEtapa($etapa),
            'accion' => $this->accionEtapa($etapa),
            'hora_inicio' => $this->hora($etapa->hora_inicio),
            'hora_fin' => $this->hora($etapa->hora_fin),
            'observaciones' => $etapa->observaciones,
            'requiere_foto' => (bool) $etapa->requiere_foto,
            'fotos_count' => (int) ($etapa->fotos_count ?? $etapa->fotos->count()),
            'personal_count' => (int) ($etapa->personal_count ?? $etapa->personal->count()),
            'completada_at' => optional($etapa->completada_at)->toIso8601String(),
        ];

        if ($detallado) {
            $payload['produccion'] = $this->produccionEtapa($etapa);

            $payload['fotos'] = $etapa->fotos
                ->map(fn ($foto) => [
                    'id' => (int) $foto->id,
                    'disk' => $foto->disk,
                    'path' => $foto->path,
                    'url' => $foto->path ? Storage::disk($foto->disk ?? 'public')->url($foto->path) : null,
                    'mime_type' => $foto->mime_type,
                    'size' => $foto->size !== null ? (int) $foto->size : null,
                    'comentario' => $foto->comentario,
                    'created_at' => optional($foto->created_at)->toIso8601String(),
                ])
                ->values();

            $payload['personal'] = $etapa->personal
                ->map(fn ($personal) => [
                    'id' => (int) $personal->id,
                    'obra_empleado_id' => (int) $personal->obra_empleado_id,
                    'empleado_id' => $personal->empleado_id !== null ? (int) $personal->empleado_id : null,
                    'empleado_nombre' => $this->nombreEmpleado($personal->asignacionEmpleado?->empleado),
                    'rol_id' => $personal->rol_id !== null ? (int) $personal->rol_id : null,
                    'rol_nombre' => $personal->rol?->nombre,
                    'actividad_id' => $personal->actividad_id !== null ? (int) $personal->actividad_id : null,
                    'actividad_nombre' => $personal->actividad?->nombre,
                    'comisiona' => (bool) $personal->comisiona,
                    'importe_comision' => (float) $personal->importe_comision,
                    'notas' => $personal->notas,
                ])
                ->values();
        }

        return $payload;
    }

    private function produccionEtapa(ComisionEtapa $etapa): ?array
    {
        $config = self::PRODUCCION_POR_ETAPA[$etapa->etapa] ?? null;

        if (! $config) {
            return null;
        }

        $detalle = $config['detalle'];
        $valor = $etapa->comision?->detalles?->sum($detalle) ?? 0;

        return [
            'campo' => $config['campo'],
            'detalle' => $detalle,
            'label' => $config['label'],
            'unidad' => $config['unidad'],
            'valor' => (float) $valor,
            'input' => [
                'type' => 'number',
                'step' => '0.01',
                'min' => 0,
            ],
        ];
    }

    private function avance(Collection $etapas): array
    {
        $total = $etapas->count();
        $completadas = $etapas->where('estado', ComisionEtapa::ESTADO_COMPLETADA)->count();
        $noAplica = $etapas->where('estado', ComisionEtapa::ESTADO_NO_APLICA)->count();
        $pendientes = $total - $completadas - $noAplica;
        $base = max($total - $noAplica, 0);

        return [
            'total' => $total,
            'completadas' => $completadas,
            'pendientes' => max($pendientes, 0),
            'no_aplica' => $noAplica,
            'porcentaje' => $base > 0 ? (int) round(($completadas / $base) * 100) : null,
        ];
    }

    private function validarCambioEtapa(Comision $comision, ComisionEtapa $etapa, array $data): void
    {
        $estado = $data['estado'] ?? null;

        if ($estado === ComisionEtapa::ESTADO_NO_APLICA && ! $this->esEtapaOpcional($etapa->etapa)) {
            throw ValidationException::withMessages([
                'estado' => ['Esta etapa es obligatoria y no puede marcarse como no aplica.'],
            ]);
        }

        if ($estado !== ComisionEtapa::ESTADO_NO_APLICA && ! $this->puedeRegistrarEtapa($etapa)) {
            throw ValidationException::withMessages([
                'etapa' => ['Completa primero la etapa anterior aplicable.'],
            ]);
        }

        if ($comision->estado === 'cerrada') {
            $comision->forceFill([
                'estado' => 'pendiente',
                'cerrada_at' => null,
            ])->save();
        }
    }

    private function esEtapaOpcional(string $etapa): bool
    {
        return in_array($etapa, self::ETAPAS_OPCIONALES, true);
    }

    private function puedeActivarEtapa(ComisionEtapa $etapa): bool
    {
        return $this->esEtapaOpcional($etapa->etapa)
            && $etapa->estado === ComisionEtapa::ESTADO_NO_APLICA
            && $this->etapasPreviasAplicablesCompletas($etapa);
    }

    private function puedeRegistrarEtapa(ComisionEtapa $etapa): bool
    {
        return in_array($etapa->estado, [
            ComisionEtapa::ESTADO_PENDIENTE,
            ComisionEtapa::ESTADO_EN_PROCESO,
            ComisionEtapa::ESTADO_NO_APLICA,
        ], true)
            && $this->etapasPreviasAplicablesCompletas($etapa);
    }

    private function accionEtapa(ComisionEtapa $etapa): ?string
    {
        if ($etapa->estado === ComisionEtapa::ESTADO_COMPLETADA) {
            return 'ver';
        }

        if ($this->puedeActivarEtapa($etapa)) {
            return 'activar';
        }

        if ($this->puedeRegistrarEtapa($etapa)) {
            return $etapa->estado === ComisionEtapa::ESTADO_EN_PROCESO ? 'continuar' : 'registrar';
        }

        return null;
    }

    private function etapasPreviasAplicablesCompletas(ComisionEtapa $etapa): bool
    {
        $etapas = $etapa->relationLoaded('comision')
            ? $etapa->comision->etapas
            : $etapa->comision()->with('etapas')->first()?->etapas;

        if (! $etapas instanceof Collection) {
            $etapas = collect($etapas);
        }

        return $etapas
            ->where('orden', '<', $etapa->orden)
            ->filter(fn (ComisionEtapa $previa) => $previa->estado !== ComisionEtapa::ESTADO_NO_APLICA)
            ->every(fn (ComisionEtapa $previa) => $previa->estado === ComisionEtapa::ESTADO_COMPLETADA);
    }

    private function siguienteEtapa(Collection $etapas): ?array
    {
        $etapa = $etapas
            ->first(fn (ComisionEtapa $etapa) => in_array($etapa->estado, [
                ComisionEtapa::ESTADO_PENDIENTE,
                ComisionEtapa::ESTADO_EN_PROCESO,
            ], true) && $this->puedeRegistrarEtapa($etapa));

        if (! $etapa) {
            return null;
        }

        return [
            'id' => (int) $etapa->id,
            'etapa' => $etapa->etapa,
            'estado' => $etapa->estado,
            'accion' => $this->accionEtapa($etapa),
        ];
    }

    private function estatusVisual(Comision $comision, Collection $etapas): array
    {
        $avance = $this->avance($etapas);

        if ($comision->estado === 'cerrada') {
            return ['color' => 'verde', 'label' => 'Completa'];
        }

        if ($comision->estado === 'cancelada') {
            return ['color' => 'gris', 'label' => 'Cancelada'];
        }

        return [
            'color' => 'ambar',
            'label' => 'Pendiente ' . ($avance['porcentaje'] ?? 0) . '%',
        ];
    }

    private function hora(?string $hora): ?string
    {
        return $hora ? substr($hora, 0, 5) : null;
    }

    private function nombreEmpleado($empleado): ?string
    {
        if (! $empleado) {
            return null;
        }

        return trim(($empleado->Nombre ?? '') . ' ' . ($empleado->Apellidos ?? '')) ?: null;
    }
}
