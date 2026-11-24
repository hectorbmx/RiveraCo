<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresupuestoVolPila extends Model
{
    public const AVANCE_PREDETERMINADO = 60;
    public const CALIBRE_PREDETERMINADO = 6;

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
        'calibre_zuncho_l0', 'calibre_zuncho_l1', 'calibre_zuncho_l2',
        'calibre_varilla_l0', 'calibre_varilla_l1',
        'volumen_objetivo', 'avance_metros_por_jornal',
    ];

    public function volumetria(float $traslapeVarilla = 0.0): array
    {
        $diametro = (float) $this->diametro;
        $area = pi() * $diametro ** 2 / 4;
        // Conserva la precisión de L0 cuando se obtiene a partir del volumen objetivo.
        $longitudL0 = $this->volumen_objetivo > 0 && $area > 0
            ? (float) $this->volumen_objetivo / $area
            : (float) $this->longitud_l0;
        $longitud = $longitudL0 + (float) $this->longitud_l1 + (float) $this->longitud_l2;
        $metrosLineales = $this->piezas * $longitud;
        // Las filas anteriores al campo de avance conservan su rendimiento capturado.
        $avance = match (true) {
            $this->avance_metros_por_jornal > 0 => (float) $this->avance_metros_por_jornal,
            $longitud > 0 && $this->pilas_por_jornal > 0 => $longitud * (float) $this->pilas_por_jornal,
            default => (float) self::AVANCE_PREDETERMINADO,
        };
        $volumen = $metrosLineales * $area;
        $perimetro = pi() * max(0, $diametro - 0.15);
        $metrosZunchoL0 = $this->metrosZuncho($perimetro, $longitudL0, (float) $this->intervalo_l0);
        $metrosZunchoL1 = $this->metrosZuncho($perimetro, (float) $this->longitud_l1, (float) $this->intervalo_l1);
        $metrosZunchoL2 = $this->metrosZuncho($perimetro, (float) $this->longitud_l2, (float) $this->intervalo_l2);
        $armadoPorCalibre = $this->calibre_varilla_l0 !== null || $this->calibre_varilla_l1 !== null;
        $calibreL0 = (string) ($this->calibre_varilla_l0 ?? self::CALIBRE_PREDETERMINADO);
        $calibreL1 = (string) ($this->calibre_varilla_l1 ?? self::CALIBRE_PREDETERMINADO);
        $calibresZuncho = self::calibresZunchoPredeterminados($armadoPorCalibre);
        $zunchosL0 = $metrosZunchoL0 * self::PESOS_VARILLA[$this->calibre_zuncho_l0 ?? $calibresZuncho['l0']['peso']];
        $zunchosL1 = $metrosZunchoL1 * self::PESOS_VARILLA[$this->calibre_zuncho_l1 ?? $calibresZuncho['l1']['peso']];
        $zunchosL2 = $metrosZunchoL2 * self::PESOS_VARILLA[$this->calibre_zuncho_l2 ?? $calibresZuncho['l2']['peso']];
        $traslapeZunchoL0 = $this->pesoTraslapeZuncho($metrosZunchoL0, (int) ($this->calibre_zuncho_l0 ?? $calibresZuncho['l0']['traslape']));
        $traslapeZunchoL1 = $this->pesoTraslapeZuncho($metrosZunchoL1, (int) ($this->calibre_zuncho_l1 ?? $calibresZuncho['l1']['traslape']));
        $traslapeZunchoL2 = $this->pesoTraslapeZuncho($metrosZunchoL2, (int) ($this->calibre_zuncho_l2 ?? $calibresZuncho['l2']['traslape']));
        $traslapeZunchos = $traslapeZunchoL0 + $traslapeZunchoL1 + $traslapeZunchoL2;
        $anclaje = $diametro * 0.4;
        $varillasL0 = $this->varillas_l0 ?? 0;
        $varillasL1 = $this->varillas_l1 ?? 0;
        $pesoL0 = self::PESOS_VARILLA[$calibreL0];
        $pesoL1 = self::PESOS_VARILLA[$calibreL1];
        $pesoVarillasL0 = $varillasL0 * $pesoL0 * ($longitudL0 + $anclaje);
        $pesoVarillasL1 = $varillasL1 * $pesoL1 * ((float) $this->longitud_l1 + $anclaje);
        if ($armadoPorCalibre && $traslapeVarilla == 0.0) {
            // La tabla de VOL expresa el traslape como 50 diámetros de la varilla.
            $traslapeVarilla = self::TRASLAPES_METROS[$calibreL0] ?? 50 * (float) $calibreL0 * 0.0254 / 8;
        }
        $pesoTraslapeVarillas = $traslapeVarilla * $pesoL0 * $varillasL0;
        $aceroCalculado = $zunchosL0 + $zunchosL1 + $zunchosL2 + $traslapeZunchos
            + $pesoVarillasL0 + $pesoVarillasL1 + $pesoTraslapeVarillas;
        $calculoAutomatico = $armadoPorCalibre || $this->calibre_zuncho_l0 !== null
            || $this->calibre_zuncho_l1 !== null || $this->calibre_zuncho_l2 !== null;
        $aceroPorPila = $this->acero_kg_por_pila ?? ($calculoAutomatico ? $aceroCalculado : 0);

        $jornalesExactos = $metrosLineales / $avance;
        // Evita sumar un jornal por el error de punto flotante de un cociente entero.
        $jornales = ceil($jornalesExactos - $jornalesExactos * PHP_FLOAT_EPSILON * 4);

        // VOL usa el mismo volumen teórico para perforación, concreto y bentonita.
        return [
            'area' => $area,
            'perimetro' => $perimetro,
            'longitud_l0' => $longitudL0,
            'avance_metros_por_jornal' => $avance,
            'pilas_por_jornal' => $longitud > 0 ? $avance / $longitud : 0,
            'jornales' => $jornales,
            'jornales_exactos' => $jornalesExactos,
            'longitud' => $longitud,
            'zunchos_l0' => $zunchosL0,
            'zunchos_l1' => $zunchosL1,
            'zunchos_l2' => $zunchosL2,
            'traslape_zunchos_l0' => $traslapeZunchoL0,
            'traslape_zunchos_l1' => $traslapeZunchoL1,
            'traslape_zunchos_l2' => $traslapeZunchoL2,
            'traslape_zunchos' => $traslapeZunchos,
            'calibres_zuncho_predeterminados' => $calibresZuncho,
            'calibre_varilla_l0' => (float) $calibreL0,
            'calibre_varilla_l1' => (float) $calibreL1,
            'peso_varillas_l0' => $pesoVarillasL0,
            'peso_varillas_l1' => $pesoVarillasL1,
            'traslape_varillas' => $pesoTraslapeVarillas,
            'metros_lineales' => $metrosLineales,
            'perforacion' => $volumen,
            'concreto' => $volumen,
            'bentonita' => $volumen,
            'retiro' => $volumen * 1.3,
            'comision_ml' => max(1, $diametro) * $metrosLineales,
            'acero_kg_por_pila' => (float) $aceroPorPila,
            'acero' => $this->piezas * $aceroPorPila,
        ];
    }

    public static function calibresZunchoPredeterminados(bool $armadoPorCalibre): array
    {
        return [
            'l0' => ['peso' => 3, 'traslape' => 3],
            'l1' => ['peso' => 4, 'traslape' => $armadoPorCalibre ? 4 : 3],
            'l2' => ['peso' => 4, 'traslape' => 3],
        ];
    }

    private function metrosZuncho(float $perimetro, float $longitud, float $intervalo): float
    {
        return $intervalo > 0 ? $longitud / $intervalo * $perimetro : 0;
    }

    private function pesoTraslapeZuncho(float $metros, int $calibre): float
    {
        return ceil($metros / self::LARGO_BARRA_METROS) * self::TRASLAPES_METROS[$calibre] * self::PESOS_VARILLA[$calibre];
    }
}
