@extends('layouts.admin')

@section('title', 'Pagos a proveedores')

@section('content')
<div class="max-w-7xl mx-auto p-6" x-data="{ open: false, pago: null }">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Pagos a proveedores</h1>
            <p class="text-sm text-slate-500">Programacion semanal de pagos ligados a ordenes de compra.</p>
        </div>
        @can('pagos_proveedores.schedule.access')
            <a href="{{ route('pagos-proveedores.create') }}" class="rounded-xl bg-[#0B265A] px-5 py-2 text-sm font-semibold text-white">
                Programar pago
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-100 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg bg-red-100 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Desde</label>
            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio', $fechaInicio->toDateString()) }}" class="rounded-xl border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta</label>
            <input type="date" name="fecha_fin" value="{{ request('fecha_fin', $fechaFin->toDateString()) }}" class="rounded-xl border-slate-300">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Estatus</label>
            <select name="estatus" class="rounded-xl border-slate-300">
                <option value="">Todos</option>
                @foreach(['programado' => 'Programado', 'autorizado' => 'Autorizado', 'pagado' => 'Pagado', 'cancelado' => 'Cancelado'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('estatus') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
        <a href="{{ route('pagos-proveedores.index') }}" class="rounded-xl border px-4 py-2 text-sm font-semibold text-slate-700">Semana actual</a>
    </form>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-slate-600">
            Semana: <span class="font-semibold">{{ $fechaInicio->format('d/m/Y') }}</span>
            al <span class="font-semibold">{{ $fechaFin->format('d/m/Y') }}</span>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('pagos-proveedores.index', ['fecha_inicio' => $semanaAnteriorInicio, 'fecha_fin' => $semanaAnteriorFin]) }}"
               class="rounded-xl border px-4 py-2 text-sm font-semibold text-slate-700">Semana anterior</a>
            <a href="{{ route('pagos-proveedores.index', ['fecha_inicio' => $semanaSiguienteInicio, 'fecha_fin' => $semanaSiguienteFin]) }}"
               class="rounded-xl border px-4 py-2 text-sm font-semibold text-slate-700">Semana siguiente</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div class="rounded-xl bg-white p-4 shadow">
            <p class="text-xs font-semibold text-slate-500">Programado</p>
            <p class="text-xl font-bold text-[#0B265A]">${{ number_format($pagos->where('estatus', 'programado')->sum('monto'), 2) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow">
            <p class="text-xs font-semibold text-slate-500">Autorizado</p>
            <p class="text-xl font-bold text-[#0B265A]">${{ number_format($pagos->where('estatus', 'autorizado')->sum('monto'), 2) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow">
            <p class="text-xs font-semibold text-slate-500">Pagado</p>
            <p class="text-xl font-bold text-[#0B265A]">${{ number_format($pagos->where('estatus', 'pagado')->sum('monto'), 2) }}</p>
        </div>
        <div class="rounded-xl bg-white p-4 shadow">
            <p class="text-xs font-semibold text-slate-500">Registros</p>
            <p class="text-xl font-bold text-[#0B265A]">{{ number_format($pagos->total()) }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Fecha probable</th>
                    <th class="px-4 py-3 text-left">OC</th>
                    <th class="px-4 py-3 text-left">Proveedor</th>
                    <th class="px-4 py-3 text-left">Destino</th>
                    <th class="px-4 py-3 text-left">Banco</th>
                    <th class="px-4 py-3 text-right">Monto</th>
                    <th class="px-4 py-3 text-center">Estatus</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($pagos as $pago)
                    @php
                        $oc = $pago->ordenCompra;
                        $destino = $oc?->obra?->nombre
                            ?? ($oc?->centroCosto ? (($oc->centroCosto->codigo ? $oc->centroCosto->codigo.' - ' : '').$oc->centroCosto->nombre) : 'Compra general');
                        $detallePago = [
                            'folio' => $oc?->folio,
                            'proveedor' => $pago->proveedor?->nombre,
                            'proveedor_rfc' => $pago->proveedor?->rfc,
                            'destino' => $destino,
                            'fecha_programada' => optional($pago->fecha_programada)->format('d/m/Y'),
                            'fecha_pago' => optional($pago->fecha_pago)->format('d/m/Y'),
                            'monto' => '$' . number_format((float) $pago->monto, 2) . ' ' . $pago->moneda,
                            'estatus' => ucfirst($pago->estatus),
                            'banco' => $pago->cuentaBancoEmpresa?->banco,
                            'cuenta' => $pago->cuentaBancoEmpresa?->numero_cuenta,
                            'clabe' => $pago->cuentaBancoEmpresa?->clabe,
                            'metodo_pago' => $pago->metodo_pago,
                            'referencia' => $pago->referencia,
                            'observaciones' => $pago->observaciones,
                            'programado_por' => $pago->programadoPor?->name,
                            'created_at' => optional($pago->created_at)->format('d/m/Y H:i'),
                        ];
                    @endphp
                    <tr>
                        <td class="px-4 py-3">{{ optional($pago->fecha_programada)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $oc->folio ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($pago->proveedor)
                                <a href="{{ route('proveedores.show', ['proveedor' => $pago->proveedor->id, 'tab' => 'ordenes']) }}"
                                   class="font-medium text-blue-700 hover:underline">
                                    {{ $pago->proveedor->nombre }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $destino }}</td>
                        <td class="px-4 py-3">{{ $pago->cuentaBancoEmpresa->banco ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format($pago->monto, 2) }} {{ $pago->moneda }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-700">{{ ucfirst($pago->estatus) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        class="text-slate-700 hover:underline"
                                        @click='pago = @json($detallePago); open = true'>
                                    Ver
                                </button>
                                @if($pago->estatus === 'programado')
                                    @can('pagos_proveedores.authorize.access')
                                    <form method="POST" action="{{ route('pagos-proveedores.autorizar', $pago) }}">
                                        @csrf @method('PATCH')
                                        <button class="text-green-700 hover:underline">Autorizar</button>
                                    </form>
                                    @endcan
                                    @can('pagos_proveedores.cancel.access')
                                    <form method="POST" action="{{ route('pagos-proveedores.cancelar', $pago) }}">
                                        @csrf @method('PATCH')
                                        <button class="text-red-700 hover:underline">Cancelar</button>
                                    </form>
                                    @endcan
                                @elseif($pago->estatus === 'autorizado')
                                    @can('pagos_proveedores.pay.access')
                                    <form method="POST" action="{{ route('pagos-proveedores.pagar', $pago) }}" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <input type="date" name="fecha_pago" value="{{ now()->toDateString() }}" class="w-36 rounded-lg border-slate-300 text-xs">
                                        <input name="referencia" placeholder="Referencia" class="w-32 rounded-lg border-slate-300 text-xs">
                                        <button class="text-blue-700 hover:underline">Pagar</button>
                                    </form>
                                    @endcan
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">No hay pagos para el periodo seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pagos->links() }}</div>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-3xl rounded-2xl bg-white shadow-xl" @click.outside="open = false">
            <div class="flex items-center justify-between border-b px-6 py-4">
                <div>
                    <h2 class="text-lg font-bold text-[#0B265A]">Detalle de pago</h2>
                    <p class="text-sm text-slate-500" x-text="pago?.folio || ''"></p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-900" @click="open = false">Cerrar</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6 text-sm">
                <div>
                    <p class="text-xs font-semibold text-slate-500">Proveedor</p>
                    <p class="font-semibold text-slate-900" x-text="pago?.proveedor || '-'"></p>
                    <p class="text-xs text-slate-500" x-text="pago?.proveedor_rfc || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Destino</p>
                    <p class="font-semibold text-slate-900" x-text="pago?.destino || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Fecha probable</p>
                    <p class="text-slate-900" x-text="pago?.fecha_programada || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Fecha de pago</p>
                    <p class="text-slate-900" x-text="pago?.fecha_pago || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Monto</p>
                    <p class="font-semibold text-slate-900" x-text="pago?.monto || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Estatus</p>
                    <p class="text-slate-900" x-text="pago?.estatus || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Banco</p>
                    <p class="text-slate-900" x-text="pago?.banco || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Cuenta / CLABE</p>
                    <p class="text-slate-900">
                        <span x-text="pago?.cuenta || '-'"></span>
                        <span class="text-slate-400"> / </span>
                        <span x-text="pago?.clabe || '-'"></span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Metodo de pago</p>
                    <p class="text-slate-900" x-text="pago?.metodo_pago || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Referencia</p>
                    <p class="text-slate-900" x-text="pago?.referencia || '-'"></p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500">Programo</p>
                    <p class="text-slate-900" x-text="pago?.programado_por || '-'"></p>
                    <p class="text-xs text-slate-500" x-text="pago?.created_at || ''"></p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs font-semibold text-slate-500">Observaciones</p>
                    <p class="whitespace-pre-line text-slate-900" x-text="pago?.observaciones || '-'"></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
