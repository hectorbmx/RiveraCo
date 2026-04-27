@extends('layouts.admin')

@section('title', 'CFDIs Estadísticas')

@section('content')
<div class="p-6 space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Estadísticas CFDI</h1>
        <p class="mt-1 text-gray-600">
            Empresa: {{ $empresa->nombre ?? 'Sin nombre' }} | RFC: {{ $empresaRfc }}
        </p>
    </div>

    <form method="GET" class="flex items-center gap-3">
        <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">

        <label for="year" class="text-sm font-medium text-gray-700">
            Año
        </label>

        <select
            id="year"
            name="year"
            onchange="this.form.submit()"
            class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
            @foreach($years as $availableYear)
                <option value="{{ $availableYear }}" @selected((int) $year === (int) $availableYear)>
                    {{ $availableYear }}
                </option>
            @endforeach
        </select>
    </form>

    {{-- CARDS --}}
    <div class="flex flex-nowrap gap-4 overflow-x-auto">

        <div class="min-w-[250px] flex-1 rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm p-5">
            <div class="text-sm text-emerald-700">Ingresos</div>
            <div class="mt-2 text-2xl font-bold text-emerald-900">
                ${{ number_format($totalIngresos, 2) }}
            </div>
            <div class="mt-1 text-xs text-emerald-600">
                {{ number_format($totalCfdisIngresos) }} CFDIs
            </div>
        </div>

        <div class="min-w-[250px] flex-1 rounded-2xl border border-rose-200 bg-rose-50 shadow-sm p-5">
            <div class="text-sm text-rose-700">Gastos</div>
            <div class="mt-2 text-2xl font-bold text-rose-900">
                ${{ number_format($totalGastos, 2) }}
            </div>
            <div class="mt-1 text-xs text-rose-600">
                {{ number_format($totalCfdisGastos) }} CFDIs
            </div>
        </div>

        <div class="min-w-[250px] flex-1 rounded-2xl border border-indigo-200 bg-indigo-50 shadow-sm p-5">
            <div class="text-sm text-indigo-700">Balance</div>
            <div class="mt-2 text-2xl font-bold text-indigo-900">
                ${{ number_format($balance, 2) }}
            </div>
            <div class="mt-1 text-xs text-indigo-900">
                Ingresos - gastos
            </div>
        </div>

        <div class="min-w-[250px] flex-1 rounded-2xl border border-gray-200 bg-gray-50 shadow-sm p-5">
            <div class="text-sm text-gray-600">Año</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">
                {{ $year }}
            </div>
            <div class="mt-1 text-xs text-gray-500">
                {{ $empresaRfc }}
            </div>
        </div>

    </div>

    {{-- GRÁFICA INGRESOS VS GASTOS --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Ingresos vs Gastos</h2>
                <p class="text-sm text-gray-500">Resumen anual {{ $year }}</p>
            </div>
        </div>

        <div id="chartIngresosGastos"></div>

        {{-- DETALLE MENSUAL --}}
        <div id="detalleMensualWrapper" class="hidden mt-8 border-t border-gray-200 pt-6">

            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <h3 id="detalleMensualTitulo" class="text-lg font-semibold text-gray-900">
                        Detalle mensual
                    </h3>
                    <p id="detalleMensualSubtitulo" class="text-sm text-gray-500">
                        Selecciona una barra de la gráfica.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="cerrarDetalleMensual()"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                >
                    Cerrar
                </button>
            </div>

            <div id="detalleMensualLoading" class="hidden rounded-xl bg-gray-50 p-4 text-sm text-gray-500">
                Cargando detalle...
            </div>

            <div id="detalleMensualEmpty" class="hidden rounded-xl bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-700">
                No hay CFDIs para este mes.
            </div>

            <div id="detalleMensualError" class="hidden rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                No se pudo cargar el detalle mensual.
            </div>

            <div id="detalleMensualTableContainer" class="hidden overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Fecha</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">UUID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Serie/Folio</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">RFC</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Nombre</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Subtotal</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Total</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="detalleMensualBody" class="divide-y divide-gray-100 bg-white"></tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-700">
                                Total
                            </td>
                            <td id="detalleMensualSubtotal" class="px-4 py-3 text-right font-bold text-gray-900">
                                $0.00
                            </td>
                            <td id="detalleMensualTotal" class="px-4 py-3 text-right font-bold text-gray-900">
                                $0.00
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>

    {{-- TOP CLIENTES / PROVEEDORES --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900">Top 10 clientes</h2>
            <p class="text-sm text-gray-500 mb-4">Por total facturado en {{ $year }}</p>
            <div id="chartTopClientes"></div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900">Top 10 proveedores</h2>
            <p class="text-sm text-gray-500 mb-4">Por total recibido en {{ $year }}</p>
            <div id="chartTopProveedores"></div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    const empresaId = @json($empresa->id);
    const year = @json((int) $year);

    const meses = @json($meses);
    const dataIngresos = @json($dataIngresos);
    const dataGastos = @json($dataGastos);

    const detalleMesUrlBase = @json(route('sat.cfdis.estadisticas.detalleMes', $empresa->id));

    function moneyFormatter(value) {
        return '$' + Number(value || 0).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';

        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function showDetalleState(state) {
        document.getElementById('detalleMensualWrapper').classList.remove('hidden');

        document.getElementById('detalleMensualLoading').classList.add('hidden');
        document.getElementById('detalleMensualEmpty').classList.add('hidden');
        document.getElementById('detalleMensualError').classList.add('hidden');
        document.getElementById('detalleMensualTableContainer').classList.add('hidden');

        if (state === 'loading') {
            document.getElementById('detalleMensualLoading').classList.remove('hidden');
        }

        if (state === 'empty') {
            document.getElementById('detalleMensualEmpty').classList.remove('hidden');
        }

        if (state === 'error') {
            document.getElementById('detalleMensualError').classList.remove('hidden');
        }

        if (state === 'table') {
            document.getElementById('detalleMensualTableContainer').classList.remove('hidden');
        }
    }

    function cerrarDetalleMensual() {
        document.getElementById('detalleMensualWrapper').classList.add('hidden');
    }

    async function cargarDetalleMensual(month, tipo, mesLabel) {
        showDetalleState('loading');

        document.getElementById('detalleMensualTitulo').innerText = `${tipo === 'ingresos' ? 'Ingresos' : 'Gastos'} de ${mesLabel} ${year}`;
        document.getElementById('detalleMensualSubtitulo').innerText = 'Detalle de CFDIs incluidos en la barra seleccionada.';

        const url = `${detalleMesUrlBase}?year=${year}&month=${month}&tipo=${tipo}`;

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Error HTTP ' + response.status);
            }

            const data = await response.json();

            const items = data.items ?? data.cfdis ?? [];

            if (!items.length) {
                document.getElementById('detalleMensualBody').innerHTML = '';
                document.getElementById('detalleMensualSubtotal').innerText = moneyFormatter(0);
                document.getElementById('detalleMensualTotal').innerText = moneyFormatter(0);
                showDetalleState('empty');
                return;
            }

            let subtotal = 0;
            let total = 0;

            const rows = items.map(item => {
                subtotal += Number(item.subtotal || 0);
                total += Number(item.total || 0);

                const cfdiUrl = item.url ?? (item.id ? `/sat/cfdis/${item.id}` : '#');

                return `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-700">
                            ${escapeHtml(item.fecha ?? '')}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            <span class="font-mono text-xs">${escapeHtml(item.uuid ?? '')}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            ${escapeHtml(item.serie_folio ?? item.folio ?? '')}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            ${escapeHtml(item.rfc ?? item.rfc_emisor ?? item.rfc_receptor ?? '')}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            ${escapeHtml(item.nombre ?? item.nombre_emisor ?? item.nombre_receptor ?? '')}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            ${moneyFormatter(item.subtotal)}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">
                            ${moneyFormatter(item.total)}
                        </td>
                        <td class="px-4 py-3 text-center">
                            ${item.id ? `<a href="${cfdiUrl}" class="text-indigo-600 hover:text-indigo-800 font-medium">Ver</a>` : ''}
                        </td>
                    </tr>
                `;
            }).join('');

            document.getElementById('detalleMensualBody').innerHTML = rows;
            document.getElementById('detalleMensualSubtotal').innerText = moneyFormatter(data.subtotal ?? subtotal);
            document.getElementById('detalleMensualTotal').innerText = moneyFormatter(data.total ?? total);

            showDetalleState('table');

            document.getElementById('detalleMensualWrapper').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

        } catch (error) {
            console.error(error);
            showDetalleState('error');
        }
    }

    const options = {
        chart: {
            type: 'bar',
            height: 380,
            toolbar: {
                show: true
            },
            events: {
                dataPointSelection: function(event, chartContext, config) {
                    const monthIndex = config.dataPointIndex;
                    const seriesIndex = config.seriesIndex;

                    if (monthIndex < 0 || seriesIndex < 0) return;

                    const month = monthIndex + 1;
                    const mesLabel = meses[monthIndex];

                    const tipo = seriesIndex === 0 ? 'ingresos' : 'gastos';

                    cargarDetalleMensual(month, tipo, mesLabel);
                }
            }
        },
        series: [
            {
                name: 'Ingresos',
                data: dataIngresos
            },
            {
                name: 'Gastos',
                data: dataGastos
            }
        ],
        xaxis: {
            categories: meses
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    return '$' + Number(value).toLocaleString('es-MX');
                }
            }
        },
        tooltip: {
            y: {
                formatter: moneyFormatter
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 6,
                columnWidth: '45%'
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            position: 'top'
        }
    };

    new ApexCharts(document.querySelector("#chartIngresosGastos"), options).render();

    const topClientesLabels = @json($topClientesLabels);
    const topClientesData = @json($topClientesData);
    const topProveedoresLabels = @json($topProveedoresLabels);
    const topProveedoresData = @json($topProveedoresData);

    const baseBarOptions = {
        chart: {
            type: 'bar',
            height: 380,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 6
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            labels: {
                formatter: function (value) {
                    return '$' + Number(value).toLocaleString('es-MX');
                }
            }
        },
        tooltip: {
            y: {
                formatter: moneyFormatter
            }
        }
    };

    new ApexCharts(document.querySelector("#chartTopClientes"), {
        ...baseBarOptions,
        series: [{
            name: 'Total',
            data: topClientesData
        }],
        xaxis: {
            ...baseBarOptions.xaxis,
            categories: topClientesLabels
        }
    }).render();

    new ApexCharts(document.querySelector("#chartTopProveedores"), {
        ...baseBarOptions,
        series: [{
            name: 'Total',
            data: topProveedoresData
        }],
        xaxis: {
            ...baseBarOptions.xaxis,
            categories: topProveedoresLabels
        }
    }).render();
</script>
@endpush