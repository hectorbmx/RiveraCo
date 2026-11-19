@extends('layouts.admin')
@section('title', 'Nuevo presupuesto')

@section('content')
    <div class="space-y-5">
        <a href="{{ route('presupuesto.index') }}" class="text-sm font-medium text-[#0B265A] hover:underline">← Volver a presupuestos</a>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h1 class="mb-4 text-xl font-semibold text-[#0B265A]">Nuevo presupuesto</h1>
            @include('presupuesto.partials.datos')
        </section>
    </div>
@endsection
