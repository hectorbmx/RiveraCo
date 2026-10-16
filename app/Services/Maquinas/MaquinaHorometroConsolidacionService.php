<?php

namespace App\Services\Maquinas;

use App\Models\Maquina;
use App\Models\MaquinaHorometroAjuste;
use App\Models\ObraMaquinaRegistro;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MaquinaHorometroConsolidacionService
{
    /**
     * Normaliza las fuentes de horómetro y calcula solo avances válidos.
     *
     * El valor de inicio de cada lectura se usa como referencia para no
     * convertir el primer registro de una asignación en horas ficticias.
     */
    public function consolidar(Maquina $maquina): Collection
    {
        $registros = ObraMaquinaRegistro::query()
            ->where('maquina_id', $maquina->id)
            ->with(['obra:id,nombre,clave_obra', 'createdBy:id,name'])
            ->get();

        $ajustes = MaquinaHorometroAjuste::query()
            ->where('maquina_id', $maquina->id)
            ->with(['obra:id,nombre,clave_obra', 'createdBy:id,name'])
            ->get();

        $lecturas = $registros->map(function (ObraMaquinaRegistro $registro) {
            $fecha = $registro->inicio ?? $registro->created_at;

            return [
                'registro_id' => $registro->id,
                'fuente' => $registro->origen ?: 'web_obras',
                'fecha' => $fecha ? Carbon::parse($fecha) : null,
                'obra_id' => $registro->obra_id,
                'obra' => $registro->obra?->nombre ?? $registro->obra?->clave_obra,
                'usuario' => $registro->createdBy?->name,
                'horometro_anterior' => (float) $registro->horometro_inicio,
                'horometro_nuevo' => (float) $registro->horometro_fin,
                'diferencia_reportada' => (float) ($registro->horas ?? 0),
                'created_at' => $registro->created_at,
            ];
        });

        $lecturas = $lecturas->concat($ajustes->map(function (MaquinaHorometroAjuste $ajuste) {
            $fecha = $ajuste->fecha_registro ?? $ajuste->created_at;

            return [
                'registro_id' => $ajuste->id,
                'fuente' => $ajuste->origen ?: 'web_maquinas',
                'fecha' => $fecha ? Carbon::parse($fecha) : null,
                'obra_id' => $ajuste->obra_id,
                'obra' => $ajuste->obra?->nombre ?? $ajuste->obra?->clave_obra,
                'usuario' => $ajuste->createdBy?->name,
                'horometro_anterior' => (float) ($ajuste->horometro_anterior ?? 0),
                'horometro_nuevo' => (float) $ajuste->horometro_nuevo,
                'diferencia_reportada' => (float) ($ajuste->diferencia ?? 0),
                'created_at' => $ajuste->created_at,
            ];
        }));

        $ordenadas = $lecturas
            ->sortBy(fn (array $lectura) => [
                $lectura['fecha']?->timestamp ?? 0,
                $lectura['created_at']?->timestamp ?? 0,
                $lectura['registro_id'],
            ])
            ->values();

        $maximoValido = (float) ($maquina->horometro_base ?? 0);

        return $ordenadas->map(function (array $lectura) use (&$maximoValido) {
            $umbral = max($maximoValido, $lectura['horometro_anterior']);
            $nuevo = $lectura['horometro_nuevo'];
            $estado = 'valida';
            $avanceValido = 0.0;

            if ($nuevo <= $umbral) {
                $estado = abs($nuevo - $umbral) < 0.00001
                    ? 'duplicada'
                    : 'menor';
            } else {
                $avanceValido = round($nuevo - $umbral, 2);
                $maximoValido = $nuevo;
            }

            $lectura['umbral'] = round($umbral, 2);
            $lectura['avance_valido'] = $avanceValido;
            $lectura['estado'] = $estado;
            $lectura['mes'] = $lectura['fecha']?->format('Y-m');

            return $lectura;
        });
    }

    public function resumen(Maquina $maquina): array
    {
        $lecturas = $this->consolidar($maquina);

        return [
            'lecturas' => $lecturas->count(),
            'validas' => $lecturas->where('estado', 'valida')->count(),
            'duplicadas' => $lecturas->where('estado', 'duplicada')->count(),
            'menores' => $lecturas->where('estado', 'menor')->count(),
            'avance_valido' => round((float) $lecturas->sum('avance_valido'), 2),
            'fuentes' => $lecturas->pluck('fuente')->filter()->unique()->values()->all(),
        ];
    }
}
