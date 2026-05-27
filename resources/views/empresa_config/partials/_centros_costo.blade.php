<div x-show="tab === 'centros_costo'" x-cloak class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Centros de costo</h2>
        <p class="text-sm text-gray-600">Catalogo para gastos administrativos y operativos que no pertenecen a una obra.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h3 class="text-base font-semibold text-slate-900">Nuevo centro</h3>
                <p class="text-sm text-slate-500 mt-1">Define el nombre base. Despues se usara para clasificar gastos.</p>
            </div>

            <form method="POST" action="{{ route('empresa_config.centros-costo.store') }}" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Codigo</label>
                    <input type="text"
                           name="codigo"
                           value="{{ old('codigo') }}"
                           class="w-full rounded-xl border-slate-300"
                           placeholder="OF-GRAL">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                    <input type="text"
                           name="nombre"
                           value="{{ old('nombre') }}"
                           required
                           class="w-full rounded-xl border-slate-300"
                           placeholder="Oficina general">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Descripcion</label>
                    <textarea name="descripcion"
                              rows="3"
                              class="w-full rounded-xl border-slate-300"
                              placeholder="Gastos administrativos, renta, servicios, etc.">{{ old('descripcion') }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Guardar centro
                    </button>
                </div>
            </form>
        </div>

        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 px-6 py-5">
                <h3 class="text-base font-semibold text-slate-900">Listado</h3>
                <p class="text-sm text-slate-500 mt-1">{{ $centrosCosto->count() }} centro(s) de costo registrados.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Codigo</th>
                            <th class="px-5 py-3 text-left font-semibold">Centro de costo</th>
                            <th class="px-5 py-3 text-left font-semibold">Estado</th>
                            <th class="px-5 py-3 text-right font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($centrosCosto as $centro)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ $centro->codigo ?: '-' }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-medium text-slate-900">{{ $centro->nombre }}</div>
                                    <div class="text-xs text-slate-500">{{ $centro->descripcion ?: 'Sin descripcion' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $centro->activo ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-50 text-slate-600 border border-slate-200' }}">
                                        {{ $centro->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <form method="POST" action="{{ route('empresa_config.centros-costo.toggle-activo', $centro) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            {{ $centro->activo ? 'Deshabilitar' : 'Habilitar' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-slate-500">
                                    Aun no hay centros de costo registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
