<?php

namespace App\Support;

use App\Models\Obra;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PlaneacionVersionObra
{
    public static function filas(array $relacion, string $campo, string $bolsa): Collection
    {
        $filas = collect($relacion['partidas'] ?? [])->flatMap(fn (array $grupo) => collect($grupo['filas'])
            ->map(fn (array $fila) => $fila + ['partida' => $grupo['partida']]))
            ->filter(fn (array $fila) => $fila['monto_programado'] != 0);

        if ($filas->contains(fn (array $fila) => $fila['monto_programado'] < 0
            || max($fila['monto_programado'], $fila['cantidad'], $fila['precio_unitario']) > 9999999999999.99)) {
            throw ValidationException::withMessages([
                $campo => 'La planeación de Obra requiere importes positivos y valores dentro del rango de captura.',
            ])->errorBag($bolsa);
        }

        return $filas;
    }

    public static function copiar(Obra $obra, Collection $filas, bool $copiarSemanas = true): void
    {
        foreach ($filas as $fila) {
            $gasto = $obra->planeacionGastos()->create([
                'partida' => $fila['partida'],
                'concepto' => $fila['concepto'],
                'unidad' => $fila['unidad'],
                'cantidad' => $fila['cantidad'] ?? 0,
                'precio_unitario' => $fila['precio_unitario'],
                'monto_programado' => $fila['monto_programado'],
                'numero_semana' => 0,
            ]);
            $semanas = [];
            foreach ($copiarSemanas ? $fila['planeacion']['montos'] : [] as $semana => $centavos) {
                $semanas[] = ['numero_semana' => $semana, 'monto_programado' => $centavos / 100];
            }
            $gasto->semanas()->createMany($semanas);
        }
    }
}
