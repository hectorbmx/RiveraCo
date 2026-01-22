@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

    {{-- TOTAL CLIENTES --}}
    <div class="bg-white rounded-xl p-6 shadow">
        <h3 class="text-sm font-medium text-slate-500">Clientes Registrados</h3>
        <p class="mt-3 text-3xl font-bold text-[#0B265A]">{{ $totalClientes ?? 0 }}</p>
    </div>

    {{-- TOTAL OBRAS --}}
    <div class="bg-white rounded-xl p-6 shadow">
        <h3 class="text-sm font-medium text-slate-500">Obras Activas</h3>
        <p class="mt-3 text-3xl font-bold text-[#0B265A]">{{ $obrasActivas ?? 0 }}</p>
    </div>

    {{-- OBRAS COMPLETADAS --}}
    <div class="bg-white rounded-xl p-6 shadow">
        <h3 class="text-sm font-medium text-slate-500">Obras Completadas</h3>
        <p class="mt-3 text-3xl font-bold text-[#0B265A]">{{ $obrasTerminadas ?? 0 }}</p>
    </div>

</div>


{{-- OTROS WIDGETS / CARDS --}}
<div class="grid grid-cols-1  gap-6">

    {{-- RESUMEN DE OBRAS --}}
    <!-- <div class="bg-white rounded-xl p-6 shadow lg:col-span-2">
        <h3 class="text-lg font-semibold mb-4">Resumen de Obras</h3>

        <div class="h-48 flex items-center justify-center text-slate-400">
            <p>Aquí va una gráfica (Bar/Line chart)</p>
        </div>
    </div> -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <div>
            <div class="text-sm font-semibold text-gray-900">Reporte diario de maquinaria (en vivo)</div>
            <div class="text-xs text-gray-600">Hoy: {{ now()->toDateString() }}</div>
        </div>

        <a href="{{ route('reportes.maquinaria.reporte_diario', ['fecha' => now()->toDateString()]) }}"
           class="px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs hover:bg-gray-800">
            Abrir completo
        </a>
    </div>

        <iframe
        src="{{ route('reportes.maquinaria.reporte_diario', ['fecha' => now()->toDateString(), 'embed' => 1]) }}"
        class="w-full"
        style="height: 820px;"
        loading="lazy"
        ></iframe>

</div>



    {{-- PRÓXIMOS HITOS --}}
    <!-- <div class="bg-white rounded-xl p-6 shadow">
        <h3 class="text-lg font-semibold mb-4">Próximos Hitos</h3>

        <ul class="space-y-3 text-sm">
            <li class="flex justify-between border-b pb-2">
                <span>Inicio Excavación</span>
                <span class="text-slate-500">12 Feb</span>
            </li>

            <li class="flex justify-between border-b pb-2">
                <span>Vaciado de Concreto</span>
                <span class="text-slate-500">15 Feb</span>
            </li>

            <li class="flex justify-between border-b pb-2">
                <span>Entrega Final</span>
                <span class="text-slate-500">03 Mar</span>
            </li>
        </ul>
    </div> -->

</div>

@endsection
