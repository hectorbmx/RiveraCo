<?php

namespace App\Services\Presupuestos;

use App\Models\Presupuesto;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;

class CalculoPresupuestoPorPartidas
{
    private const NOTAS_IMPORTANTES = [
        'Este presupuesto incluye EPP según la norma, seguro social y personal calificado.',
        'Este presupuesto no incluye segurista, suministro de acero ni concreto, ni habilitado de acero.',
        'Este presupuesto se contempla ejecutar mediante la modalidad de precio alzado. Si hay un aumento en el mercado del 3 %, se ajustarán los precios.',
        'Al aceptar este presupuesto, enviar la orden de compra y el presupuesto firmado de autorizado por su representante legal.',
        'No nos hacemos responsables bajo ninguna circunstancia por daños a instalaciones subterráneas de cualquier tipo.',
        'Debajo de los puntos a perforar no deberán existir trabes, losas de concreto, tuberías o cualquier tipo de obstáculo.',
        'Cualquier actividad fuera de presupuesto deberá contar con un precio unitario autorizado antes de ejecutar los trabajos.',
        'Todo nuestro personal está asegurado ante el IMSS, por lo que se solicita que se nos comparta el SIROC para su debido registro.',
        'Se requiere por parte del cliente un velador o seguridad privada para el resguardo de la maquinaria y herramienta dentro de obra.',
        'En obras fuera de Guadalajara, nuestro personal tomará salida de obra al final de cada tercera semana y se ausentará el viernes y el sábado de esa semana.',
        'Vigencia del presupuesto: 30 días a partir de la fecha de elaboración.',
    ];

    public function presentacion(Presupuesto $presupuesto): array
    {
        return $this->calcular($presupuesto) + Arr::only($presupuesto->presentacion_comercial ?? [], [
            'domicilio', 'atencion', 'telefono', 'correo', 'ubicacion', 'anticipo', 'condiciones_pago',
            'plazo_semanas', 'anticipo_y_plazo_en_notas',
        ]) + $this->notas($presupuesto);
    }

    public function estadoEditor(Presupuesto $presupuesto): array
    {
        $calculo = $this->calcular($presupuesto) + $this->notas($presupuesto);

        return $calculo + ['revision' => hash('sha256', json_encode($calculo, JSON_THROW_ON_ERROR))];
    }

    private function notas(Presupuesto $presupuesto): array
    {
        return [
            'nota_ejecucion' => $presupuesto->presentacion_comercial['nota_ejecucion'] ?? [],
            'notas' => $presupuesto->presentacion_comercial['notas'] ?? self::NOTAS_IMPORTANTES,
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
