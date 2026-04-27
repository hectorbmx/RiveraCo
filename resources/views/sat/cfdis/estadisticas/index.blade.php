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

    <div class="flex flex-nowrap gap-4 overflow-x-auto">



    {{-- INGRESOS --}}
    <!-- <div class="flex-1 rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm p-5"> -->
        <div class="min-w-[250px] flex-1 rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm p-5">
        <div class="text-sm text-emerald-700">Ingresos</div>
        <div class="mt-2 text-2xl font-bold text-emerald-900">
            ${{ number_format($totalIngresos, 2) }}
        </div>
        <div class="mt-1 text-xs text-emerald-600">
            {{ number_format($totalCfdisIngresos) }} CFDIs
        </div>
    </div>

    {{-- GASTOS --}}
<div class="min-w-[250px] flex-1 rounded-2xl  border border-rose-200 bg-rose-50 shadow-sm p-5">     
       <div class="text-sm text-rose-700">Gastos</div>
        <div class="mt-2 text-2xl font-bold text-rose-900">
            ${{ number_format($totalGastos, 2) }}
        </div>
        <div class="mt-1 text-xs text-rose-600">
            {{ number_format($totalCfdisGastos) }} CFDIs
        </div>
    </div>

    {{-- BALANCE --}}
    <div class="min-w-[250px] flex-1 rounded-2xl border border-indigo-200 bg-indigo-50 shadow-sm p-5">
        <div class="text-sm text-indigo-700">Balance</div>
        <div class="mt-2 text-2xl font-bold text-indigo-900"">
            ${{ number_format($balance, 2) }}
        </div>
        <div class="mt-1 text-xs text-indigo-900"">
            Ingresos - gastos
        </div>
    </div>

    {{-- AÑO --}}
    <div class="min-w-[250px] flex-1 rounded-2xl border border-gray-200 bg-gray-50  shadow-sm p-5">
        <div class="text-sm text-gray-600">Año</div>
        <div class="mt-2 text-2xl font-bold text-gray-900">
            {{ $year }}
        </div>
        <div class="mt-1 text-xs text-gray-500">
            {{ $empresaRfc }}
        </div>
    </div>

</div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Ingresos vs Gastos</h2>
                <p class="text-sm text-gray-500">Resumen anual {{ $year }}</p>
            </div>
        </div>

        <div id="chartIngresosGastos"></div>
    </div>

</div>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    const meses = @json($meses);
    const dataIngresos = @json($dataIngresos);
    const dataGastos = @json($dataGastos);

    const options = {
        chart: {
            type: 'bar',
            height: 380,
            toolbar: {
                show: true
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
                formatter: function (value) {
                    return '$' + Number(value).toLocaleString('es-MX', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
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

    const chart = new ApexCharts(document.querySelector("#chartIngresosGastos"), options);
    chart.render();

    const topClientesLabels = @json($topClientesLabels);
const topClientesData = @json($topClientesData);
const topProveedoresLabels = @json($topProveedoresLabels);
const topProveedoresData = @json($topProveedoresData);

function moneyFormatter(value) {
    return '$' + Number(value).toLocaleString('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

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