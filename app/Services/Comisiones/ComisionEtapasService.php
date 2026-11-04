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
use App\Notifications\ObraOperacionNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

/**
 * Operaciones compartidas. Cada entrada debe autorizar al usuario sobre la obra.
 */
class ComisionEtapasService
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

    public function index(Obra $obra): array
    {

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

    public function show(Obra $obra, Comision $comision): array
    {

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

    public function create(User $user, Obra $obra, array $data, ?int $residenteId = null): array
    {
        $comision = $this->createRecord($user, $obra, $data, $residenteId);

        return $this->show($obra, $comision)['comision'];
    }

    /** Alta compartida para entradas autorizadas; admite una transaccion exterior. */
    public function createRecord(User $user, Obra $obra, array $data, ?int $residenteId = null): Comision
    {
        $this->validarPilaDeObra($obra, (int) $data['pila_id']);

        return DB::transaction(function () use ($user, $obra, $data, $residenteId) {
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
                'residente_id' => $residenteId,
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
                if (! empty($data['obra_maquina_id'])) {
                    $comision->detalles()->orderBy('id')->firstOrFail()
                        ->update(['obra_maquina_id' => $data['obra_maquina_id']]);
                }
            }

            DB::afterCommit(function () use ($user, $obra, $comision) {
                $perforacion = $comision->etapas()->where('etapa', ComisionEtapa::ETAPA_PERFORACION)->first();
                if ($perforacion) {
                    $this->notificarMovimientoObra($user, $obra, $comision, $perforacion);
                }
            });

            return $comision;
        });
    }

    public function updateEtapa(User $user, Obra $obra, Comision $comision, string $etapaKey, array $data): array
    {
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

        $etapaRefrescada = $etapa->fresh();
        if ($etapaRefrescada) {
            $this->notificarMovimientoObra($user, $obra, $comision, $etapaRefrescada);
        }

        return $this->show($obra, $comision->fresh())['comision'];
    }

    public function storeFoto(User $user, Obra $obra, Comision $comision, string $etapaKey, UploadedFile $foto, ?string $comentario = null): array
    {
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

        try {
            if (! Storage::disk('public')->put($path, (string) $imagen)) {
                throw new \RuntimeException('No se pudo guardar la fotografía.');
            }
            $fotoModel = ComisionEtapaFoto::create([
                'comision_etapa_id' => $etapa->id,
                'disk' => 'public',
                'path' => $path,
                'mime_type' => 'image/webp',
                'size' => strlen((string) $imagen),
                'comentario' => $comentario,
                'uploaded_by' => $user->id,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

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

    public function personalDisponible(Obra $obra): EloquentCollection
    {
        return ObraEmpleado::with(['empleado', 'rol'])
            ->where('obra_id', $obra->id)
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->orderBy('id')->get()
            ->filter(fn ($asignacion) => $asignacion->empleado
                && mb_strtolower(trim((string) $asignacion->rol?->nombre)) !== 'residente')
            ->values();
    }

    /** Guarda una captura web validada, incluidos archivos y personal, como una unidad. */
    public function completarEtapa(User $user, Obra $obra, Comision $comision, string $key, array $data, array $fotos): Comision
    {
        $this->validarComisionDeObra($obra, $comision);
        $archivosNuevos = [];

        try {
            $guardada = DB::transaction(function () use ($user, $obra, $comision, $key, $data, $fotos, &$archivosNuevos) {
                $actual = Comision::whereKey($comision->id)->lockForUpdate()->firstOrFail();
                $this->validarComisionDeObra($obra, $actual);
                if (in_array($actual->estado, ['cerrada', 'cancelada'], true)) {
                    throw ValidationException::withMessages(['etapa' => 'Esta comisión no admite acciones de captura.']);
                }
                $actual->load('etapas');
                $etapa = $actual->etapas->firstWhere('etapa', $key);
                if (! $etapa || ! in_array($this->accionEtapa($etapa), ['registrar', 'continuar'], true)) {
                    throw ValidationException::withMessages(['etapa' => 'La etapa ya no está disponible. Actualiza el detalle de la comisión.']);
                }

                $disponibles = $this->personalDisponible($obra)->keyBy('id');
                $ids = collect($data['personal_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
                if ($ids->diff($disponibles->keys())->isNotEmpty()) {
                    throw ValidationException::withMessages(['personal_ids' => 'Selecciona únicamente personal con asignación activa disponible en esta obra.']);
                }

                // La pantalla captura totales; conservar los demás renglones de producción.
                $config = self::PRODUCCION_POR_ETAPA[$key];
                $detalles = $actual->detalles()->orderBy('id')->get();
                if ($detalles->count() > 1) {
                    $resto = (float) $detalles->skip(1)->sum($config['detalle']);
                    if ((float) $data[$config['campo']] < $resto) {
                        throw ValidationException::withMessages([
                            $config['campo'] => 'El total no puede ser menor a lo ya registrado en otros renglones de producción: ' . $resto . '.',
                        ]);
                    }
                    $data[$config['campo']] = round((float) $data[$config['campo']] - $resto, 2);
                }

                unset($data['personal_ids']);
                $data['estado'] = ComisionEtapa::ESTADO_COMPLETADA;
                $this->validarCambioEtapa($actual, $etapa, $data);
                $this->guardarEtapa($user, $obra, $actual, $etapa, $data);

                // Mantener intactos los metadatos del personal previo y su historial.
                foreach ($ids as $id) {
                    $asignacion = $disponibles->get($id);
                    $etapa->personal()->firstOrCreate(['obra_empleado_id' => $id], [
                        'empleado_id' => $asignacion->empleado_id,
                        'rol_id' => $asignacion->rol_id,
                        'comisiona' => true,
                        'importe_comision' => 0,
                    ]);
                }
                $etapa->personal()->whereIn('obra_empleado_id', $disponibles->keys())
                    ->whereNotIn('obra_empleado_id', $ids)->delete();

                foreach ($fotos as $foto) {
                    $archivo = $this->storeFoto($user, $obra, $actual, $key, $foto);
                    $archivosNuevos[] = $archivo['path'];
                }
                $this->actualizarEstadoComision($actual);

                return $actual;
            });
        } catch (\Throwable $exception) {
            if ($archivosNuevos !== []) {
                Storage::disk('public')->delete($archivosNuevos);
            }
            throw $exception;
        }

        // La notificación sale después de confirmar todos los datos y archivos.
        $this->notificarMovimientoObra($user, $obra, $guardada, $guardada->etapas()->where('etapa', $key)->firstOrFail());

        return $guardada;
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

        $detalle = $comision->detalles()->orderBy('id')->firstOrNew([]);
        $detalle->fill([
            'diametro' => array_key_exists('diametro', $data) ? (string) $data['diametro'] : $detalle->diametro,
            'cantidad' => array_key_exists('cantidad', $data) ? (int) ($data['cantidad'] ?? 0) : ($detalle->cantidad ?? 1),
            'profundidad' => array_key_exists('profundidad', $data) ? (float) ($data['profundidad'] ?? 0) : $detalle->profundidad,
            'metros_sujetos_comision' => array_key_exists('metros_comision', $data) ? (float) ($data['metros_comision'] ?? 0) : $detalle->metros_sujetos_comision,
            'kg_acero' => array_key_exists('kg_acero', $data) ? (float) ($data['kg_acero'] ?? 0) : $detalle->kg_acero,
            'vol_bentonita' => array_key_exists('vol_bentonita', $data) ? (float) ($data['vol_bentonita'] ?? 0) : $detalle->vol_bentonita,
            'vol_concreto' => array_key_exists('vol_concreto', $data) ? (float) ($data['vol_concreto'] ?? 0) : $detalle->vol_concreto,
            'ml_ademe_bauer' => array_key_exists('ml_ademe_bauer', $data) ? (float) ($data['ml_ademe_bauer'] ?? 0) : ($detalle->ml_ademe_bauer ?? 0),
            'campana_pzas' => array_key_exists('campana_pzas', $data) ? (int) ($data['campana_pzas'] ?? 0) : ($detalle->campana_pzas ?? 0),
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

    private function notificarMovimientoObra(User $user, Obra $obra, Comision $comision, ComisionEtapa $etapa): void
    {
        if ($etapa->estado !== ComisionEtapa::ESTADO_COMPLETADA) {
            return;
        }

        try {
            $comision->loadMissing(['pila']);
            $etapa->loadMissing(['pila']);

            $destinatarios = User::role(['super-admin', 'admin-rivera'])
                ->whereKeyNot($user->id)
                ->get();

            if ($destinatarios->isEmpty()) {
                return;
            }

            Notification::send($destinatarios, new ObraOperacionNotification($obra, $comision, $etapa, $user));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar notificacion de operacion de obra.', [
                'obra_id' => $obra->id,
                'comision_id' => $comision->id,
                'etapa_id' => $etapa->id,
                'etapa' => $etapa->etapa,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function mapComision(Comision $comision, bool $detallado = false): array
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
        return $this->produccionParaEtapa($etapa->comision, $etapa->etapa);
    }

    public function produccionParaEtapa(?Comision $comision, string $etapa): ?array
    {
        $config = self::PRODUCCION_POR_ETAPA[$etapa] ?? null;

        if (! $config) {
            return null;
        }

        $detalle = $config['detalle'];
        $valor = $comision?->detalles?->sum($detalle) ?? 0;

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

