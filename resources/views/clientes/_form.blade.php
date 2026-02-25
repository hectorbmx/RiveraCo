@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm text-slate-600 mb-1">Nombre comercial *</label>
    <input name="nombre_comercial" value="{{ old('nombre_comercial', $cliente->nombre_comercial ?? '') }}"
           class="w-full border rounded px-3 py-2" required>
    @error('nombre_comercial') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Razón social</label>
    <input name="razon_social" value="{{ old('razon_social', $cliente->razon_social ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('razon_social') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">RFC</label>
    <input name="rfc" value="{{ old('rfc', $cliente->rfc ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('rfc') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Teléfono</label>
    <input name="telefono" value="{{ old('telefono', $cliente->telefono ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('telefono') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Email</label>
    <input type="email" name="email" value="{{ old('email', $cliente->email ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div class="flex items-center gap-2 mt-6 md:mt-0">
    <input type="checkbox" id="activo" name="activo" value="1"
           @checked(old('activo', ($cliente->activo ?? 1)) == 1)>
    <label for="activo" class="text-sm text-slate-700">Activo</label>
    @error('activo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm text-slate-600 mb-1">Dirección (texto libre)</label>
    <input name="direccion" value="{{ old('direccion', $cliente->direccion ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('direccion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Calle</label>
    <input name="calle" value="{{ old('calle', $cliente->calle ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('calle') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Colonia</label>
    <input name="colonia" value="{{ old('colonia', $cliente->colonia ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('colonia') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Ciudad</label>
    <input name="ciudad" value="{{ old('ciudad', $cliente->ciudad ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('ciudad') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Estado</label>
    <input name="estado" value="{{ old('estado', $cliente->estado ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('estado') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">País</label>
    <input name="pais" value="{{ old('pais', $cliente->pais ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('pais') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>
</div>

<div class="mt-6 flex items-center gap-2">
  <button class="px-4 py-2 rounded bg-[#0B265A] text-white hover:opacity-90">
    Guardar
  </button>
  <a href="{{ route('clientes.index') }}" class="px-4 py-2 rounded bg-slate-100 hover:bg-slate-200">
    Cancelar
  </a>
</div>