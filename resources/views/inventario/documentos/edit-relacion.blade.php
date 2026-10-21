@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Corregir relación de entrada #{{ $doc->id }}</h1>
            <p class="text-sm text-slate-500">Esta corrección solo cambia el abastecimiento relacionado; no modifica inventario ni cantidades.</p>
        </div>
        <a href="{{ route('inventario.documentos.show', $doc) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700">
            Cancelar
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <strong>Corrección protegida.</strong>
        La entrada ya fue aplicada y el servicio relacionado puede estar terminado. Verifica el folio antes de guardar.
    </div>

    <form method="POST" action="{{ route('inventario.documentos.update-relacion', $doc) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PUT')

        <div class="grid gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700">Relación actual</label>
                <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    {{ $doc->mantenimientoAbastecimiento?->folio ?? 'Sin relación' }}
                </div>
            </div>

            <div>
                <label for="mantenimiento_abastecimiento_id" class="block text-sm font-semibold text-slate-700">Nueva solicitud de abastecimiento</label>
                <select id="mantenimiento_abastecimiento_id" name="mantenimiento_abastecimiento_id" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                    <option value="">Selecciona una solicitud</option>
                    @foreach($abastecimientos as $abastecimiento)
                        <option value="{{ $abastecimiento->id }}" @selected(old('mantenimiento_abastecimiento_id') == $abastecimiento->id)>
                            {{ $abastecimiento->folio }} · {{ $abastecimiento->mantenimiento?->maquina?->nombre ?? 'Equipo' }} · {{ ucfirst($abastecimiento->tipo) }} · {{ ucfirst(str_replace('_', ' ', $abastecimiento->estado)) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="submit" class="rounded-xl bg-[#0B265A] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                Guardar corrección
            </button>
        </div>
    </form>
</div>
@endsection
