<div
    x-data="giraldaEppModal({
        stockUrl: '{{ route('inventario.stock.index.json') }}',
        almacenId: 1
    })"
    class="inline-block text-left"
>
    <button type="button" @click="open = true" class="px-3 py-1.5 rounded bg-[#0B265A] text-white hover:bg-blue-900">
        Agregar EPP
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div @click.away="open = false" class="w-full max-w-2xl bg-white rounded-lg shadow-xl">
            <div class="p-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-[#0B265A]">Entrega de EPP</h3>
                    <p class="text-xs text-slate-500">{{ $empleado->nombre_completo }}</p>
                </div>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700">X</button>
            </div>

            <form method="POST" action="{{ route('empleados.epp.store', $empleado->id_Empleado) }}" class="p-4 space-y-3">
                @csrf
                <input type="hidden" name="redirect_to" value="giralda.empleados">
                <input type="hidden" name="articulo" :value="selectedLabel || search">

                <div class="relative">
                    <label class="block text-sm font-medium mb-1">Articulo de inventario</label>
                    <input
                        type="text"
                        x-model="search"
                        @input.debounce.300ms="buscar"
                        @focus="results.length && (showResults = true)"
                        class="w-full border rounded p-2"
                        placeholder="Buscar por nombre o SKU..."
                        autocomplete="off"
                        required
                    >
                    <div x-show="showResults" class="absolute z-50 mt-1 w-full bg-white border rounded shadow max-h-64 overflow-auto">
                        <template x-for="item in results" :key="item.producto_id">
                            <button type="button" @click="seleccionar(item)" class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b last:border-b-0">
                                <div class="font-medium" x-text="item.nombre"></div>
                                <div class="text-xs text-slate-500">
                                    <span x-text="item.sku || 'Sin SKU'"></span>
                                    <span> · Stock: </span>
                                    <span x-text="Number(item.stock_actual || 0).toFixed(2)"></span>
                                    <span x-text="item.unidad ? ' ' + item.unidad : ''"></span>
                                </div>
                            </button>
                        </template>
                        <div x-show="!loading && search.length >= 2 && results.length === 0" class="p-3 text-sm text-slate-500">Sin resultados.</div>
                        <div x-show="loading" class="p-3 text-sm text-slate-500">Buscando...</div>
                    </div>
                    <p x-show="selectedLabel" class="text-xs text-green-700 mt-1">Seleccionado: <span x-text="selectedLabel"></span></p>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Cantidad</label>
                        <input type="number" step="0.01" min="0.01" name="cantidad" value="1" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Talla</label>
                        <input name="talla" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha</label>
                        <input type="date" name="fecha_entrega" value="{{ now()->toDateString() }}" class="w-full border rounded p-2" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Condicion</label>
                        <select name="condicion" class="w-full border rounded p-2" required>
                            <option value="nuevo">Nuevo</option>
                            <option value="bueno">Bueno</option>
                            <option value="reposicion">Reposicion</option>
                            <option value="usado">Usado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Area</label>
                        <select name="area_id" class="w-full border rounded p-2">
                            <option value="">Sin area</option>
                            @foreach(($areas ?? collect()) as $area)
                                <option value="{{ $area->id }}" @selected((int)($areaGiralda?->id) === (int)$area->id)>
                                    {{ $area->codigo ? $area->codigo . ' - ' : '' }}{{ $area->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Obra</label>
                    <select name="obra_id" class="w-full border rounded p-2">
                        <option value="">Sin obra especifica</option>
                        @foreach(($obrasActivas ?? collect()) as $obra)
                            <option value="{{ $obra->id }}">
                                {{ $obra->clave_obra ? $obra->clave_obra . ' - ' : '' }}{{ $obra->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="3" class="w-full border rounded p-2"></textarea>
                </div>


                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded bg-slate-200">Cancelar</button>
                    <button class="px-4 py-2 rounded bg-[#0B265A] text-white">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function giraldaEppModal(config) {
                return {
                    open: false,
                    search: '',
                    selectedLabel: '',
                    results: [],
                    showResults: false,
                    loading: false,
                    async buscar() {
                        this.selectedLabel = '';
                        const q = this.search.trim();
                        if (q.length < 2) {
                            this.results = [];
                            this.showResults = false;
                            return;
                        }

                        this.loading = true;
                        this.showResults = true;

                        try {
                            const url = new URL(config.stockUrl, window.location.origin);
                            url.searchParams.set('q', q);
                            url.searchParams.set('almacen_id', config.almacenId || 1);
                            const response = await fetch(url, { headers: { Accept: 'application/json' } });
                            const payload = await response.json();
                            this.results = payload?.data?.data || [];
                        } catch (error) {
                            this.results = [];
                        } finally {
                            this.loading = false;
                        }
                    },
                    seleccionar(item) {
                        const sku = item.sku ? item.sku + ' - ' : '';
                        this.selectedLabel = sku + item.nombre;
                        this.search = this.selectedLabel;
                        this.results = [];
                        this.showResults = false;
                    }
                };
            }
        </script>
    @endpush
@endonce
