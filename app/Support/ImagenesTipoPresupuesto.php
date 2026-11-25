<?php

namespace App\Support;

use App\Services\Contracts\ImageOptimizerInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImagenesTipoPresupuesto
{
    private const CARPETA = 'images/SVG';
    private const CARGAS = 'presupuestos/imagenes-tipo';
    private const PREDETERMINADAS = ['PILAS' => 'Pilas.svg', 'POZOS' => 'Pozos.svg', 'MUROS' => 'Muros.svg'];

    public function __construct(private ImageOptimizerInterface $optimizador)
    {
    }

    public function rutas(): array
    {
        $guardadas = DB::table('presupuesto_tipo_imagenes')->pluck('archivo', 'tipo')->all();
        $rutas = [];
        foreach (array_replace(self::PREDETERMINADAS, $guardadas) as $tipo => $archivo) {
            if ($this->esCarga($archivo)) {
                if (Storage::disk('public')->exists($archivo)) {
                    $rutas[$tipo] = 'storage/' . $archivo;
                }
            } elseif ($archivo && ! str_contains($archivo, '/') && ! str_contains($archivo, '\\')
                && strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) === 'svg'
                && File::isFile(public_path(self::CARPETA . '/' . $archivo))) {
                $rutas[$tipo] = self::CARPETA . '/' . rawurlencode($archivo);
            }
        }

        return $rutas;
    }

    public function rutaPdf(?string $tipo): ?string
    {
        $ruta = $this->rutas()[$tipo] ?? null;
        if ($ruta === null) {
            return null;
        }

        return str_starts_with($ruta, 'storage/')
            ? Storage::disk('public')->path(substr($ruta, strlen('storage/')))
            : public_path(rawurldecode($ruta));
    }

    public function subir(UploadedFile $archivo): string
    {
        $contenido = $this->optimizador->optimize($archivo, [
            'format' => 'webp', 'max_width' => 192, 'max_height' => 192, 'quality' => 70,
        ]);
        $ruta = self::CARGAS . '/' . Str::uuid() . '.webp';

        if (! Storage::disk('public')->put($ruta, $contenido)) {
            throw new RuntimeException('No se pudo guardar la imagen optimizada.');
        }

        return $ruta;
    }

    public function guardar(string $tipo, ?string $archivo): ?string
    {
        DB::table('presupuesto_tipo_imagenes')->insertOrIgnore(['tipo' => $tipo]);
        $configuracion = DB::table('presupuesto_tipo_imagenes')->where('tipo', $tipo);
        $anterior = (clone $configuracion)->lockForUpdate()->value('archivo');
        $configuracion->update(['archivo' => $archivo]);

        return $anterior;
    }

    public function eliminarCarga(?string $archivo): void
    {
        if ($this->esCarga($archivo)) {
            Storage::disk('public')->delete($archivo);
        }
    }

    private function esCarga(?string $archivo): bool
    {
        return $archivo !== null && str_starts_with($archivo, self::CARGAS . '/')
            && preg_match('/^[a-f0-9-]{36}\.webp$/D', substr($archivo, strlen(self::CARGAS) + 1)) === 1;
    }
}
