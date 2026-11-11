<?php

namespace App\Support\Impresion;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class FirmaUsuario
{
    public static function ruta(?User $usuario): ?string
    {
        $ruta = $usuario?->firma_digital_path;
        if (! is_string($ruta) || ! preg_match('#^usuarios/[0-9]+/firmas/[^/\\\\]+\.png$#i', $ruta)) {
            return null;
        }

        $archivo = Storage::disk('public')->path($ruta);
        $base = realpath(Storage::disk('public')->path('usuarios/'.$usuario->id.'/firmas'));
        $real = realpath($archivo);
        if (! $base || ! $real || ! str_starts_with($real, $base.DIRECTORY_SEPARATOR) || ! is_file($real)) {
            return null;
        }
        $imagen = @getimagesize($real);
        return $imagen && ($imagen[2] ?? null) === IMAGETYPE_PNG ? $real : null;
    }

    public static function url(?User $usuario): ?string
    {
        return self::ruta($usuario) ? Storage::disk('public')->url($usuario->firma_digital_path) : null;
    }
}
