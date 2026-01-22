@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="mb-4">
        <h1 class="text-xl font-semibold text-slate-800">Editar comisión</h1>
        <p class="text-sm text-slate-500">
            Obra: {{ $obra->nombre ?? 'Obra #' . $obra->id }} · Comisión #{{ $comision->id }}
        </p>
    </div>

    <form method="POST" action="{{ route('obras.comisiones.update', [$obra, $comision]) }}">
        @csrf
        @method('PUT')

        @include('obras.comisiones.create-form', ['modo' => 'edit'])

        <div class="mt-6 flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
                Guardar cambios
            </button>

            <a href="{{ route('obras.edit', ['obra' => $obra->id, 'tab' => 'comisiones']) }}"
               class="px-4 py-2 rounded-lg text-sm border border-slate-300 text-slate-700 hover:bg-slate-50">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
