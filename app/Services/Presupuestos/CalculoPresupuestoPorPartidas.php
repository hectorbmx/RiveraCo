<?php

namespace App\Services\Presupuestos;

use App\Models\Presupuesto;
use App\Support\PlantillasNotasPresupuesto;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;

class CalculoPresupuestoPorPartidas
{
    public function presentacion(Presupuesto $presupuesto): array
    {
        $notas = $this->notas($presupuesto);
        if ($notas['nota_fija'] !== null) {
            array_unshift($notas['notas'], $notas['nota_fija']);
        }

        return $this->calcular($presupuesto) + Arr::only($presupuesto->presentacion_comercial ?? [], [
            'domicilio', 'atencion', 'telefono', 'correo', 'ubicacion', 'anticipo', 'condiciones_pago',
            'plazo_semanas', 'anticipo_y_plazo_en_notas',
        ]) + $notas;
    }

    public function estadoEditor(Presupuesto $presupuesto): array
    {
        $calculo = $this->calcular($presupuesto) + $this->notas($presupuesto);

        return $calculo + ['revision' => hash('sha256', json_encode($calculo, JSON_THROW_ON_ERROR))];
    }

    private function notas(Presupuesto $presupuesto): array
    {
        $grupo = PlantillasNotasPresupuesto::grupo($presupuesto->tipo);

        return [
            'nota_ejecucion' => $presupuesto->presentacion_comercial['nota_ejecucion'] ?? [],
            'notas' => $presupuesto->presentacion_comercial['notas']
                ?? array_column(PlantillasNotasPresupuesto::delBorrador($presupuesto, $grupo), 'texto'),
            'nota_fija' => $presupuesto->tipo === 'POZOS' ? PlantillasNotasPresupuesto::PATENTE_POZOS : null,
        ];
    }

    public function calcular(Presupuesto $presupuesto): array
    {
        $presupuesto->loadMissing('partidas.subpartidas');
        $subtotal = BigDecimal::zero();
        $partidas = [];

        foreach ($presupuesto->partidas->values() as $indice => $partida) {
            $subtotalPartida = BigDecimal::zero();
            $subpartidas = [];

            foreach ($partida->subpartidas->values() as $posicion => $subpartida) {
                $importe = BigDecimal::of($subpartida->cantidad)->multipliedBy($subpartida->precio_unitario);
                $subtotalPartida = $subtotalPartida->plus($importe);
                $subpartidas[] = $subpartida->only(['id', 'orden', 'concepto', 'unidad', 'cantidad', 'precio_unitario'])
                    + ['numero' => (string) ($posicion + 1), 'importe' => $this->moneda($importe)];
            }

            $subtotal = $subtotal->plus($subtotalPartida);
            $partidas[] = $partida->only(['id', 'orden', 'nombre']) + [
                'clave' => $this->clave($indice + 1),
                'subpartidas' => $subpartidas,
                'subtotal' => $this->moneda($subtotalPartida),
            ];
        }

        // El Excel suma los importes con su precisión original antes de mostrarlos a dos decimales.
        $tasaIva = BigDecimal::of($presupuesto->presentacion_comercial['iva'] ?? 16);
        $iva = $subtotal->multipliedBy($tasaIva)->exactlyDividedBy(100);

        return [
            'partidas' => $partidas,
            'subtotal' => $this->moneda($subtotal),
            'tasa_iva' => $this->moneda($tasaIva),
            'iva' => $this->moneda($iva),
            'total' => $this->moneda($subtotal->plus($iva)),
        ];
    }

    private function moneda(BigDecimal $importe): string
    {
        return (string) $importe->toScale(2, RoundingMode::HALF_UP);
    }

    private function clave(int $numero): string
    {
        $clave = '';
        while ($numero > 0) {
            $numero--;
            $clave = chr(65 + $numero % 26) . $clave;
            $numero = intdiv($numero, 26);
        }

        return $clave;
    }
}
