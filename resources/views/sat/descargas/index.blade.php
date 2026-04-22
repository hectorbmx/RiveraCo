@extends('layouts.admin')

@section('title', 'Descarga Masiva SAT')

@section('content')
<div class="max-w-8xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">CFDIs descargados SAT</h1>
            <p class="text-sm text-gray-600 mt-1">
                Consulta, verificación y procesamiento de CFDIs descargados desde el SAT.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Actualizar
            </button>

            <a href="{{ route('sat.descargas.create') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Nueva solicitud
                </a>
        </div>
    </div>
@if(session('success'))
    <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
        {{ session('success') }}
    </div>
@endif
    {{-- Tabla principal --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Solicitudes SAT</h3>
                <p class="text-sm text-gray-500">Historial de consultas y descargas procesadas.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-indigo-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Periodo de CFDIs</th>
                        <th class="px-4 py-3 text-left font-medium">RFC Solicitante</th>
                        <th class="px-4 py-3 text-left font-medium">Tipo</th>
                        <th class="px-4 py-3 text-left font-medium">Request SAT</th>
                        <th class="px-4 py-3 text-left font-medium">XML</th>
                        <th class="px-4 py-3 text-left font-medium">Proceso</th>
                        <th class="px-4 py-3 text-left font-medium">Creado</th>
                        <th class="px-4 py-3 text-left font-medium">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($requests as $req)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900">
                                <div>{{ \Carbon\Carbon::parse($req->fecha_inicio)->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($req->fecha_fin)->format('d/m/Y') }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $req->rfc_solicitante }}
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $req->tipo_descarga }}
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                <div class="max-w-[220px] truncate" title="{{ $req->request_id_sat }}">
                                    {{ $req->request_id_sat ?: '-' }}
                                </div>
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $req->total_xml ?? 0 }}
                            </td>

                            <td class="px-4 py-3">
                                    @php
                                        $estado = $req->estado ?? 'pendiente';
                                    @endphp

                                 

                                    @if($estado === 'querying')
                                        <span class="inline-flex items-center rounded-xl border border-blue-300 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                                            Consultando SAT
                                        </span>

                                    @elseif($estado === 'pending')
                                        <span class="inline-flex items-center rounded-xl border border-gray-300 bg-gray-50 px-3 py-1 text-sm font-medium text-gray-700">
                                            Pendiente
                                        </span>

                                    @elseif($estado === 'verifying')
                                        <span class="inline-flex items-center rounded-xl border border-yellow-300 bg-yellow-50 px-3 py-1 text-sm font-medium text-yellow-700">
                                            Verificando
                                        </span>

                                    @elseif($estado === 'downloading')
                                        <span class="inline-flex items-center rounded-xl border border-indigo-300 bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">
                                            Descargando
                                        </span>

                                    @elseif($estado === 'completed')
                                        <span class="inline-flex items-center rounded-xl border border-green-300 bg-green-50 px-3 py-1 text-sm font-medium text-green-700">
                                            Completado
                                        </span>

                                    @elseif($estado === 'failed')
                                        <span class="inline-flex items-center rounded-xl border border-red-300 bg-red-50 px-3 py-1 text-sm font-medium text-red-700">
                                            Error
                                        </span>

                                    @else
                                        <span class="inline-flex items-center rounded-xl border border-gray-300 bg-gray-50 px-3 py-1 text-sm font-medium text-gray-700">
                                            {{ ucfirst($estado) }}
                                        </span>
                                    @endif

                                    @if($req->error_message)
                                        <div class="mt-1 text-xs text-gray-500 max-w-xs truncate" title="{{ $req->error_message }}">
                                            {{ $req->error_message }}
                                        </div>
                                    @endif
                                </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ $req->created_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                @if(in_array($req->estado, ['verifying', 'failed']) && $req->request_id_sat)
                                    <form method="POST" action="{{ route('sat.descargas.retry', $req->id) }}">
                                        @csrf
                                        <button type="submit"
                                        onclick="this.disabled=true; this.innerText='Procesando...'; this.form.submit();"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 border border-blue-200 hover:bg-blue-100 transition">
                                            
                                            @if($req->estado === 'verifying')
                                                🔄 Verificar
                                            @else
                                                🔁 Reintentar
                                            @endif

                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No hay solicitudes registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection