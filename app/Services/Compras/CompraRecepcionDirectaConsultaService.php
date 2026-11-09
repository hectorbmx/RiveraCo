<?php

namespace App\Services\Compras;

use App\Models\CompraRecepcionDirecta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CompraRecepcionDirectaConsultaService
{
    private const RELACIONES = [
        'ordenCompra:id,folio,obra_id', 'obra:id,nombre', 'almacen:id,nombre',
        'entrada:id,folio,estado', 'salida:id,folio,estado', 'confirmadoPor:id,name',
        'detalles.ordenCompraDetalle', 'detalles.requisicionDetalle.requisicion',
    ];

    private function consulta(): Builder
    {
        return CompraRecepcionDirecta::with(self::RELACIONES)->orderByDesc('id');
    }

    public function porOrden(int $ordenId): LengthAwarePaginator
    {
        return $this->consulta()->where('orden_compra_id', $ordenId)
            ->paginate(10, ['*'], 'recepciones_page')->withQueryString();
    }

    public function porObra(int $obraId): LengthAwarePaginator
    {
        return $this->consulta()->where('obra_id', $obraId)
            ->paginate(10, ['*'], 'recepciones_page')->withQueryString();
    }

    public function todasPorObra(int $obraId): Collection
    {
        return $this->consulta()->where('obra_id', $obraId)->get()->groupBy('orden_compra_id');
    }

    public function cargar(CompraRecepcionDirecta $recepcion): CompraRecepcionDirecta
    {
        return $recepcion->load(self::RELACIONES);
    }
}
