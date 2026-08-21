@extends('layouts.admin')

@section('title', 'Insumos de obra civil')

@section('content')
<div class="max-w-8xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Explosion de insumos</h1>
            <p class="text-sm text-slate-500">{{ $obra->nombre }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('obra_civil.material-requests.index', [$obra, 'scope' => 'no_atendidas']) }}"
               class="rounded-lg border border-amber-700 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50">
                Solicitudes no atendidas
            </a>
            <a href="{{ route('obra_civil.details', $obra) }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver al detalle
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Cargar o reemplazar insumos</h2>
                <p class="text-sm text-slate-500">Al cargar un nuevo Excel, los insumos activos anteriores se desactivan y queda vigente la nueva explosion.</p>
            </div>
            <form method="POST" action="{{ route('obra_civil.insumos.upload', $obra) }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-center" data-loading-message="Subiendo y leyendo explosion de insumos...">
                @csrf
                <input name="insumos"
                       type="file"
                       accept=".xlsx,.xlsm"
                       required
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#0B265A] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white sm:w-96">
                <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                    Cargar insumos
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-6">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Insumos activos</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($insumoStats['total'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Materiales</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($insumoStats['materiales'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Mano de obra</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($insumoStats['mano_obra'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Equipo</div>
                <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($insumoStats['equipo_herramienta'] ?? 0) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Importe materiales</div>
                <div class="mt-1 text-xl font-bold text-slate-900">${{ number_format((float) ($insumoStats['importe_materiales'] ?? 0), 2) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Usado</div>
                <div class="mt-1 text-xl font-bold text-red-700">${{ number_format((float) ($insumoStats['usado_total'] ?? 0), 2) }}</div>
            </div>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Insumos activos</h2>
                    @if($activeInsumoImport)
                        <p class="mt-1 text-sm text-slate-500">
                            Archivo vigente: <span class="font-semibold text-slate-700">{{ $activeInsumoImport->filename }}</span>
                            / hoja {{ $activeInsumoImport->sheet_name ?: '-' }}
                            / {{ $activeInsumoImport->created_at->format('d/m/Y H:i') }}
                        </p>
                        @php($warnings = $activeInsumoImport->metadata['warnings'] ?? [])
                        @if(!empty($warnings))
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                                {{ count($warnings) }} advertencia(s). Primer aviso: {{ $warnings[0] }}
                            </div>
                        @endif
                    @endif
                </div>

                <form method="GET" action="{{ route('obra_civil.insumos.index', $obra) }}" class="w-full rounded-lg border border-slate-200 bg-slate-100/80 p-3 shadow-sm xl:max-w-3xl">
                    <label for="q" class="block text-xs font-semibold uppercase text-slate-500">Buscar en la lista</label>
                    <div class="mt-1 flex flex-col gap-2 sm:flex-row">
                        <input id="q"
                               name="q"
                               type="search"
                               value="{{ $search }}"
                               placeholder="Codigo, concepto, unidad o tipo..."
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                        <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#12346f]">
                            Buscar
                        </button>
                        @if($search !== '')
                            <a href="{{ route('obra_civil.insumos.index', $obra) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Limpiar
                            </a>
                        @endif
                    </div>
                    @if($search !== '')
                        <div class="mt-2 text-xs font-semibold text-slate-500">
                            {{ number_format($insumos->count()) }} resultado(s)
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1350px] text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Codigo</th>
                        <th class="px-4 py-3 text-left">Concepto</th>
                        <th class="px-4 py-3 text-left">Unidad</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-right">Precio</th>
                        <th class="px-4 py-3 text-right">Importe</th>
                        <th class="px-4 py-3 text-right">Usado</th>
                        <th class="px-4 py-3 text-right">Solicitado pendiente</th>
                        <th class="px-4 py-3 text-right">Disponible</th>
                        <th class="px-4 py-3 text-right">Ordenes</th>
                        <th class="px-4 py-3 text-right">Fila</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($insumos as $insumo)
                        @php($balance = $insumoBalances->get($insumo->id, []))
                        @php($usedQuantity = (float) ($balance['used_quantity'] ?? 0))
                        @php($availableQuantity = (float) ($balance['available_quantity'] ?? $insumo->cantidad_presupuestada))
                        @php($usedAmount = (float) ($balance['used_amount'] ?? 0))
                        @php($availableAmount = (float) ($balance['available_amount'] ?? $insumo->importe_importado))
                        @php($ordenesCount = (int) ($balance['ordenes_count'] ?? 0))
                        @php($requestedPendingQuantity = (float) ($requestedPendingQuantities->get($insumo->id, 0) ?? 0))
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">{{ str_replace('_', ' ', $insumo->tipo ?: 'sin tipo') }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-slate-600">{{ $insumo->codigo }}</td>
                            <td class="px-4 py-2 text-slate-800">{{ $insumo->concepto }}</td>
                            <td class="px-4 py-2 text-slate-600">{{ $insumo->unidad }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ number_format((float) $insumo->cantidad_presupuestada, 4) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">${{ number_format((float) $insumo->precio_unitario, 4) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums font-semibold">${{ number_format((float) $insumo->importe_importado, 2) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">
                                <div>{{ number_format($usedQuantity, 4) }}</div>
                                <div class="text-xs text-slate-400">${{ number_format($usedAmount, 2) }}</div>
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums {{ $requestedPendingQuantity > 0 ? 'text-amber-700 font-semibold' : 'text-slate-400' }}">
                                <div>{{ number_format($requestedPendingQuantity, 4) }}</div>
                                <div class="text-xs">{{ $requestedPendingQuantity > 0 ? 'Solicitado' : '-' }}</div>
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums {{ $availableQuantity < 0 || $availableAmount < 0 ? 'text-red-700 font-semibold' : 'text-slate-700' }}">
                                <div>{{ number_format($availableQuantity, 4) }}</div>
                                <div class="text-xs {{ $availableAmount < 0 ? 'text-red-600' : 'text-slate-400' }}">${{ number_format($availableAmount, 2) }}</div>
                            </td>
                            <td class="px-4 py-2 text-right">
                                @if($ordenesCount > 0)
                                    <a href="{{ route('obra_civil.insumos.orders', [$obra, $insumo]) }}" class="font-semibold text-[#0B265A] hover:underline">
                                        {{ number_format($ordenesCount) }} OC
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">Sin OC</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-xs text-slate-500">{{ $insumo->source_row }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-5 py-10 text-center text-sm text-slate-500">Aun no hay explosion de insumos cargada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Historial de cargas de insumos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Archivo</th>
                        <th class="px-5 py-3 text-left">Estado</th>
                        <th class="px-5 py-3 text-left">Usuario</th>
                        <th class="px-5 py-3 text-right">Materiales</th>
                        <th class="px-5 py-3 text-right">Insumos</th>
                        <th class="px-5 py-3 text-right">Importe</th>
                        <th class="px-5 py-3 text-right">Fecha</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($insumoImports as $import)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $import->filename }}</td>
                            <td class="px-5 py-3">{{ $import->status }}</td>
                            <td class="px-5 py-3">{{ $import->importedBy->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($import->total_materiales) }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($import->total_insumos) }}</td>
                            <td class="px-5 py-3 text-right">${{ number_format((float) $import->total_importe, 2) }}</td>
                            <td class="px-5 py-3 text-right">{{ $import->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <form method="POST"
                                      action="{{ route('obra_civil.insumos.destroy', [$obra, $import]) }}"
                                      data-loading-message="Eliminando carga de insumos..."
                                      onsubmit="return confirm('Eliminar esta carga de insumos? Tambien se eliminaran sus renglones asociados.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-red-600 hover:underline">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Sin cargas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection


