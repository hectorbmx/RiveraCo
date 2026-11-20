<?php

namespace App\Support;

use App\Models\Presupuesto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PresentacionPresupuesto
{
    public const ENCABEZADO_PERFORACION = 'encabezado-perforacion';

    private const CONCEPTOS = [
        'venta-194' => 'Campana al fondo de la pila.',
        'venta-195' => 'Brocal de ademe metálico para estabilizar el inicio de la perforación.',
        'venta-206' => 'Suministro, preparación y colocación de lodos bentoníticos para estabilizar la perforación. Incluye mano de obra, herramienta menor y supervisión.',
        'venta-203' => 'Habilitado y armado de acero de refuerzo para pilas. Incluye mano de obra, herramienta menor y supervisión.',
        'venta-204' => 'Suministro y colocación de conectores para el acero de refuerzo.',
        'venta-199-acero' => 'Colocación de armado de acero de refuerzo dentro de la perforación. Incluye mano de obra, herramienta menor y supervisión.',
        'venta-200-concreto' => 'Colocación de concreto de revenimiento de 18 cm con sistema de tubería Tremie. Incluye mano de obra, herramienta menor y supervisión. El suministro durante el colado deberá ser continuo para proteger la integridad de la pila.',
        'venta-201' => 'Colocación de acero de refuerzo y concreto con sistema de tubería Tremie dentro de la perforación.',
        'venta-207' => 'Carga y retiro de lodos bentoníticos mediante vactor o pipa a tiradero autorizado. Incluye mano de obra, operación y supervisión.',
        'venta-209' => 'Suministro de concreto para pilas de cimentación.',
        'venta-210' => 'Suministro de acero de refuerzo para pilas de cimentación.',
        'venta-197' => 'Servicio de topografía por iguala semanal. Incluye equipo, mano de obra y herramienta.',
        'venta-212' => 'Carga y retiro de material producto de la perforación a tiradero autorizado. Incluye mano de obra, operación y supervisión.',
        'venta-213' => 'Descabece de pila de concreto reforzado.',
        'venta-215' => 'Traslado de maquinaria, herramientas y accesorios del almacén a la obra y regreso.',
        'venta-217' => 'Fianzas',
        'venta-218' => 'Costo de financiamiento por cadenas productivas.',
    ];

    public static function preparar(Presupuesto $presupuesto, Collection $costos, array $resumen, array $duracion): array
    {
        $captura = $presupuesto->presentacion_comercial ?? [];
        $cliente = $presupuesto->cliente;
        $direccion = collect([
            $cliente?->direccion ?: $cliente?->calle,
            collect([$cliente?->colonia, $cliente?->codigo_postal])->filter()->implode(', '),
            collect([$cliente?->ciudad, $cliente?->estado])->filter()->implode(', '),
        ])->filter()->implode("\n");
        $rentas = $costos->only(array_map(fn (int $fila) => 'apupi-' . $fila, range(24, 32)))
            ->filter(fn (array $fila) => $fila['cantidad'] > 0);
        $equipoPesado = $rentas->isEmpty()
            ? str_starts_with($presupuesto->equipo_propuesto ?? '', 'bauer_')
            : $rentas->hasAny(['apupi-28', 'apupi-29', 'apupi-30', 'apupi-31', 'apupi-32']);
        $filas = self::filas(collect($resumen['propuesta']['filas']), $captura);
        $subtotal = PropuestaApupi::totalVenta($filas);
        $tasa = $captura['iva'] ?? 16;
        $iva = round($subtotal * (float) $tasa / 100, 2);

        return [
            'domicilio' => $captura['domicilio'] ?? $direccion,
            'atencion' => $captura['atencion'] ?? null,
            'telefono' => $captura['telefono'] ?? $cliente?->telefono,
            'correo' => $captura['correo'] ?? $cliente?->email,
            'ubicacion' => $captura['ubicacion'] ?? null,
            'descripcion_perforacion' => $captura['descripcion_perforacion'] ?? 'Perforación con perforadora rotaria. Incluye operación y supervisión.',
            'anticipo' => $captura['anticipo'] ?? null,
            'condiciones_pago' => $captura['condiciones_pago'] ?? null,
            'plazo_semanas' => $captura['plazo_semanas'] ?? $duracion['realista']['semanas'],
            'nota_ejecucion' => $captura['nota_ejecucion'] ?? [],
            'notas' => $captura['notas'] ?? self::notas($equipoPesado, $filas),
            'filas' => self::numerar($filas)->all(),
            'subtotal' => $subtotal,
            'tasa_iva' => $tasa,
            'iva' => $iva,
            'total' => round($subtotal + $iva, 2),
        ];
    }

    public static function numerar(Collection $filas): Collection
    {
        $partida = 0;
        $subpartida = 0;

        return $filas->map(function (array $fila) use (&$partida, &$subpartida) {
            $encabezado = null;
            if ($fila['partida'] === 'perforacion') {
                if ($subpartida === 0) {
                    $encabezado = (string) ++$partida;
                }
                $numero = self::letrasSubpartida(++$subpartida);
            } else {
                $numero = (string) ++$partida;
            }

            return $fila + ['numero' => $numero, 'numero_encabezado' => $encabezado];
        });
    }

    public static function htmlNotaEjecucion(array $operaciones): string
    {
        $html = '';
        $renglon = '';
        $lista = null;

        foreach ($operaciones as $operacion) {
            $atributos = $operacion['attributes'] ?? [];
            $fragmentos = explode("\n", $operacion['insert']);
            foreach ($fragmentos as $indice => $fragmento) {
                if ($indice > 0) {
                    $tipo = match ($atributos['list'] ?? null) {
                        'ordered' => 'ol',
                        'bullet' => 'ul',
                        default => null,
                    };
                    if ($tipo !== $lista) {
                        if ($lista !== null) {
                            $html .= "</{$lista}>";
                        }
                        if ($tipo !== null) {
                            $html .= "<{$tipo}>";
                        }
                        $lista = $tipo;
                    }

                    $etiqueta = $lista === null ? 'p' : 'li';
                    $html .= "<{$etiqueta}>" . ($renglon === '' ? '<br>' : $renglon) . "</{$etiqueta}>";
                    $renglon = '';
                }

                if ($fragmento === '') {
                    continue;
                }
                $texto = e($fragmento);
                if ($atributos['italic'] ?? false) {
                    $texto = "<em>{$texto}</em>";
                }
                if ($atributos['bold'] ?? false) {
                    $texto = "<strong>{$texto}</strong>";
                }
                $renglon .= $texto;
            }
        }

        if ($lista !== null) {
            $html .= "</{$lista}>";
        }
        if ($renglon !== '') {
            $html .= "<p>{$renglon}</p>";
        }

        return $html;
    }

    private static function letrasSubpartida(int $indice): string
    {
        $letras = '';
        while ($indice > 0) {
            $indice--;
            $letras = chr(ord('a') + $indice % 26) . $letras;
            $indice = intdiv($indice, 26);
        }

        return $letras;
    }

    public static function descripcion(string $clave, array $fila): string
    {
        if ($clave === PropuestaApupi::PERFORACION_M3) {
            return 'Perforación desde 120 cm hasta 150 cm de diámetro.';
        }

        return self::CONCEPTOS[$clave] ?? $fila['concepto'];
    }

    private static function filas(Collection $filas, array $captura): Collection
    {
        $orden = array_flip(array_keys(self::CONCEPTOS));

        return $filas->filter(fn ($fila) => $fila['cantidad'] > 0 && $fila['precio_unitario'] > 0)
            ->sortBy(fn ($fila, $clave) => $fila['partida'] === 'perforacion' ? -1 : ($orden[$clave] ?? PHP_INT_MAX))
            ->map(fn ($fila, $clave) => [
                'partida' => $fila['partida'],
                'concepto' => $captura['descripciones'][$clave] ?? self::descripcion($clave, $fila),
                'unidad' => $captura['unidades'][$clave] ?? $fila['unidad'],
                'cantidad' => $fila['cantidad'],
                'precio_unitario' => $fila['precio_unitario'],
                'importe' => $fila['importe'],
            ]);
    }

    private static function notas(bool $equipoPesado, Collection $filas): array
    {
        $exclusiones = collect([
            'venta-209' => 'suministro de concreto', 'venta-210' => 'suministro de acero',
            'venta-203' => 'habilitado de acero', 'venta-197' => 'servicio de topografía',
            'venta-212' => 'retiro de material producto de la perforación',
        ])->reject(fn ($concepto, $clave) => $filas->has($clave))->values();
        $peso = $equipoPesado ? '60' : '30';
        $horaExtra = $equipoPesado ? '3,800' : '2,800';
        $diametro = $equipoPesado ? '1.60' : '1.00';
        $volumen = $equipoPesado ? '2.00' : '1.00';

        return array_values(array_filter([
            'Se requiere la mecánica de suelos y visita a la obra para validar el presupuesto.',
            'Incluye EPP, seguro social y personal calificado.',
            $exclusiones->isEmpty() ? null : 'Esta propuesta no incluye ' . $exclusiones->join(', ', ' ni ') . '.',
            'Se cobrará la cantidad real medida en obra. Si cambia el volumen o la ejecución se divide en varios eventos, se revisarán los precios y traslados.',
            'La perforación en roca o escombro queda fuera del alcance.',
            'Cuando el armado sea por cuenta del cliente, deberá acercarlo al punto de perforación.',
            'Equipo sujeto a disponibilidad. Confirmar antes del inicio de los trabajos.',
            'Para autorizar, enviar la orden de compra y el presupuesto firmado.',
            'Si el material perforado difiere del presupuestado, se ajustarán los precios unitarios.',
            "El terreno y sus accesos deberán soportar aproximadamente {$peso} toneladas.",
            'El cliente deberá informar la ubicación de las instalaciones subterráneas.',
            'Los puntos de perforación deberán estar libres de obstáculos.',
            'Mantener los cables eléctricos a un mínimo de 6 m de la torre.',
            'El ingreso a la obra y la zona de perforación deberán ser accesibles.',
            'Hora extra o equipo en espera por causas ajenas a Rivera Construcciones: $' . $horaExtra . '.00 por hora de perforadora y $1,600.00 por hora de grúa.',
            'El cliente proporcionará trazo, niveles, agua, iluminación y corriente eléctrica según lo requiera la obra.',
            'Marcar el centro de cada perforación e indicar los niveles de perforación, acero y colado.',
            'Se requiere retroexcavadora para mover el material producto de la perforación a un primer punto.',
            'Para preparar lodos bentoníticos, se requiere un cárcamo de al menos 20 m³.',
            'Las actividades fuera del presupuesto requieren autorización previa del precio unitario.',
            'Compartir el SIROC para el registro correspondiente.',
            'Se requiere velador o seguridad privada para el resguardo de maquinaria y herramientas.',
            "Las pilas de diámetro mayor a {$diametro} m se perforan con rima. Considerar aproximadamente {$volumen} m³ de sobreperforación en el suministro de concreto.",
            'En obras fuera de Guadalajara el personal toma salida de obra al final de cada tercera semana.',
            'Vigencia del presupuesto: 30 días a partir de la fecha de elaboración.',
        ]));
    }

    public static function notaPorRenglon(string $texto): array
    {
        return collect(preg_split('/\R/u', $texto))->map(fn ($linea) => Str::squish($linea))->filter()->values()->all();
    }
}
