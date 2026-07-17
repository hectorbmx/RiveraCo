@extends('layouts.admin')

@section('title', 'Proveedor')

@section('content')
<div class="max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">
                Proveedor: {{ $proveedor->nombre }}
            </h1>
            <p class="text-sm text-slate-500">
                ID: {{ $proveedor->id }}
                @if($proveedor->rfc) Ã‚Â· RFC: {{ $proveedor->rfc }} @endif
                Ã‚Â· Estatus:
                @if($proveedor->activo)
                    <span class="text-green-600 font-semibold">Activo</span>
                @else
                    <span class="text-red-600 font-semibold">Inactivo</span>
                @endif
            </p>
        </div>

        <a href="{{ route('proveedores.index') }}"
           class="text-sm text-slate-500 hover:text-slate-800">
            Ã¢â€ Â Volver al listado
        </a>
    </div>

    {{-- Tabs --}}
    <div class="border-b mb-4 flex gap-6 text-sm">
        @php
            $tabs = [
                'general'  => 'General',
                'productos'=> 'Productos',
                'ordenes'  => 'Ãƒâ€œrdenes',
                'facturas' => 'Facturas',
                'pagado'   => 'Pagado',
                'seguimiento' => 'Seguimiento',
            ];
        @endphp

        @foreach($tabs as $key => $label)
            <a href="{{ route('proveedores.show', ['proveedor' => $proveedor->id, 'tab' => $key]) }}"
               class="pb-2 border-b-2 transition-all
               {{ $tab === $key
                    ? 'border-[#FFC107] text-[#0B265A] font-semibold'
                    : 'border-transparent text-slate-500 hover:text-slate-800'
                }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Contenido --}}
    <div class="bg-white rounded-2xl shadow p-6">
        @if($tab === 'general')
            <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-[#0B265A]">Telefonia proveedor</h2>
                        @if(isset($extensionTelefoniaActual) && $extensionTelefoniaActual)
                            <p class="mt-1 text-xs text-slate-500">Tu extension para llamar: <span class="font-semibold text-slate-700">{{ $extensionTelefoniaActual->extension }}</span></p>
                        @else
                            <p class="mt-1 text-xs text-amber-700">Tu usuario no tiene extension asignada para click-to-call.</p>
                        @endif
                    </div>

                    <div class="w-full lg:w-auto">
                        @if(isset($telefonosSeguimiento) && $telefonosSeguimiento->count())
                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                @foreach($telefonosSeguimiento as $telefonoSeguimiento)
                                    <form method="POST" action="{{ route('proveedores.telephony.call', ['proveedor' => $proveedor, 'phoneNumber' => $telefonoSeguimiento]) }}" onsubmit="return confirm('Se llamara desde tu extension al numero {{ $telefonoSeguimiento->raw_number }}. Deseas continuar?')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60" {{ isset($extensionTelefoniaActual) && $extensionTelefoniaActual ? '' : 'disabled' }}>
                                            <span aria-hidden="true">&#9742;</span>
                                            Llamar {{ $telefonoSeguimiento->raw_number }}
                                            @if($telefonoSeguimiento->label)
                                                <span class="text-xs font-normal opacity-80">{{ $telefonoSeguimiento->label }}</span>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                No hay telefonos indexados para este proveedor. Ejecuta el indexador si acabas de capturar el numero.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @include('proveedores.partials._general', ['proveedor' => $proveedor])
        @endif

        @if($tab === 'productos')
            @include('proveedores.partials._productos', ['proveedor' => $proveedor])
        @endif

        @if($tab === 'ordenes')
            @include('proveedores.partials._ordenes', ['proveedor' => $proveedor, 'ordenes' => $ordenes])
        @endif
        @if($tab === 'facturas')
            @include('proveedores.partials._facturas', ['proveedor' => $proveedor, 'facturas' => $facturas])
        @endif

        @if($tab === 'pagado')
            @include('proveedores.partials._pagado', ['proveedor' => $proveedor])
        @endif

        @if($tab === 'seguimiento')
            <div class="flex flex-col gap-4 mb-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">Llamadas relacionadas</h2>
                    <p class="text-sm text-slate-500">Historial identificado por los telefonos registrados del proveedor.</p>
                </div>

                <div class="w-full lg:w-auto">
                    @if(isset($telefonosSeguimiento) && $telefonosSeguimiento->count())
                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            @foreach($telefonosSeguimiento as $telefonoSeguimiento)
                                <form method="POST" action="{{ route('proveedores.telephony.call', ['proveedor' => $proveedor, 'phoneNumber' => $telefonoSeguimiento]) }}" onsubmit="return confirm('Se llamara desde tu extension al numero {{ $telefonoSeguimiento->raw_number }}. Deseas continuar?')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 rounded bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60" {{ isset($extensionTelefoniaActual) && $extensionTelefoniaActual ? '' : 'disabled' }}>
                                        <span aria-hidden="true">&#9742;</span>
                                        Llamar {{ $telefonoSeguimiento->raw_number }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            No hay telefonos indexados para este proveedor.
                        </div>
                    @endif
                </div>
            </div>

            @if(isset($llamadasSeguimiento) && $llamadasSeguimiento && $llamadasSeguimiento->count())
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600 text-xs font-semibold uppercase tracking-wide">
                            <tr>
                                <th class="text-left px-4 py-3">Fecha</th>
                                <th class="text-left px-4 py-3">Empleado</th>
                                <th class="text-left px-4 py-3">Extension</th>
                                <th class="text-left px-4 py-3">Numero</th>
                                <th class="text-left px-4 py-3">Direccion</th>
                                <th class="text-left px-4 py-3">Estado</th>
                                <th class="text-right px-4 py-3">Duracion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($llamadasSeguimiento as $llamada)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ optional($llamada->started_at)->format('Y-m-d H:i') ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $llamada->user_name_snapshot ?: optional($llamada->user)->name ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $llamada->extension_snapshot ?: optional($llamada->extension)->extension ?: '-' }}
                                        @if($llamada->extension_name_snapshot)
                                            <div class="text-xs text-slate-400">{{ $llamada->extension_name_snapshot }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-700">
                                        {{ $llamada->matched_number ?: $llamada->destination_number ?: $llamada->source_number ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $llamada->direction ?: '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $llamada->status === 'answered' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                            {{ $llamada->status ?: '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-700">
                                        {{ gmdate('H:i:s', (int) $llamada->duration_seconds) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $llamadasSeguimiento->links() }}
                </div>
            @else
                <div class="p-4 bg-slate-50 border rounded-lg text-slate-600 text-sm">
                    No hay llamadas relacionadas todavia. Importa CDR y valida que el telefono del proveedor este normalizado en SIRICO.
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
