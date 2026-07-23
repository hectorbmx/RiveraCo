<div class="space-y-6">
  <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <h2 class="text-lg font-semibold text-slate-800">Portales del cliente</h2>
    <p class="mt-1 text-sm text-slate-500">Accesos a portales donde el cliente solicita subir facturas u otros movimientos.</p>
  </div>

  <form method="POST" action="{{ route('clientes.portales.store', $cliente) }}" class="rounded-xl border border-slate-200 bg-white p-4">
    @csrf
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
      <div>
        <label class="mb-1 block text-sm font-semibold text-slate-700">Titulo</label>
        <input type="text" name="titulo" value="{{ old('titulo') }}" required placeholder="Portal de facturas"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>
      <div class="lg:col-span-3">
        <label class="mb-1 block text-sm font-semibold text-slate-700">Link de acceso</label>
        <input type="url" name="link_acceso" value="{{ old('link_acceso') }}" required placeholder="https://portal.cliente.com"
               class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
      </div>
      <div class="lg:col-span-2">
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
            <th class="px-4 py-3 text-left">Titulo</th>
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
                <form id="portal-form-{{ $portal->id }}" method="POST" action="{{ route('clientes.portales.update', [$cliente, $portal]) }}">
                  @csrf
                  @method('PUT')
                  <input type="text" name="titulo" value="{{ old('titulo', $portal->titulo) }}" required
                         class="w-full min-w-[170px] rounded-lg border-slate-300 text-sm font-semibold text-slate-800 focus:border-[#0B265A] focus:ring-[#0B265A]">
                </form>
              </td>
              <td class="px-4 py-3">
                <div class="space-y-2">
                  <input form="portal-form-{{ $portal->id }}" type="url" name="link_acceso" value="{{ old('link_acceso', $portal->link_acceso) }}" required
                         class="w-full min-w-[260px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                  <a href="{{ $portal->link_acceso }}" target="_blank" rel="noopener noreferrer" class="inline-flex text-xs font-semibold text-[#0B265A] hover:underline">
                    Abrir portal
                  </a>
                </div>
              </td>
              <td class="px-4 py-3">
                <input form="portal-form-{{ $portal->id }}" type="text" name="usuario" value="{{ old('usuario', $portal->usuario) }}"
                       class="w-full min-w-[180px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
              </td>
              <td class="px-4 py-3">
                <div x-data="{ show: false }" class="space-y-2">
                  <div class="flex min-w-[220px] overflow-hidden rounded-lg border border-slate-300 bg-slate-50 focus-within:border-[#0B265A] focus-within:ring-1 focus-within:ring-[#0B265A]">
                    <input :type="show ? 'text' : 'password'" readonly value="{{ $portal->password }}"
                           class="min-w-0 flex-1 border-0 bg-slate-50 text-sm text-slate-700 focus:ring-0">
                    <button type="button" @click="show = !show" class="flex w-11 items-center justify-center border-l border-slate-200 text-slate-500 hover:bg-white hover:text-[#0B265A]" :title="show ? 'Ocultar contraseña' : 'Ver contraseña'">
                      <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                      </svg>
                      <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.6 10.6A3 3 0 0 0 13.4 13.4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 6.8C3.8 8.6 2.25 12 2.25 12s3.75 6.75 9.75 6.75c1.6 0 3.02-.48 4.24-1.16" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.9 5.48A8.2 8.2 0 0 1 12 5.25c6 0 9.75 6.75 9.75 6.75a16.4 16.4 0 0 1-2.62 3.32" />
                      </svg>
                    </button>
                  </div>
                  <input form="portal-form-{{ $portal->id }}" type="password" name="password" placeholder="Nueva contraseña"
                         autocomplete="new-password" class="w-full min-w-[220px] rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                </div>
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