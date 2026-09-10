@extends('layouts.admin')

@section('title', 'Detalle de máquina')

@section('content')
<div class="w-full space-y-6">

    {{-- Encabezado de la página --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Registrar Póliza de Seguro</h1>
            <p class="text-sm text-slate-500 mt-1">Ingresa los datos del seguro asociados a esta máquina.</p>
        </div>
        <a 
            href="{{ route('maquinas.show', $maquina) }}?tab=seguros"
            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 transition-colors"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver a la máquina
        </a>
    </div>

    {{-- Formulario Principal --}}
    <form 
        action="{{ route('maquinas.seguros.store', $maquina) }}" 
        method="POST" 
        enctype="multipart/form-data"
        class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden"
    >
        @csrf

        <div class="p-6 md:p-8 space-y-8">

            {{-- SECCIÓN 1: Información General y Financiera --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="font-semibold text-slate-800">Datos Principales y Financieros</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Aseguradora <span class="text-red-500">*</span></label>
                        <input type="text" name="aseguradora" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" required placeholder="Ej. GNP, AXA...">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Número de póliza <span class="text-red-500">*</span></label>
                        <input type="text" name="poliza_numero" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" required placeholder="Nº de contrato/póliza">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Tipo de seguro</label>
                        <input type="text" name="tipo_seguro" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="Ej. Cobertura Amplia">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Método de pago</label>
                        <input type="text" name="metodo_pago" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="Ej. Transferencia, Tarjeta">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Costo</label>
                        <input type="number" step="0.01" name="costo" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Moneda</label>
                        <select name="moneda" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                            <option value="MXN">MXN - Peso Mexicano</option>
                            <option value="USD">USD - Dólar Americano</option>
                            <option value="EUR">EUR - Euro</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Suma asegurada</label>
                        <input type="number" step="0.01" name="suma_asegurada" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Deducible</label>
                        <input type="number" step="0.01" name="deducible" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="0.00">
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 2: Vigencia y Coberturas --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="font-semibold text-slate-800">Fechas y Detalles de Cobertura</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Fecha de compra</label>
                        <input type="date" name="fecha_compra" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Vigencia desde <span class="text-red-500">*</span></label>
                        <input type="date" name="vigencia_desde" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" required>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Vigencia hasta <span class="text-red-500">*</span></label>
                        <input type="date" name="vigencia_hasta" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Cobertura</label>
                        <textarea name="cobertura" rows="3" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="Detalles de la cobertura..."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Observaciones</label>
                        <textarea name="observaciones" rows="3" class="w-full text-sm border-slate-300 rounded-lg px-3 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" placeholder="Notas adicionales o comentarios..."></textarea>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 3: Archivos Adjuntos --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <h3 class="font-semibold text-slate-800">Documentación Adjunta</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-300 hover:border-slate-400 transition-colors">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Documento de póliza (PDF/Imagen)</label>
                        <input type="file" name="documento" class="mt-2 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-300 hover:border-slate-400 transition-colors">
                        <label class="block text-xs font-medium text-slate-700 mb-1">Comprobante de pago (PDF/Imagen)</label>
                        <input type="file" name="comprobante" class="mt-2 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer de Botones --}}
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center justify-end gap-3">
            <a 
                href="{{ route('maquinas.show', $maquina) }}?tab=seguros"
                class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-white transition-colors"
            >
                Cancelar
            </a>

            <button 
                type="submit"
                class="inline-flex items-center justify-center px-5 py-2 rounded-lg bg-blue-600 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Guardar póliza
            </button>
        </div>

    </form>
</div>
@endsection