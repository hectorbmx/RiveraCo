<?php

namespace App\Support;

use App\Models\PresupuestoDetalle;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlantillaApupi
{
    public const FIANZAS = 'FIANZAS Y SEGURO DE RESPONSABILIDAD CIVIL';
    public const INDIRECTO = 33;
    public const TASAS_FIANZA = [
        'apupi-154' => 0.0075,
        'apupi-155' => 0.003,
        'apupi-156' => 0.0045,
    ];

    // El número de fila del machote es la clave de cada concepto. Las claves de Traslado ya existen en los ajustes guardados.
    private const PARTIDAS = [
        'PERFORACION' => [
            24 => ['Retro micropilas', 'X JOR.', 5600, 'jornales'],
            25 => ['B2, B3, B5 y B6', 'X JOR.', 8500, 'jornales'],
            26 => ['B7 y B8', 'X JOR.', 12500, 'jornales'],
            27 => ['Spiradrill S1 S2 S3', 'X JOR.', 15000, 'jornales'],
            28 => ['Bauer BG-9V', 'X JOR.', 20000, 'jornales'],
            29 => ['Bauer BG-12 H', 'X JOR.', 22500, 'jornales'],
            30 => ['Bauer BG-18V', 'X JOR.', 25000, 'jornales'],
            31 => ['Bauer BG-25V', 'X JOR.', 27500, 'jornales'],
            32 => ['Bauer BG-26V', 'X JOR.', 30000, 'jornales'],
            33 => ['Combustibles de perforadoras', 'LTO. X JOR', 25, 'jornales'],
            35 => ['Viáticos (3 personas)', 'X JOR.', 900, 'dias', 'viaticos'],
            36 => ['Hospedaje', 'X JOR.', 2400, 'dias'],
            37 => ['Oper. Bauer', 'X JOR.', 1800, 'dias', 'oper-bauer'],
            38 => ['Oper. Bores', 'X JOR.', 1200, 'dias', 'oper-bores'],
            39 => ['Ayudantes', 'X JOR.', 900, 'dias', 'ayudantes'],
            40 => ['Residentes', 'X JOR.', 2400, 'dias', 'residentes'],
            41 => ['Segurista', 'X JOR.', 1950, 'dias', 'segurista'],
            42 => ['Topógrafo', 'X JOR.', 1800, 'dias', 'topografo'],
            43 => ['Comisiones por ML (perforación)', 'ML.', 8.3, 'directo'],
            45 => ['T. E. personal (2 h/día/persona)', 'HRS. EXTRA', 235.71, 'horas_extra', 't-e-personal'],
            46 => ['Renta camioneta de apoyo', 'X JOR.', 600, 'jornales'],
            47 => ['Traslado camioneta', 'X JOR.', 600, 'jornales'],
            48 => ['Supervisión', 'VISITAS', 5000, 'directo'],
            49 => ['Gastos varios diversos', 'X DIAS.', 1600, 'activar_jornales'],
            50 => ['Imprevistos', 'LOTE', 0, 'reserva'],
            51 => ['Polímero y equipos preparación', 'M3.', 356, 'activar_jornales'],
            52 => ['Gastos almacén', 'LOTE', 0, 'reserva'],
            53 => ['Brocal de ademe metálico', 'ML.', 400, 'directo'],
            54 => ['Boletos de camión (1 boleto por persona)', 'PER/BOL', 950, 'directo', 'boletos-de-camion'],
            55 => ['Partida de seguridad', 'LOTE', 25000, 'seguridad'],
        ],
        'GRUA PARA COLOCAR ACERO DE REFUERZO Y CONCRETO' => [
            62 => ['Renta de grúa', 'JOR', 9600, 'jornales'],
            63 => ['Traslado de grúa', 'Viaje sencillo', 15000, 'directo'],
            64 => ['Diésel grúa', 'LTS', 25, 'jornales'],
            65 => ['Ayudantes (2)', 'JOR', 900, 'activar_dias', 'ayudantes'],
            66 => ['Viáticos (2 personas)', 'JOR', 300, 'activar_dias', 'viaticos'],
            67 => ['Hospedaje', 'JOR', 600, 'activar_dias'],
            68 => ['Cabo y ayudante RC', 'JOR', 933.33, 'activar_jornales', 'cabo-y-ay-rc'],
            69 => ['Comisiones por m³ (concreto)', 'M3', 4.8, 'directo', 'comisiones-por-m3-concreto'],
            70 => ['Tubería tremie', 'M3', 40, 'directo'],
            71 => ['Ayudantes externos (1)', 'JOR', 1050, 'activar_jornales', 'ay-externos'],
            72 => ['Boletos de camión', 'PER/BOL', 950, 'directo', 'boletos-de-camion'],
            73 => ['Tiempo extra personal', 'HRS.', 92.86, 'horas_extra'],
        ],
        'BENTONITA' => [
            81 => ['Suministro de lodo bentonítico', 'TON', 2250, 'directo'],
            82 => ['Flete de bentonita', 'TON', 1500, 'directo'],
            83 => ['Descarga bentonita', 'TON', 120, 'directo'],
            84 => ['Equipo de batido', 'M3', 65, 'directo'],
            85 => ['Combustible motobombas', 'LTS', 25, 'jornales'],
            86 => ['Ayudantes (2)', 'JOR', 1650, 'activar_dias', 'ayudantes'],
            87 => ['Viáticos (1 persona)', 'DIAS.', 600, 'activar_dias', 'viaticos'],
            88 => ['Hospedaje', 'DIAS.', 1200, 'activar_dias'],
            89 => ['Boletos de camión', 'PER/BOL', 950, 'directo', 'boletos-de-camion'],
            90 => ['Comisiones por m³ (bentonita)', 'M3', 4.8, 'directo', 'comisiones-por-m3-bentonita'],
            91 => ['Tiempo extra personal', 'HRS.', 185.71, 'horas_extra'],
            92 => ['Pipa de agua', 'M3', 0, 'directo'],
        ],
        'RETIRO DE LODOS BENTONITICOS' => [
            98 => ['Retiro de lodo bentonítico', 'M3', 350, 'directo'],
            99 => ['Comprobante de tiro en tiradero autorizado', 'M3', 0, 'directo'],
        ],
        'HABILITADO DE ACERO DE REFUERZO' => [
            106 => ['Mano de obra', 'KG', 3.5, 'directo'],
            107 => ['IMSS', 'KG', 1.75, 'directo'],
            108 => ['Separador plástico', 'PZA', 29, 'directo'],
            109 => ['Alambre recocido', 'KG', 20, 'directo'],
            110 => ['Conectores', 'PZA', 82, 'directo'],
            111 => ['Máquina roscadora', 'MES', 35000, 'directo'],
            112 => ['Generador de energía eléctrica', 'MES', 38000, 'directo'],
        ],
        'SUMINISTRO DE ACERO' => [
            118 => ['Varilla', 'KG', 17, 'directo'],
            119 => ['Alambre recocido', 'KG', 24, 'directo'],
            120 => ['Separador plástico', 'PZA', 0, 'directo'],
        ],
        'SUMINISTRO DE CONCRETO' => [
            126 => ['Concreto', 'M3', 1735, 'directo'],
        ],
        'DESCABECE' => [
            131 => ['Diámetro de pila 60 cm', 'PZA', 400, 'directo'],
            132 => ['Diámetro de pila 80 cm', 'PZA', 550, 'directo'],
            133 => ['Diámetro de pila 100 cm', 'PZA', 750, 'directo'],
            134 => ['Diámetro de pila 120 cm', 'PZA', 1200, 'directo'],
            135 => ['Diámetro de pila 140 cm', 'PZA', 1750, 'directo'],
            136 => ['Diámetro de pila 160 cm', 'PZA', 2400, 'directo'],
        ],
        'TOPOGRAFIA' => [
            142 => ['Topografía', 'SEM', 12000, 'directo'],
        ],
        'RETIRO DE MATERIALES DE PERFORACION' => [
            147 => ['Retro', 'JOR', 4800, 'activar_jornales'],
            148 => ['Volteo', 'M3', 114.29, 'directo'],
        ],
        self::FIANZAS => [
            154 => ['Fianza anticipo', 'PZA', 0, 'directo'],
            155 => ['Fianza garantía y vicios ocultos', 'PZA', 0, 'directo'],
            156 => ['Seguro de responsabilidad civil', 'PZA', 0, 'directo'],
        ],
    ];

    public static function conceptos(): array
    {
        $conceptos = [];
        foreach (PresupuestoDetalle::CONCEPTOS_TRASLADO as $codigo => [$nombre, $precio]) {
            $conceptos[$codigo] = [PresupuestoDetalle::PARTIDA_TRASLADO, $nombre, 'Viaje sencillo', $precio, 'directo'];
        }
        foreach (self::PARTIDAS as $partida => $filas) {
            foreach ($filas as $numero => $fila) {
                $conceptos['apupi-' . $numero] = [$partida, ...$fila];
            }
        }

        return $conceptos;
    }

    public static function codigoImportado(PresupuestoDetalle $detalle): ?string
    {
        if ($detalle->partidaApupi() === PresupuestoDetalle::PARTIDA_TRASLADO) {
            return $detalle->codigoTrasladoApupi();
        }

        $concepto = Str::slug($detalle->concepto);
        foreach (self::PARTIDAS[$detalle->partidaApupi()] ?? [] as $numero => $fila) {
            $prefijo = $fila[4] ?? null;
            if ($concepto === Str::slug($fila[0]) || ($prefijo !== null && ($concepto === $prefijo || str_starts_with($concepto, $prefijo . '-')))) {
                return 'apupi-' . $numero;
            }
        }

        return null;
    }

    public static function partidasConIndirecto(): array
    {
        return [PresupuestoDetalle::PARTIDA_TRASLADO, ...array_keys(array_diff_key(self::PARTIDAS, [self::FIANZAS => true]))];
    }

    public static function sugerenciasCantidad(array $vol, array $duracion): array
    {
        $realista = $duracion['realista'];
        $sugerencias = [
            'apupi-45' => $realista['jornales'] * 4,
            'apupi-73' => $realista['jornales'] * 4,
            'apupi-91' => $realista['jornales'] * 4,
            'apupi-142' => $realista['semanas'],
        ];
        if ($vol['filas']->isEmpty()) {
            return $sugerencias;
        }

        $totales = $vol['totales'];
        $bentonita = $totales['bentonita'];
        $concreto = $totales['concreto'];
        $varilla = $totales['acero'] * 1.1;
        $sugerencias += [
            'apupi-43' => $totales['comision_ml'],
            'apupi-69' => $concreto,
            'apupi-70' => $concreto,
            'apupi-81' => $bentonita * 60 / 1000,
            'apupi-82' => $bentonita * 60 / 1000,
            'apupi-83' => $bentonita * 60 / 1000,
            'apupi-84' => $bentonita,
            'apupi-90' => $bentonita,
            'apupi-92' => $bentonita,
            'apupi-98' => $bentonita,
            'apupi-99' => $bentonita,
            'apupi-106' => $totales['acero'],
            'apupi-107' => $totales['acero'],
            'apupi-118' => $varilla,
            'apupi-119' => $varilla * 0.05,
            'apupi-120' => $totales['piezas'] * ceil($totales['longitud_l0_promedio'] / 3),
            'apupi-126' => $concreto * 1.1,
            'apupi-148' => $totales['retiro'],
        ];
        foreach (['0.600', '0.800', '1.000', '1.200', '1.400', '1.600'] as $indice => $diametro) {
            $sugerencias['apupi-' . (131 + $indice)] = $vol['porDiametro'][$diametro]['piezas'];
        }

        return $sugerencias;
    }

    public static function precioBase(string $codigo, float $base, array $filas, float $venta): float
    {
        return round(match ($codigo) {
            'apupi-45' => (2600 + (self::rentas($filas, 28)->sum('cantidad') >= 1 ? 6000 : 4000)) / 28,
            'apupi-50' => self::reserva($filas, 0.1),
            'apupi-52' => self::reserva($filas, 0.2),
            'apupi-64', 'apupi-85' => $filas['apupi-33']['precio_unitario'],
            'apupi-72', 'apupi-89' => $filas['apupi-54']['precio_unitario'],
            'apupi-107' => $filas['apupi-106']['precio_unitario'] * 0.5,
            'apupi-154', 'apupi-155', 'apupi-156' => $venta * self::TASAS_FIANZA[$codigo],
            default => $base,
        }, 2);
    }

    private static function rentas(array $filas, int $desde = 24): Collection
    {
        return collect($filas)->only(array_map(fn ($numero) => 'apupi-' . $numero, range($desde, 32)));
    }

    private static function reserva(array $filas, float $tasa): float
    {
        $rentas = self::rentas($filas);

        return $rentas->sum('cantidad') >= 1 ? $rentas->sum('importes.realista') * $tasa : 0;
    }
}
