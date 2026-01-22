@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <h1 class="text-xl font-semibold mb-4">
        Orden de compra {{ $oc->folio }}
    </h1>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div><strong>Proveedor:</strong> {{ $oc->proveedor->nombre ?? '-' }}</div>
        <div><strong>Área:</strong> {{ $oc->areaCatalogo->nombre ?? $oc->area }}</div>
        <div><strong>Fecha:</strong> {{ $oc->fecha }}</div>
        <div><strong>Estado:</strong> {{ ucfirst($oc->estado_normalizado) }}</div>
    </div>

    <table class="w-full text-sm border mb-4">
        <thead class="bg-gray-100">
        <tr>
            <th class="p-2 border">Descripción</th>
            <th class="p-2 border">Cantidad</th>
            <th class="p-2 border">Precio</th>
            <th class="p-2 border">IVA</th>
            <th class="p-2 border">Importe</th>
        </tr>
        </thead>
        <tbody>
        @foreach($oc->detalles as $d)
            <tr>
                <td class="p-2">{{ $d->descripcion }}</td>
                <td class="p-2">{{ $d->cantidad }}</td>
                <td class="p-2">${{ number_format($d->precio_unitario,2) }}</td>
                <td class="p-2">${{ number_format($d->iva,2) }}</td>
                <td class="p-2">${{ number_format($d->importe,2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="text-right space-y-1">
        <div>Subtotal: ${{ number_format($oc->subtotal,2) }}</div>
        <div>IVA: ${{ number_format($oc->iva,2) }}</div>
        <div>Otros: ${{ number_format($oc->otros_impuestos,2) }}</div>
        <div class="font-semibold">Total: ${{ number_format($oc->total,2) }}</div>
    </div>
</div>
@endsection
