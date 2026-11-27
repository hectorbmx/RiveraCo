<?php

namespace App\Support;

use App\Models\CatalogoPila;
use App\Models\Obra;
use App\Models\PresupuestoVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerforacionesVersionObra
{
    public static function versionAprobada(Obra $obra): ?PresupuestoVersion
    {
        if (! in_array($obra->tipo_obra, ['PILAS', 'POZOS'], true)) {
            return null;
        }

        $version = $obra->presupuestos_vinculados()
            ->where('presupuestos.obra_generada_id', $obra->id)
            ->with('versionAprobada')
            ->first()
            ?->versionAprobada;

        return empty($version?->datos['vol']['filas']) ? null : $version;
    }

    public static function cargarAprobadas(Obra $obra): int
    {
        return DB::transaction(function () use ($obra): int {
            $actual = Obra::lockForUpdate()->findOrFail($obra->id);
            $version = self::versionAprobada($actual);
            if (! $version) {
                throw ValidationException::withMessages([
                    'perforaciones' => 'Esta obra no tiene una versión aprobada con perforaciones en VOL.',
                ]);
            }
            if ($actual->pilas()->exists()) {
                throw ValidationException::withMessages([
                    'perforaciones' => 'La obra ya tiene perforaciones registradas. Revisa las asignaciones existentes.',
                ]);
            }

            return self::copiar($actual, $version);
        }, 3);
    }

    public static function copiar(Obra $obra, PresupuestoVersion $version): int
    {
        $filas = $version->datos['vol']['filas'] ?? [];
        if (! $filas || ! in_array($obra->tipo_obra, ['PILAS', 'POZOS'], true)) {
            return 0;
        }

        $catalogo = CatalogoPila::where('activa', true)->orderBy('codigo')->get()->groupBy('diametro_cm');
        $prefijo = $obra->tipo_obra === 'POZOS' ? 'PO' : 'PI';
        $registros = [];
        foreach ($filas as $fila) {
            $tipo = trim((string) ($fila['pila']['tipo'] ?? ''));
            $piezas = (int) ($fila['pila']['piezas'] ?? 0);
            $diametro = round((float) ($fila['pila']['diametro'] ?? 0), 2);
            $profundidad = round((float) ($fila['vol']['longitud'] ?? 0), 2);
            if ($piezas <= 0 || $piezas > 65535 || $diametro <= 0 || $diametro > 999999.99
                || $profundidad <= 0 || $profundidad > 999999.99) {
                throw ValidationException::withMessages([
                    'perforaciones' => "Revisa cantidad, diámetro y profundidad de la perforación {$tipo} en la versión.",
                ]);
            }

            $centimetros = (int) round($diametro * 100);
            $opciones = $catalogo->get($centimetros, collect());
            $entrada = $opciones->firstWhere('codigo', $prefijo . $centimetros)
                ?? ($opciones->count() === 1 ? $opciones->first() : null);
            $registros[] = [
                'numero_pila' => count($registros) + 1,
                'tipo' => $entrada?->codigo ?? $tipo,
                'cantidad_programada' => $piezas,
                'diametro_proyecto' => $diametro,
                'profundidad_proyecto' => $profundidad,
                'activo' => true,
                'notas' => 'Tipo VOL ' . $tipo . ' · Presupuesto ' . $version->folio(),
            ];
        }

        $obra->pilas()->createMany($registros);

        return count($registros);
    }
}
