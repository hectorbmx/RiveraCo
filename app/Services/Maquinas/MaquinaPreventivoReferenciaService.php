<?php

namespace App\Services\Maquinas;

use App\Models\EmpresaServicioPreventivoTipo;
use App\Models\Maquina;
use App\Models\MaquinaPreventivoReferencia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MaquinaPreventivoReferenciaService
{
    public const PERMISO = 'ajuste_horario_servicio.access';

    public function __construct(private MaquinaHorometroService $horometro) {}

    public function guardar(int $maquinaId, array $entrada, User $usuario): MaquinaPreventivoReferencia
    {
        abort_unless(! $usuario->hasDeniedPermission(self::PERMISO) && $usuario->can(self::PERMISO), 403);
        $entrada['motivo'] = is_string($entrada['motivo'] ?? null) ? trim($entrada['motivo']) : ($entrada['motivo'] ?? null);
        $data = Validator::make($entrada, [
            'servicio_preventivo_tipo_id' => ['present', 'nullable', 'integer'],
            'horometro_esperado' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'meta_horometro' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'motivo' => ['required', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($maquinaId, $data, $usuario) {
            $maquina = Maquina::query()->lockForUpdate()->findOrFail($maquinaId);
            $tipos = EmpresaServicioPreventivoTipo::query()->maquinaria()->activos();
            $tipoId = $data['servicio_preventivo_tipo_id'] ?? null;
            if (($tipoId !== null && ! (clone $tipos)->whereKey($tipoId)->exists()) || ($tipoId === null && $tipos->exists())) {
                throw ValidationException::withMessages(['servicio_preventivo_tipo_id' => 'Selecciona un servicio preventivo vigente de maquinaria.']);
            }
            $actual = $this->horometro->horometroActual($maquina);
            if ($actual === null || number_format($actual, 2, '.', '') !== number_format((float) $data['horometro_esperado'], 2, '.', '')) {
                throw ValidationException::withMessages(['horometro_esperado' => 'El horómetro cambió o no tiene una lectura válida. Actualiza la página antes de guardar.']);
            }

            // Permitir corregir la meta incluso con la misma lectura y en el mismo día.
            $ultima = MaquinaPreventivoReferencia::where('maquina_id', $maquinaId)
                ->where('servicio_preventivo_tipo_id', $tipoId)->latest('id')->first();
            if ($ultima && (float) $ultima->horometro === $actual && $ultima->meta_horometro !== null
                && (float) $ultima->meta_horometro === (float) $data['meta_horometro']
                && $ultima->motivo === $data['motivo'] && (int) $ultima->created_by === (int) $usuario->id
                && $ultima->fecha_referencia->isToday()) {
                return $ultima;
            }

            return MaquinaPreventivoReferencia::create([
                'maquina_id' => $maquinaId, 'servicio_preventivo_tipo_id' => $tipoId,
                'horometro' => $actual, 'fecha_referencia' => now(), 'motivo' => $data['motivo'], 'created_by' => $usuario->id,
                'meta_horometro' => $data['meta_horometro'],
            ]);
        });
    }
}
