<div class="space-y-6">
  <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <h2 class="text-lg font-semibold text-slate-800">Portales del cliente</h2>
    <p class="mt-1 text-sm text-slate-500">Accesos a portales donde el cliente solicita subir facturas u otros movimientos.</p>
  </div>

  <form method="POST" action="{{ route('clientes.portales.store', $cliente) }}" class="rounded-xl border border-slate-200 bg-white p-4">
    @csrf
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <div class="lg:col-span-3">
        <label class="mb-1 block text-sm font-semibold text-slate-700">Link de acceso</label>
        <input type="url" name="link_acceso" value="{{ old('link_acceso') }}" required placeholder="https://portal.cliente.com"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Usuario</label>
        <input type="text" name="usuario" value="{{ old('usuario') }}" placeholder="correo, RFC o usuario"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Contraseña</label>
        <input type="password" name="password" required autocomplete="new-password"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>
      <div class="flex items-end">
        <button type="submit" class="w-full rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#123a7c]">
          Guardar portal
        </button>
      </div>
    </div>
  </form>

  @if($portales && $portales->count())
    <div class="overflow-x-auto rounded-xl border border-slate-200">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
          <tr>
            <th class="px-4 py-3 text-left">Link</th>
            <th class="px-4 py-3 text-left">Usuario</th>
            <th class="px-4 py-3 text-left">Contraseña</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach($portales as $portal)
            <tr class="align-top hover:bg-slate-50">
              <td class="px-4 py-3">
                <form id="portal-form-{{ $portal->id }}" method="POST" action="{{ route('clientes.portales.update', [$cliente, $portal]) }}" class="space-y-2">
                  @csrf
                  @method('PUT')
                  <input type="url" name="link_acceso" value="{{ old('link_acceso', $portal->link_acceso) }}" required
                         class="w-full min-w-[260px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                  <a href="{{ $portal->link_acceso }}" target="_blank" rel="noopener noreferrer" class="inline-flex text-xs font-semibold text-[#0B265A] hover:underline">
                    Abrir portal
                  </a>
                </form>
              </td>
              <td class="px-4 py-3">
                <input form="portal-form-{{ $portal->id }}" type="text" name="usuario" value="{{ old('usuario', $portal->usuario) }}"
                       class="w-full min-w-[180px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
              </td>
              <td class="px-4 py-3">
                <div x-data="{ show: false }" class="space-y-2">
                  <input :type="show ? 'text' : 'password'" readonly value="{{ $portal->password }}"
                         class="w-full min-w-[180px] rounded-lg border-slate-300 bg-slate-50 text-sm text-slate-700">
                  <button type="button" @click="show = !show" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                    <span x-text="show ? 'Ocultar' : 'Mostrar'"></span>
                  </button>
                </div>
                <input form="portal-form-{{ $portal->id }}" type="password" name="password" placeholder="Nueva contraseña"
                       autocomplete="new-password" class="mt-2 w-full min-w-[180px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
                  <button form="portal-form-{{ $portal->id }}" type="submit" class="rounded-lg bg-[#0B265A] px-3 py-2 text-xs font-semibold text-white hover:bg-[#123a7c]">
                    Actualizar
                  </button>
                  <form method="POST" action="{{ route('clientes.portales.destroy', [$cliente, $portal]) }}" onsubmit="return confirm('Eliminar este acceso de portal?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
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
      Este cliente aun no tiene portales registrados.
    </div>
  @endif
</div>