@extends('layouts.admin')

@section('title', 'Programar pago a proveedor')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Programar pago a proveedor</h1>
            <p class="text-sm text-slate-500">Selecciona una orden autorizada que aun no tenga pago activo.</p>
        </div>
        <a href="{{ route('pagos-proveedores.index') }}" class="text-sm text-slate-600 hover:underline">Volver</a>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-100 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('pagos-proveedores.store') }}" class="bg-white rounded-2xl shadow p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Orden de compra</label>
            <select name="orden_compra_id" id="orden_compra_id" class="w-full rounded-xl border-slate-300" required>
                <option value="">Selecciona orden</option>
                @foreach($ordenes as $oc)
                    @php
                        $destino = $oc->obra?->nombre
                            ?? ($oc->centroCosto ? (($oc->centroCosto->codigo ? $oc->centroCosto->codigo.' - ' : '').$oc->centroCosto->nombre) : 'Compra general');
                    @endphp
                    <option value="{{ $oc->id }}"
                            data-monto="{{ $oc->total }}"
                            data-moneda="{{ $oc->moneda ?? 'MXN' }}"
                            @selected(old('orden_compra_id', $ordenSeleccionada?->id) == $oc->id)>
                        {{ $oc->folio }} | {{ $oc->proveedor->nombre ?? '-' }} | {{ $destino }} | ${{ number_format($oc->total, 2) }}
                    </option>
                @endforeach
            </select>
            @error('orden_compra_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Fecha probable de pago</label>
                <input type="date" name="fecha_programada" value="{{ old('fecha_programada', now()->toDateString()) }}"
                       class="w-full rounded-xl border-slate-300" required>
                @error('fecha_programada')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Cuenta bancaria</label>
                <select name="cuenta_banco_empresa_id" class="w-full rounded-xl border-slate-300">
                    <option value="">Selecciona cuenta</option>
                    @foreach($cuentasBanco as $cuenta)
                        <option value="{{ $cuenta->id }}" @selected(old('cuenta_banco_empresa_id') == $cuenta->id)>
                            {{ $cuenta->banco }} | {{ $cuenta->nombre }} | {{ $cuenta->numero_cuenta ?? $cuenta->clabe }}
                        </option>
                    @endforeach
                </select>
                @error('cuenta_banco_empresa_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Metodo de pago</label>
                <select name="metodo_pago" class="w-full rounded-xl border-slate-300">
                    <option value="">Selecciona metodo</option>
                    <option value="transferencia" @selected(old('metodo_pago') === 'transferencia')>Transferencia</option>
                    <option value="cheque" @selected(old('metodo_pago') === 'cheque')>Cheque</option>
                    <option value="efectivo" @selected(old('metodo_pago') === 'efectivo')>Efectivo</option>
                </select>
                @error('metodo_pago')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Monto</label>
                <input type="number" step="0.01" min="0.01" name="monto" id="monto"
                       value="{{ old('monto', $ordenSeleccionada?->total) }}"
                       class="w-full rounded-xl border-slate-300" required>
                @error('monto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Referencia</label>
            <input name="referencia" value="{{ old('referencia') }}" class="w-full rounded-xl border-slate-300">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Observaciones</label>
            <textarea name="observaciones" rows="3" class="w-full rounded-xl border-slate-300">{{ old('observaciones') }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('pagos-proveedores.index') }}" class="rounded-xl border px-4 py-2 text-slate-700">Cancelar</a>
            <button class="rounded-xl bg-[#0B265A] px-5 py-2 font-semibold text-white">Programar pago</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('orden_compra_id');
    const monto = document.getElementById('monto');
    select?.addEventListener('change', () => {
        const option = select.selectedOptions[0];
        if (option?.dataset?.monto) {
            monto.value = Number(option.dataset.monto).toFixed(2);
        }
    });
});
</script>
@endsection
