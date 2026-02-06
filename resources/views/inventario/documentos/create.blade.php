{{-- resources/views/inventario/documentos/create.blade.php --}}
@extends('layouts.admin')



@section('content')
@php
  $tipo = $tipo ?? request('tipo','entrada');

  $tipoLabel = [
    'entrada' => 'Entrada',
    'salida' => 'Salida',
    'resguardo' => 'Resguardo',
    'devolucion' => 'Devolución',
    'ajuste' => 'Ajuste',
  ][$tipo] ?? ucfirst($tipo);

  $needsObra = in_array($tipo, ['salida','resguardo','devolucion'], true);
  $needsMotivo = in_array($tipo, ['entrada','ajuste'], true);
@endphp

<div class="max-w-6xl mx-auto px-6 py-6">

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-semibold">Nuevo documento — {{ $tipoLabel }}</h1>
      <p class="text-slate-500 text-sm">Captura cabecera y partidas. Puedes guardar como borrador o aplicar.</p>
    </div>

    <a href="{{ route('inventario.documentos.index', array_merge(request()->query(), ['tipo'=>$tipo])) }}"
       class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm">
      Volver
    </a>
  </div>

  <form method="POST"
        action="{{ route('inventario.documentos.store', array_merge(request()->query(), ['tipo'=>$tipo])) }}"
        class="space-y-6">
    @csrf

    {{-- tipo fijo por tab --}}
    <input type="hidden" name="tipo" value="{{ $tipo }}">

    {{-- CARD: Cabecera --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <h2 class="font-semibold mb-4">Cabecera</h2>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Almacén --}}
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Almacén *</label>
          <select name="almacen_id" required
                  class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
            <option value="">Selecciona…</option>
            @foreach(($almacenes ?? []) as $a)
              <option value="{{ $a->id }}" @selected(old('almacen_id')==$a->id)>{{ $a->nombre }}</option>
            @endforeach
          </select>
          @error('almacen_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        </div>

        {{-- Fecha (datetime) --}}
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Fecha y hora *</label>
          <input type="datetime-local" name="fecha" required
                 value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}"
                 class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
          @error('fecha')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        </div>

      
        @if($tipo === 'entrada')
  <div class="relative" x-data="buscadorProveedor()">
    <label class="block text-sm font-medium text-slate-700 mb-1">Proveedor (opcional)</label>
    
    <div class="relative">
        <input type="text" 
               x-model="search" 
               @input.debounce.300ms="buscar()"
               placeholder="Escribe RFC o nombre (mín. 3 letras)..."
               class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200 text-sm">
        
        <div x-show="loading" class="absolute right-3 top-2.5">
            <svg class="animate-spin h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
    </div>

    <input type="hidden" name="proveedor_id" :value="selectedId">

    <div x-show="results.length > 0" 
         class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-auto"
         @click.away="results = []">
        <template x-for="p in results" :key="p.id">
            <button type="button" 
                    @click="select(p)"
                    class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 border-b border-slate-50 last:border-0">
                <div class="font-medium text-slate-800" x-text="p.nombre"></div>
                <div class="text-xs text-slate-500" x-text="p.rfc"></div>
            </button>
        </template>
    </div>

    @error('proveedor_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
  </div>
@endif

        {{-- Obra (si aplica) --}}
        @if($needsObra)
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Obra *</label>
            <select name="obra_id" 
                    class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
              <option value="">Selecciona…</option>
              @foreach(($obras ?? []) as $o)
                <option value="{{ $o->id }}" @selected(old('obra_id')==$o->id)>{{ $o->clave_obra }}</option>
              @endforeach
            </select>
            @error('obra_id')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
          </div>
        @endif

        {{-- Motivo --}}
        <div class="{{ $needsObra ? 'md:col-span-3' : 'md:col-span-2' }}">
          <label class="block text-sm font-medium text-slate-700 mb-1">
            Motivo{!! $needsMotivo ? ' *' : '' !!}
          </label>
          <input type="text" name="motivo" {{ $needsMotivo ? 'required' : '' }}
                 value="{{ old('motivo') }}"
                 placeholder="{{ $tipo==='entrada' ? 'Ej. Compra directa, recepción, etc.' : 'Opcional' }}"
                 class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200">
          @error('motivo')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        </div>

        {{-- Notas --}}
        <div class="md:col-span-3">
          <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
          <textarea name="notas" rows="2"
                    class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                    placeholder="Observaciones…">{{ old('notas') }}</textarea>
          @error('notas')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        </div>

        {{-- Ajuste: tipo incremento/decremento (solo UI; se manda en request) --}}
        @if($tipo === 'ajuste')
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de ajuste *</label>
            <select name="ajuste_tipo" required
                    class="w-full rounded-xl border-slate-200 focus:border-slate-400 focus:ring-slate-200"
                    id="ajusteTipoSelect">
              <option value="incremento" @selected(old('ajuste_tipo','incremento')==='incremento')>Incremento</option>
              <option value="decremento" @selected(old('ajuste_tipo')==='decremento')>Decremento</option>
            </select>
            @error('ajuste_tipo')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
          </div>
        @endif
      </div>
    </div>

{{-- CARD: Partidas --}}
<div class="bg-white rounded-2xl border border-slate-200 p-5" x-data="documentoPartidas()">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
    <div>
        <h2 class="font-semibold mb-2">Partidas</h2>
        <p class="text-xs text-slate-500">Busca un producto para añadirlo a la lista</p>
    </div>

    <div class="relative flex-1 max-w-xl">
        <div class="flex gap-2">
            <div class="relative flex-1">
                <input type="text" 
                       x-model="search" 
                       @input.debounce.300ms="buscar()"
                       @keydown.enter.prevent="if(selectedProduct) addRow()"
                       placeholder="Buscar producto por nombre o descripción..."
                       class="w-full rounded-xl border-slate-200 text-sm focus:border-[#0B265A] focus:ring-0">
                
                <div x-show="results.length > 0" 
                     class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-64 overflow-auto"
                     @click.away="results = []">
                    <template x-for="p in results" :key="p.id">
                        <button type="button" 
                                @click="preselect(p)"
                                class="w-full text-left px-4 py-3 hover:bg-slate-50 border-b border-slate-50 last:border-0 transition-colors">
                            <div class="font-medium text-slate-800 text-sm" x-text="p.nombre"></div>
                            <div class="text-[10px] text-slate-400 uppercase tracking-wider" x-text="p.descripcion ? p.descripcion.substring(0, 60) + '...' : ''"></div>
                        </button>
                    </template>
                </div>
            </div>
            
            <button type="button" 
                    @click="addRow()"
                    :disabled="!selectedProduct"
                    class="px-4 py-2 rounded-xl bg-[#0B265A] text-white text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <span class="text-lg">+</span> Agregar
            </button>
        </div>
    </div>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="text-slate-500">
        <tr class="border-b">
          <th class="text-left py-3 pr-3">Producto</th>
          <th class="text-left py-3 pr-3 w-32">Cantidad</th>
          <th class="text-left py-3 pr-3 w-40">Costo unitario</th>
          <th class="text-left py-3 pr-3">Notas</th>
          <th class="text-right py-3 w-16"></th>
        </tr>
      </thead>
      <tbody>
        <template x-for="(item, index) in partidas" :key="index">
            <tr class="border-b align-top bg-white">
              <td class="py-3 pr-3">
                <div class="font-medium text-slate-700" x-text="item.nombre"></div>
                <input type="hidden" :name="`detalles[${index}][producto_id]`" :value="item.producto_id">
              </td>
              <td class="py-3 pr-3">
                <input type="number" step="0.001" min="0.001" required
                       :name="`detalles[${index}][cantidad]`"
                       x-model="item.cantidad"
                       class="w-full rounded-lg border-slate-200 text-sm">
              </td>
              <td class="py-3 pr-3">
                <input type="number" step="0.0001"
                       :name="`detalles[${index}][costo_unitario]`"
                       x-model="item.costo_unitario"
                       :required="'{{ $tipo }}' === 'entrada'"
                       class="w-full rounded-lg border-slate-200 text-sm">
              </td>
              <td class="py-3 pr-3">
                <input type="text" :name="`detalles[${index}][notas]`"
                       x-model="item.notas"
                       class="w-full rounded-lg border-slate-200 text-sm">
              </td>
              <td class="py-3 text-right">
                <button type="button" @click="removeRow(index)" class="text-red-400 hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
              </td>
            </tr>
        </template>
        <tr x-show="partidas.length === 0">
            <td colspan="5" class="py-8 text-center text-slate-400 italic">
                No hay productos agregados. Utiliza el buscador de arriba.
            </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
    {{-- Acciones --}}
    <div class="flex items-center justify-end gap-3">
      <button type="submit" name="action" value="draft"
              class="px-5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-sm">
        Guardar borrador
      </button>

      <button type="submit" name="action" value="apply"
              class="px-5 py-2 rounded-xl bg-[#0B265A] text-white text-sm hover:opacity-95">
        Guardar y aplicar
      </button>
    </div>

  </form>
</div>

{{-- JS mínimo para filas --}}
<script>
(function() {
  const rowsBody = document.getElementById('rowsBody');
  const btnAddRow = document.getElementById('btnAddRow');

  function renumberRows() {
    const rows = rowsBody.querySelectorAll('tr[data-row]');
    rows.forEach((tr, idx) => {
      tr.querySelectorAll('select, input').forEach((el) => {
        const name = el.getAttribute('name');
        if (!name) return;
        // detalles[0][campo]
        el.setAttribute('name', name.replace(/detalles\[\d+\]/, `detalles[${idx}]`));
      });
    });
  }

  function applyCostRules() {
    const tipo = @json($tipo);
    const ajusteSelect = document.getElementById('ajusteTipoSelect');
    const ajusteTipo = ajusteSelect ? ajusteSelect.value : null;

    const needCost = (tipo === 'entrada') || (tipo === 'ajuste' && ajusteTipo === 'incremento');

    rowsBody.querySelectorAll('.costo-unitario').forEach((input) => {
      if (needCost) {
        input.required = true;
        input.min = "0.0001";
      } else {
        input.required = false;
        // para evitar validación HTML, lo dejamos sin min cuando no aplica
        input.removeAttribute('min');
      }
    });
  }

  btnAddRow?.addEventListener('click', () => {
    const first = rowsBody.querySelector('tr[data-row]');
    const clone = first.cloneNode(true);

    // limpiar valores
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);

    rowsBody.appendChild(clone);
    renumberRows();
    applyCostRules();
  });

  rowsBody.addEventListener('click', (e) => {
    const btn = e.target.closest('.btnRemoveRow');
    if (!btn) return;

    const rows = rowsBody.querySelectorAll('tr[data-row]');
    if (rows.length <= 1) return; // siempre dejar al menos 1 fila

    btn.closest('tr[data-row]').remove();
    renumberRows();
    applyCostRules();
  });

  document.getElementById('ajusteTipoSelect')?.addEventListener('change', applyCostRules);

  // initial
  applyCostRules();
})();
//termina busccar producto
function buscadorProveedor() {
    return {
        search: '',
        selectedId: '',
        results: [],
        loading: false,
        async buscar() {
            if (this.search.length < 3) {
                this.results = [];
                return;
            }
            this.loading = true;
            try {
                const res = await fetch(`{{ route('inventario.documentos.buscar-proveedor') }}?q=${this.search}`);
                this.results = await res.json();
            } catch (e) {
                console.error("Error buscando proveedores");
            } finally {
                this.loading = false;
            }
        },
        select(p) {
            this.selectedId = p.id;
            this.search = p.nombre; // Muestra el nombre en el input
            this.results = [];      // Cierra la lista
        }
    }
}

function documentoPartidas() {
    return {
        search: '',
        results: [],
        selectedProduct: null,
        partidas: [], // Aquí se guardan las filas de la tabla

        async buscar() {
            if (this.search.length < 3) {
                this.results = [];
                return;
            }
            try {
                const response = await fetch(`{{ route('inventario.documentos.buscar-producto') }}?q=${this.search}`);
                this.results = await response.json();
            } catch (e) {
                console.error("Error al buscar productos");
            }
        },

        preselect(producto) {
            this.selectedProduct = producto;
            this.search = producto.nombre;
            this.results = [];
        },

        addRow() {
            if (!this.selectedProduct) return;

            const tempId = 'qty-' + Date.now();

            // Agregamos el producto a la lista
            this.partidas.push({
                producto_id: this.selectedProduct.id,
                nombre: this.selectedProduct.nombre,
                cantidad: 1,
                costo_unitario: 0,
                notas: '',
                refId: tempId
            });

            // Limpiamos el buscador
            this.selectedProduct = null;
            this.search = '';

            this.$nextTick(() =>{
              const input =document.getElementById(tempId);
              if(input){
                input.focus();
                input.select();
              }
            })
        },

        removeRow(index) {
            this.partidas.splice(index, 1);
        }
    }
}
</script>
@endsection
