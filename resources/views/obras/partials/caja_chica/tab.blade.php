@php
    $gastosCajaChica = $cajaChicaGastos ?? collect();
    $statsCajaChica = $cajaChicaStats ?? [
        'total' => 0,
        'registrado' => 0,
        'autorizado' => 0,
        'pendientes' => 0,
        'rechazados' => 0,
    ];

    $estadoBadge = function ($estado) {
        return match (strtolower((string) $estado)) {
            'autorizado' => 'bg-green-100 text-green-800 border-green-200',
            'rechazado' => 'bg-red-100 text-red-800 border-red-200',
            'pendiente' => 'bg-amber-100 text-amber-800 border-amber-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    };

    $formaPagoLabel = function ($formaPago) {
        return match ((string) $formaPago) {
            '01', 'efectivo' => 'Efectivo',
            '02', 'cheque' => 'Cheque',
            '03', 'transferencia' => 'Transferencia',
            '04', 'tarjeta_credito' => 'Tarjeta credito',
            '28', 'tarjeta_debito' => 'Tarjeta debito',
            '99' => 'Por definir',
            default => filled($formaPago) ? str($formaPago)->replace('_', ' ')->title() : 'Sin definir',
        };
    };
@endphp

<div class="space-y-5">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Gastos de caja chica de la obra</h2>
            <p class="text-sm text-slate-500">Registros capturados desde reposicion de caja chica y asignados a esta obra.</p>
        </div>
        <a href="{{ route('reposicion-caja-chica.index', ['ambito' => 'obra']) }}"
           class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-[#0B265A] shadow-sm hover:bg-slate-50 transition">
            Ver bandeja
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Gastos</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $statsCajaChica['total'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registrado</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">${{ number_format((float) ($statsCajaChica['registrado'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Autorizado</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">${{ number_format((float) ($statsCajaChica['autorizado'] ?? 0), 2) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pendientes / rechazados</p>
            <p class="mt-2 text-2xl font-bold text-[#0B265A]">{{ $statsCajaChica['pendientes'] ?? 0 }} / {{ $statsCajaChica['rechazados'] ?? 0 }}</p>
        </div>
    </div>

    @if($gastosCajaChica->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <p class="text-sm text-slate-500">No hay gastos de caja chica asociados a esta obra.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Folio</th>
                            <th class="px-4 py-3 font-semibold">Fecha</th>
                            <th class="px-4 py-3 font-semibold">Proveedor</th>
                            <th class="px-4 py-3 font-semibold">Categoria</th>
                            <th class="px-4 py-3 font-semibold">Concepto</th>
                            <th class="px-4 py-3 font-semibold">Activo</th>
                            <th class="px-4 py-3 font-semibold">Pago</th>
                            <th class="px-4 py-3 text-right font-semibold">Registrado</th>
                            <th class="px-4 py-3 text-right font-semibold">Autorizado</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gastosCajaChica as $gasto)
                            <tr class="border-t border-slate-200 hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold text-[#0B265A]">{{ $gasto->folio ?? ('G-' . $gasto->id) }}</div>
                                    <div class="text-xs text-slate-500">Captura {{ optional($gasto->created_at)->format('d/m/Y') ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600">
                                    {{ optional($gasto->fecha_gasto)->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium text-slate-800">{{ $gasto->proveedor_nombre ?: ($gasto->proveedor->nombre ?? 'Sin proveedor') }}</div>
                                    @if($gasto->proveedor_rfc || optional($gasto->proveedor)->rfc)
                                        <div class="text-xs text-slate-500">{{ $gasto->proveedor_rfc ?: $gasto->proveedor->rfc }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600">
                                    <div>{{ $gasto->categoria->nombre ?? 'Sin categoria' }}</div>
                                    @if($gasto->subcategoria)
                                        <div class="text-xs text-slate-500">{{ $gasto->subcategoria->nombre }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600 max-w-xs">
                                    <div class="line-clamp-2">{{ $gasto->concepto ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600">
                                    {{ $gasto->activo_operativo_label ?? '-' }}
                                </td>
                                <td class="px-4 py-3 align-top text-slate-600">
                                    {{ $formaPagoLabel($gasto->forma_pago) }}
                                </td>
                                <td class="px-4 py-3 align-top text-right font-semibold text-slate-800">
                                    ${{ number_format((float) ($gasto->importe_registrado ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-3 align-top text-right font-bold text-[#0B265A]">
                                    ${{ number_format((float) ($gasto->importe_autorizado ?? 0), 2) }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $estadoBadge($gasto->estado_autorizacion) }}">
                                        {{ ucfirst((string) ($gasto->estado_autorizacion ?? 'pendiente')) }}
                                    </span>
                                    @if($gasto->resueltoPor)
                                        <div class="mt-1 text-xs text-slate-500">{{ $gasto->resueltoPor->name }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="flex justify-end">
                                        <a href="{{ route('reposicion-caja-chica.show', $gasto) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            Ver
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
