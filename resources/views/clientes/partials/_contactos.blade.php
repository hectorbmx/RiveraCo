<div class="space-y-6">

  {{-- Encabezado --}}
  <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <h2 class="text-lg font-semibold text-slate-800">Contactos del cliente</h2>
    <p class="mt-1 text-sm text-slate-500">Personas de contacto dentro de la empresa cliente.</p>
  </div>

  {{-- Formulario para agregar nuevo contacto --}}
  <form method="POST" action="{{ route('clientes.contactos.store', $cliente) }}"
        class="rounded-xl border border-slate-200 bg-white p-4">
    @csrf
    <p class="mb-3 text-sm font-semibold text-slate-700">Agregar contacto</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">
          Nombre <span class="text-red-500">*</span>
        </label>
        <input type="text" name="nombre" value="{{ old('nombre') }}" required
               placeholder="Ej. Juan Pérez"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Cargo</label>
        <input type="text" name="cargo" value="{{ old('cargo') }}"
               placeholder="Ej. Director de compras"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Teléfono</label>
        <input type="text" name="telefono" value="{{ old('telefono') }}"
               placeholder="Ej. 8181234567"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Ext.</label>
        <input type="text" name="ext" value="{{ old('ext') }}"
               placeholder="Ej. 102"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>

      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Email</label>
        <input type="email" name="email" value="{{ old('email') }}"
               placeholder="contacto@empresa.com"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>

      <div class="flex flex-col justify-between gap-3">
        <div class="flex items-center gap-2 pt-6">
          <input type="hidden" name="activo" value="0">
          <input type="checkbox" id="nuevo_activo" name="activo" value="1"
                 {{ old('activo', '1') == '1' ? 'checked' : '' }}
                 class="h-4 w-4 rounded border-slate-300 text-[#0B265A] focus:ring-[#0B265A]">
          <label for="nuevo_activo" class="text-sm font-semibold text-slate-700">
            Activo (sigue en la empresa)
          </label>
        </div>
        <button type="submit"
                class="w-full rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123a7c]">
          Guardar contacto
        </button>
      </div>

    </div>
  </form>

  {{-- Tabla de contactos existentes --}}
  @if($contactos && $contactos->count())
    <div class="overflow-x-auto rounded-xl border border-slate-200">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left">Nombre</th>
            <th class="px-4 py-3 text-left">Cargo</th>
            <th class="px-4 py-3 text-left">Teléfono</th>
            <th class="px-4 py-3 text-left">Ext.</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-center">Estatus</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach($contactos as $contacto)
            <tr class="align-top hover:bg-slate-50">

              {{-- Nombre --}}
              <td class="px-4 py-3">
                <form id="contacto-form-{{ $contacto->id }}"
                      method="POST"
                      action="{{ route('clientes.contactos.update', [$cliente, $contacto]) }}">
                  @csrf
                  @method('PUT')
                  <input type="hidden" name="activo" value="0">
                  <input type="text" name="nombre"
                         value="{{ old('nombre', $contacto->nombre) }}" required
                         class="w-full min-w-[140px] rounded-lg border-slate-300 text-sm font-semibold text-slate-800 focus:border-[#0B265A] focus:ring-[#0B265A]">
                </form>
              </td>

              {{-- Cargo --}}
              <td class="px-4 py-3">
                <input form="contacto-form-{{ $contacto->id }}"
                       type="text" name="cargo"
                       value="{{ old('cargo', $contacto->cargo) }}"
                       placeholder="—"
                       class="w-full min-w-[120px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
              </td>

              {{-- Teléfono --}}
              <td class="px-4 py-3">
                <input form="contacto-form-{{ $contacto->id }}"
                       type="text" name="telefono"
                       value="{{ old('telefono', $contacto->telefono) }}"
                       placeholder="—"
                       class="w-full min-w-[120px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
              </td>

              {{-- Ext --}}
              <td class="px-4 py-3">
                <input form="contacto-form-{{ $contacto->id }}"
                       type="text" name="ext"
                       value="{{ old('ext', $contacto->ext) }}"
                       placeholder="—"
                       class="w-full min-w-[60px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
              </td>

              {{-- Email --}}
              <td class="px-4 py-3">
                <input form="contacto-form-{{ $contacto->id }}"
                       type="email" name="email"
                       value="{{ old('email', $contacto->email) }}"
                       placeholder="—"
                       class="w-full min-w-[180px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
              </td>

              {{-- Estatus (checkbox inline) --}}
              <td class="px-4 py-3 text-center">
                <div class="flex flex-col items-center gap-1">
                  <input form="contacto-form-{{ $contacto->id }}"
                         type="checkbox" id="activo-{{ $contacto->id }}" name="activo" value="1"
                         {{ $contacto->activo ? 'checked' : '' }}
                         class="h-4 w-4 rounded border-slate-300 text-[#0B265A] focus:ring-[#0B265A]">
                  <label for="activo-{{ $contacto->id }}"
                         class="text-[11px] font-semibold
                                {{ $contacto->activo ? 'text-emerald-600' : 'text-slate-400' }}">
                    {{ $contacto->activo ? 'Activo' : 'Inactivo' }}
                  </label>
                </div>
              </td>

              {{-- Acciones --}}
              <td class="px-4 py-3 text-right">
                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                  <button form="contacto-form-{{ $contacto->id }}" type="submit"
                          class="rounded-lg bg-[#0B265A] px-3 py-2 text-xs font-semibold text-white hover:bg-[#123a7c]">
                    Actualizar
                  </button>
                  <form method="POST"
                        action="{{ route('clientes.contactos.destroy', [$cliente, $contacto]) }}"
                        onsubmit="return confirm('¿Eliminar este contacto?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                      Eliminar
                    </button>
                  </form>
                </div>
              </td>

            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
      Este cliente aún no tiene contactos registrados.
    </div>
  @endif

</div>
