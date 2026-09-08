<?php

namespace App\Services\Inventario;

use App\Models\Almacen;
use App\Models\InventarioCorte;
use App\Models\InventarioCorteDetalle;
use App\Models\InventarioDocumento;
use App\Models\InventarioDocumentoDetalle;
use App\Models\InventarioMovimiento;
use App\Models\InventarioStock;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class HuentitanInventoryImportService
{
    public function importarUltimaHoja(string $path, Almacen $almacen, array $data): InventarioCorte
    {
        $sheet = $this->readLastSheet($path);
        $rows = $this->extractRows($sheet['rows']);

        if (count($rows) === 0) {
            throw new RuntimeException('No se detectaron productos numerados en la ultima hoja del archivo.');
        }

        return DB::transaction(function () use ($rows, $sheet, $almacen, $data, $path) {
            $corte = InventarioCorte::create([
                'almacen_id' => $almacen->id,
                'fecha_desde' => $data['fecha_desde'],
                'fecha_hasta' => $data['fecha_hasta'],
                'titulo' => $data['titulo'] ?? null,
                'archivo_original' => basename($path),
                'hoja_importada' => $sheet['name'],
                'estado' => 'borrador',
                'total_productos' => count($rows),
                'total_con_existencia' => collect($rows)->where('existencia_actual', '!=', 0)->count(),
                'valor_total_actual' => collect($rows)->sum('valor_actual'),
                'importado_por' => auth()->id(),
                'notas' => $data['notas'] ?? null,
            ]);

            foreach ($rows as $row) {
                $producto = $this->resolverProducto($row);

                InventarioCorteDetalle::create([
                    'corte_id' => $corte->id,
                    'producto_id' => $producto->id,
                    'numero_importacion' => $row['numero_importacion'],
                    'nombre_producto' => $row['nombre_producto'],
                    'unidad' => $row['unidad'],
                    'existencia_anterior' => $row['existencia_anterior'],
                    'entrada_periodo' => $row['entrada_periodo'],
                    'salida_periodo' => $row['salida_periodo'],
                    'existencia_actual' => $row['existencia_actual'],
                    'precio_unitario' => $row['precio_unitario'],
                    'valor_anterior' => $row['valor_anterior'],
                    'valor_actual' => $row['valor_actual'],
                    'tipo_inventario' => $row['tipo_inventario'],
                    'origen_abastecimiento' => $row['origen_abastecimiento'],
                    'requiere_formula' => $row['requiere_formula'],
                    'es_danado' => $row['es_danado'],
                    'raw' => $row['raw'],
                ]);
            }

            return $corte->load('detalles.producto', 'almacen');
        });
    }

    public function aplicarCorte(InventarioCorte $corte): InventarioCorte
    {
        if ($corte->estado === 'aplicado') {
            return $corte;
        }

        return DB::transaction(function () use ($corte) {
            $documento = InventarioDocumento::create([
                'folio' => 'CORTE-' . $corte->id,
                'tipo' => 'inicial',
                'almacen_id' => $corte->almacen_id,
                'estado' => 'aplicado',
                'fecha' => $corte->fecha_hasta,
                'motivo' => 'Corte semanal importado',
                'creado_por' => auth()->id(),
                'notas' => 'Aplicacion de corte ' . $corte->hoja_importada,
            ]);

            $detalles = $corte->detalles()->with('producto')->get();

            foreach ($detalles as $detalle) {
                if (! $detalle->producto_id) {
                    continue;
                }

                $stock = InventarioStock::firstOrNew([
                    'almacen_id' => $corte->almacen_id,
                    'producto_id' => $detalle->producto_id,
                ]);

                $stockAnterior = (float) ($stock->stock_actual ?? 0);
                $stockActual = (float) $detalle->existencia_actual;
                $diferencia = $stockActual - $stockAnterior;

                $stock->stock_actual = $stockActual;
                $stock->stock_reservado = $stock->stock_reservado ?? 0;
                $stock->costo_promedio = $detalle->precio_unitario;
                $stock->valor_total = $detalle->valor_actual;
                $stock->save();

                InventarioDocumentoDetalle::create([
                    'documento_id' => $documento->id,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $stockActual,
                    'costo_unitario' => $detalle->precio_unitario,
                    'notas' => 'Corte semanal Huentitan',
                ]);

                if (abs($diferencia) > 0.0001) {
                    InventarioMovimiento::create([
                        'almacen_id' => $corte->almacen_id,
                        'producto_id' => $detalle->producto_id,
                        'documento_id' => $documento->id,
                        'fecha' => $corte->fecha_hasta,
                        'tipo_movimiento' => $diferencia >= 0 ? 'in' : 'out',
                        'cantidad' => abs($diferencia),
                        'costo_unitario' => $detalle->precio_unitario,
                        'saldo_cantidad' => $stockActual,
                        'creado_por' => auth()->id(),
                    ]);
                }
            }

            $corte->update([
                'estado' => 'aplicado',
                'documento_id' => $documento->id,
                'aplicado_por' => auth()->id(),
                'aplicado_at' => now(),
            ]);

            return $corte->fresh(['detalles.producto', 'almacen', 'documento']);
        });
    }

    private function resolverProducto(array $row): Producto
    {
        $sku = 'HUE-' . str_pad((string) $row['numero_importacion'], 4, '0', STR_PAD_LEFT);

        return Producto::updateOrCreate(
            ['sku' => $sku],
            [
                'nombre' => $row['nombre_producto'],
                'descripcion' => $row['nombre_producto'],
                'unidad' => $row['unidad'],
                'tipo_inventario' => $row['tipo_inventario'],
                'origen_abastecimiento' => $row['origen_abastecimiento'],
                'requiere_formula' => $row['requiere_formula'],
                'stock_minimo' => 0,
                'punto_reorden' => 0,
                'activo' => true,
            ]
        );
    }

    private function extractRows(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $numero = $row[0] ?? null;
            $nombre = trim((string) ($row[1] ?? ''));

            if (! is_numeric($numero) || $nombre === '') {
                continue;
            }

            $clasificacion = $this->clasificarProducto($nombre);

            $items[] = [
                'numero_importacion' => (int) $numero,
                'nombre_producto' => $this->normalizarTexto($nombre),
                'unidad' => $this->normalizarTexto((string) ($row[2] ?? '')),
                'existencia_anterior' => $this->toFloat($row[3] ?? 0),
                'entrada_periodo' => $this->toFloat($row[4] ?? 0),
                'salida_periodo' => $this->toFloat($row[5] ?? 0),
                'existencia_actual' => $this->toFloat($row[6] ?? 0),
                'precio_unitario' => $this->toFloat($row[7] ?? 0),
                'valor_anterior' => $this->toFloat($row[8] ?? 0),
                'valor_actual' => $this->toFloat($row[9] ?? 0),
                'tipo_inventario' => $clasificacion['tipo_inventario'],
                'origen_abastecimiento' => $clasificacion['origen_abastecimiento'],
                'requiere_formula' => $clasificacion['requiere_formula'],
                'es_danado' => $clasificacion['es_danado'],
                'raw' => $row,
            ];
        }

        return $items;
    }

    private function clasificarProducto(string $nombre): array
    {
        $upper = mb_strtoupper($nombre);
        $esDanado = str_contains($upper, 'DANADO') || str_contains($upper, 'DAÑADO') || str_contains($upper, 'DA�ADO');

        if (str_contains($upper, 'ARMADO')) {
            return [
                'tipo_inventario' => 'subensamble',
                'origen_abastecimiento' => 'compra',
                'requiere_formula' => false,
                'es_danado' => $esDanado,
            ];
        }

        $terminados = ['TUBO', 'BASE', 'LOZA', 'BROCAL', 'TAPA', 'REJILLA', 'TRINCHERA', 'ANILLETA', 'VASO'];
        foreach ($terminados as $terminado) {
            if (str_starts_with($upper, $terminado) || str_contains($upper, ' ' . $terminado . ' ')) {
                return [
                    'tipo_inventario' => 'producto_terminado',
                    'origen_abastecimiento' => 'compra',
                    'requiere_formula' => false,
                    'es_danado' => $esDanado,
                ];
            }
        }

        return [
            'tipo_inventario' => 'materia_prima',
            'origen_abastecimiento' => 'compra',
            'requiere_formula' => false,
            'es_danado' => $esDanado,
        ];
    }

    private function readLastSheet(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $workbook = $this->xmlFromZip($zip, 'xl/workbook.xml');
        $rels = $this->xmlFromZip($zip, 'xl/_rels/workbook.xml.rels');

        $sheets = [];
        foreach ($workbook->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes();
            $relAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $sheets[] = [
                'name' => (string) $attrs['name'],
                'rid' => (string) $relAttrs['id'],
            ];
        }

        if (count($sheets) === 0) {
            throw new RuntimeException('El archivo no tiene hojas.');
        }

        $targets = [];
        foreach ($rels->Relationship as $rel) {
            $attrs = $rel->attributes();
            $targets[(string) $attrs['Id']] = (string) $attrs['Target'];
        }

        $last = $sheets[count($sheets) - 1];
        $target = $targets[$last['rid']] ?? null;
        if (! $target) {
            throw new RuntimeException('No se pudo resolver la ultima hoja del XLSX.');
        }

        $worksheetPath = 'xl/' . ltrim($target, '/');
        if (! str_starts_with($worksheetPath, 'xl/worksheets/')) {
            $worksheetPath = 'xl/worksheets/' . basename($target);
        }

        $worksheet = $this->xmlFromZip($zip, $worksheetPath);
        $rows = [];

        foreach ($worksheet->sheetData->row as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $attrs = $cell->attributes();
                $ref = (string) ($attrs['r'] ?? 'A1');
                $col = $this->columnIndex($ref);
                $row[$col] = $this->cellValue($cell, $sharedStrings);
            }

            if ($row !== []) {
                $max = max(array_keys($row));
                $filled = [];
                for ($i = 0; $i <= $max; $i++) {
                    $filled[] = $row[$i] ?? null;
                }
                $rows[] = $filled;
            }
        }

        $zip->close();

        return [
            'name' => $last['name'],
            'rows' => $rows,
        ];
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $xml = $this->xmlFromZip($zip, 'xl/sharedStrings.xml');
        $strings = [];

        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }

            $text = '';
            foreach ($si->r as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function xmlFromZip(ZipArchive $zip, string $name): SimpleXMLElement
    {
        $content = $zip->getFromName($name);
        if ($content === false) {
            throw new RuntimeException("No se encontro {$name} dentro del XLSX.");
        }

        return new SimpleXMLElement($content);
    }

    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): mixed
    {
        $attrs = $cell->attributes();
        $type = (string) ($attrs['t'] ?? '');

        if ($type === 's') {
            $index = (int) ($cell->v ?? 0);
            return $sharedStrings[$index] ?? null;
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        $value = (string) ($cell->v ?? '');
        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : $value;
    }

    private function columnIndex(string $cellRef): int
    {
        preg_match('/^[A-Z]+/i', $cellRef, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', ','], ['', '.'], (string) $value);
        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function normalizarTexto(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}