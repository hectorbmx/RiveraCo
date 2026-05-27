<div x-show="tab === 'iva'" x-cloak class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Tipos de IVA</h2>
        <p class="text-sm text-gray-600">Catalogo de porcentajes disponibles para productos y ordenes de compra.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h3 class="text-base font-semibold text-slate-900">Nuevo tipo</h3>
                <p class="text-sm text-slate-500 mt-1">Agrega tasas que se puedan usar en compras.</p>
            </div>

            <form method="POST" action="{{ route('empresa_config.tipos-iva.store') }}" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                    <input name="nombre" value="{{ old('nombre') }}" required class="w-full rounded-xl border-slate-300" placeholder="IVA 16%">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Porcentaje</label>
                    <input type="number" step="0.01" min="0" max="100" name="porcentaje" value="{{ old('porcentaje') }}" required class="w-full rounded-xl border-slate-300" placeholder="16">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="default" value="1" class="rounded border-slate-300">
                    Usar como default
                </label>

                <div class="flex justify-end">
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Guardar IVA
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <h3 class="text-base font-semibold text-slate-900">Listado</h3>
                <p class="text-sm text-slate-500 mt-1">{{ $tiposIva->count() }} tipo(s) registrados.</p>
            </div>

            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Nombre</th>
                        <th class="px-5 py-3 text-left font-semibold">Porcentaje</th>
                        <th class="px-5 py-3 text-left font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tiposIva as $tipo)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-900">{{ $tipo->nombre }}</div>
                                @if($tipo->default)
                                    <div class="text-xs font-semibold text-blue-700">Default</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">{{ number_format((float) $tipo->porcentaje, 2) }}%</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $tipo->activo ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-50 text-slate-600 border border-slate-200' }}">
                                    {{ $tipo->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-2">
                                @if(!$tipo->default)
                                    <form method="POST" action="{{ route('empresa_config.tipos-iva.default', $tipo) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Default
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('empresa_config.tipos-iva.toggle-activo', $tipo) }}" class="inline">
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
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">Aun no hay tipos de IVA registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
