<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Models\MaquinaHorometroAjuste;
use App\Models\Obra;
use App\Models\ObraMaquina;
use App\Models\ObraMaquinaRegistro;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MaquinaHorasService
{
    public const PERMISO = 'maquinas.horas.create.access';
    private const ORIGEN_MOVIL = 'app_mecanico';

    public function __construct(private MaquinaHorometroService $horometro) {}

    public function puedeCapturar(User $user): bool
    {
        return ! $user->hasDeniedPermission(self::PERMISO) && $user->can(self::PERMISO);
    }

    public function guardarMovil(int $maquinaId, array $entrada, User $user): array
    {
        abort_unless($this->puedeCapturar($user), 403);
        $data = $this->validarMovil($entrada);
        $hash = hash('sha256', json_encode([
            'version' => 1, 'maquina_id' => $maquinaId, 'user_id' => (int) $user->id,
            'origen' => self::ORIGEN_MOVIL, 'data' => $data,
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($maquinaId, $data, $hash, $user) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquinaId);
            $clase = $data['asignacion_esperada_id'] !== null ? ObraMaquinaRegistro::class : MaquinaHorometroAjuste::class;
            $previo = $clase::query()->where('maquina_id', $maquinaId)
                ->where('created_by', $user->id)->where('origen', self::ORIGEN_MOVIL)
                ->where('solicitud_hash', $hash)->first();
            if ($previo) {
                return $this->resultado($previo, true);
            }

            $asignacion = $this->asignacion($maquina);
            $actual = $this->horometro->horometroActual($maquina);
            if ($asignacion?->id !== $data['asignacion_esperada_id']
                || $this->decimal($actual) !== $data['horometro_esperado']) {
                throw new ConflictHttpException('La lectura o la asignación cambió. Actualiza la máquina y revisa la captura.');
            }
            if (! $asignacion && $data['obra_id'] !== null) {
                $valida = Obra::query()->whereKey($data['obra_id'])
                    ->where('estatus_nuevo', '!=', Obra::ESTATUS_CANCELADA)->exists();
                if (! $valida) {
                    throw ValidationException::withMessages(['obra_id' => 'Selecciona una obra existente no cancelada.']);
                }
            }

            $registro = $this->crear($maquina, $asignacion, $data, $user, self::ORIGEN_MOVIL);
            // La huella y la lectura se confirman o revierten en la misma transacción.
            $registro->forceFill(['solicitud_hash' => $hash])->save();

            return $this->resultado($registro, false);
        });
    }

    public function guardarWeb(Maquina $maquina, array $data, User $user): array
    {
        abort_unless($this->puedeCapturar($user), 403);

        return DB::transaction(function () use ($maquina, $data, $user) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquina->id);
            $asignacion = $this->asignacion($maquina);
            Validator::make($data, [
                'horometro_fin' => $this->reglasLectura(),
                'fecha_registro' => [$asignacion ? 'nullable' : 'required', 'date'],
            ])->validate();

            return $this->resultado($this->crear($maquina, $asignacion, $data, $user, 'web_maquinas'), false);
        });
    }

    private function asignacion(Maquina $maquina): ?ObraMaquina
    {
        return $maquina->asignacionActiva()->lockForUpdate()->first();
    }

    private function crear(Maquina $maquina, ?ObraMaquina $asignacion, array $data, User $user, string $origen): ObraMaquinaRegistro|MaquinaHorometroAjuste
    {
        try {
            if ($asignacion) {
                return $this->horometro->crearRegistro($asignacion, $data, $user, $origen);
            }

            return $this->horometro->crearAjuste($maquina, [
                'horometro_nuevo' => $data['horometro_fin'], 'obra_id' => $data['obra_id'] ?? null,
                'fecha_registro' => $data['fecha_registro'], 'tipo' => 'ajuste', 'notas' => $data['notas'] ?? null,
            ], $user, $origen);
        } catch (RuntimeException $error) {
            // No convertir fallos SQL/infraestructura en errores de captura.
            if ($error::class !== RuntimeException::class) {
                throw $error;
            }
            throw ValidationException::withMessages(['horometro_fin' => $error->getMessage()]);
        }
    }

    private function reglasLectura(): array
    {
        return ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'];
    }

    private function validarMovil(array $entrada): array
    {
        $campos = ['asignacion_esperada_id', 'horometro_esperado', 'horometro_fin', 'inicio', 'fin', 'fecha_registro', 'obra_id', 'notas'];
        foreach (array_diff(array_keys($entrada), $campos) as $campo) {
            throw ValidationException::withMessages([$campo => 'Este campo no está permitido.']);
        }
        $conAsignacion = isset($entrada['asignacion_esperada_id']);
        $fechaHora = ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-](0\d|1[0-3]):[0-5]\d|[+-]14:00)$/'];
        $reglas = [
            'asignacion_esperada_id' => ['present', 'nullable', 'integer', 'min:1'],
            'horometro_esperado' => ['present', 'nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'horometro_fin' => $this->reglasLectura(), 'notas' => ['nullable', 'string', 'max:500'],
        ];
        if ($conAsignacion) {
            $reglas += ['inicio' => $fechaHora, 'fin' => [...$fechaHora, 'after_or_equal:inicio']];
            $prohibidos = ['obra_id', 'fecha_registro'];
        } else {
            $reglas += ['fecha_registro' => ['required', 'date_format:Y-m-d'], 'obra_id' => ['nullable', 'integer', 'min:1']];
            $prohibidos = ['inicio', 'fin'];
        }
        foreach ($prohibidos as $campo) {
            if (array_key_exists($campo, $entrada)) {
                throw ValidationException::withMessages([$campo => 'Este campo no corresponde a la captura.']);
            }
        }
        $data = Validator::make($entrada, $reglas)->validate();
        $normalizado = [
            'asignacion_esperada_id' => $conAsignacion ? (int) $data['asignacion_esperada_id'] : null,
            'horometro_esperado' => $this->decimal($data['horometro_esperado']),
            'horometro_fin' => $this->decimal($data['horometro_fin']),
            'notas' => isset($data['notas']) && trim($data['notas']) !== '' ? trim($data['notas']) : null,
        ];
        if ($conAsignacion) {
            foreach (['inicio', 'fin'] as $campo) {
                $normalizado[$campo] = Carbon::parse($data[$campo])->setTimezone('America/Mexico_City')->format('Y-m-d H:i:s');
            }
        } else {
            $normalizado['fecha_registro'] = $data['fecha_registro'];
            $normalizado['obra_id'] = isset($data['obra_id']) ? (int) $data['obra_id'] : null;
        }

        return $normalizado;
    }

    private function decimal(mixed $valor): ?string
    {
        return $valor === null ? null : number_format((float) $valor, 2, '.', '');
    }

    private function resultado(ObraMaquinaRegistro|MaquinaHorometroAjuste $fila, bool $reutilizada): array
    {
        $registro = $fila instanceof ObraMaquinaRegistro;

        return [
            'tipo' => $registro ? 'registro_horas' : 'ajuste_horometro', 'id' => (int) $fila->id,
            'asignacion_id' => $registro ? (int) $fila->obra_maquina_id : null,
            'horometro_anterior' => (float) ($registro ? $fila->horometro_inicio : $fila->horometro_anterior),
            'horometro_nuevo' => (float) ($registro ? $fila->horometro_fin : $fila->horometro_nuevo),
            'diferencia' => (float) ($registro ? $fila->horas : $fila->diferencia), 'reutilizada' => $reutilizada,
        ];
    }
}
