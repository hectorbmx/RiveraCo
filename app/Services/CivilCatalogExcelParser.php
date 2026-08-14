<?php

namespace App\Services;

use App\Models\CivilBuilding;
use App\Models\CivilCatalogImport;
use App\Models\CivilConcept;
use App\Models\CivilPartida;
use RuntimeException;
use ZipArchive;

class CivilCatalogExcelParser
{
    private array $sharedStrings = [];
    private array $styleFills = [];

    public function parse(CivilCatalogImport $import, string $path): array
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
            $this->styleFills = $this->readStyleFills($zip);
            $sheetPath = $this->resolveSheetPath($zip, $import->sheet_name ?: 'CATALOGO');
            $rows = $this->readRows($zip, $sheetPath);
        } finally {
            $zip->close();
        }

        return $this->persistRows($import, $rows);
    }

    private function resolveSheetPath(ZipArchive $zip, string $sheetName): string
    {
        $workbook = $this->xmlFromZip($zip, 'xl/workbook.xml');
        $rels = $this->xmlFromZip($zip, 'xl/_rels/workbook.xml.rels');
        $relationTargets = [];

        foreach ($rels->Relationship as $relationship) {
            $attrs = $relationship->attributes();
            $relationTargets[(string) $attrs['Id']] = (string) $attrs['Target'];
        }

        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheets = $workbook->xpath('//main:sheets/main:sheet') ?: [];

        foreach ($sheets as $sheet) {
            $attrs = $sheet->attributes();
            $relAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');

            if (strcasecmp((string) $attrs['name'], $sheetName) !== 0) {
                continue;
            }

            $target = $relationTargets[(string) $relAttrs['id']] ?? null;

            if (!$target) {
                break;
            }

            return $this->normalizeWorkbookTarget($target);
        }

        return 'xl/worksheets/sheet1.xml';
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

    private function readStyleFills(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/styles.xml');

        if ($xml === false) {
            return [];
        }

        $styles = simplexml_load_string($xml);
        $fills = [];

        foreach ($styles->fills->fill ?? [] as $fill) {
            $color = null;

            if (isset($fill->patternFill->fgColor)) {
                $attrs = $fill->patternFill->fgColor->attributes();
                $color = strtoupper((string) ($attrs['rgb'] ?? $attrs['indexed'] ?? $attrs['theme'] ?? ''));
            }

            $fills[] = $color;
        }

        $styleFills = [];

        foreach ($styles->cellXfs->xf ?? [] as $index => $xf) {
            $attrs = $xf->attributes();
            $fillId = (int) ($attrs['fillId'] ?? 0);
            $styleFills[(int) $index] = $fills[$fillId] ?? null;
        }

        return $styleFills;
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
            $colors = [];

            foreach ($rowNode->c as $cell) {
                $attrs = $cell->attributes();
                $reference = (string) ($attrs['r'] ?? '');
                $column = preg_replace('/\d+/', '', $reference);

                if (!$column || $this->columnIndex($column) > 8) {
                    continue;
                }

                $style = isset($attrs['s']) ? (int) $attrs['s'] : null;
                $cells[$column] = $this->cellValue($cell, (string) ($attrs['t'] ?? ''));
                $colors[$column] = $style !== null ? ($this->styleFills[$style] ?? null) : null;
            }

            if ($cells) {
                $rows[] = [
                    'row' => $rowNumber,
                    'cells' => $cells,
                    'colors' => $colors,
                ];
            }
        }

        return $rows;
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

    private function persistRows(CivilCatalogImport $import, array $rows): array
    {
        $import->buildings()->delete();

        $currentBuilding = null;
        $currentPartida = null;
        $buildingCount = 0;
        $partidaCount = 0;
        $conceptCount = 0;
        $totalAmount = 0.0;
        $warnings = [];

        foreach ($rows as $row) {
            $rowNumber = $row['row'];
            $cells = $row['cells'];

            if ($rowNumber <= 5 || $this->isHeaderRow($cells)) {
                continue;
            }

            $a = $this->text($cells['A'] ?? '');
            $b = $this->text($cells['B'] ?? '');
            $unit = $this->text($cells['D'] ?? '');
            $quantity = $this->number($cells['E'] ?? null);
            $unitPrice = $this->number($cells['F'] ?? null);
            $unitPriceText = $this->text($cells['G'] ?? '');
            $amount = $this->number($cells['H'] ?? null);
            $rowColor = $this->dominantColor($row['colors']);

            if ($rowNumber > 5 && strlen($b) > 200 && $unit === '' && $quantity === null && $unitPrice === null && $amount !== null) {
                break;
            }

            if ($this->isBuildingRow($a, $b, $rowColor, $unit, $quantity, $unitPrice, $unitPriceText, $amount)) {
                $buildingName = $b !== '' ? $b : $a;

                $buildingCount++;
                $currentBuilding = CivilBuilding::create([
                    'civil_catalog_import_id' => $import->id,
                    'name' => $buildingName,
                    'excel_row' => $rowNumber,
                    'sort_order' => $buildingCount,
                ]);
                $currentPartida = null;
                continue;
            }

            if ($this->isPartidaRow($a, $b, $rowColor, $unit, $quantity, $unitPrice, $unitPriceText, $amount)) {
                if (!$currentBuilding) {
                    $buildingCount++;
                    $currentBuilding = CivilBuilding::create([
                        'civil_catalog_import_id' => $import->id,
                        'name' => 'Sin edificio',
                        'excel_row' => $rowNumber,
                        'sort_order' => $buildingCount,
                    ]);
                    $warnings[] = "Fila {$rowNumber}: partida sin edificio previo; se asigno a Sin edificio.";
                }

                $partidaCount++;
                $currentPartida = CivilPartida::create([
                    'civil_building_id' => $currentBuilding->id,
                    'code' => $a ?: null,
                    'name' => $b !== '' ? $b : $a,
                    'budget_amount' => $amount ?? 0,
                    'excel_row' => $rowNumber,
                    'sort_order' => $partidaCount,
                ]);
                continue;
            }

            if (!$this->isConceptRow($a, $b, $unit, $quantity, $unitPrice, $unitPriceText, $amount)) {
                continue;
            }

            if (!$currentPartida) {
                $warnings[] = "Fila {$rowNumber}: concepto ignorado porque no tiene partida activa.";
                continue;
            }

            $conceptCount++;
            $conceptAmount = $amount ?? (($quantity ?? 0) * ($unitPrice ?? 0));
            $totalAmount += $conceptAmount;

            CivilConcept::create([
                'civil_partida_id' => $currentPartida->id,
                'excel_code' => $a ?: null,
                'description' => $b,
                'unit' => $unit ?: null,
                'budget_quantity' => $quantity ?? 0,
                'unit_price' => $unitPrice ?? 0,
                'unit_price_text' => $unitPriceText ?: null,
                'budget_amount' => $conceptAmount,
                'excel_row' => $rowNumber,
                'sort_order' => $conceptCount,
                'is_active' => true,
                'metadata' => [
                    'source' => 'excel',
                ],
            ]);
        }

        $import->update([
            'total_buildings' => $buildingCount,
            'total_partidas' => $partidaCount,
            'total_concepts' => $conceptCount,
            'total_amount' => $totalAmount,
            'metadata' => array_merge($import->metadata ?? [], [
                'parser_status' => $conceptCount > 0 ? 'parsed' : 'empty',
                'warnings' => $warnings,
            ]),
        ]);

        return [
            'buildings' => $buildingCount,
            'partidas' => $partidaCount,
            'concepts' => $conceptCount,
            'amount' => $totalAmount,
            'warnings' => $warnings,
        ];
    }

    private function isHeaderRow(array $cells): bool
    {
        $joined = strtoupper(implode(' ', array_map(fn ($value) => (string) $value, $cells)));

        return str_contains($joined, 'CATALOGO DE CONCEPTOS')
            || str_contains($joined, 'CLAVE') && str_contains($joined, 'DESCRIPCION')
            || str_contains($joined, 'PRECIO UNITARIO');
    }

    private function isBuildingRow(string $a, string $b, ?string $color, string $unit, ?float $quantity, ?float $price, string $unitPriceText, ?float $amount): bool
    {
        if (preg_match('/^EDIFICIO\b/i', $b) === 1 || preg_match('/^EDIFICIO\b/i', $a) === 1) {
            return true;
        }

        if ($this->isBlue($color) && !$this->isPartidaCode($a)) {
            return true;
        }

        return $this->hasOnlySummaryColumns($a, $b, $unit, $quantity, $price, $unitPriceText, $amount)
            && !$this->isPartidaCode($a);
    }

    private function isPartidaRow(string $a, string $b, ?string $color, string $unit, ?float $quantity, ?float $price, string $unitPriceText, ?float $amount): bool
    {
        return ($this->hasOnlySummaryColumns($a, $b, $unit, $quantity, $price, $unitPriceText, $amount) && $this->isPartidaCode($a))
            || ($this->isGreen($color) && $b !== '');
    }

    private function isConceptRow(string $a, string $b, string $unit, ?float $quantity, ?float $price, string $unitPriceText, ?float $amount): bool
    {
        return $a !== ''
            && $b !== ''
            && $unit !== ''
            && $quantity !== null
            && $price !== null
            && $unitPriceText !== ''
            && $amount !== null
            && !$this->isPartidaCode($a);
    }

    private function hasOnlySummaryColumns(string $a, string $b, string $unit, ?float $quantity, ?float $price, string $unitPriceText, ?float $amount): bool
    {
        return $a !== ''
            && $b !== ''
            && $unit === ''
            && $quantity === null
            && $price === null
            && $unitPriceText === ''
            && $amount !== null;
    }

    private function isPartidaCode(string $code): bool
    {
        return preg_match('/^[A-Z0-9]+\.\d+/i', $code) === 1;
    }

    private function dominantColor(array $colors): ?string
    {
        $counts = [];

        foreach ($colors as $color) {
            if (!$color) {
                continue;
            }

            $counts[$color] = ($counts[$color] ?? 0) + 1;
        }

        if (!$counts) {
            return null;
        }

        arsort($counts);

        return array_key_first($counts);
    }

    private function isBlue(?string $color): bool
    {
        if (!$color || !preg_match('/^[A-F0-9]{6,8}$/i', $color)) {
            return false;
        }

        $rgb = substr($color, -6);
        $r = hexdec(substr($rgb, 0, 2));
        $g = hexdec(substr($rgb, 2, 2));
        $b = hexdec(substr($rgb, 4, 2));

        return $b > $r && $b >= $g && $b > 90;
    }

    private function isGreen(?string $color): bool
    {
        if (!$color || !preg_match('/^[A-F0-9]{6,8}$/i', $color)) {
            return false;
        }

        $rgb = substr($color, -6);
        $r = hexdec(substr($rgb, 0, 2));
        $g = hexdec(substr($rgb, 2, 2));
        $b = hexdec(substr($rgb, 4, 2));

        return $g > $r && $g > $b && $g > 90;
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