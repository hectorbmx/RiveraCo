@extends('layouts.admin')
@section('title', 'Devoluciones de obra pendientes')
@section('content')
<div class="mx-auto max-w-7xl space-y-5 px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-semibold text-slate-900">Devoluciones de obra pendientes</h1>
            <p class="text-sm text-slate-500">Solicitudes presentadas o parcialmente recibidas. El almacén indicado es el de origen, no una recepción confirmada.</p></div>
        <a href="{{ route('inventario.documentos.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm">Documentos de inventario</a>
    </div>
    @if(session('status'))<div class="rounded-lg bg-green-50 p-4 text-green-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="GET" class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-4">
        <label for="q" class="sr-only">Buscar por folio u obra</label>
        <input id="q" name="q" maxlength="120" value="{{ $busqueda }}" placeholder="Buscar folio, obra o clave" class="min-w-64 flex-1 rounded-lg border-slate-300">
        <button class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Buscar</button>
        <a href="{{ route('inventario.devoluciones-obra.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Limpiar</a>
    </form>
    @include('inventario.devoluciones-obra.partials.lista')
    @if($solicitudes->hasPages())<div>{{ $solicitudes->links() }}</div>@endif
    <p class="text-xs text-slate-500">Las recepciones físicas pendientes de aplicación aún no generan entrada al inventario ni ajustan el costo de la obra.</p>
</div>
@endsection
