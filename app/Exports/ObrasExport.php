<?php

namespace App\Exports;

use App\Services\Obras\ReporteObrasService;
use Illuminate\Support\Collection;
class ObrasExport
{
    public function __construct(private Collection $obras) {}

    public function headings(): array
    {
        return ['Clave', 'Nombre', 'Cliente', 'Máquinas asignadas', 'Estatus', 'Valor obra', 'Facturado', 'Cobrado', 'Gastado', 'Por facturar', 'Avance financiero (%)', 'Avance de obra (%)'];
    }

    public function collection(): Collection
    {
        $reporte = app(ReporteObrasService::class);

        return $this->obras->map(function ($obra) use ($reporte) {
            $indicadores = $reporte->indicadores($obra);
            $maquinas = $obra->maquinasAsignadas->filter(fn ($asignacion) => $asignacion->maquina)
                ->map(fn ($asignacion) => collect([$asignacion->maquina->nombre, $asignacion->maquina->codigo, $asignacion->maquina->modelo])->filter()->join(' · '))
                ->join(', ');

            return [
                $obra->clave_obra, $obra->nombre, $obra->cliente?->nombre_comercial,
                $maquinas ?: 'Sin máquina', $obra->estatus_label,
                $indicadores['valorObra'], $indicadores['facturado'], $indicadores['cobrado'],
                $indicadores['gastado'], $indicadores['porFacturar'], $indicadores['avancePct'], $indicadores['avancePilasPct'],
            ];
        });
    }

    public function escribirCsv($archivo): void
    {
        fwrite($archivo, "\xEF\xBB\xBF");
        fputcsv($archivo, $this->headings(), ',', '"', '');

        foreach ($this->collection() as $fila) {
            $fila = array_map(function ($valor) {
                if (is_string($valor) && preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $valor)) {
                    return "'" . $valor;
                }

                return $valor;
            }, $fila);
            fputcsv($archivo, $fila, ',', '"', '');
        }
    }
}
