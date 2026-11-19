<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresupuestoVolPila extends Model
{
    public const PESOS_VARILLA = [
        '2.5' => 0.384, 3 => 0.557, 4 => 0.996, 5 => 1.56, 6 => 2.25, 7 => 3.034,
        8 => 3.975, 9 => 5.033, 10 => 6.225, 11 => 7.503, 12 => 8.938,
    ];

    public const TRASLAPES_METROS = [
        3 => 0.47625, 4 => 0.635, 5 => 0.79375, 6 => 0.9525, 8 => 1.27, 10 => 1.5875,
    ];

    public const DIAMETROS_VOL = [
        '0.200', '0.250', '0.300', '0.400', '0.500', '0.600', '0.700', '0.800',
        '0.900', '1.000', '1.100', '1.200', '1.300', '1.400', '1.500', '1.600',
        '1.800', '2.000', '2.200', '2.400',
    ];

    private const LARGO_BARRA_METROS = 12;

    protected $table = 'presupuesto_vol_pilas';

    protected $fillable = [
        'tipo', 'diametro', 'piezas', 'pilas_por_jornal',
        'longitud_l0', 'longitud_l1', 'longitud_l2', 'acero_kg_por_pila',
        'intervalo_l0', 'intervalo_l1', 'intervalo_l2', 'varillas_l0', 'varillas_l1',
        'volumen_objetivo', 'avance_metros_por_jornal',
    ];

    public function volumetria(float $traslapeVarilla = 0.0): array
    {
        $diametro = (float) $this->diametro;
        $area = pi() * $diametro ** 2 / 4;
        // VOL H6 y F6 conservan la precisión de la fórmula antes de calcular jornales.
        $longitudL0 = $this->volumen_objetivo > 0 && $area > 0
            ? (float) $this->volumen_objetivo / $area
            : (float) $this->longitud_l0;
        $pilasPorJornal = $this->avance_metros_por_jornal > 0 && $longitudL0 > 0
            ? (float) $this->avance_metros_por_jornal / $longitudL0
            : (float) $this->pilas_por_jornal;
        $longitud = $longitudL0 + (float) $this->longitud_l1 + (float) $this->longitud_l2;
        $metrosLineales = $this->piezas * $longitud;
        $volumen = $metrosLineales * $area;
        $perimetro = pi() * max(0, $diametro - 0.15);
        $metrosZunchoL0 = $this->metrosZuncho($perimetro, $longitudL0, (float) $this->intervalo_l0);
        $metrosZunchoL1 = $this->metrosZuncho($perimetro, (float) $this->longitud_l1, (float) $this->intervalo_l1);
        $metrosZunchoL2 = $this->metrosZuncho($perimetro, (float) $this->longitud_l2, (float) $this->intervalo_l2);
        $traslapesZunchos = ceil($metrosZunchoL0 / self::LARGO_BARRA_METROS)
            + ceil($metrosZunchoL1 / self::LARGO_BARRA_METROS)
            + ceil($metrosZunchoL2 / self::LARGO_BARRA_METROS);
        $anclaje = $diametro * 0.4;
        $varillasL0 = $this->varillas_l0 ?? 0;
        $varillasL1 = $this->varillas_l1 ?? 0;
        $pesoVarilla = self::PESOS_VARILLA[6];

        // VOL usa el mismo volumen teórico para perforación, concreto y bentonita.
        return [
            'area' => $area,
            'perimetro' => $perimetro,
            'longitud_l0' => $longitudL0,
            'pilas_por_jornal' => $pilasPorJornal,
            'jornales' => $pilasPorJornal > 0 ? $this->piezas / $pilasPorJornal : 0,
            'longitud' => $longitud,
            'zunchos_l0' => $metrosZunchoL0 * self::PESOS_VARILLA[3],
            'zunchos_l1' => $metrosZunchoL1 * self::PESOS_VARILLA[4],
            'zunchos_l2' => $metrosZunchoL2 * self::PESOS_VARILLA[4],
            // La columna R usa Q27 en las tres zonas para el traslape.
            'traslape_zunchos' => $traslapesZunchos * self::TRASLAPES_METROS[3] * self::PESOS_VARILLA[3],
            'peso_varillas_l0' => $varillasL0 * $pesoVarilla * ($longitudL0 + $anclaje),
            'peso_varillas_l1' => $varillasL1 * $pesoVarilla * ((float) $this->longitud_l1 + $anclaje),
            'traslape_varillas' => $traslapeVarilla * $pesoVarilla * $varillasL0,
            'metros_lineales' => $metrosLineales,
            'perforacion' => $volumen,
            'concreto' => $volumen,
            'bentonita' => $volumen,
            'retiro' => $volumen * 1.3,
            'comision_ml' => max(1, $diametro) * $metrosLineales,
            'acero' => $this->piezas * ($this->acero_kg_por_pila ?? 0),
        ];
    }

    private function metrosZuncho(float $perimetro, float $longitud, float $intervalo): float
    {
        return $intervalo > 0 ? $longitud / $intervalo * $perimetro : 0;
    }
}
