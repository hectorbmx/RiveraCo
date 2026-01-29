@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-4xl">
    <h1 class="text-xl font-semibold mb-4">Nueva orden de compra</h1>

    <form method="POST" action="{{ route('ordenes_compra.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-4">
          <div class="relative">
    <label class="block text-sm font-medium mb-1">Proveedor</label>

    {{-- ID real que se envía en el form --}}
    <input type="hidden" name="proveedor_id" id="proveedor_id" value="{{ old('proveedor_id') }}">
    <input type="hidden" name="proveedor_texto" id="proveedor_texto" value="{{ old('proveedor_texto') }}">

    {{-- Input visible --}}
    <input
        type="text"
        id="proveedor_busqueda"
        class="w-full border p-2 rounded"
        placeholder="Escribe al menos 3 caracteres..."
        autocomplete="off"
        value="{{ old('proveedor_texto') }}"
    >

    {{-- Lista de resultados --}}
    <div
        id="proveedor_resultados"
        class="absolute left-0 top-full z-50 mt-1 w-full bg-white border rounded shadow hidden max-h-64 overflow-auto"
    ></div>

    @error('proveedor_id')
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>




            <div>
                <label>Área</label>
                <select name="area_id" class="w-full border p-2">
                    @foreach($areas as $a)
                        <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Obra</label>
                <select name="obra_id" class="w-full border p-2">
                    <option value="">Compra general</option>
                    @foreach($obras as $o)
                        <option value="{{ $o->id }}">{{ $o->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Fecha</label>
                <input type="date" name="fecha" class="w-full border p-2" value="{{ date('Y-m-d') }}">
            </div>

            <div>
                <label>Moneda</label>
                <select name="moneda" class="w-full border p-2">
                    <option value="MXN">MXN</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                </select>
            </div>
            <div>
            <label>IVA base (%)</label>
                    <select name="iva" class="w-full border p-2">
                        <option value="0">0%</option>
                        <option value="12">12%</option>
                        <option value="16" selected>16%</option>
                    </select>
                </div>

            <div>
                <label>Tipo de cambio</label>
                <input type="number" step="0.0001" name="tipo_cambio" class="w-full border p-2">
            </div>
        </div>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Crear orden
        </button>
    </form>
</div>
@stack('scripts')

@endsection
@push('scripts')
<script>
(function () {
    console.log('=== INICIO DEBUG BUSCADOR PROVEEDORES ===');

    const input = document.getElementById('proveedor_busqueda');
    const hiddenId = document.getElementById('proveedor_id');
    const hiddenTxt = document.getElementById('proveedor_texto');
    const box = document.getElementById('proveedor_resultados');

    console.log('Elementos encontrados:', {
        input: !!input,
        hiddenId: !!hiddenId,
        hiddenTxt: !!hiddenTxt,
        box: !!box
    });

    if (!input || !hiddenId || !box) {
        console.error('❌ FALTAN ELEMENTOS DEL DOM');
        return;
    }

    console.log('✅ Todos los elementos encontrados');

    let timer = null;
    let lastFetchController = null;

    function closeBox() {
        console.log('🔒 Cerrando dropdown');
        box.classList.add('hidden');
        box.innerHTML = '';
    }

    function openBox() {
        console.log('🔓 Abriendo dropdown');
        box.classList.remove('hidden');
    }

    function setSelected(id, nombre) {
        console.log('✅ Proveedor seleccionado:', { id, nombre });
        hiddenId.value = id;
        input.value = nombre;
        if (hiddenTxt) hiddenTxt.value = nombre;
        closeBox();
    }

    function render(items) {
        console.log('🎨 Renderizando items:', items);

        if (!items || !items.length) {
            box.innerHTML = `<div class="p-2 text-sm text-slate-500">Sin resultados</div>`;
            openBox();
            return;
        }

        box.innerHTML = items.map(i => `
            <button type="button"
                class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b last:border-b-0"
                data-id="${i.id}"
                data-nombre="${String(i.nombre || '').replace(/"/g, '&quot;')}"
            >
                <div class="font-medium">${i.nombre ?? ''}</div>
                ${i.rfc ? `<div class="text-xs text-slate-500">RFC: ${i.rfc}</div>` : ``}
            </button>
        `).join('');

        openBox();

        box.querySelectorAll('button[data-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                setSelected(btn.dataset.id, btn.dataset.nombre);
            });
        });
    }

    async function search(q) {
        console.log('🔍 Iniciando búsqueda para:', q);

        if (q.length < 3) {
            console.log('⚠️ Búsqueda cancelada: menos de 3 caracteres');
            hiddenId.value = '';
            closeBox();
            return;
        }

        if (lastFetchController) {
            console.log('⏹️ Abortando petición anterior');
            lastFetchController.abort();
        }
        lastFetchController = new AbortController();

        try {
            const url = `{{ route('proveedores.buscar') }}?q=${encodeURIComponent(q)}`;
            console.log('📡 Haciendo fetch a:', url);

            const res = await fetch(url, {
                signal: lastFetchController.signal,
                headers: { 'Accept': 'application/json' }
            });

            console.log('📥 Respuesta HTTP:', res.status, res.statusText);

            if (!res.ok) {
                const errorText = await res.text();
                console.error('❌ Error en respuesta:', errorText);
                throw new Error(`HTTP ${res.status}: ${errorText}`);
            }

            const data = await res.json();
            console.log('✅ Datos recibidos:', data);

            render(Array.isArray(data) ? data : []);
        } catch (e) {
            if (e.name === 'AbortError') {
                console.log('⏹️ Petición abortada');
                return;
            }
            console.error('❌ Error en búsqueda:', e);
            box.innerHTML = `<div class="p-2 text-sm text-red-600">Error: ${e.message}</div>`;
            openBox();
        }
    }

    input.addEventListener('input', (e) => {
        const q = e.target.value.trim();
        console.log('⌨️ Input evento:', q, '(length:', q.length, ')');

        hiddenId.value = '';
        if (hiddenTxt) hiddenTxt.value = q;

        clearTimeout(timer);
        console.log('⏱️ Timer reiniciado, esperando 250ms...');
        timer = setTimeout(() => {
            console.log('⏰ Timer ejecutado, llamando search()');
            search(q);
        }, 250);
    });

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && e.target !== input) {
            console.log('👆 Click fuera, cerrando dropdown');
            closeBox();
        }
    });

    console.log('=== BUSCADOR INICIALIZADO CORRECTAMENTE ===');
})();
</script>
@endpush