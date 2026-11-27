<?php

namespace App\Services\Presupuestos;

use App\Models\Presupuesto;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Arr;

class CalculoPresupuestoPorPartidas
{
    private const NOTAS_MUROS_Y_ANCLAS = [
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

    private const PATENTE_POZOS = 'Patente N.º 163291. La invención de los pozos de infiltración prefabricados está protegida por la patente de la SECOFI N.º 163291 de S. H. Rivera. El desarrollo tecnológico y los procedimientos prácticos de aplicación son propiedad de Rivera Construcciones, S.A. de C.V.';

    private const NOTAS_POZOS = [
        'Este presupuesto no incluye trabajos de topografía como trazo y niveles de pozos, bocas de tormenta y trampas de grasa. En caso de derrumbes por materiales no estables, Rivera Construcciones, S.A. de C.V. no es responsable de las reparaciones correspondientes.',
        'Para ejecutar los trabajos se requiere una orden de compra y un anticipo del 100 % del pago total para programar la máquina perforadora y suministrar el material.',
        'Al recibir el pago se programará la realización de la obra.',
        'El tiempo aproximado de ejecución del pozo es de un jornal.',
        'Estos precios están sujetos a cambio sin previo aviso.',
        'Las dimensiones del pozo se cobrarán sobre medidas teóricas y pueden variar en obra.',
        'No incluye reposición de carpeta asfáltica.',
        'No incluye retiro del material producto de la perforación del pozo.',
        'No incluye ajustes de pozos, bocas de tormenta y trampas de grasa.',
        'No incluye la interconexión de la boca de tormenta al pozo o entre pozos.',
        'No incluye relleno fluido de 50 kg en pozos de infiltración.',
        'No incluye el reempedrado o reencarpetamiento en la interconexión en la parte superior (20 cm).',
        'Los pozos son exclusivamente para infiltrar aguas pluviales.',
        'No realizamos trámites ante las dependencias correspondientes a la localidad del proyecto, tales como Obras Públicas y la Comisión Nacional del Agua.',
        'No nos hacemos responsables por daños a instalaciones subterráneas como agua potable, drenaje, instalaciones eléctricas, teléfonos o gasoductos.',
        'Al recibir el pago se dan por aceptadas las cláusulas y notas de este presupuesto.',
    ];

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

        return $calculo + [
            'revision' => hash('sha256', json_encode($calculo, JSON_THROW_ON_ERROR)),
            'notas_base' => $this->notasBase($presupuesto),
        ];
    }

    private function notas(Presupuesto $presupuesto): array
    {
        return [
            'nota_ejecucion' => $presupuesto->presentacion_comercial['nota_ejecucion'] ?? [],
            'notas' => $presupuesto->presentacion_comercial['notas'] ?? $this->notasBase($presupuesto),
            'nota_fija' => $presupuesto->tipo === 'POZOS' ? self::PATENTE_POZOS : null,
        ];
    }

    private function notasBase(Presupuesto $presupuesto): array
    {
        return $presupuesto->tipo === 'POZOS' ? self::NOTAS_POZOS : self::NOTAS_MUROS_Y_ANCLAS;
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
