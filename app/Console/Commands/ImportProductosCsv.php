<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\CatalogoSegmento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProductosCsv extends Command
{
    protected $signature = 'productos:import-csv
        {file : Ruta del CSV (ej: storage/app/imports/productos.csv)}
        {--delimiter= : Delimitador opcional (, o ;) }
        {--dry : Solo preflight, no inserta/actualiza}
        {--stage=all : legacy_id|desc|segmento|unidad|all }';

public function handle(): int
{
    $path = $this->argument('file');

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        return self::FAILURE;
    }

    $delimiter = $this->option('delimiter');
    $dry = (bool) $this->option('dry');
    $stage = (string) ($this->option('stage') ?? 'all'); // requiere que agregues --stage en signature

    $validStages = ['legacy_id','desc','segmento','unidad','all'];
    if (!in_array($stage, $validStages, true)) {
        $this->error("Stage inválido. Usa: " . implode('|', $validStages));
        return self::FAILURE;
    }

    $fh = fopen($path, 'r');
    if (!$fh) {
        $this->error("No se pudo abrir el archivo: {$path}");
        return self::FAILURE;
    }

    // Detectar delimitador si no viene explícito
    if (!$delimiter) {
        $sample = fgets($fh);
        rewind($fh);
        $delimiter = (substr_count((string)$sample, ';') > substr_count((string)$sample, ',')) ? ';' : ',';
    }

    // Leer encabezados
    $headers = fgetcsv($fh, 0, $delimiter);
    if (!$headers) {
        $this->error("CSV sin encabezados o vacío.");
        fclose($fh);
        return self::FAILURE;
    }

    $headersNorm = array_map(fn($h) => $this->normHeader($h), $headers);

    // Columnas esperadas
    $idxCodigo = $this->findCol($headersNorm, ['CODIGO']);
    $idxDesc   = $this->findCol($headersNorm, ['DESCRIPCION PRODUCTO', 'DESCRIPCION_PRODUCTO', 'DESCRIPCION']);
    $idxSeg    = $this->findCol($headersNorm, ['SEGMENTO', 'SEGMENT']);
    $idxUnidad = $this->findCol($headersNorm, ['UNIDAD', 'UNIDAD DE MEDIDA', 'UNIDAD_DE_MEDIDA']);

    // fallback legacy (si tu excel trae segmento fijo en col 4)
    if ($idxSeg === null && isset($headersNorm[3])) {
        $idxSeg = 3;
    }

    if ($idxCodigo === null) {
        $this->error("Falta columna requerida: CODIGO.");
        $this->line("Encabezados detectados: " . implode(' | ', $headersNorm));
        fclose($fh);
        return self::FAILURE;
    }

    // Para stages que ocupan descripción, exige la columna
    if (in_array($stage, ['desc','all'], true) && $idxDesc === null) {
        $this->error("Falta columna requerida para stage={$stage}: DESCRIPCION PRODUCTO (o equivalente).");
        $this->line("Encabezados detectados: " . implode(' | ', $headersNorm));
        fclose($fh);
        return self::FAILURE;
    }

    // =========================
    // PREFLIGHT
    // =========================
    $total = 0;
    $emptySku = 0;
    $dupInFile = 0;
    $skuSeen = [];
    $skus = [];

    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
        $total++;

        if ($this->isEmptyRow($row)) {
            continue;
        }

        $sku = trim((string)$this->cleanUtf8($this->val($row, $idxCodigo)));

        if ($sku === '') {
            $emptySku++;
            continue;
        }

        $skuKey = mb_strtoupper($sku);
        if (isset($skuSeen[$skuKey])) $dupInFile++;
        $skuSeen[$skuKey] = true;

        $skus[$skuKey] = $sku;
    }

    // Detect create/update en DB (seguimos usando sku como clave “actual” del sistema)
    $existing = Producto::query()
        ->whereIn('sku', array_values($skus))
        ->pluck('sku')
        ->map(fn($s) => mb_strtoupper((string)$s))
        ->flip();

    $toUpdate = 0;
    $toCreate = 0;

    foreach ($skus as $skuKey => $skuRaw) {
        if (isset($existing[$skuKey])) $toUpdate++;
        else $toCreate++;
    }

    $this->info("PREVIEW");
    $this->line("Archivo: {$path}");
    $this->line("Stage: {$stage}");
    $this->line("Delimitador: " . ($delimiter === ';' ? '; (punto y coma)' : ', (coma)'));
    $this->line("Filas (sin header): {$total}");
    $this->line("SKU vacíos: {$emptySku}");
    $this->line("Duplicados en archivo (por SKU): {$dupInFile}");
    $this->line("Crear: {$toCreate} | Actualizar: {$toUpdate}");

    if ($dry) {
        $this->warn("Dry-run activo: no se insertó/actualizó nada.");
        fclose($fh);
        return self::SUCCESS;
    }

    // =========================
    // PROCESO REAL
    // =========================
    rewind($fh);
    fgetcsv($fh, 0, $delimiter); // saltar header

    $created = 0;
    $updated = 0;
    $skipped = 0;
    $errors  = 0;

    $buffer = [];
    $chunkSize = 500;

    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {

        if ($this->isEmptyRow($row)) {
            continue;
        }

        // ✅ SIEMPRE definimos variables (ya no hay undefined)
        $sku = trim((string)$this->cleanUtf8($this->val($row, $idxCodigo)));

        $desc = '';
        if ($idxDesc !== null) {
            $desc = trim((string)$this->cleanUtf8($this->val($row, $idxDesc)));
        }

        $seg = null;
        if ($idxSeg !== null) {
            $segTmp = trim((string)$this->cleanUtf8($this->val($row, $idxSeg)));
            $seg = $segTmp !== '' ? $segTmp : null;
        }

        $unidad = null;
        if ($idxUnidad !== null) {
            $uniTmp = trim((string)$this->cleanUtf8($this->val($row, $idxUnidad)));
            $unidad = $uniTmp !== '' ? $uniTmp : null;
        }

        if ($sku === '') {
            $skipped++;
            continue;
        }

        // Para desc/all, exige desc no vacía (no actualizamos/creamos sin descripción)
        if (in_array($stage, ['desc','all'], true) && $desc === '') {
            $skipped++;
            continue;
        }

        $buffer[] = [
            'codigo' => $sku,                // CODIGO del CSV
            'sku' => $sku,                   // seguimos usando sku = codigo para upsert legacy (como hoy)
            'descripcion' => $desc,
            'segmento_legacy' => $seg,
            'uso_label' => $seg,
            'unidad' => $unidad,
        ];

        if (count($buffer) >= $chunkSize) {
            // OJO: persistChunk debe aceptar ($rows, $stage)
            [$c, $u, $s, $e] = $this->persistChunk($buffer, $stage);
            $created += $c;
            $updated += $u;
            $skipped += $s;
            $errors  += $e;
            $buffer = [];
        }
    }

    if ($buffer) {
        [$c, $u, $s, $e] = $this->persistChunk($buffer, $stage);
        $created += $c;
        $updated += $u;
        $skipped += $s;
        $errors  += $e;
    }

    fclose($fh);

    $this->info("RESULTADO");
    $this->line("Creados: {$created}");
    $this->line("Actualizados: {$updated}");
    $this->line("Saltados (faltan datos): {$skipped}");
    $this->line("Errores: {$errors}");

    return self::SUCCESS;
}

    /**
     * Persiste un chunk.
     * Importante: no tiramos todo el chunk por 1 fila mala; contamos error por fila.
     */
    private function persistChunk(array $rows, string $stage): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $r) {
                try {
                    $skuClean  = trim((string)$this->cleanUtf8($r['sku'] ?? ''));
                    $descClean = trim((string)$this->cleanUtf8($r['descripcion'] ?? ''));
                    $uniClean  = isset($r['unidad']) && $r['unidad'] !== null
                        ? trim((string)$this->cleanUtf8((string)$r['unidad']))
                        : null;

                    if ($skuClean === '' || $descClean === '') {
                        $skipped++;
                        continue;
                    }

                    // Segmento: crear/obtener (si viene)
                    $usoType = null;
                    $usoId = null;

                    if (!empty($r['segmento_legacy'])) {
                        $segRaw = (string)$this->cleanUtf8((string)$r['segmento_legacy']);
                        $segName = $this->normSegmento($segRaw);

                        $seg = CatalogoSegmento::firstOrCreate(
                            ['nombre' => $segName],
                            ['activo' => 1]
                        );

                        $usoType = 'segmento';
                        $usoId   = $seg->id;
                    }

                    $nombre = mb_substr($descClean, 0, 255);
                    $legacyId = mb_substr($skuClean, 0, 100);

                    $data = [
                        // 'legacy_prod_id' => null,
                        // 'legacy_prod_id' => mb_substr($skuClean, 0, 100),
                        'legacy_prod_id' => $legacyId,
                        'nombre' => $nombre,
                        'descripcion' => mb_substr($descClean, 0, 500),
                        'sku' => mb_substr($skuClean, 0, 100),
                        'unidad' => ($uniClean !== null && $uniClean !== '') ? mb_substr($uniClean, 0, 50) : null,

                        'tipo_inventario' => 'consumible',
                        'stock_minimo' => 0,
                        'punto_reorden' => 0,
                        'iva_default' => 16.00,
                        'activo' => 1,

                        'uso_type' => $usoType,
                        'uso_id' => $usoId,
                        'uso_label' => $this->cleanUtf8($r['uso_label'] ?? null),
                        'segmento_legacy' => $this->cleanUtf8($r['segmento_legacy'] ?? null),
                    ];

                    $p = Producto::where('sku', $data['sku'])->first();

                    if ($p) {
                        $p->fill($data);
                        $p->save();
                        $updated++;
                    } else {
                        Producto::create($data);
                        $created++;
                    }

                } catch (\Throwable $rowEx) {
                    $errors++;
                    // No rompemos el chunk completo por una fila
                    $this->error("Error fila SKU [" . ($r['sku'] ?? 'NA') . "]: " . $rowEx->getMessage());
                }
            }

            DB::commit();
        } catch (\Throwable $ex) {
            DB::rollBack();
            $errors++;
            $this->error("Error chunk (rollback): " . $ex->getMessage());
        }

        return [$created, $updated, $skipped, $errors];
    }

    private function isEmptyRow(array $row): bool
    {
        $nonEmpty = array_filter($row, fn($v) => trim((string)$v) !== '');
        return count($nonEmpty) === 0;
    }

    private function normHeader(?string $h): string
    {
         $h = (string) $h;

    // ✅ limpiar encoding y espacios raros (incluye NBSP)
    $h = (string) $this->cleanUtf8($h);
    $h = str_replace("\xC2\xA0", ' ', $h); // NBSP típico de Excel
    $h = trim($h);

    $h = mb_strtoupper($h);
    $h = str_replace(['Á','É','Í','Ó','Ú','Ü','Ñ'], ['A','E','I','O','U','U','N'], $h);
    $h = preg_replace('/\s+/', ' ', $h);

    return $h;
    }

    private function normSegmento(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/', ' ', $s);

        $s = mb_strtolower($s);
        $s = mb_convert_case($s, MB_CASE_TITLE, "UTF-8");

        return mb_substr($s, 0, 100);
    }

    private function findCol(array $headersNorm, array $candidates): ?int
    {
        $candidates = array_map(fn($c) => $this->normHeader($c), $candidates);

        foreach ($headersNorm as $i => $h) {
            foreach ($candidates as $c) {
                if ($h === $c) return $i;
            }
        }
        return null;
    }

    private function val(array $row, int $idx): string
    {
        return isset($row[$idx]) ? (string) $row[$idx] : '';
    }

    private function cleanUtf8(?string $s): ?string
    {
        if ($s === null) return null;

        $s = (string) $s;

        // Quitar BOM si llegara
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);

        // Si NO es UTF-8 válido, convertir desde Windows-1252 (ANSI típico de Excel)
        if (!mb_check_encoding($s, 'UTF-8')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $s);
            if ($converted !== false) $s = $converted;
        }

        // Remover controles raros
        $s = preg_replace('/[^\P{C}\t\n\r]+/u', '', $s);

        return $s;
    }
    
}
