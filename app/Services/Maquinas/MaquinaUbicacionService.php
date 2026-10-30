<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Models\MaquinaMovimiento;
use App\Models\ObraMaquina;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MaquinaUbicacionService
{
    public const PERMISO = 'maquinas.ubicacion.update.access';

    public function __construct(private MaquinaService $maquinas, private MaquinaHorometroService $horometro) {}

    public function puedeCambiar(User $user): bool
    {
        return ! $user->hasDeniedPermission(self::PERMISO) && $user->can(self::PERMISO);
    }

    public function consultar(int $maquinaId, User $user): array
    {
        abort_unless($this->puedeCambiar($user), 403);

        return $this->datos(Maquina::findOrFail($maquinaId), $user);
    }

    // También se usa en las opciones generales de consulta del panel mecánico.
    public function datos(Maquina $maquina, User $user): array
    {
        return DB::transaction(function () use ($maquina, $user) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquina->id);
            [$activa, $coherente] = $this->asignacion($maquina);
            $destinos = $this->puedeCambiar($user) && $coherente ? $this->destinos($maquina, $activa) : [];

            return [
                'maquina' => $maquina->only(['id', 'codigo', 'nombre', 'estado', 'ubicacion']),
                'obra_asignada' => $activa?->obra?->only(['id', 'nombre', 'clave_obra']),
                'contexto_ubicacion' => $this->contexto($maquina, $activa),
                'ubicaciones_permitidas' => $destinos,
                'cambiar_ubicacion' => $destinos !== [],
            ];
        });
    }

    public function guardar(int $maquinaId, array $entrada, User $user, string $origen = 'app_mecanico'): array
    {
        abort_unless($this->puedeCambiar($user), 403);
        $data = $this->validar($entrada);
        $hash = hash('sha256', json_encode(['version' => 1, 'origen' => $origen, 'tipo' => 'cambio_ubicacion',
            'maquina_id' => $maquinaId, 'user_id' => (int) $user->id, 'data' => $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($maquinaId, $data, $hash, $user) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquinaId);
            $previo = MaquinaMovimiento::query()->where('maquina_id', $maquinaId)->where('user_id', $user->id)
                ->where('tipo', 'cambio_ubicacion')->where('solicitud_hash', $hash)->first();
            if ($previo) {
                return $this->resultado($previo, true);
            }

            [$activa, $coherente] = $this->asignacion($maquina);
            $contexto = $this->contexto($maquina, $activa);
            foreach ($contexto as $campo => $valor) {
                if ($valor !== $data[$campo]) {
                    throw new ConflictHttpException('La ubicación, lectura o asignación cambió. Actualiza la máquina y revisa el movimiento.');
                }
            }
            if (! $coherente) {
                throw new ConflictHttpException('La máquina tiene asignaciones pendientes de regularizar. Revisa sus datos antes de cambiar la ubicación.');
            }
            if (! in_array($data['ubicacion'], $this->destinos($maquina, $activa), true)) {
                throw ValidationException::withMessages(['ubicacion' => $activa && $data['ubicacion'] === Maquina::UBIC_EN_PATIO
                    ? 'Usa el retiro de obra para finalizar la asignación y enviar la máquina al patio.'
                    : 'El destino no está permitido desde la ubicación y asignación actuales.']);
            }

            $this->maquinas->cambiarUbicacion(
                maquina: $maquina, nuevaUbicacion: $data['ubicacion'],
                obraId: $activa?->obra_id, obraMaquinaId: $activa?->id,
                motivo: $data['motivo'], notas: $data['notas'], user: $user
            );
            $movimiento = MaquinaMovimiento::query()->where('maquina_id', $maquinaId)
                ->where('tipo', 'cambio_ubicacion')->where('user_id', $user->id)->latest('id')->firstOrFail();
            $movimiento->forceFill(['solicitud_hash' => $hash])->save();

            return $this->resultado($movimiento, false);
        });
    }

    private function asignacion(Maquina $maquina): array
    {
        // No convertir una asignación a obra cancelada/terminada en «sin obra».
        $activas = ObraMaquina::query()->where('maquina_id', $maquina->id)
            ->where('estado', 'activa')->whereNull('fecha_fin')->orderByDesc('id')->lockForUpdate()->get();
        $activa = $activas->first();
        $coherente = $activas->count() <= 1 && (! $activa || $maquina->asignacionActiva()->whereKey($activa->id)->exists());

        return [$activa, $coherente];
    }

    private function contexto(Maquina $maquina, ?ObraMaquina $activa): array
    {
        $ultimoMovimiento = $maquina->movimientos()->max('id');

        return [
            'asignacion_esperada_id' => $activa ? (int) $activa->id : null,
            'horometro_esperado' => $this->decimal($this->horometro->horometroActual($maquina)),
            'ubicacion_esperada' => $maquina->ubicacion,
            'movimiento_esperado_id' => $ultimoMovimiento !== null ? (int) $ultimoMovimiento : null,
        ];
    }

    private function destinos(Maquina $maquina, ?ObraMaquina $activa): array
    {
        $destinos = [Maquina::UBIC_EN_CAMINO, Maquina::UBIC_EN_REPARACION];
        if (! $activa) {
            $destinos[] = Maquina::UBIC_EN_PATIO;
        } elseif (in_array($maquina->ubicacion, [Maquina::UBIC_EN_CAMINO, Maquina::UBIC_EN_REPARACION], true)) {
            $destinos[] = Maquina::UBIC_EN_OBRA;
        }

        return array_values(array_filter($destinos, fn ($destino) => $destino !== $maquina->ubicacion));
    }

    private function validar(array $entrada): array
    {
        $campos = ['asignacion_esperada_id', 'horometro_esperado', 'ubicacion_esperada', 'movimiento_esperado_id', 'ubicacion', 'motivo', 'notas'];
        foreach (array_diff(array_keys($entrada), $campos) as $campo) {
            throw ValidationException::withMessages([$campo => 'Este campo no está permitido.']);
        }
        foreach (['motivo', 'notas'] as $campo) {
            if (isset($entrada[$campo]) && is_string($entrada[$campo])) {
                $entrada[$campo] = trim($entrada[$campo]);
            }
        }
        $ubicaciones = [Maquina::UBIC_EN_OBRA, Maquina::UBIC_EN_CAMINO, Maquina::UBIC_EN_REPARACION, Maquina::UBIC_EN_PATIO];
        $data = Validator::make($entrada, [
            'asignacion_esperada_id' => ['present', 'nullable', 'integer', 'min:1'],
            'horometro_esperado' => ['present', 'nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'ubicacion_esperada' => ['present', 'nullable', Rule::in($ubicaciones)],
            'movimiento_esperado_id' => ['present', 'nullable', 'integer', 'min:1'],
            'ubicacion' => ['required', Rule::in($ubicaciones)],
            'motivo' => ['required', 'string', 'max:190'], 'notas' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        return [
            'asignacion_esperada_id' => isset($data['asignacion_esperada_id']) ? (int) $data['asignacion_esperada_id'] : null,
            'horometro_esperado' => $this->decimal($data['horometro_esperado']),
            'ubicacion_esperada' => $data['ubicacion_esperada'],
            'movimiento_esperado_id' => isset($data['movimiento_esperado_id']) ? (int) $data['movimiento_esperado_id'] : null,
            'ubicacion' => $data['ubicacion'], 'motivo' => $data['motivo'], 'notas' => ($data['notas'] ?? '') !== '' ? $data['notas'] : null,
        ];
    }

    private function decimal(mixed $valor): ?string
    {
        return $valor === null ? null : number_format((float) $valor, 2, '.', '');
    }

    private function resultado(MaquinaMovimiento $movimiento, bool $reutilizada): array
    {
        return ['id' => (int) $movimiento->id, 'movimiento_id' => (int) $movimiento->id, 'tipo' => 'cambio_ubicacion',
            'asignacion_id' => $movimiento->obra_maquina_id === null ? null : (int) $movimiento->obra_maquina_id,
            'obra_id' => $movimiento->obra_id === null ? null : (int) $movimiento->obra_id,
            'ubicacion_anterior' => $movimiento->ubicacion_anterior, 'ubicacion_nueva' => $movimiento->ubicacion_nueva,
            'motivo' => $movimiento->motivo, 'fecha_evento' => $movimiento->fecha_evento?->toIso8601String(),
            'reutilizada' => $reutilizada];
    }
}
