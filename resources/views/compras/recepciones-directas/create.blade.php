@extends('layouts.admin')

@section('title', 'Recepción directa en obra')

@section('content')
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Recepción directa en obra</h1>
            <p class="text-sm text-slate-500">Orden {{ $ordenCompra->folio }} · {{ $ordenCompra->proveedor?->nombre ?? 'Sin proveedor' }}</p>
        </div>
        <a href="{{ route('ordenes_compra.edit', $ordenCompra) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Volver a la orden</a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 md:grid-cols-3">
        <div><div class="text-xs font-semibold uppercase text-slate-500">Obra destino</div><div class="font-semibold">{{ $ordenCompra->obra?->nombre ?? '-' }}</div></div>
        <div><div class="text-xs font-semibold uppercase text-slate-500">Almacén responsable</div><div class="font-semibold">{{ $ordenCompra->almacenDestino?->nombre ?? '-' }}</div></div>
        <div><div class="text-xs font-semibold uppercase text-slate-500">Moneda de compra</div><div class="font-semibold">{{ $ordenCompra->moneda }}</div></div>
        <p class="text-sm text-slate-600 md:col-span-3">El destino está fijado en la orden. Compras confirma la entrega después de verificarla con el receptor en obra.</p>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ $accionConfirmar ?? '#' }}"
          data-recepcion-directa data-confirmacion-habilitada="{{ $confirmacionHabilitada ? '1' : '0' }}"
          @unless($confirmacionHabilitada) onsubmit="return false" @endunless
          class="space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        <input type="hidden" name="operacion_uuid" value="{{ old('operacion_uuid', (string) \Illuminate\Support\Str::uuid()) }}">
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <label for="fecha_recepcion" class="mb-1 block text-sm font-semibold">Fecha de recepción</label>
                <input id="fecha_recepcion" name="fecha_recepcion" type="date" required max="{{ now('America/Mexico_City')->toDateString() }}"
                       value="{{ old('fecha_recepcion', now('America/Mexico_City')->toDateString()) }}" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="receptor_nombre" class="mb-1 block text-sm font-semibold">Quién recibió en obra</label>
                <input id="receptor_nombre" name="receptor_nombre" required maxlength="160" value="{{ old('receptor_nombre') }}" class="w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label for="remision" class="mb-1 block text-sm font-semibold">Número de remisión</label>
                <input id="remision" name="remision" maxlength="160" value="{{ old('remision') }}" class="w-full rounded-lg border-slate-300">
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-[#0B265A] text-left text-white"><tr>
                    <th class="px-3 py-3">Recibir</th><th class="px-3 py-3">Material</th><th class="px-3 py-3">Unidad</th>
                    <th class="px-3 py-3 text-right">Ordenado</th><th class="px-3 py-3 text-right">Recibido</th><th class="px-3 py-3 text-right">Pendiente</th>
                    <th class="px-3 py-3 text-right">Cantidad a recibir</th>
                </tr></thead>
                <tbody>
                    @forelse($partidas as $partida)
                        @php
                            $capturaAnterior = collect(old('partidas', []))->firstWhere('orden_compra_detalle_id', $partida['id']);
                            $seleccionada = $capturaAnterior !== null && $partida['producto_id'];
                        @endphp
                        <tr class="border-t border-slate-200" data-partida-recepcion>
                            <td class="px-3 py-3"><input type="checkbox" data-seleccionar-partida aria-label="Recibir {{ $partida['descripcion'] }}" @checked($seleccionada) @disabled(! $partida['producto_id'])></td>
                            <td class="px-3 py-3">{{ $partida['descripcion'] }}
                                @unless($partida['producto_id'])<p class="mt-1 text-xs text-amber-700">Vincula esta partida al catálogo de productos antes de recibirla.</p>@endunless
                                <input type="hidden" data-campo-partida name="partidas[{{ $loop->index }}][orden_compra_detalle_id]" value="{{ $partida['id'] }}" @disabled(! $seleccionada)>
                            </td>
                            <td class="px-3 py-3">{{ $partida['unidad'] }}</td>
                            <td class="px-3 py-3 text-right">{{ number_format($partida['ordenado'], 3) }}</td>
                            <td class="px-3 py-3 text-right">{{ number_format($partida['recibido'], 3) }}</td>
                            <td class="px-3 py-3 text-right font-semibold">{{ number_format($partida['pendiente'], 3) }}</td>
                            <td class="px-3 py-3 text-right"><input type="number" step="0.001" min="0.001"
                                max="{{ $partida['pendiente'] }}" data-campo-partida @disabled(! $seleccionada)
                                aria-label="Cantidad recibida de {{ $partida['descripcion'] }}"
                                name="partidas[{{ $loop->index }}][cantidad_recibida]" value="{{ $capturaAnterior['cantidad_recibida'] ?? $partida['pendiente'] }}"
                                class="w-36 rounded-lg border-slate-300 text-right"></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">La orden no tiene partidas pendientes de recepción.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            <label for="evidencias" class="mb-1 block text-sm font-semibold">Remisión o fotografías de entrega</label>
            <input id="evidencias" name="evidencias[]" type="file" multiple required accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full text-sm">
            <p class="mt-1 text-xs text-slate-500">Adjunta de 1 a 5 archivos, hasta 10 MB cada uno. No necesitas la factura para confirmar la recepción.</p>
        </div>
        <div>
            <label for="observaciones" class="mb-1 block text-sm font-semibold">Observaciones</label>
            <textarea id="observaciones" name="observaciones" maxlength="2000" rows="3" class="w-full rounded-lg border-slate-300">{{ old('observaciones') }}</textarea>
        </div>
        <div class="flex justify-end">
            <button type="submit" data-confirmar-recepcion @disabled(! $confirmacionHabilitada) class="rounded-lg bg-[#0B265A] px-5 py-2.5 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Confirmar recepción en obra</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const receiptForm = document.querySelector('[data-recepcion-directa]');
const receiptButton = receiptForm?.querySelector('[data-confirmar-recepcion]');
const updateReceiptButton = () => {
    if (receiptButton) receiptButton.disabled = receiptForm.dataset.confirmacionHabilitada !== '1'
        || receiptForm.dataset.enviando === '1'
        || !receiptForm.querySelector('[data-seleccionar-partida]:checked');
};
document.querySelectorAll('[data-partida-recepcion]').forEach((row) => {
    const checkbox = row.querySelector('[data-seleccionar-partida]');
    const sync = () => row.querySelectorAll('[data-campo-partida]').forEach((input) => {
        input.disabled = !checkbox.checked;
        if (input.type === 'number') input.required = checkbox.checked;
    });
    checkbox.addEventListener('change', () => { sync(); updateReceiptButton(); });
    sync();
});
receiptForm?.addEventListener('submit', (event) => {
    if (receiptForm.dataset.enviando === '1' || !receiptForm.querySelector('[data-seleccionar-partida]:checked')) {
        event.preventDefault();
        return;
    }
    receiptForm.dataset.enviando = '1';
    if (receiptButton) receiptButton.textContent = 'Confirmando recepción...';
    updateReceiptButton();
});
window.addEventListener('pageshow', () => {
    if (receiptForm) receiptForm.dataset.enviando = '0';
    if (receiptButton) receiptButton.textContent = 'Confirmar recepción en obra';
    updateReceiptButton();
});
updateReceiptButton();
</script>
@endpush
