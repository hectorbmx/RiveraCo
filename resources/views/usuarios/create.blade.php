@extends('layouts.admin')


@section('content')
<div class="max-w-4xl mx-auto p-6">
  <h1 class="text-2xl font-semibold mb-6">Nuevo usuario App</h1>

  @if ($errors->any())
    <div class="mb-4 p-3 rounded bg-red-50 text-red-800">
      <ul class="list-disc ml-5">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-5">
    @csrf

    {{-- BUSCADOR EMPLEADO --}}
    <div>
      <label class="block font-medium mb-1">Empleado (legacy)</label>
      <input id="empleado_search" type="text" class="w-full border rounded px-3 py-2"
             placeholder="Busca por nombre, apellido, email o ID (mín 2 chars)">

      <div id="empleado_results"
           class="border rounded mt-2 overflow-hidden hidden"></div>

      <input type="hidden" name="empleado_id" id="empleado_id" value="{{ old('empleado_id') }}">

      <div id="empleado_selected" class="mt-2 text-sm text-gray-700"></div>
    </div>

    {{-- EMAIL --}}
    <div>
      <label class="block font-medium mb-1">Email</label>
      <input id="email" name="email" type="email" class="w-full border rounded px-3 py-2"
             value="{{ old('email') }}" placeholder="correo@dominio.com" required>
    </div>

    {{-- ROL --}}
    <div>
      <label class="block font-medium mb-1">Rol</label>
      <select name="role" class="w-full border rounded px-3 py-2" required>
        <option value="">Selecciona un rol</option>
        @foreach($roles as $r)
          <option value="{{ $r->name }}" @selected(old('role')===$r->name)>{{ $r->name }}</option>
        @endforeach
      </select>
    </div>

    {{-- PASSWORD --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block font-medium mb-1">Contraseña</label>
        <input name="password" type="password" class="w-full border rounded px-3 py-2" required>
      </div>
      <div>
        <label class="block font-medium mb-1">Confirmar contraseña</label>
        <input name="password_confirmation" type="password" class="w-full border rounded px-3 py-2" required>
      </div>
    </div>

    {{-- ACTIVO --}}
    <div class="flex items-center gap-2">
      <input type="checkbox" name="is_active" value="1" id="is_active"
             @checked(old('is_active', 1))>
      <label for="is_active" class="font-medium">Activo</label>
    </div>

    <div class="flex gap-3">
      <button class="bg-blue-600 text-white px-5 py-2 rounded">Guardar</button>
      <a href="{{ route('usuarios.index') }}" class="px-5 py-2 rounded border">Cancelar</a>
    </div>
  </form>
</div>

<script>
(() => {
  const input   = document.getElementById('empleado_search');
  const box     = document.getElementById('empleado_results');
  const hid     = document.getElementById('empleado_id');
  const sel     = document.getElementById('empleado_selected');
  const email   = document.getElementById('email');

  let timer = null;

  function render(items) {
    if (!items.length) {
      box.innerHTML = `<div class="p-3 text-sm text-gray-600">Sin resultados</div>`;
      box.classList.remove('hidden');
      return;
    }

    box.innerHTML = items.map(i => `
      <button type="button"
        class="w-full text-left p-3 hover:bg-gray-50 border-b last:border-b-0"
        data-id="${i.id}"
        data-nombre="${escapeHtml(i.nombre)}"
        data-email="${escapeHtml(i.email || '')}"
        data-puesto="${escapeHtml(i.puesto || '')}">
        <div class="font-medium">${escapeHtml(i.nombre)} <span class="text-gray-500">#${i.id}</span></div>
        <div class="text-sm text-gray-600">${escapeHtml(i.puesto || '')} ${i.email ? '• '+escapeHtml(i.email) : ''}</div>
      </button>
    `).join('');

    box.classList.remove('hidden');
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, m => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  async function search(q) {
    const url = "{{ route('usuarios.empleados.search') }}" + "?q=" + encodeURIComponent(q);
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const json = await res.json();
    return json.data || [];
  }

  input.addEventListener('input', () => {
    const q = input.value.trim();
    clearTimeout(timer);

    if (q.length < 2) {
      box.classList.add('hidden');
      box.innerHTML = '';
      return;
    }

    timer = setTimeout(async () => {
      const items = await search(q);
      render(items);
    }, 250);
  });

  box.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button[data-id]');
    if (!btn) return;

    const id = btn.dataset.id;
    const nombre = btn.dataset.nombre;
    const em = btn.dataset.email;

    hid.value = id;
    sel.innerHTML = `<span class="font-medium">Seleccionado:</span> ${nombre} <span class="text-gray-500">#${id}</span>`;
    box.classList.add('hidden');

    // si el empleado trae email, lo autoponemos (pero puedes editarlo)
    if (em && !email.value) email.value = em;
  });

  document.addEventListener('click', (ev) => {
    if (!box.contains(ev.target) && ev.target !== input) box.classList.add('hidden');
  });
})();
</script>
@endsection
