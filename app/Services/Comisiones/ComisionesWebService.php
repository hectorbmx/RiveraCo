<?php

namespace App\Services\Comisiones;

use App\Models\Comision;
use App\Models\Obra;
use App\Models\ObraEmpleado;
use App\Models\ObraMaquina;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Entrada web para la captura por etapas, independiente de la habilitacion movil. */
class ComisionesWebService
{
    public function __construct(private ComisionEtapasService $etapas)
    {
    }

    public function showForUser(User $user, Obra $obra, Comision $comision): array
    {
        $this->autorizar($user, $obra);

        return $this->etapas->show($obra, $comision);
    }

    public function datosGeneralesForUser(User $user, Obra $obra): array
    {
        $this->autorizar($user, $obra);
        $this->validarObraParaAlta($obra);
        $obra->loadMissing('cliente');
        $pilas = $obra->pilas()->activas()->orderBy('id')->get();
        $maquinas = ObraMaquina::with('maquina')->where('obra_id', $obra->id)
            ->activas()->orderBy('id')->get()->filter(fn ($asignacion) => $asignacion->maquina)->values();
        $residente = $this->residenteAsignado($obra);
        $maquinaInicial = $maquinas->first(fn ($asignacion) => mb_strtoupper(trim((string) $asignacion->maquina->tipo)) === 'PERFORADORA')
            ?? $maquinas->first();

        return [
            'pilas' => $pilas,
            'asignacionesMaquinas' => $maquinas,
            'residenteAsignado' => $residente,
            'personalDisponibleAlta' => $this->etapas->personalDisponible($obra),
            'maquinaInicialId' => $maquinaInicial?->id,
            'puedeContinuarAlta' => $pilas->isNotEmpty() && $maquinas->isNotEmpty() && $residente !== null,
        ];
    }

    private function validarObraParaAlta(Obra $obra): void
    {
        if (in_array((int) $obra->estatus_nuevo, [Obra::ESTATUS_TERMINADA, Obra::ESTATUS_CANCELADA], true)) {
            throw ValidationException::withMessages(['obra' => 'No puedes iniciar un trabajo en una obra terminada o cancelada.']);
        }
    }

    private function residenteAsignado(Obra $obra): ?ObraEmpleado
    {
        return ObraEmpleado::with(['empleado', 'rol'])->where('obra_id', $obra->id)
            ->where('activo', true)->whereNull('fecha_baja')->orderBy('id')->get()
            ->first(fn ($asignacion) => $asignacion->empleado
                && (mb_strtoupper(trim((string) $asignacion->rol?->rol_key)) === 'RESIDENTE'
                    || mb_strtolower(trim((string) $asignacion->rol?->nombre)) === 'residente'));
    }

    /** Alta web por etapas; datos, participantes y evidencias forman una unidad. */
    public function createForUser(User $user, Obra $obra, array $input): Comision
    {
        $this->autorizar($user, $obra);
        $this->validarObraParaAlta($obra);

        $comunes = ComisionEtapaRules::rules('perforacion.');
        $data = Validator::make($input, [
            'fecha' => ['required', 'date'],
            'pila_id' => ['required', 'integer', Rule::exists('obras_pilas', 'id')->where(fn ($query) => $query->where('obra_id', $obra->id)->where('activo', true))],
            'obra_maquina_id' => ['required', 'integer', Rule::exists('obra_maquina', 'id')->where(fn ($query) => $query->where('obra_id', $obra->id)->where('estado', 'activa')->whereNull('fecha_fin'))],
            'numero_formato' => ['nullable', 'string', 'max:50'],
            'cliente_nombre' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],
            'trabajo_id' => ['nullable', 'integer', 'exists:catalogo_trabajos_comision,id'],
            'opcionales' => ['nullable', 'array'],
            'opcionales.bentonita' => ['nullable', 'boolean'],
            'opcionales.ademe' => ['nullable', 'boolean'],
            'perforacion' => ['required', 'array'],
            'perforacion.hora_inicio' => ['required', 'date_format:H:i'],
            'perforacion.hora_fin' => $comunes['perforacion.hora_fin'],
            'perforacion.observaciones' => $comunes['perforacion.observaciones'],
            'perforacion.diametro' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'perforacion.profundidad' => ['nullable', 'numeric', 'min:0', 'decimal:0,2', 'max:999999.99'],
            'perforacion.metros_comision' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'perforacion.campana_pzas' => $comunes['perforacion.campana_pzas'],
            'perforacion.adicional' => $comunes['perforacion.adicional'],
            'perforacion.personal_ids' => ['nullable', 'array'],
            'perforacion.personal_ids.*' => ['required', 'integer', 'distinct'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ])->validate();

        if (! ObraMaquina::whereKey($data['obra_maquina_id'])->whereHas('maquina')->exists()) {
            throw ValidationException::withMessages(['obra_maquina_id' => 'La máquina asignada ya no está disponible.']);
        }

        $residente = $this->residenteAsignado($obra);
        if (! $residente) {
            throw ValidationException::withMessages(['residente' => 'Asigna un residente activo a la obra antes de iniciar el trabajo.']);
        }

        $disponibles = $this->etapas->personalDisponible($obra)->keyBy('id');
        $ids = collect($data['perforacion']['personal_ids'] ?? [])->map(fn ($id) => (int) $id);
        if ($ids->diff($disponibles->keys())->isNotEmpty()) {
            throw ValidationException::withMessages(['perforacion.personal_ids' => 'Selecciona únicamente personal con asignación activa disponible en esta obra.']);
        }
        // No aceptar estado, importes ni cantidades de etapas posteriores desde el cliente.
        $perforacion = array_intersect_key($data['perforacion'], array_flip([
            'hora_inicio', 'hora_fin', 'observaciones', 'diametro', 'profundidad',
            'metros_comision', 'campana_pzas', 'adicional',
        ]));
        $perforacion['cantidad'] = 1;
        $perforacion['estado'] = empty($perforacion['hora_fin']) ? 'en_proceso' : 'completada';
        $perforacion['personal'] = $ids->map(fn ($id) => [
            'obra_empleado_id' => $id, 'comisiona' => true, 'importe_comision' => 0,
        ])->all();

        $datosAlta = [
            'fecha' => $data['fecha'], 'pila_id' => $data['pila_id'],
            'obra_maquina_id' => $data['obra_maquina_id'],
            'numero_formato' => $data['numero_formato'] ?? null,
            'cliente_nombre' => $data['cliente_nombre'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'trabajo_id' => $data['trabajo_id'] ?? null,
            'opcionales' => [
                'bentonita' => (bool) ($data['opcionales']['bentonita'] ?? false),
                'ademe' => (bool) ($data['opcionales']['ademe'] ?? false),
            ],
            'perforacion' => $perforacion,
        ];

        $archivosNuevos = [];
        try {
            return DB::transaction(function () use ($user, $obra, $datosAlta, $residente, $data, &$archivosNuevos) {
                $comision = $this->etapas->createRecord($user, $obra, $datosAlta, (int) $residente->empleado_id);
                foreach ($data['fotos'] ?? [] as $foto) {
                    $archivo = $this->etapas->storeFoto($user, $obra, $comision, 'perforacion', $foto);
                    $archivosNuevos[] = $archivo['path'];
                }

                return $comision;
            });
        } catch (\Throwable $exception) {
            if ($archivosNuevos !== []) {
                Storage::disk('public')->delete($archivosNuevos);
            }
            throw $exception;
        }
    }

    public function updateEtapaForUser(User $user, Obra $obra, Comision $comision, string $etapa, array $data): array
    {
        $this->autorizar($user, $obra);

        return $this->etapas->updateEtapa($user, $obra, $comision, $etapa, $data);
    }

    public function storeFotoForUser(User $user, Obra $obra, Comision $comision, string $etapa, UploadedFile $foto, ?string $comentario = null): array
    {
        $this->autorizar($user, $obra);

        return $this->etapas->storeFoto($user, $obra, $comision, $etapa, $foto, $comentario);
    }

    public function puedeCapturar(User $user, Obra $obra): bool
    {
        return $this->puedeOperacion($user, $obra, 'obras.edit');
    }

    public function autorizarOperacion(User $user, Obra $obra, string $permiso): void
    {
        $this->autorizar($user, $obra, $permiso);
    }

    public function puedeOperacion(User $user, Obra $obra, string $permiso): bool
    {
        try {
            $this->autorizar($user, $obra, $permiso);

            return true;
        } catch (AuthorizationException $exception) {
            return false;
        }
    }

    public function seleccionarEtapaForUser(User $user, Obra $obra, Comision $comision, string $etapa): array
    {
        $seleccionada = $this->etapaConAccion($user, $obra, $comision, $etapa, ['registrar', 'continuar']);
        $comision->loadMissing('detalles');
        $seleccionada['produccion'] = $this->etapas->produccionParaEtapa($comision, $etapa);

        return $seleccionada;
    }

    public function activarEtapaForUser(User $user, Obra $obra, Comision $comision, string $etapa): array
    {
        $this->etapaConAccion($user, $obra, $comision, $etapa, ['activar']);

        return $this->etapas->updateEtapa($user, $obra, $comision, $etapa, [
            'estado' => 'pendiente',
            'requiere_foto' => true,
        ]);
    }

    public function completarEtapaForUser(User $user, Obra $obra, Comision $comision, string $etapa, array $input): Comision
    {
        $seleccionada = $this->seleccionarEtapaForUser($user, $obra, $comision, $etapa);
        $campo = $seleccionada['produccion']['campo'];
        $comunes = ComisionEtapaRules::rules();
        $data = Validator::make($input, [
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
            'observaciones' => $comunes['observaciones'],
            $campo => ['required', ...$comunes[$campo], 'decimal:0,2',
                $campo === 'vol_concreto' ? 'gt:0' : 'min:0',
                $campo === 'metros_comision' ? 'max:99999999.99' : 'max:9999999999.99'],
            'personal_ids' => ['nullable', 'array'],
            'personal_ids.*' => ['required', 'integer', 'distinct'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ])->validate();
        $fotos = $data['fotos'] ?? [];
        unset($data['fotos']);

        return $this->etapas->completarEtapa($user, $obra, $comision, $etapa, $data, $fotos);
    }

    private function etapaConAccion(User $user, Obra $obra, Comision $comision, string $key, array $acciones): array
    {
        $this->autorizar($user, $obra);
        if ((int) $comision->obra_id !== (int) $obra->id) {
            throw new AuthorizationException('La comisión no pertenece a esta obra.');
        }

        if (in_array($comision->estado, ['cerrada', 'cancelada'], true)) {
            throw ValidationException::withMessages(['etapa' => 'Esta comisión no admite acciones de captura.']);
        }

        $comision->loadMissing(['pila', 'etapas.fotos', 'etapas.personal']);
        $etapa = collect($this->etapas->mapComision($comision)['etapas'])->firstWhere('etapa', $key);
        if (! $etapa || ! in_array($etapa['accion'], $acciones, true)) {
            throw ValidationException::withMessages(['etapa' => 'La acción no está disponible. Completa primero la etapa anterior aplicable.']);
        }

        return $etapa;
    }

    private function autorizar(User $user, Obra $obra, string $permiso = 'obras.edit'): void
    {
        // can() respeta tambien las denegaciones explicitas del usuario.
        if (! $user->can($permiso)) {
            throw new AuthorizationException('No tienes permiso para realizar esta operación de comisiones.');
        }

        if ($user->hasAnyRole(['super-admin', 'admin-rivera'])) {
            return;
        }

        // Mismo alcance de obra/area que ObraController; no exige UsuarioApp activo.
        $empleado = $user->empleado;
        if (! $empleado) {
            throw new AuthorizationException('No tienes acceso a esta obra.');
        }

        $puesto = mb_strtoupper(trim(($empleado->puesto_base ?: $empleado->Puesto) ?? ''));
        if (str_contains($puesto, 'RESIDENTE')) {
            $esResponsable = (int) $obra->responsable_id === (int) $empleado->id_Empleado;
            $estaAsignado = $esResponsable || $obra->empleadosAsignados()
                ->where('empleado_id', $empleado->id_Empleado)
                ->where('activo', true)
                ->exists();

            if (! $estaAsignado) {
                throw new AuthorizationException('No tienes acceso a esta obra.');
            }

            return;
        }

        if (! $empleado->area_id || (int) $obra->area_id !== (int) $empleado->area_id) {
            throw new AuthorizationException('No tienes acceso a esta obra.');
        }
    }
}
