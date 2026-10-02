<div x-show="tab === 'tipos_sueldo'" x-cloak class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Tipos de sueldo</h2>
        <p class="text-sm text-gray-600">Catalogo base para clasificar empleados y periodos de nomina.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h3 class="text-base font-semibold text-slate-900">Nuevo tipo</h3>
                <p class="text-sm text-slate-500 mt-1">Agrega periodos de pago adicionales para empleados.</p>
            </div>

            <form method="POST" action="{{ route('empresa_config.tipos-sueldo.store') }}" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                    <input name="nombre" value="{{ old('nombre') }}" required class="w-full rounded-xl border-slate-300" placeholder="Semanal especial">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Codigo</label>
                    <input name="codigo" value="{{ old('codigo') }}" required class="w-full rounded-xl border-slate-300" placeholder="semanal_especial">
                    <p class="mt-1 text-xs text-slate-500">Usa letras, numeros y guion bajo. Se normaliza automaticamente.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Dias periodo</label>
                        <input type="number" min="1" max="366" name="dias_periodo" value="{{ old('dias_periodo') }}" class="w-full rounded-xl border-slate-300" placeholder="7">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Factor mensual</label>
                        <input type="number" step="0.0001" min="0" name="factor_mensual" value="{{ old('factor_mensual') }}" class="w-full rounded-xl border-slate-300" placeholder="4.3333">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Orden</label>
                        <input type="number" min="0" name="orden" value="{{ old('orden', 0) }}" class="w-full rounded-xl border-slate-300">
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-700 mt-7">
                        <input type="checkbox" name="activo" value="1" class="rounded border-slate-300" checked>
                        Activo
                    </label>
                </div>

                <div class="flex justify-end">
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Guardar tipo
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5 flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Listado</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ ($tiposSueldo ?? collect())->count() }} tipo(s) registrado(s).</p>
                </div>
                <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    Catalogo de nomina
                </span>
            </div>

            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Nombre</th>
                        <th class="px-5 py-3 text-left font-semibold">Codigo</th>
                        <th class="px-5 py-3 text-left font-semibold">Periodo</th>
                        <th class="px-5 py-3 text-left font-semibold">Factor</th>
                        <th class="px-5 py-3 text-left font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tiposSueldo ?? [] as $tipo)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-900">{{ $tipo->nombre }}</div>
                                <div class="text-xs text-slate-500">Orden {{ $tipo->orden }} / ID {{ $tipo->id }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                    {{ $tipo->codigo }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $tipo->dias_periodo ? $tipo->dias_periodo . ' dias' : 'Sin definir' }}</td>
                            <td class="px-5 py-4 text-slate-700">
                                {{ $tipo->factor_mensual !== null ? number_format((float) $tipo->factor_mensual, 4) : 'Sin definir' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $tipo->activo ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-50 text-slate-600 border border-slate-200' }}">
                                    {{ $tipo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <form method="POST" action="{{ route('empresa_config.tipos-sueldo.toggle-activo', $tipo) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                        {{ $tipo->activo ? 'Deshabilitar' : 'Habilitar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">Aun no hay tipos de sueldo registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
