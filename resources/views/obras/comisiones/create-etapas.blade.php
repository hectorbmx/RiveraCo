@extends('layouts.admin')

@section('title', 'Nueva comisión de perforación')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Nueva comisión de perforación</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $obra->nombre ?? $obra->clave_obra ?? 'Obra' }} · {{ $obra->cliente?->nombre_comercial ?? 'Sin cliente registrado' }}</p>
        </div>
        <a href="{{ route('obras.edit', ['obra' => $obra, 'tab' => 'comisiones']) }}" class="text-sm font-medium text-[#0B265A] hover:underline">Volver a comisiones</a>
    </div>

    <form method="POST" action="{{ route('obras.comisiones.store-etapas', [$obra]) }}" enctype="multipart/form-data" x-data="comisionAltaFormulario" data-loading-message="Guardando comisión, perforación y fotografías…"
          @submit="enviar($event)" @pageshow.window="guardando = false" @input="actualizarEstado()" class="space-y-5">
        @csrf
        @include('obras.comisiones.partials.datos-generales')
        @include('obras.comisiones.partials.perforacion-inicial')
        @include('obras.comisiones.partials.personal-fotos-inicial')
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" @disabled(! $puedeContinuarAlta) :disabled="guardando || @js(! $puedeContinuarAlta)" class="rounded-xl bg-[#0B265A] px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-900 disabled:opacity-50">
                <span x-text="guardando ? 'Guardando…' : 'Guardar comisión'">Guardar comisión</span>
            </button>
            <p class="text-xs text-slate-500">Se guardarán la perforación inicial, el personal seleccionado y las fotografías.</p>
        </div>
    </form>
@endsection
