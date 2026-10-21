<?php

namespace App\Services\Proveedores;

use App\Models\Proveedor;

class ProveedorResolverService
{
    /**
     * Busca un proveedor por RFC normalizado o crea un registro minimo.
     *
     * El llamador conserva la responsabilidad de ejecutar esto dentro de su
     * transaccion cuando forme parte de un guardado mayor.
     *
     * @return array{proveedor: Proveedor|null, creado: bool, rfc: string|null}
     */
    public function resolver(array $datos): array
    {
        $rfc = $this->normalizarRfc($datos['rfc'] ?? null);

        if ($rfc === null) {
            return [
                'proveedor' => null,
                'creado' => false,
                'rfc' => null,
            ];
        }

        $proveedor = Proveedor::query()
            ->whereRaw('UPPER(TRIM(rfc)) = ?', [$rfc])
            ->orderByDesc('activo')
            ->orderBy('id')
            ->first();

        if ($proveedor) {
            return [
                'proveedor' => $proveedor,
                'creado' => false,
                'rfc' => $rfc,
            ];
        }

        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $razonSocial = trim((string) ($datos['razon_social'] ?? ''));
        $nombreMinimo = $nombre !== '' ? $nombre : $razonSocial;

        if ($nombreMinimo === '') {
            $nombreMinimo = 'Proveedor ' . $rfc;
        }

        $proveedor = Proveedor::create([
            'nombre' => mb_substr($nombreMinimo, 0, 100),
            'razon_social' => $razonSocial !== '' ? mb_substr($razonSocial, 0, 255) : null,
            'rfc' => $rfc,
            'activo' => true,
            'fecha_registro' => now()->toDateString(),
        ]);

        return [
            'proveedor' => $proveedor,
            'creado' => true,
            'rfc' => $rfc,
        ];
    }

    public function normalizarRfc(?string $rfc): ?string
    {
        $rfc = strtoupper(trim((string) $rfc));
        $rfc = preg_replace('/\s+/', '', $rfc) ?? '';

        return $rfc !== '' ? $rfc : null;
    }
}
