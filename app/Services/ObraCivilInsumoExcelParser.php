<?php

namespace App\Services;

use App\Models\ObraCivilInsumo;
use App\Models\ObraCivilInsumoImport;
use RuntimeException;
use ZipArchive;

class ObraCivilInsumoExcelParser
{
    private array $sharedStrings = [];

    public function parse(ObraCivilInsumoImport $import, string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extension ZIP de PHP no esta disponible para leer archivos Excel.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        try {
            $this->sharedStrings = $this->readSharedStrings($zip);
            $sheet = $this->findSheetWithHeader($zip);
        } finally {
            $zip->close();
        }

        if (!$sheet) {
            throw new RuntimeException('No se encontro una hoja con encabezado Codigo en la columna A.');
        }

        return $this->persistRows($import, $sheet['rows'], $sheet['name'], $sheet['header_row']);
    }

    private function findSheetWithHeader(ZipArchive $zip): ?array
    {
        foreach ($this->workbookSheets($zip) as $sheet) {
            $rows = $this->readRows($zip, $sheet['path']);
            $headerRow = $this->findHeaderRow($rows);

            if ($headerRow !== null) {
                return [
                    'name' => $sheet['name'],
                    'rows' => $rows,
                    'header_row' => $headerRow,
                ];
            }
        }

        return null;
    }

    private function workbookSheets(ZipArchive $zip): array
    {
        $workbook = $this->xmlFromZip($zip, 'xl/workbook.xml');
        $rels = $this->xmlFromZip($zip, 'xl/_rels/workbook.xml.rels');
        $relationTargets = [];

        foreach ($rels->Relationship as $relationship) {
            $attrs = $relationship->attributes();
            $relationTargets[(string) $attrs['Id']] = (string) $attrs['Target'];
        }

        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $sheets = $workbook->xpath('//main:sheets/main:sheet') ?: [];
        $result = [];

        foreach ($sheets as $sheet) {
            $attrs = $sheet->attributes();
            $relAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $target = $relationTargets[(string) $relAttrs['id']] ?? null;

            if (!$target) {
                continue;
            }

            $result[] = [
                'name' => (string) $attrs['name'],
                'path' => $this->normalizeWorkbookTarget($target),
            ];
        }

        return $result ?: [
            ['name' => 'sheet1', 'path' => 'xl/worksheets/sheet1.xml'],
        ];
    }

    private function readRows(ZipArchive $zip, string $sheetPath): array
    {
        $sheet = $this->xmlFromZip($zip, $sheetPath);
        $sheet->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rowNodes = $sheet->xpath('//main:sheetData/main:row') ?: [];
        $rows = [];

        foreach ($rowNodes as $rowNode) {
            $rowNumber = (int) ($rowNode->attributes()['r'] ?? 0);
            $cells = [];

            foreach ($rowNode->c as $cell) {
                $attrs = $cell->attributes();
                $reference = (string) ($attrs['r'] ?? '');
                $column = preg_replace('/\d+/', '', $reference);

                if (!$column || $this->columnIndex($column) > 8) {
                    continue;
                }

                $cells[$column] = $this->cellValue($cell, (string) ($attrs['t'] ?? ''));
            }

            if ($cells) {
                $rows[] = [
                    'row' => $rowNumber,
                    'cells' => $cells,
                ];
            }
        }

        return $rows;
    }

    private function persistRows(ObraCivilInsumoImport $import, array $rows, string $sheetName, int $headerRow): array
    {
        ObraCivilInsumo::query()
            ->where('obra_id', $import->obra_id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        ObraCivilInsumoImport::query()
            ->where('obra_id', $import->obra_id)
            ->where('id', '!=', $import->id)
            ->where('status', 'imported')
            ->update(['status' => 'replaced']);

        $currentTipo = null;
        $current = null;
        $items = [];
        $warnings = [];
        $sortOrder = 0;

        foreach ($rows as $row) {
            $rowNumber = $row['row'];

            if ($rowNumber <= $headerRow) {
                continue;
            }

            $cells = $row['cells'];
            $codigo = $this->text($cells['A'] ?? '');
            $concepto = $this->text($cells['B'] ?? '');
            $unidad = $this->text($cells['C'] ?? '');
            $cantidad = $this->number($cells['D'] ?? null);
            $precio = $this->number($cells['F'] ?? null);
            $importe = $this->number($cells['G'] ?? null);
            $incidencia = $this->percent($cells['H'] ?? null);

            if ($this->isEmptyRow($codigo, $concepto, $unidad, $cantidad, $precio, $importe)) {
                continue;
            }

            $categoria = $this->knownCategory($concepto ?: $codigo);

            if ($codigo === '' && $categoria) {
                $this->pushCurrent($items, $current);
                $currentTipo = $categoria;
                continue;
            }

            if ($this->isValidInsumoRow($codigo, $concepto, $unidad, $cantidad, $precio)) {
                $this->pushCurrent($items, $current);
                $sortOrder++;

                $calculated = round(($cantidad ?? 0) * ($precio ?? 0), 2);

                if ($importe !== null && abs($importe - $calculated) > 0.01) {
                    $warnings[] = "Fila {$rowNumber}: importe importado distinto al calculado para {$codigo}.";
                }

                $current = [
                    'obra_civil_insumo_import_id' => $import->id,
                    'obra_id' => $import->obra_id,
                    'tipo' => $currentTipo,
                    'codigo' => $codigo,
                    'concepto' => $concepto,
                    'unidad' => $unidad ?: null,
                    'cantidad_presupuestada' => $cantidad ?? 0,
                    'precio_unitario' => $precio ?? 0,
                    'importe_importado' => $importe ?? $calculated,
                    'importe_calculado' => $calculated,
                    'incidencia' => $incidencia,
                    'source_row' => $rowNumber,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'metadata' => [
                        'source' => 'excel',
                    ],
                ];
                continue;
            }

            if ($codigo === '' && $concepto !== '' && $current !== null && !$this->knownCategory($concepto)) {
                $current['concepto'] = trim($current['concepto'] . ' ' . $concepto);
                continue;
            }

            $warnings[] = "Fila {$rowNumber}: renglon omitido.";
        }

        $this->pushCurrent($items, $current);

        foreach ($items as $item) {
            ObraCivilInsumo::create($item);
        }

        $totalsByType = collect($items)->groupBy('tipo')->map->count();
        $totalImporte = collect($items)->sum(fn ($item) => (float) $item['importe_importado']);

        $import->update([
            'sheet_name' => $sheetName,
            'status' => 'imported',
            'total_insumos' => count($items),
            'total_materiales' => (int) ($totalsByType->get('material') ?? 0),
            'total_mano_obra' => (int) ($totalsByType->get('mano_obra') ?? 0),
            'total_equipo_herramienta' => (int) ($totalsByType->get('equipo_herramienta') ?? 0),
            'total_importe' => round($totalImporte, 2),
            'metadata' => array_merge($import->metadata ?? [], [
                'parser_status' => count($items) > 0 ? 'parsed' : 'empty',
                'header_row' => $headerRow,
                'warnings' => $warnings,
            ]),
        ]);

        return [
            'insumos' => count($items),
            'materiales' => (int) ($totalsByType->get('material') ?? 0),
            'mano_obra' => (int) ($totalsByType->get('mano_obra') ?? 0),
            'equipo_herramienta' => (int) ($totalsByType->get('equipo_herramienta') ?? 0),
            'importe' => round($totalImporte, 2),
            'warnings' => $warnings,
        ];
    }

    private function pushCurrent(array &$items, ?array &$current): void
    {
        if ($current === null) {
            return;
        }

        $items[] = $current;
        $current = null;
    }

    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $row) {
            $a = $this->normalizeHeader($row['cells']['A'] ?? '');

            if ($a === 'CODIGO') {
                return $row['row'];
            }
        }

        return null;
    }

    private function isValidInsumoRow(string $codigo, string $concepto, string $unidad, ?float $cantidad, ?float $precio): bool
    {
        return $codigo !== ''
            && $concepto !== ''
            && $unidad !== ''
            && $cantidad !== null
            && $precio !== null;
    }

    private function knownCategory(string $value): ?string
    {
        $normalized = $this->normalizeHeader($value);

        return match ($normalized) {
            'MATERIALES' => 'material',
            'MANO DE OBRA' => 'mano_obra',
            'EQUIPO Y HERRAMIENTA', 'EQUIPO HERRAMIENTA' => 'equipo_herramienta',
            default => null,
        };
    }

    private function isEmptyRow(string $codigo, string $concepto, string $unidad, ?float $cantidad, ?float $precio, ?float $importe): bool
    {
        return $codigo === ''
            && $concepto === ''
            && $unidad === ''
            && $cantidad === null
            && $precio === null
            && $importe === null;
    }

    private function normalizeWorkbookTarget(string $target): string
    {
        $target = str_replace('\\', '/', $target);
        $target = ltrim($target, '/');

        if (str_starts_with($target, 'xl/')) {
            return $target;
        }

        return 'xl/' . $target;
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);
        $strings = [];

        foreach ($shared->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }

            $value = '';
            foreach ($si->r as $run) {
                $value .= (string) $run->t;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    private function cellValue(\SimpleXMLElement $cell, string $type): string
    {
        if ($type === 'inlineStr') {
            return trim((string) ($cell->is->t ?? ''));
        }

        $raw = trim((string) ($cell->v ?? ''));

        if ($type === 's') {
            return trim((string) ($this->sharedStrings[(int) $raw] ?? ''));
        }

        return $raw;
    }

    private function text(mixed $value): string
    {
        return trim((string) $value);
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace([',', '$', ' '], '', (string) $value);

        if ($clean === '' || $clean === '-') {
            return null;
        }

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function percent(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace(['%', ',', ' '], '', (string) $value);

        if ($clean === '' || $clean === '-') {
            return null;
        }

        if (!is_numeric($clean)) {
            return null;
        }

        $number = (float) $clean;

        return $number <= 1 ? $number * 100 : $number;
    }

    private function normalizeHeader(mixed $value): string
    {
        $text = trim((string) $value);
        $text = strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/', ' ', strtoupper($text)) ?: '';
    }

    private function columnIndex(string $column): int
    {
        $index = 0;

        foreach (str_split(strtoupper($column)) as $char) {
            $index = ($index * 26) + (ord($char) - 64);
        }

        return $index;
    }

    private function xmlFromZip(ZipArchive $zip, string $path): \SimpleXMLElement
    {
        $xml = $zip->getFromName($path);

        if ($xml === false) {
            throw new RuntimeException("No se encontro {$path} dentro del archivo Excel.");
        }

        $document = simplexml_load_string($xml);

        if (!$document) {
            throw new RuntimeException("No se pudo leer {$path} dentro del archivo Excel.");
        }

        return $document;
    }
}
