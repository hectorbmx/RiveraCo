@extends('layouts.admin')

@section('title', 'Expediente del empleado')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Header expediente --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">
                Expediente: {{ $empleado->Nombre }} {{ $empleado->Apellidos }}
            </h1>
            <p class="text-sm text-slate-500">
                ID: {{ $empleado->id_Empleado }} ·
                Estatus: 
                @if((int)$empleado->Estatus === 2)
                    <span class="text-red-600">Baja</span>
                @else
                    <span class="text-green-600">Activo</span>
                @endif
            </p>
        </div>

        <a href="{{ route('empleados.index') }}"
           class="text-sm text-slate-500 hover:text-slate-800">
            ← Volver al listado
        </a>
    </div>

    {{-- Tabs --}}
    <div class="border-b mb-4 flex gap-6 text-sm">
        @php
            $tabs = [
                'datos'     => 'Datos generales',
                'notas'     => 'Notas',
                'emergencia'=> 'Contactos emergencia',
                'contrato'  => 'Contrato',
                'nomina'    => 'Nómina',
                'kardex'    => 'Kardex',
            ];
        @endphp

        @foreach($tabs as $key => $label)
            <a href="{{ route('empleados.edit', ['empleado' => $empleado->id_Empleado, 'tab' => $key]) }}"
               class="pb-2 border-b-2 transition-all 
               {{ $tab === $key 
                    ? 'border-[#FFC107] text-[#0B265A] font-semibold' 
                    : 'border-transparent text-slate-500 hover:text-slate-800'
                }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    {{-- CONTENIDO DE CADA TAB --}}
    <div class="bg-white rounded-2xl shadow p-6">

        {{-- TAB: DATOS GENERALES --}}
        @if($tab === 'datos')
            @include('empleados.partials._datos', ['empleado' => $empleado])
        @endif

        {{-- TAB: NOTAS --}}
        @if($tab === 'notas')
            @include('empleados.partials._notas', ['empleado' => $empleado])
        @endif

        {{-- TAB: CONTACTOS EMERGENCIA --}}
        @if($tab === 'emergencia')
            @include('empleados.partials._emergencia', ['empleado' => $empleado])
        @endif

        {{-- TAB: CONTRATO --}}
        @if($tab === 'contrato')
            @include('empleados.partials._contrato', ['empleado' => $empleado])
        @endif

        {{-- TAB: NOMINA --}}
        @if($tab === 'nomina')
            @include('empleados.partials._nomina', ['empleado' => $empleado])
        @endif
        {{-- TAB: KARDEX --}}
        @if($tab === 'kardex')
        @include('empleados.partials._kardex', ['empleado' => $empleado, 'kardex' => $kardex ?? collect()])
        @endif

    </div>
</div>
@endsection
