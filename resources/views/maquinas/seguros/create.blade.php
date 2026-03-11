@extends('layouts.admin')

@section('title', 'Detalle de máquina')

@section('content')


<form 
    action="{{ route('maquinas.seguros.store', $maquina) }}" 
    method="POST" 
    enctype="multipart/form-data"
    class="max-w-3xl bg-white rounded-xl border p-6 space-y-6"
>
@csrf

<h2 class="text-lg font-semibold text-slate-800">
    Registrar póliza de seguro
</h2>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <label class="text-sm text-slate-600">Aseguradora</label>
        <input type="text" name="aseguradora" class="w-full border rounded-lg px-3 py-2" required>
    </div>

    <div>
        <label class="text-sm text-slate-600">Número de póliza</label>
        <input type="text" name="poliza_numero" class="w-full border rounded-lg px-3 py-2" required>
    </div>

    <div>
        <label class="text-sm text-slate-600">Tipo de seguro</label>
        <input type="text" name="tipo_seguro" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="text-sm text-slate-600">Método de pago</label>
        <input type="text" name="metodo_pago" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="text-sm text-slate-600">Costo</label>
        <input type="number" step="0.01" name="costo" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="text-sm text-slate-600">Moneda</label>
        <select name="moneda" class="w-full border rounded-lg px-3 py-2">
            <option value="MXN">MXN</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
        </select>
    </div>

    <div>
        <label class="text-sm text-slate-600">Fecha de compra</label>
        <input type="date" name="fecha_compra" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="text-sm text-slate-600">Suma asegurada</label>
        <input type="number" step="0.01" name="suma_asegurada" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="text-sm text-slate-600">Deducible</label>
        <input type="number" step="0.01" name="deducible" class="w-full border rounded-lg px-3 py-2">
    </div>

</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <label class="text-sm text-slate-600">Vigencia desde</label>
        <input type="date" name="vigencia_desde" class="w-full border rounded-lg px-3 py-2" required>
    </div>

    <div>
        <label class="text-sm text-slate-600">Vigencia hasta</label>
        <input type="date" name="vigencia_hasta" class="w-full border rounded-lg px-3 py-2" required>
    </div>

</div>

<div>
    <label class="text-sm text-slate-600">Cobertura</label>
    <textarea name="cobertura" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
</div>

<div>
    <label class="text-sm text-slate-600">Observaciones</label>
    <textarea name="observaciones" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <label class="text-sm text-slate-600">Documento de póliza</label>
        <input type="file" name="documento" class="w-full border rounded-lg px-3 py-2">
    </div>

    <div>
        <label class="text-sm text-slate-600">Comprobante de pago</label>
        <input type="file" name="comprobante" class="w-full border rounded-lg px-3 py-2">
    </div>

</div>

<div class="flex justify-end gap-3 pt-4 border-t">

    <a 
        href="{{ route('maquinas.show', $maquina) }}?tab=seguros"
        class="px-4 py-2 rounded-lg border text-slate-600"
    >
        Cancelar
    </a>

    <button 
        type="submit"
        class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
    >
        Guardar póliza
    </button>

</div>

</form>
@endsection