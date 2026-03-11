@extends('layouts.admin')

@section('title', 'Editar seguro de vehículo')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Editar póliza</h1>
            <p class="text-sm text-slate-500">
                Vehículo: {{ $vehiculo->placas ?? $vehiculo->numero_economico ?? ('#' . $vehiculo->id) }}
            </p>
        </div>

        <a
            href="{{ route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => 'seguro']) }}"
            class="px-4 py-2 rounded-lg border bg-white text-slate-700 hover:bg-slate-50"
        >
            ← Volver
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="font-semibold text-red-700 mb-2">Hay errores en el formulario:</div>
            <ul class="list-disc ml-5 text-sm text-red-600 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('vehiculos.seguros.update', [$vehiculo, $seguro]) }}"
        method="POST"
        enctype="multipart/form-data"
        class="rounded-xl border bg-white overflow-hidden"
    >
        @csrf
        @method('PUT')

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Aseguradora *</label>
                <input
                    type="text"
                    name="aseguradora"
                    value="{{ old('aseguradora', $seguro->aseguradora) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Número de póliza *</label>
                <input
                    type="text"
                    name="poliza_numero"
                    value="{{ old('poliza_numero', $seguro->poliza_numero) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de seguro</label>
                <input
                    type="text"
                    name="tipo_seguro"
                    value="{{ old('tipo_seguro', $seguro->tipo_seguro) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Ej. cobertura amplia"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Método de pago</label>
                <input
                    type="text"
                    name="metodo_pago"
                    value="{{ old('metodo_pago', $seguro->metodo_pago) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Ej. transferencia"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Costo</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="costo"
                    value="{{ old('costo', $seguro->costo) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Moneda</label>
                <select
                    name="moneda"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >
                    @php($monedaActual = old('moneda', $seguro->moneda ?? 'MXN'))
                    <option value="MXN" {{ $monedaActual === 'MXN' ? 'selected' : '' }}>MXN</option>
                    <option value="USD" {{ $monedaActual === 'USD' ? 'selected' : '' }}>USD</option>
                    <option value="EUR" {{ $monedaActual === 'EUR' ? 'selected' : '' }}>EUR</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Fecha de compra</label>
                <input
                    type="date"
                    name="fecha_compra"
                    value="{{ old('fecha_compra', optional($seguro->fecha_compra)->format('Y-m-d')) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Suma asegurada</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="suma_asegurada"
                    value="{{ old('suma_asegurada', $seguro->suma_asegurada) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Deducible</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="deducible"
                    value="{{ old('deducible', $seguro->deducible) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Vigencia desde *</label>
                <input
                    type="date"
                    name="vigencia_desde"
                    value="{{ old('vigencia_desde', optional($seguro->vigencia_desde)->format('Y-m-d')) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    required
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Vigencia hasta *</label>
                <input
                    type="date"
                    name="vigencia_hasta"
                    value="{{ old('vigencia_hasta', optional($seguro->vigencia_hasta)->format('Y-m-d')) }}"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    required
                >
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Cobertura</label>
                <textarea
                    name="cobertura"
                    rows="3"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >{{ old('cobertura', $seguro->cobertura) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Observaciones</label>
                <textarea
                    name="observaciones"
                    rows="3"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >{{ old('observaciones', $seguro->observaciones) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Documento de póliza</label>
                <input
                    type="file"
                    name="documento"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >
                @if($seguro->documento_path)
                    <div class="mt-2 text-sm">
                        <a
                            href="{{ asset('storage/' . $seguro->documento_path) }}"
                            target="_blank"
                            class="text-blue-600 hover:underline"
                        >
                            Ver documento actual
                        </a>
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Comprobante de pago</label>
                <input
                    type="file"
                    name="comprobante"
                    class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                >
                @if($seguro->comprobante_path)
                    <div class="mt-2 text-sm">
                        <a
                            href="{{ asset('storage/' . $seguro->comprobante_path) }}"
                            target="_blank"
                            class="text-blue-600 hover:underline"
                        >
                            Ver comprobante actual
                        </a>
                    </div>
                @endif
            </div>

        </div>

        <div class="px-6 py-4 border-t bg-slate-50 flex items-center justify-end gap-3">
            <a
                href="{{ route('mantenimiento.vehiculos.edit', ['vehiculo' => $vehiculo->id, 'tab' => 'seguro']) }}"
                class="px-4 py-2 rounded-lg border bg-white text-slate-700 hover:bg-slate-50"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
            >
                Guardar cambios
            </button>
        </div>
    </form>
</div>
@endsection