<?php

namespace App\Support;

use App\Models\Presupuesto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

class FoliosPresupuesto
{
    public static function periodoActual(): string
    {
        return now('America/Mexico_City')->format('Y-m');
    }

    public function resumen(string $periodo): array
    {
        $series = DB::table('presupuesto_folios')->where('periodo', $periodo)->pluck('ultimo_consecutivo', 'tipo');
        $resumen = [];

        foreach (Presupuesto::TIPOS as $tipo => $prefijo) {
            $ultimo = max((int) ($series[$tipo] ?? 0), $this->ultimoExistente($tipo, $periodo));
            $resumen[$tipo] = ['prefijo' => $prefijo, 'ultimo' => $ultimo, 'siguiente' => $this->formatear($tipo, $periodo, $ultimo + 1)];
        }

        return $resumen;
    }

    public function asignar(Presupuesto $presupuesto): void
    {
        $tipo = $presupuesto->tipo;
        $periodo = self::periodoActual();
        $serie = $this->bloquearSerie($tipo, $periodo);
        $siguiente = max((int) $serie->ultimo_consecutivo, $this->ultimoExistente($tipo, $periodo)) + 1;

        DB::table('presupuesto_folios')->where('id', $serie->id)->update(['ultimo_consecutivo' => $siguiente]);
        $presupuesto->folio_serie_id = $serie->id;
        $presupuesto->codigo_proyecto = $this->formatear($tipo, $periodo, $siguiente);
    }

    public function actualizarUltimo(string $tipo, string $periodo, int $ultimo): void
    {
        DB::transaction(function () use ($tipo, $periodo, $ultimo) {
            $serie = $this->bloquearSerie($tipo, $periodo);
            $minimo = max((int) $serie->ultimo_consecutivo, $this->ultimoExistente($tipo, $periodo));
            if ($ultimo < $minimo) {
                throw ValidationException::withMessages([
                    'ultimo_consecutivo' => "El último consecutivo no puede ser menor a {$minimo}.",
                ])->errorBag('folioPresupuesto');
            }

            DB::table('presupuesto_folios')->where('id', $serie->id)->update(['ultimo_consecutivo' => $ultimo]);
        }, 3);
    }

    private function bloquearSerie(string $tipo, string $periodo): stdClass
    {
        $serie = ['tipo' => $tipo, 'periodo' => $periodo];
        DB::table('presupuesto_folios')->insertOrIgnore($serie);

        return DB::table('presupuesto_folios')->where($serie)->lockForUpdate()->sole();
    }

    private function ultimoExistente(string $tipo, string $periodo): int
    {
        $base = Presupuesto::TIPOS[$tipo];
        $fecha = str_replace('-', '', substr($periodo, 2));
        $compacto = $base . $fecha . '-';
        $anterior = $base . '-' . $fecha . '-';
        $patron = '/^(?:' . $compacto . '|' . $anterior . ')(\d+)(?:\s*-?\s*R\s*\d*)?$/i';

        return Presupuesto::query()
            ->where('codigo_proyecto', 'like', $compacto . '%')
            ->orWhere('codigo_proyecto', 'like', $anterior . '%')
            ->pluck('codigo_proyecto')
            ->max(fn (string $folio) => preg_match($patron, $folio, $partes) ? (int) $partes[1] : 0) ?? 0;
    }

    private function formatear(string $tipo, string $periodo, int $consecutivo): string
    {
        $fecha = str_replace('-', '', substr($periodo, 2));

        return sprintf('%s%s-%02d', Presupuesto::TIPOS[$tipo], $fecha, $consecutivo);
    }
}
