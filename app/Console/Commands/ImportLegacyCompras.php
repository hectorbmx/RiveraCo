<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportLegacyCompras extends Command
{
    protected $signature = 'legacy:import-compras';
    protected $description = 'Importa proveedores, productos, ordenes de compra y detalles desde el sistema legacy.';

    public function handle()
    {
        $this->info("=== Importador Legacy de Compras ===");

        DB::beginTransaction();

        try {
            $this->importProveedores();
            $this->importProductos();
            $this->importOrdenesCompra();
            $this->importDetalles();
            $this->importProductoProveedor();

            DB::commit();

            $this->info("\n✔ Importación completada correctamente");
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->error("\n❌ Error durante la importación:");
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
        }

        return Command::SUCCESS;
    }

    private function importProveedores()
{
    $this->info("\n➡ Importando proveedores...");

    // Nombre de la tabla legacy (SOLO la tabla, la BD la marca la conexión 'legacy')
    $legacyTable = 'proveedores';

    // Validamos que exista la tabla en la conexión LEGACY
    if (!Schema::connection('legacy')->hasTable($legacyTable)) {
        $this->error("❌ La tabla legacy '{$legacyTable}' no existe en la conexión 'legacy'.");
        return;
    }

    $legacy = DB::connection('legacy'); // 👈 conexión a riveraco

    $totalLegacy = $legacy->table($legacyTable)->count();

    if ($totalLegacy === 0) {
        $this->info("No hay proveedores en la tabla legacy ({$legacyTable}). Nada que importar.");
        return;
    }

    $this->info("Se encontraron {$totalLegacy} proveedores legacy.");

    $importados = 0;
    $saltados   = 0;

    $legacy->table($legacyTable)
        ->orderBy('id_proveedor')
        ->chunk(100, function ($rows) use (&$importados, &$saltados) {
            foreach ($rows as $row) {

                // Evitar duplicar si ya importamos este legacy_id
                $yaExiste = DB::table('proveedores') // 👈 default connection (rivera_v2)
                    ->where('legacy_id', $row->id_proveedor)
                    ->exists();

                if ($yaExiste) {
                    $saltados++;
                    continue;
                }

                DB::table('proveedores')->insert([
                    'legacy_id'       => $row->id_proveedor,
                    'nombre'          => $row->nombre ?? '',
                    'descripcion'     => $row->descripcion ?? null,
                    'rfc'             => $row->RFC ?? null,          // ajusta si la columna es 'rfc'
                    'domicilio'       => $row->domicilio ?? null,
                    'telefono'        => $row->telefono ?? null,
                    'email'           => $row->email ?? null,
                    'banco'           => $row->banco ?? null,
                    'clabe'           => $row->clabe ?? null,
                    'cuenta'          => $row->cuenta ?? null,
                    'fecha_registro'  => $row->fecha_registro ?? null,
                    'activo'          => 1,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $importados++;
            }
        });

    $this->info("✔ Proveedores importados: {$importados}");
    $this->info("↪ Proveedores ya existentes (saltados): {$saltados}");
}



    private function importProductos()
    {
        $this->info("\n➡ Importando productos...");
        // lo llenaremos después
    }

    private function importOrdenesCompra()
    {
        $this->info("\n➡ Importando órdenes de compra...");
        // lo llenaremos después
    }

    private function importDetalles()
    {
        $this->info("\n➡ Importando detalles...");
        // lo llenaremos después
    }

    private function importProductoProveedor()
    {
        $this->info("\n➡ Generando relaciones producto-proveedor...");
        // lo llenaremos después
    }
}
