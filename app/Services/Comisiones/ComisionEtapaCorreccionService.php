<?php

namespace App\Services\Comisiones;

use App\Models\Comision;
use App\Models\ComisionEtapa;
use App\Models\ComisionEtapaFoto;
use App\Models\Obra;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Correcciones web con permisos independientes de la captura ordinaria. */
class ComisionEtapaCorreccionService
{
    private const PRODUCCION = [
        'perforacion' => ['campo' => 'metros_comision', 'columna' => 'metros_sujetos_comision', 'max' => '99999999.99'],
        'acero' => ['campo' => 'kg_acero', 'columna' => 'kg_acero', 'max' => '9999999999.99'],
        'colado' => ['campo' => 'vol_concreto', 'columna' => 'vol_concreto', 'max' => '9999999999.99'],
    ];

    public function __construct(private ComisionesWebService $web, private ComisionEtapasService $motor) {}

    public function permisos(User $user, Obra $obra): array
    {
        $resultado = [];
        foreach (array_keys(self::PRODUCCION) as $key) {
            foreach (['editar' => 'editar', 'borrar' => 'borrar', 'fotos' => 'borrar_fotos'] as $opcion => $accion) {
                $resultado[$key][$opcion] = $this->web->puedeOperacion($user, $obra, $accion . '_' . $key . '.access');
            }
        }
        return $resultado;
    }

    private function autorizar(User $user, Obra $obra, string $key, string $accion): void
    {
        if (! array_key_exists($key, self::PRODUCCION)) {
            throw new AuthorizationException('Esta etapa no tiene permiso de corrección configurado.');
        }
        $this->web->autorizarOperacion($user, $obra, $accion . '_' . $key . '.access');
    }

    private function etapa(Obra $obra, Comision $comision, string $key): ComisionEtapa
    {
        if ((int) $comision->obra_id !== (int) $obra->id) {
            throw new AuthorizationException('La comisión no pertenece a esta obra.');
        }
        if ($comision->estado === 'cancelada') {
            throw ValidationException::withMessages(['etapa' => 'No puedes modificar una comisión cancelada.']);
        }
        $etapa = $comision->etapas()->where('etapa', $key)->firstOrFail();
        if (! in_array($etapa->estado, ['en_proceso', 'completada'], true)) {
            throw ValidationException::withMessages(['etapa' => 'Esta etapa no tiene una captura iniciada para editar o borrar.']);
        }
        return $etapa;
    }

    public function formulario(User $user, Obra $obra, Comision $comision, string $key): array
    {
        $this->autorizar($user, $obra, $key, 'editar');
        $etapa = $this->etapa($obra, $comision, $key);
        return [
            'etapa' => $key, 'accion' => 'editar', 'estado' => $etapa->estado,
            'hora_inicio' => $etapa->hora_inicio ? substr((string) $etapa->hora_inicio, 0, 5) : null,
            'hora_fin' => $etapa->hora_fin ? substr((string) $etapa->hora_fin, 0, 5) : null,
            'observaciones' => $etapa->observaciones,
            'produccion' => $this->motor->produccionParaEtapa($comision, $key),
        ];
    }

    public function editar(User $user, Obra $obra, Comision $comision, string $key, array $input): void
    {
        $this->autorizar($user, $obra, $key, 'editar');
        $this->conArchivosRetirados(function (&$retirados, &$nuevos) use ($user, $obra, $comision, $key, $input) {
            $actual = Comision::whereKey($comision->id)->lockForUpdate()->firstOrFail();
            $etapa = $this->etapa($obra, $actual, $key);
            $config = self::PRODUCCION[$key];
            $campo = $config['campo'];
            $columna = $config['columna'];
            $data = Validator::make($input, [
                'hora_inicio' => ['required', 'date_format:H:i'],
                'hora_fin' => [$etapa->estado === 'completada' ? 'required' : 'nullable', 'date_format:H:i'],
                'observaciones' => ['nullable', 'string'],
                $campo => ['required', 'numeric', $key === 'colado' ? 'gt:0' : 'min:0', 'decimal:0,2', 'max:' . $config['max']],
                'personal_ids' => ['nullable', 'array'], 'personal_ids.*' => ['integer', 'distinct'],
                'diametro' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
                'profundidad' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
                'campana_pzas' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
                'adicional' => ['nullable', 'string', 'max:100'],
                'fotos' => ['nullable', 'array'],
                'fotos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
                'fotos_borrar' => ['nullable', 'array'],
                'fotos_borrar.*' => ['required', 'integer', 'distinct'],
            ])->validate();
            $idsFotos = collect($data['fotos_borrar'] ?? [])->map(fn ($id) => (int) $id);
            if ($idsFotos->isNotEmpty()) {
                $this->autorizar($user, $obra, $key, 'borrar_fotos');
            }
            $fotosBorrar = $etapa->fotos()->whereIn('id', $idsFotos)->lockForUpdate()->get();
            if ($idsFotos->diff($fotosBorrar->pluck('id'))->isNotEmpty()) {
                throw ValidationException::withMessages(['fotos_borrar' => 'Una de las fotografías ya no existe o no pertenece a esta etapa. Actualiza la página.']);
            }
            // La corrección mantiene el estado; completar sigue siendo una operación de captura.
            if ($etapa->estado === 'en_proceso' && ! empty($data['hora_fin'])) {
                throw ValidationException::withMessages(['hora_fin' => 'Para completar una etapa en proceso utiliza Guardar y completar etapa.']);
            }
            $disponibles = $this->motor->personalDisponible($obra)->keyBy('id');
            $ids = collect($data['personal_ids'] ?? [])->map(fn ($id) => (int) $id);
            if ($ids->diff($disponibles->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['personal_ids' => 'Selecciona únicamente personal activo de esta obra.']);
            }
            $detalles = $actual->detalles()->orderBy('id')->get();
            $resto = (float) $detalles->skip(1)->sum($columna);
            if ((float) $data[$campo] < $resto) {
                throw ValidationException::withMessages([$campo => 'El total no puede ser menor a lo registrado en otros renglones: ' . $resto . '.']);
            }
            $detalle = $detalles->first() ?? $actual->detalles()->make(['cantidad' => 1, 'ml_ademe_bauer' => 0, 'campana_pzas' => 0]);
            if ($key === 'perforacion') {
                $extra = array_intersect_key($data, array_flip(['diametro', 'profundidad', 'campana_pzas', 'adicional']));
                if (array_key_exists('campana_pzas', $extra)) $extra['campana_pzas'] ??= 0;
                $detalle->fill($extra);
            }
            $detalle->fill([$columna => round((float) $data[$campo] - $resto, 2)])->save();
            $etapa->fill([
                'hora_inicio' => $data['hora_inicio'], 'hora_fin' => $data['hora_fin'] ?? null,
                'observaciones' => $data['observaciones'] ?? null, 'updated_by' => $user->id,
            ])->save();
            foreach ($ids as $id) {
                $asignacion = $disponibles->get($id);
                $etapa->personal()->firstOrCreate(['obra_empleado_id' => $id], [
                    'empleado_id' => $asignacion->empleado_id, 'rol_id' => $asignacion->rol_id,
                    'comisiona' => true, 'importe_comision' => 0,
                ]);
            }
            $etapa->personal()->whereIn('obra_empleado_id', $disponibles->keys())->whereNotIn('obra_empleado_id', $ids)->delete();
            foreach ($data['fotos'] ?? [] as $foto) {
                $archivo = $this->motor->storeFoto($user, $obra, $actual, $key, $foto);
                $nuevos[] = ['disk' => $archivo['disk'], 'path' => $archivo['path']];
            }
            foreach ($fotosBorrar as $foto) {
                $this->retirarArchivo($foto, $retirados);
                $foto->delete();
            }
            $actual->forceFill(['updated_by' => $user->id])->save();
        });
    }

    public function borrar(User $user, Obra $obra, Comision $comision, string $key): void
    {
        $this->autorizar($user, $obra, $key, 'borrar');
        $this->conArchivosRetirados(function (&$retirados) use ($user, $obra, $comision, $key) {
            $actual = Comision::whereKey($comision->id)->lockForUpdate()->firstOrFail();
            $etapa = $this->etapa($obra, $actual, $key);
            if ($actual->etapas()->where('orden', '>', $etapa->orden)->whereIn('estado', ['en_proceso', 'completada'])->exists()) {
                throw ValidationException::withMessages(['etapa' => 'No puedes borrar esta captura porque ya hay una etapa posterior iniciada o completada.']);
            }
            $columnas = $key === 'perforacion'
                ? ['metros_sujetos_comision' => 0, 'campana_pzas' => 0, 'adicional' => null]
                : [self::PRODUCCION[$key]['columna'] => 0];
            $actual->detalles()->update($columnas);
            foreach ($etapa->fotos()->get() as $foto) {
                $this->retirarArchivo($foto, $retirados);
            }
            $etapa->fotos()->delete();
            $etapa->personal()->delete();
            $etapa->fill([
                'estado' => 'pendiente', 'hora_inicio' => null, 'hora_fin' => null,
                'observaciones' => null, 'completada_at' => null, 'updated_by' => $user->id,
            ])->save();
            $actual->forceFill(['estado' => 'pendiente', 'cerrada_at' => null, 'updated_by' => $user->id])->save();
        });
    }

    public function borrarFoto(User $user, Obra $obra, Comision $comision, string $key, ComisionEtapaFoto $foto): void
    {
        $this->autorizar($user, $obra, $key, 'borrar_fotos');
        $this->conArchivosRetirados(function (&$retirados) use ($obra, $comision, $key, $foto) {
            $actual = Comision::whereKey($comision->id)->lockForUpdate()->firstOrFail();
            if ((int) $actual->obra_id !== (int) $obra->id) {
                throw new AuthorizationException('La comisión no pertenece a esta obra.');
            }
            $etapa = $actual->etapas()->where('etapa', $key)->firstOrFail();
            $actualFoto = $etapa->fotos()->whereKey($foto->id)->lockForUpdate()->firstOrFail();
            $this->retirarArchivo($actualFoto, $retirados);
            $actualFoto->delete();
        });
    }

    private function retirarArchivo(ComisionEtapaFoto $foto, array &$retirados): void
    {
        $disk = $foto->disk ?: 'public';
        $disco = Storage::disk($disk);
        if (! $disco->exists($foto->path)) return;
        $temporal = 'comisiones/.eliminadas/' . Str::uuid() . '.webp';
        if (! $disco->move($foto->path, $temporal)) {
            throw ValidationException::withMessages(['etapa' => 'No se pudo retirar una fotografía. No se borró la captura.']);
        }
        $retirados[] = ['disk' => $disk, 'path' => $foto->path, 'temporal' => $temporal];
    }

    /** Retira archivos antes del commit y los restaura si la escritura falla. */
    private function conArchivosRetirados(callable $operacion): void
    {
        $retirados = [];
        $nuevos = [];
        try {
            DB::transaction(function () use ($operacion, &$retirados, &$nuevos) {
                $operacion($retirados, $nuevos);
            });
        } catch (\Throwable $exception) {
            foreach (array_reverse($retirados) as $archivo) {
                try {
                    if (! Storage::disk($archivo['disk'])->move($archivo['temporal'], $archivo['path'])) {
                        Log::error('No se pudo restaurar una foto de comisión.', $archivo);
                    }
                } catch (\Throwable $restauracion) {
                    Log::error('No se pudo restaurar una foto de comisión.', $archivo + ['exception' => $restauracion]);
                }
            }
            foreach ($nuevos as $archivo) {
                try {
                    if (! Storage::disk($archivo['disk'])->delete($archivo['path'])) {
                        Log::error('No se pudo limpiar una foto nueva de comisión.', $archivo);
                    }
                } catch (\Throwable $limpieza) {
                    Log::error('No se pudo limpiar una foto nueva de comisión.', $archivo + ['exception' => $limpieza]);
                }
            }
            throw $exception;
        }
        DB::afterCommit(function () use ($retirados) {
            foreach ($retirados as $archivo) {
                try {
                    if (! Storage::disk($archivo['disk'])->delete($archivo['temporal'])) {
                        Log::warning('Pendiente limpiar foto retirada de comisión.', $archivo);
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Pendiente limpiar foto retirada de comisión.', $archivo + ['exception' => $exception]);
                }
            }
        });
    }
}
