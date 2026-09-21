<?php

namespace App\Support\Inventario;

use Illuminate\Validation\Rule;

class UnidadMedidaCatalogo
{
    public static function opciones(): array
    {
        return [
            'PZA' => 'Pieza',
            'KG' => 'Kilogramo',
            'LT' => 'Litro',
            'ML' => 'Metro lineal',
            'M2' => 'Metro cuadrado',
            'M3' => 'Metro cubico',
            'PAR' => 'Par',
            'JGO' => 'Juego',
            'KIT' => 'Kit',
            'SERVICIO' => 'Servicio',
            'HORA' => 'Hora',
            'VIAJE' => 'Viaje',
            'CAJA' => 'Caja',
            'BOLSA' => 'Bolsa',
            'BULTO' => 'Bulto',
            'SACO' => 'Saco',
            'CUBETA' => 'Cubeta',
            'ROLLO' => 'Rollo',
            'TRAMO' => 'Tramo',
            'PACA' => 'Paca',
            'GALON' => 'Galon',
            'LATA' => 'Lata',
        ];
    }

    public static function base(): array
    {
        return collect(self::opciones())
            ->only(['PZA', 'KG', 'LT', 'ML', 'M2', 'M3', 'PAR', 'JGO', 'KIT', 'SERVICIO', 'HORA', 'VIAJE'])
            ->all();
    }

    public static function compra(): array
    {
        return self::opciones();
    }

    public static function codigos(): array
    {
        return array_keys(self::opciones());
    }

    public static function regla(): \Illuminate\Validation\Rules\In
    {
        return Rule::in(self::codigos());
    }

    public static function normalizar(?string $unidad): ?string
    {
        $unidad = mb_strtoupper(trim((string) $unidad), 'UTF-8');

        return $unidad !== '' ? $unidad : null;
    }

    public static function textoLegacy(?string $unidadCompra, mixed $cantidadPorUnidad, ?string $unidadBase): ?string
    {
        $unidadCompra = self::normalizar($unidadCompra);
        $unidadBase = self::normalizar($unidadBase);
        $cantidad = (float) ($cantidadPorUnidad ?: 1);

        if (! $unidadCompra && ! $unidadBase) {
            return null;
        }

        if ($cantidad <= 0) {
            $cantidad = 1;
        }

        if ($unidadCompra && $unidadBase && $unidadCompra !== $unidadBase) {
            return trim($unidadCompra . ' ' . self::formatearCantidad($cantidad) . ' ' . $unidadBase);
        }

        if ($unidadCompra && abs($cantidad - 1) > 0.000001 && $unidadBase) {
            return trim($unidadCompra . ' ' . self::formatearCantidad($cantidad) . ' ' . $unidadBase);
        }

        return $unidadBase ?: $unidadCompra;
    }

    private static function formatearCantidad(float $cantidad): string
    {
        return rtrim(rtrim(number_format($cantidad, 6, '.', ''), '0'), '.');
    }
}
