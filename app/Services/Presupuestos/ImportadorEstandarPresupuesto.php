<?php

namespace App\Services\Presupuestos;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Exception as ErrorLectura;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use ZipArchive;

class ImportadorEstandarPresupuesto
{
    public function leer(string $ruta): array
    {
        $this->validarTamanoExcel($ruta);
        $lector = new Xlsx();
        $lector->setReadDataOnly(true);
        $lector->setReadFilter(new class implements IReadFilter
        {
            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return in_array($columnAddress, ['A', 'B', 'C', 'D', 'E', 'F'], true);
            }
        });
        try {
            $hoja = $lector->listWorksheetInfo($ruta)[0] ?? null;
            if (! $hoja || $hoja['totalRows'] > 5000 || $hoja['totalColumns'] > 100) {
                $this->error('La primera hoja debe contener hasta 5,000 filas y 100 columnas.');
            }
            $libro = $lector->setLoadSheetsOnly($hoja['worksheetName'])->load($ruta);
        } catch (ErrorLectura) {
            $this->error('No se pudo leer el archivo como un Excel .xlsx.');
        }

        try {
            $filas = $libro->getSheet(0)->rangeToArray('A1:F' . max(1, $hoja['totalRows']), null, false, false, true);
        } finally {
            $libro->disconnectWorksheets();
        }

        return $this->procesar($filas);
    }

    private function procesar(array $filas): array
    {
        $partidas = $encabezados = $contexto = [];
        $indicePartida = $indiceConcepto = null;
        $subtotal = BigDecimal::zero();
        $subtotalExcel = null;

        foreach ($this->filasDelCatalogo($filas) as $numero => $fila) {
            [$codigo, $descripcion, $unidad, $cantidad, $precio, $importe] = array_values($fila);
            $codigo = trim((string) $codigo);
            $descripcion = trim((string) $descripcion);
            $unidad = trim((string) $unidad);

            if (Str::startsWith(Str::lower(Str::ascii($codigo)), 'total del presupuesto')) {
                if (is_numeric($importe)) {
                    $subtotalExcel = $this->moneda(BigDecimal::of(trim((string) $importe)));
                }
                break;
            }

            if ($codigo === '' && $unidad === '' && $descripcion === '') {
                if (! $this->esVacioOCero($cantidad) || ! $this->esVacioOCero($precio) || ! $this->esVacioOCero($importe)) {
                    $this->error("Fila {$numero}. Hay cantidades o importes sin un concepto.");
                }
                continue;
            }

            if ($codigo === '' && $unidad === '' && $cantidad === null && $precio === null && $importe === null) {
                $encabezados[] = $descripcion;
                $indiceConcepto = null;
                continue;
            }

            if ($codigo === '' && $unidad === '' && $this->esVacioOCero($cantidad)
                && $this->esVacioOCero($precio) && $this->esVacioOCero($importe)) {
                if ($indiceConcepto === null) {
                    $this->error("Fila {$numero}. Hay texto de continuación sin un concepto al que agregarlo.");
                }
                $concepto = &$partidas[$indicePartida]['subpartidas'][$indiceConcepto]['concepto'];
                $concepto .= ' ' . $descripcion;
                unset($concepto);
                continue;
            }

            if ($codigo === '' || $descripcion === '' || $unidad === '' || mb_strlen($unidad) > 32) {
                $this->error("Fila {$numero}. Revisa el código, la descripción y la unidad del concepto.");
            }

            if ($encabezados) {
                $nombre = array_pop($encabezados);
                if (mb_strlen($nombre) > 255) {
                    $this->error("Fila {$numero}. El nombre de la partida supera los 255 caracteres.");
                }
                foreach ($encabezados as $encabezado) {
                    if (strcasecmp($encabezado, 'E') !== 0) {
                        $contexto[] = $encabezado;
                    }
                }
                $partidas[] = ['nombre' => $nombre, 'subpartidas' => []];
                $indicePartida = array_key_last($partidas);
                $encabezados = [];
            }
            if ($indicePartida === null) {
                $this->error("Fila {$numero}. El concepto debe estar debajo del encabezado de una partida.");
            }

            $cantidad = $this->decimal($cantidad, $numero, 'cantidad');
            $precio = $this->decimal($precio, $numero, 'precio unitario');
            $subtotal = $subtotal->plus($cantidad->multipliedBy($precio));
            $partidas[$indicePartida]['subpartidas'][] = [
                'fila' => $numero, 'codigo' => $codigo, 'concepto' => $descripcion, 'unidad' => $unidad,
                'cantidad' => (string) $cantidad, 'precio_unitario' => (string) $precio,
            ];
            $indiceConcepto = array_key_last($partidas[$indicePartida]['subpartidas']);
        }

        if (! $partidas) {
            $this->error('No se encontraron conceptos en el catálogo Estándar (E).');
        }
        $cantidadConceptos = 0;
        foreach ($partidas as $partida) {
            foreach ($partida['subpartidas'] as $fila) {
                if (mb_strlen($fila['concepto']) > 16000) {
                    $this->error("Fila {$fila['fila']}. La descripción supera los 16,000 caracteres.");
                }
                $cantidadConceptos++;
            }
        }

        return [
            'partidas' => $partidas,
            'contexto' => $contexto,
            'cantidad_conceptos' => $cantidadConceptos,
            'subtotal' => $this->moneda($subtotal),
            'subtotal_excel' => $subtotalExcel,
        ];
    }

    private function filasDelCatalogo(array $filas): array
    {
        foreach (array_slice($filas, 0, 40, true) as $numero => $fila) {
            $titulos = array_map(
                fn ($texto) => preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii((string) $texto))),
                array_values($fila)
            );
            if ($titulos === ['codigo', 'concepto', 'unidad', 'cantidad', 'punitario', 'importe']) {
                return array_slice($filas, $numero, null, true);
            }
        }
        $this->error('No se encontraron los encabezados del formato Estándar (E) en la primera hoja.');
    }

    private function esVacioOCero(mixed $valor): bool
    {
        return $valor === null || (is_numeric($valor) && (float) $valor === 0.0);
    }

    private function decimal(mixed $valor, int $fila, string $campo): BigDecimal
    {
        if (! is_numeric($valor)) {
            $this->error("Fila {$fila}. Captura un valor numérico para {$campo} en el Excel.");
        }
        $decimal = BigDecimal::of(trim((string) $valor))->stripTrailingZeros();
        if ($decimal->getScale() > 2 || $decimal->isNegative() || $decimal->isGreaterThan('9999999999999.99')) {
            $this->error("Fila {$fila}. El valor de {$campo} debe ser válido y tener hasta dos decimales.");
        }

        return $decimal->toScale(2);
    }

    private function moneda(BigDecimal $importe): string
    {
        return (string) $importe->toScale(2, RoundingMode::HALF_UP);
    }

    private function validarTamanoExcel(string $ruta): void
    {
        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            $this->error('No se pudo abrir el archivo Excel.');
        }
        try {
            $tamano = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $tamano += $zip->statIndex($i)['size'];
                if ($tamano > 20 * 1024 * 1024) {
                    $this->error('El contenido del Excel supera el tamaño admitido para la vista previa.');
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function error(string $mensaje): never
    {
        throw ValidationException::withMessages(['archivo' => $mensaje]);
    }
}
