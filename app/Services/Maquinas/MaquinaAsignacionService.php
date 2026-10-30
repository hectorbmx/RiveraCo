<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Models\MaquinaMovimiento;
use App\Models\Obra;
use App\Models\ObraMaquina;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MaquinaAsignacionService
{
    public const PERMISO_ASIGNAR = 'maquinas.asignar_obra.access';
    public const PERMISO_RETIRAR = 'baja_maquinas.access';

    public function __construct(private MaquinaService $maquinas, private MaquinaHorometroService $horometro) {}

    public function puedeAsignar(User $user): bool
    {
        return ! $user->hasDeniedPermission(self::PERMISO_ASIGNAR) && $user->can(self::PERMISO_ASIGNAR);
    }

    public function puedeRetirar(User $user): bool
    {
        return ! $user->hasDeniedPermission(self::PERMISO_RETIRAR) && $user->can(self::PERMISO_RETIRAR);
    }

    public function asignacionParaRetiro(Maquina $maquina, bool $bloquear = false): ?ObraMaquina
    {
        $query = ObraMaquina::query()->with('obra:id,nombre,clave_obra,estatus_nuevo')
            ->where('maquina_id', $maquina->id)->where('estado', 'activa')->whereNull('fecha_fin')->orderByDesc('id')->limit(2);
        if ($bloquear) {
            $query->lockForUpdate();
        }
        $abiertas = $query->get();

        // Una obra cerrada no elimina la asignación que todavía debe retirarse.
        // No elegir silenciosamente una de varias asignaciones abiertas.
        return $abiertas->count() === 1 ? $abiertas->first() : null;
    }

    public function asignar(int $maquinaId, array $entrada, User $user): array
    {
        abort_unless($this->puedeAsignar($user), 403);

        return $this->ejecutar($maquinaId, $this->validar($entrada, true), $user, true);
    }

    public function retirar(int $maquinaId, array $entrada, User $user): array
    {
        abort_unless($this->puedeRetirar($user), 403);

        return $this->ejecutar($maquinaId, $this->validar($entrada, false), $user, false);
    }

    private function ejecutar(int $maquinaId, array $data, User $user, bool $asignar): array
    {
        $tipo = $asignar ? 'asignacion' : 'desasignacion';
        $hash = hash('sha256', json_encode(['version' => 1, 'origen' => 'app_mecanico',
            'maquina_id' => $maquinaId, 'user_id' => (int) $user->id, 'tipo' => $tipo, 'data' => $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($maquinaId, $data, $user, $asignar, $tipo, $hash) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquinaId);
            $previo = MaquinaMovimiento::query()->where('maquina_id', $maquinaId)
                ->where('user_id', $user->id)->where('tipo', $tipo)->where('solicitud_hash', $hash)->first();
            if ($previo) {
                return $this->resultado($previo, true);
            }

            $activa = $asignar ? $maquina->asignacionActiva()->lockForUpdate()->first()
                : $this->asignacionParaRetiro($maquina, true);
            $actual = $this->horometro->horometroActual($maquina);
            if ($activa?->id !== $data['asignacion_esperada_id'] || $this->decimal($actual) !== $data['horometro_esperado']) {
                throw new ConflictHttpException('La lectura o la asignación cambió. Actualiza la máquina y revisa la operación.');
            }
            if ($asignar && ($activa || $maquina->estado !== Maquina::ESTADO_OPERATIVA)) {
                throw new ConflictHttpException('La máquina debe estar operativa y sin asignación activa. Actualiza sus datos.');
            }
            if (! $asignar && ! $activa) {
                throw new ConflictHttpException('La máquina ya no tiene la asignación indicada. Actualiza sus datos.');
            }

            try {
                if ($asignar) {
                    $obra = Obra::query()->lockForUpdate()->find($data['obra_id']);
                    if (! $obra) {
                        throw ValidationException::withMessages(['obra_id' => 'Selecciona una obra vigente con pilas activas.']);
                    }
                    $asignacion = $this->maquinas->asignarAObra($maquina, $obra, $data, $user);
                } else {
                    $asignacion = $this->maquinas->finalizarAsignacion($activa, $data, $user);
                }
            } catch (RuntimeException $error) {
                if ($error::class !== RuntimeException::class) {
                    throw $error;
                }
                throw ValidationException::withMessages([$asignar ? 'obra_id' : 'asignacion_esperada_id' => $error->getMessage()]);
            }

            $movimiento = MaquinaMovimiento::query()->where('maquina_id', $maquinaId)
                ->where('obra_maquina_id', $asignacion->id)->where('tipo', $tipo)->where('user_id', $user->id)
                ->latest('id')->firstOrFail();
            $movimiento->forceFill(['solicitud_hash' => $hash])->save();

            return $this->resultado($movimiento, false);
        });
    }

    private function validar(array $entrada, bool $asignar): array
    {
        $campos = ['asignacion_esperada_id', 'horometro_esperado', ...($asignar ? ['obra_id', 'fecha_inicio', 'horometro_inicio', 'notas'] : ['fecha_fin'])];
        foreach (array_diff(array_keys($entrada), $campos) as $campo) {
            throw ValidationException::withMessages([$campo => 'Este campo no está permitido.']);
        }
        $reglas = [
            'asignacion_esperada_id' => [$asignar ? 'present' : 'required', ...($asignar ? ['nullable'] : []), 'integer', 'min:1'],
            'horometro_esperado' => ['present', 'nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ];
        if ($asignar) {
            $reglas += ['obra_id' => ['required', 'integer', 'min:1'], 'fecha_inicio' => ['required', 'date_format:Y-m-d'],
                'horometro_inicio' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
                'notas' => ['nullable', 'string', 'max:1000']];
        } else {
            $reglas += ['fecha_fin' => ['required', 'date_format:Y-m-d']];
        }
        $data = Validator::make($entrada, $reglas)->validate();
        $normalizado = ['asignacion_esperada_id' => isset($data['asignacion_esperada_id']) ? (int) $data['asignacion_esperada_id'] : null,
            'horometro_esperado' => $this->decimal($data['horometro_esperado'])];
        if ($asignar) {
            $normalizado += ['obra_id' => (int) $data['obra_id'], 'fecha_inicio' => $data['fecha_inicio'],
                'horometro_inicio' => $this->decimal($data['horometro_inicio']),
                'notas' => isset($data['notas']) && trim($data['notas']) !== '' ? trim($data['notas']) : null];
        } else {
            $normalizado['fecha_fin'] = $data['fecha_fin'];
        }

        return $normalizado;
    }

    private function decimal(mixed $valor): ?string
    {
        return $valor === null ? null : number_format((float) $valor, 2, '.', '');
    }

    private function resultado(MaquinaMovimiento $movimiento, bool $reutilizada): array
    {
        return ['id' => (int) $movimiento->id, 'movimiento_id' => (int) $movimiento->id,
            'tipo' => $movimiento->tipo === 'asignacion' ? 'asignacion_obra' : 'retiro_obra',
            'asignacion_id' => (int) $movimiento->obra_maquina_id, 'obra_id' => (int) $movimiento->obra_id,
            'ubicacion_anterior' => $movimiento->ubicacion_anterior, 'ubicacion_nueva' => $movimiento->ubicacion_nueva,
            'reutilizada' => $reutilizada];
    }
}
