@extends('layouts.admin')
@section('title', 'Editar etapa de comisión')
@section('content')
    <h1 class="text-xl font-semibold text-slate-800">Editar etapa · Comisión #{{ $comision->id }}</h1>
    <p class="mt-1 text-sm text-slate-500">La corrección conserva el estado de la etapa y el avance del trabajo.</p>
    @include('obras.comisiones.partials.formulario-etapa')
@endsection
