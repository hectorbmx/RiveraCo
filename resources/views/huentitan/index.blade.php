@extends('layouts.admin')

@section('title', 'HUENTITAN')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Modulo operativo</div>
            <h1 class="mt-1 text-2xl font-semibold text-gray-900">HUENTITAN</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $almacen->nombre }}</p>
        </div>
        <a href="{{ route('huentitan.inventario.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">
            Abrir inventario
        </a>
    </div>

    @if(session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Productos registrados</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumen['productos']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Con existencia</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumen['con_existencia']) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Valor actual</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">${{ number_format($resumen['valor_total'], 2) }}</div>
        </div>
        <div class="bg-white border rounded-lg p-4">
            <div class="text-xs text-gray-500">Bajo minimo</div>
            <div class="mt-2 text-2xl font-semibold text-gray-900">{{ number_format($resumen['bajo_minimo']) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <a href="{{ route('huentitan.empleados.index') }}" class="bg-white border rounded-lg p-5 min-h-[170px] hover:border-gray-400 transition flex flex-col">
            <div class="text-xs px-2 py-1 rounded border bg-gray-50 text-gray-600 w-fit">Fase 1</div>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Empleados</h2>
            <p class="mt-1 text-sm text-gray-600">Personal operativo y responsables.</p>
            <div class="mt-auto pt-5 text-sm font-medium text-gray-900">Abrir</div>
        </a>

        <a href="{{ route('huentitan.productos.index') }}" class="bg-white border rounded-lg p-5 min-h-[170px] hover:border-gray-400 transition flex flex-col">
            <div class="text-xs px-2 py-1 rounded border bg-gray-50 text-gray-600 w-fit">Fase 2</div>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Productos</h2>
            <p class="mt-1 text-sm text-gray-600">Clasificacion, stock minimo y kardex.</p>
            <div class="mt-auto pt-5 text-sm font-medium text-gray-900">Abrir</div>
        </a>

        <a href="{{ route('huentitan.inventario.index') }}" class="bg-white border rounded-lg p-5 min-h-[170px] hover:border-gray-400 transition flex flex-col">
            <div class="text-xs px-2 py-1 rounded border bg-green-50 text-green-700 w-fit">Activo</div>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Inventario</h2>
            <p class="mt-1 text-sm text-gray-600">Cortes, existencias y carga inicial.</p>
            <div class="mt-auto pt-5 text-sm font-medium text-gray-900">Abrir</div>
        </a>

        <a href="{{ route('huentitan.ordenes-compra.index') }}" class="bg-white border rounded-lg p-5 min-h-[170px] hover:border-gray-400 transition flex flex-col">
            <div class="text-xs px-2 py-1 rounded border bg-gray-50 text-gray-600 w-fit">Fase 6</div>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Orden compras</h2>
            <p class="mt-1 text-sm text-gray-600">Compras para stock, obra o recepcion documental.</p>
            <div class="mt-auto pt-5 text-sm font-medium text-gray-900">Abrir</div>
        </a>

        <a href="{{ route('huentitan.ordenes-fabricacion.index') }}" class="bg-white border rounded-lg p-5 min-h-[170px] hover:border-gray-400 transition flex flex-col">
            <div class="text-xs px-2 py-1 rounded border bg-gray-50 text-gray-600 w-fit">Fase 5</div>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Orden fabricacion</h2>
            <p class="mt-1 text-sm text-gray-600">Material apartado, produccion y cierre.</p>
            <div class="mt-auto pt-5 text-sm font-medium text-gray-900">Abrir</div>
        </a>

        <a href="{{ route('huentitan.entradas.index') }}" class="bg-white border rounded-lg p-5 min-h-[170px] hover:border-gray-400 transition flex flex-col">
            <div class="text-xs px-2 py-1 rounded border bg-blue-50 text-blue-700 w-fit">Recepcion</div>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Entradas</h2>
            <p class="mt-1 text-sm text-gray-600">Recepcion de compras y afectacion de inventario.</p>
            <div class="mt-auto pt-5 text-sm font-medium text-gray-900">Abrir</div>
        </a>

        <a href="{{ route('huentitan.salidas.index') }}" class="bg-white border rounded-lg p-5 min-h-[170px] hover:border-gray-400 transition flex flex-col">
            <div class="text-xs px-2 py-1 rounded border bg-amber-50 text-amber-700 w-fit">Salida</div>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Salidas</h2>
            <p class="mt-1 text-sm text-gray-600">Entrega de material a obra y descuento de stock.</p>
            <div class="mt-auto pt-5 text-sm font-medium text-gray-900">Abrir</div>
        </a>
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b bg-gray-50 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-gray-900">Estado inicial</h2>
            @if($ultimoCorte)
                <a href="{{ route('inventario.huentitan.cortes.show', $ultimoCorte) }}" class="text-sm text-blue-700 hover:underline">Ver ultimo corte</a>
            @endif
        </div>
        <div class="p-4 text-sm text-gray-700 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <div class="text-xs text-gray-500">Almacen</div>
                <div class="mt-1 font-medium text-gray-900">{{ $almacen->nombre }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Ultimo corte</div>
                <div class="mt-1 font-medium text-gray-900">
                    @if($ultimoCorte)
                        {{ $ultimoCorte->fecha_desde->format('Y-m-d') }} al {{ $ultimoCorte->fecha_hasta->format('Y-m-d') }}
                    @else
                        Sin cortes importados
                    @endif
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Ruta principal</div>
                <div class="mt-1 font-medium text-gray-900">/huentitan</div>
            </div>
        </div>
    </div>
</div>
@endsection



