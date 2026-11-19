<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PresupuestoDetalle extends Model
{
    public const PARTIDA_TRASLADO = 'TRASLADO DE PERFORADORA';
    public const CONCEPTOS_TRASLADO = [
        'traslado-movimiento-interno' => ['Movimiento interno', 6250],
        'traslado-cama-baja-neumatica-lowboy' => ['Cama baja neumática (Lowboy)', 55000],
        'traslado-plataforma' => ['Plataforma', 45000],
        'traslado-grua-para-carga-y-descarga' => ['Grúa para carga y descarga', 6500],
        'traslado-equipo-de-15-toneladas' => ['Equipo de 15 toneladas', 8500],
    ];

    protected $table = 'presupuesto_detalles';

    protected $fillable = [
        'presupuesto_id',
        'partida',
        'concepto',
        'unidad',
        'cantidad',
        'precio_unitario',
        'importe',
        'importe_optimista',
        'importe_pesimista',
    ];

    // Relación inversa: Un detalle pertenece a un presupuesto
    public function presupuesto()
    {
        return $this->belongsTo(Presupuesto::class);
    }

    public function identidadApupi(): string
    {
        $columnas = array_map(
            fn ($valor) => Str::of((string) $valor)->squish()->ascii()->upper()->value(),
            [$this->partida, $this->concepto, $this->unidad],
        );

        return hash('sha256', json_encode($columnas, JSON_THROW_ON_ERROR));
    }

    public function codigoTrasladoApupi(): ?string
    {
        if ($this->partidaApupi() !== self::PARTIDA_TRASLADO) {
            return null;
        }

        $codigo = 'traslado-' . Str::slug($this->concepto);

        return array_key_exists($codigo, self::CONCEPTOS_TRASLADO) ? $codigo : null;
    }

    public function partidaApupi(): string
    {
        return Str::of($this->partida ?: 'Sin partida')->squish()->ascii()->upper()->value();
    }

    public function valoresApupi(?array $ajuste, array $duracion, ?string $regla = null): array
    {
        $cantidad = $ajuste['cantidad'] ?? $this->cantidad;
        $precio = $ajuste['precio_unitario'] ?? $this->precio_unitario;
        $importes = [
            'realista' => $this->importe,
            'optimista' => $this->importe_optimista,
            'pesimista' => $this->importe_pesimista,
        ];
        $ajustado = (float) $cantidad !== (float) $this->cantidad || (float) $precio !== (float) $this->precio_unitario;

        if ($ajustado || ! $this->exists) {
            $baseOriginal = (float) $this->cantidad * (float) $this->precio_unitario;
            $baseAjustada = (float) $cantidad * (float) $precio;
            $activacion = in_array($regla, ['activar_jornales', 'activar_dias'], true);
            $historial = $this->exists && $baseOriginal != 0 && $regla !== 'directo'
                && count(array_filter($importes, fn ($importe) => (float) $importe !== 0.0)) > 0;

            foreach ($importes as $escenario => $original) {
                if ($cantidad == 0 || $precio == 0) {
                    $importes[$escenario] = 0;
                    continue;
                }

                if ($historial) {
                    // Las partidas importadas conservan los factores de su cotización original.
                    $proporcion = $activacion ? $precio / $this->precio_unitario : $baseAjustada / $baseOriginal;
                    $importes[$escenario] = $original === null ? null : round($original * $proporcion, 2);
                    continue;
                }

                $factor = $this->factorApupi($regla, $escenario, $duracion);
                $importe = $activacion ? (float) $precio : $baseAjustada;
                $importes[$escenario] = $factor === null ? null : round($importe * $factor, 2);
            }
        }

        return [
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'importes' => $importes,
            'ajustado' => $ajustado,
        ];
    }

    private function factorApupi(?string $regla, string $escenario, array $duracion): ?float
    {
        $jornales = $duracion[$escenario]['jornales'];
        $realista = $duracion['realista']['jornales'];

        return match ($regla) {
            'jornales', 'activar_jornales' => $jornales,
            'dias', 'activar_dias' => $duracion[$escenario]['dias'],
            'horas_extra' => $escenario === 'realista' ? 1 : ($jornales !== null && $realista > 0 ? $jornales / $realista : null),
            'reserva' => ['realista' => 1, 'optimista' => 0.9, 'pesimista' => 1.2][$escenario],
            'seguridad' => ['realista' => 1, 'optimista' => 0.8, 'pesimista' => 1.2][$escenario],
            default => 1,
        };
    }
}
