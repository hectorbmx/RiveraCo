<?php

namespace App\Services\Obras;

use App\Models\Obra;
use App\Models\ObraFactura;
use App\Models\ObraFacturaPago;
use App\Models\SatCfdi;
use App\Models\SatFactura;
use Illuminate\Support\Collection;

class ReporteObrasService
{
    public function resumen(Collection $obras): array
    {
        $resumen = [
            'ejecucion' => $obras->where('estatus_nuevo', Obra::ESTATUS_EJECUCION)->count(),
            'terminadas' => $obras->where('estatus_nuevo', Obra::ESTATUS_TERMINADA)->count(),
            'pilas' => $obras->filter(fn ($obra) => mb_strtoupper(trim((string) $obra->tipo_obra)) === 'PILAS')->count(),
            'pozos' => $obras->filter(fn ($obra) => mb_strtoupper(trim((string) $obra->tipo_obra)) === 'POZOS')->count(),
            'facturado' => 0, 'cobrado' => 0, 'por_cobrar' => 0,
        ];

        foreach ($obras->pluck('id')->chunk(500) as $ids) {
            $facturas = ObraFactura::query()->whereIn('obra_id', $ids)->where('estado', '!=', 'cancelada')
                ->selectRaw('obra_id, SUM(monto) as total')->groupBy('obra_id')->pluck('total', 'obra_id');
            $pagos = ObraFacturaPago::query()->whereIn('obra_id', $ids)
                ->selectRaw('obra_id, SUM(monto) as total')->groupBy('obra_id')->pluck('total', 'obra_id');
            $sat = SatFactura::query()->whereIn('obra_id', $ids)->whereNotNull('uuid')
                ->where(fn ($query) => $query->whereNull('estado')->orWhere('estado', '!=', 'cancelada'))
                ->get(['obra_id', 'uuid', 'total']);
            $cfdis = SatCfdi::query()->whereIn('obra_id', $ids)->whereNotNull('uuid')->get(['obra_id', 'uuid', 'total']);

            // La tabla elimina UUID duplicados dentro de cada origen y obra por separado.
            $sumasPorObra = fn ($documentos) => $documentos
                ->filter(fn ($documento) => (string) $documento->uuid !== '')
                ->unique(fn ($documento) => $documento->obra_id . ':' . strtoupper($documento->uuid))
                ->groupBy('obra_id')->map(fn ($grupo) => $grupo->sum('total'));
            $satPorObra = $sumasPorObra($sat);
            $cfdisPorObra = $sumasPorObra($cfdis);

            foreach ($ids as $id) {
                $facturado = (float) ($facturas[$id] ?? 0) + (float) ($satPorObra[$id] ?? 0) + (float) ($cfdisPorObra[$id] ?? 0);
                $cobrado = (float) ($pagos[$id] ?? 0);
                $resumen['facturado'] += $facturado;
                $resumen['cobrado'] += $cobrado;
                $resumen['por_cobrar'] += max(0, $facturado - $cobrado);
            }
        }

        return $resumen;
    }

    public function indicadores(Obra $obra): array
    {
        $valorObra = (float) ($obra->monto_contratado ?? 0);

        $facturasSat = \App\Models\SatFactura::query()
            ->where('obra_id', $obra->id)
            ->whereNotNull('uuid')
            ->where(function ($query) {
                $query->whereNull('estado')
                    ->orWhere('estado', '!=', 'cancelada');
            })
            ->get(['uuid', 'total'])
            ->map(fn ($factura) => [
                'uuid' => strtoupper((string) ($factura->uuid ?? '')),
                'total' => (float) ($factura->total ?? 0),
            ])
            ->filter(fn ($factura) => $factura['uuid'] !== '')
            ->unique('uuid')
            ->sum('total');

        $cfdisSat = \App\Models\SatCfdi::query()
            ->where('obra_id', $obra->id)
            ->whereNotNull('uuid')
            ->get(['uuid', 'total'])
            ->map(fn ($cfdi) => [
                'uuid' => strtoupper((string) ($cfdi->uuid ?? '')),
                'total' => (float) ($cfdi->total ?? 0),
            ])
            ->filter(fn ($cfdi) => $cfdi['uuid'] !== '')
            ->unique('uuid')
            ->sum('total');

        $facturado = (float) \App\Models\ObraFactura::where('obra_id', $obra->id)
            ->where('estado', '!=', 'cancelada')
            ->sum('monto') + $facturasSat + $cfdisSat;
        $cobrado = (float) \App\Models\ObraFacturaPago::where('obra_id', $obra->id)->sum('monto');
        $gastado = (float) \App\Models\OrdenCompra::where('obra_id', $obra->id)
            ->whereNotIn('estado', ['cancelada'])
            ->sum('total');
        $porFacturar = max(0, $valorObra - $facturado);
        $porCobrar = max(0, $facturado - $cobrado);
        $avancePct = $valorObra > 0 ? min(100, round(($facturado / $valorObra) * 100)) : 0;

        $pilasProgramadas = (int) $obra->pilas()->sum('cantidad_programada');
        $pilasEjecutadas = (float) $obra->pilas()
            ->withSum('detallesComision as cantidad_ejecutada', 'cantidad')
            ->get()
            ->sum('cantidad_ejecutada');
        $avancePilasPct = $pilasProgramadas > 0
            ? min(100, round(($pilasEjecutadas / $pilasProgramadas) * 100))
            : 0;

        return compact('valorObra', 'facturado', 'cobrado', 'gastado', 'porFacturar', 'avancePct', 'avancePilasPct');
    }
}
