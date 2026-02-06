<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Models\Almacen;

class InventarioImportStockCsv extends Command
{
    protected $signature = 'inventario:import-stock-csv
                            {almacen_id : ID del almacén}
                            {file : Ruta del CSV (ej. storage/app/imports/stock.csv)}
                            {--delimiter=, : Delimitador CSV (, o ;)}
                            {--no-header : Indica que el CSV NO trae encabezados}
                            {--chunk=1000 : Tamaño de chunk para updates}
                            {--dry-run : No escribe cambios, solo reporta}';

    protected $description = 'Importa stock inicial desde CSV (CODIGO->legacy_prod_id, STOCK->stock_actual) y actualiza inventario_stock sin documentos ni movimientos.';

    public function handle(): int
    {
        $almacenId   = (int) $this->argument('almacen_id');
        $file        = (string) $this->argument('file');
        $delimiter   = (string) $this->option('delimiter');
        $hasHeader   = ! (bool) $this->option('no-header');
        $chunk       = (int) $this->option('chunk');
        $dryRun      = (bool) $this->option('dry-run');

        $almacen = Almacen::find($almacenId);
        if (!$almacen) {
            $this->error("No existe almacén con id={$almacenId}");
            return self::FAILURE;
        }

        $path = base_path($file);
        if (!is_file($path)) {
            // también permitir path absoluto
            $path = $file;
        }
        if (!is_file($path)) {
            $this->error("No se encontró el archivo: {$file}");
            return self::FAILURE;
        }

        $this->info("Almacén: {$almacen->nombre} (id={$almacen->id})");
        $this->info("Archivo: {$path}");
        $this->info("Delimiter: '{$delimiter}' | Header: " . ($hasHeader ? 'sí' : 'no') . " | chunk={$chunk} | dry-run=" . ($dryRun ? 'sí' : 'no'));

        // 1) Leer CSV y agrupar por CODIGO (sumar duplicados)
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->error("No pude abrir el archivo CSV.");
            return self::FAILURE;
        }

        $line = 0;
        $map = []; // CODIGO => stock(float)
        $invalidLines = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            // Saltar header
            if ($line === 1 && $hasHeader) {
                continue;
            }

            // Esperamos 2 columnas: CODIGO, STOCK
            $codigo = isset($row[0]) ? trim((string)$row[0]) : '';
            $stockRaw = isset($row[1]) ? trim((string)$row[1]) : '';

            if ($codigo === '') {
                $invalidLines++;
                continue;
            }

            // Normalizar número: permitir "10", "10.5", "10,5" (por si acaso)
            $stockRawNorm = str_replace(' ', '', $stockRaw);
            if (str_contains($stockRawNorm, ',') && !str_contains($stockRawNorm, '.')) {
                $stockRawNorm = str_replace(',', '.', $stockRawNorm);
            }

            if ($stockRawNorm === '') $stockRawNorm = '0';

            if (!is_numeric($stockRawNorm)) {
                $invalidLines++;
                continue;
            }

            $stock = (float) $stockRawNorm;

            // Sumar si se repite CODIGO
            if (!isset($map[$codigo])) $map[$codigo] = 0.0;
            $map[$codigo] += $stock;
        }

        fclose($handle);

        if (count($map) === 0) {
            $this->warn("No se detectaron filas válidas en el CSV.");
            return self::SUCCESS;
        }

        $this->info("Códigos únicos en CSV: " . count($map) . " | líneas inválidas: {$invalidLines}");

        // 2) Resolver CODIGO -> producto_id via legacy_prod_id
        // Para evitar un whereIn enorme, resolvemos por chunks de codigos.
        $codigos = array_keys($map);

        $found = 0;
        $notFound = [];
        $updates = []; // filas para upsert: almacen_id, producto_id, stock_actual, updated_at

        $now = now();

        foreach (array_chunk($codigos, 2000) as $codesChunk) {
            $productos = Producto::query()
                ->whereIn('legacy_prod_id', $codesChunk)
                ->get(['id','legacy_prod_id']);

            $byLegacy = [];
            foreach ($productos as $p) {
                $byLegacy[(string)$p->legacy_prod_id] = (int)$p->id;
            }

            foreach ($codesChunk as $c) {
                if (!isset($byLegacy[$c])) {
                    $notFound[] = $c;
                    continue;
                }

                $productoId = $byLegacy[$c];
                $stock = $map[$c];

                $updates[] = [
                    'almacen_id'     => $almacenId,
                    'producto_id'    => $productoId,
                    'stock_actual'   => $stock,   // 0 también actualiza (regla acordada)
                    'updated_at'     => $now,
                ];

                $found++;
            }
        }

        $this->info("Encontrados en productos: {$found}");
        $this->warn("No encontrados: " . count($notFound));

        if (count($notFound) > 0) {
            // Mostrar primeros 20 para no saturar consola
            $this->line("Ejemplos no encontrados: " . implode(', ', array_slice($notFound, 0, 20)) . (count($notFound) > 20 ? ' ...' : ''));
        }

        // 3) Aplicar updates a inventario_stock (upsert por unique almacen_id+producto_id)
        if ($dryRun) {
            $this->info("DRY-RUN: No se escribieron cambios.");
            $this->info("Filas que se actualizarían: " . count($updates));
            return self::SUCCESS;
        }

        $this->info("Actualizando inventario_stock...");

        $totalUpd = 0;
        DB::transaction(function () use ($updates, $chunk, &$totalUpd) {
            foreach (array_chunk($updates, $chunk) as $batch) {
                DB::table('inventario_stock')->upsert(
                    $batch,
                    ['almacen_id','producto_id'],
                    ['stock_actual','updated_at']
                );
                $totalUpd += count($batch);
            }
        });

        $this->info("Listo. Filas upsert: {$totalUpd}");
        $this->line("Nota: valor_total/costo_promedio no se tocan en este import (quedan en 0 hasta que haya costos reales).");

        return self::SUCCESS;
    }
}
