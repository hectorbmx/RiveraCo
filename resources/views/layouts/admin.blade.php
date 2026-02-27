<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Rivera Construcciones — Panel</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100 text-slate-900 antialiased">

    <div class="flex min-h-screen">

        {{-- SIDEBAR --}}
        <aside id="sidebar" class="w-64 bg-[#0B265A] text-white flex flex-col transition-all duration-300">

            {{-- Logo --}}
            
            <div class="flex items-center px-6 py-6 gap-3 border-b border-white/10">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center overflow-hidden">
                    <img 
                        src="{{ asset('images/logo.png') }}" 
                        alt="Rivera Construcciones"
                        class="w-full h-full object-contain"
                    >
                </div>

                <div class="font-semibold text-lg leading-tight sidebar-text">
                    Rivera<br>Construcciones
                </div>
            </div>


            {{-- MENU --}}
            <nav class="flex-1 py-6 space-y-1">

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10 {{ request()->routeIs('dashboard') ? 'bg-white/10' : '' }}"
                   title="Dashboard">
                    <span class="text-lg">📊</span>
                    <span class="sidebar-text">Dashboard</span>
                </a>
                <a href="{{ route('facturas.index') }}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10 {{ request()->is('facturas*') ? 'bg-white/10' : '' }}"
                   title="Facturas">
                    <span class="text-lg">👥</span>
                    <span class="sidebar-text">Facturas</span>
                </a>
                @can('clientes.access')
                <a href="{{ route('clientes.index') }}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10 {{ request()->is('clientes*') ? 'bg-white/10' : '' }}"
                   title="Clientes">
                    <span class="text-lg">👥</span>
                    <span class="sidebar-text">Clientes</span>
                </a>
                @endcan
                @can('obras.access')
                <a href="{{ route('obras.index') }}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10 {{ request()->is('obras*') ? 'bg-white/10' : '' }}"
                   title="Obras">
                    <span class="text-lg">🏗️</span>
                    <span class="sidebar-text">Obras</span>
                </a>
                @endcan
                @can('vehiculos.access')
                <a href="{{ route('mantenimiento.vehiculos.index') }}"
                class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                title="Vehículos">
                    <span class="text-lg">🚗</span>
                    <span class="sidebar-text">Vehículos</span>
                </a>
                @endcan
                  
                <a href="{{ route('maquinas.index') }}"
                class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                title="Maquinas">
                    <span class="text-lg">🚗</span>
                    <span class="sidebar-text">Maquinas</span>
                </a>
                

                @can('mantenimiento.access')
                <a href="{{ route ('mantenimiento.mantenimientos.index')}}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                   title="Mantenimiento">
                    <span class="text-lg">🛠️</span>
                    <span class="sidebar-text">Mantenimiento</span>
                </a>
                @endcan
                @can('empleados.access')
                <a href="{{ route('empleados.index') }}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                   title="Empleados">
                    <span class="text-lg">👥</span>
                    <span class="sidebar-text">Empleados</span>
                </a>
                @endcan
                @can('nomina.access')
                <a href="{{ route('nomina.generador.index') }}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                   title="Nómina">
                    <span class="text-lg">📄</span>
                    <span class="sidebar-text">Nómina</span>
                </a>
                @endcan
                <a href="{{ route('ordenes_compra.index') }}"
                class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                title="Órdenes de compra">
                    <span class="text-lg">🛒</span>
                    <span class="sidebar-text">Órdenes de compra</span>
                </a>
                @can('productos.access')
               <a href="{{ route('productos.index') }}"
                class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                title="Productos">
                
                    <span class="text-lg">📦</span>
                    <span class="sidebar-text">Productos</span>
                </a>
             
                <li class="menu-item has-children">
                    <a href="#"  class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                                    title="Inventario">
                        📦 Inventario
                    </a>
                    <ul class="submenu">
                        <li><a href="{{ route('inventario.documentos.index') }}"  class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                                    title="Documentos">📄 Documentos</a></li>
                        <li><a href="{{ route('inventario.stock.index') }}" class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                                    title="Stock">📊 Stock</a></li>
                        <li><a href="{{ route('inventario.kardex.index') }}" class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                                    title="Kardex">📚 Kardex</a></li>
                    </ul>
                </li>

                @endcan
                @can('proveedores.access')    
                 <a href="{{ route('proveedores.index') }}"
                    class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10 {{ request()->routeIs('proveedores.*') ? 'bg-white/10' : '' }}"
                    title="Proveedores">

                        <span class="text-lg">🏭</span>
                        <span class="sidebar-text">Proveedores</span>
                    </a>
                @endcan

                @can('reportes.access')
                <a href="{{ route('reportes.index') }}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                   title="Reportes">
                    <span class="text-lg">📑</span>
                    <span class="sidebar-text">Reportes</span>
                </a>
                @endcan
                @can('empresa.access')
                <a href="{{ route('empresa_config.edit') }}"
                    class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                    title="Configuración de empresa">
                        <span class="text-lg">🏢</span>
                        <span class="sidebar-text">Empresa</span>
                    </a>
                @endcan
                @can('usuarios app.access')
                 <a href="{{ route('usuarios.index') }}"
                   class="flex items-center gap-3 px-6 py-3 text-sm font-medium hover:bg-white/10"
                   title="Usuarios App">
                    <span class="text-lg">👥</span>
                    <span class="sidebar-text">Usuarios App</span>
                </a>
                @endcan

            </nav>

           {{-- FOOTER OPCIONAL --}}
                <div class="px-6 py-4 text-xs text-white/60 flex items-center justify-between border-t border-white/10">
                    <span>v2.0</span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="text-white/70 hover:text-red-400 transition text-xs"
                            title="Cerrar sesión"
                        >
                            Cerrar sesión
                        </button>
                    </form>
                </div>


        </aside>

        {{-- CONTENIDO PRINCIPAL --}}
        <main class="flex-1 flex flex-col">

            {{-- TOPBAR --}}
            <header class="h-16 bg-white shadow flex items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    {{-- Botón para colapsar/expandir sidebar --}}
                    <button id="sidebar-toggle"
                            type="button"
                            class="p-2 rounded-lg border border-slate-200 hover:bg-slate-100">
                        <span class="sr-only">Mostrar/ocultar menú</span>
                        ☰
                    </button>

                    <div class="font-semibold text-lg">
                        @yield('title', 'Dashboard')
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium">{{ auth()->user()->name }}</span>

                    <div class="w-9 h-9 rounded-full bg-[#0B265A] text-white flex items-center justify-center">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </header>

            {{-- CONTENIDO DE LA PÁGINA --}}
            <div class="p-6">
                @yield('content')
            </div>

        </main>

    </div>

    {{-- Script para colapsar/expandir sidebar --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const toggle  = document.getElementById('sidebar-toggle');

            if (!sidebar || !toggle) return;

            const labels = sidebar.querySelectorAll('.sidebar-text');

            toggle.addEventListener('click', function () {
                // Cambiar ancho
                sidebar.classList.toggle('w-64');
                sidebar.classList.toggle('w-20');

                // Mostrar / ocultar textos
                labels.forEach(function (el) {
                    el.classList.toggle('hidden');
                });
            });
        });
    </script>
    
    @stack('scripts')
    @if (session('success'))
<div
    x-data="{ show: true }"
    x-init="setTimeout(() => show = false, 3000)"
    x-show="show"
    x-transition
    class="fixed top-5 right-5 z-50 bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
         viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M5 13l4 4L19 7" />
    </svg>

    <span class="text-sm font-medium">
        {{ session('success') }}
    </span>
</div>
@endif

</body>
</html>
