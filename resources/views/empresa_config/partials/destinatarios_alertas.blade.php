<form method="POST" action="{{ route('empresa_config.update') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-4">
    @csrf
    @method('PUT')
    <input type="hidden" name="section" value="alertas_{{ $moduloAlerta }}">
    <div>
        <h3 class="text-base font-semibold text-gray-900">Destinatarios: {{ $tituloAlerta }}</h3>
        <p class="text-sm text-gray-600">Usuarios que recibirán estas alertas en la campana cuando estén habilitadas.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs uppercase text-gray-500"><th class="px-3 py-2">Usuario</th><th class="px-3 py-2 text-center">Activo</th></tr></thead>
            <tbody>
                @forelse ($destinatariosAlerta as $destinatario)
                    <tr class="border-b">
                        <td class="px-3 py-2">
                            <input type="hidden" name="destinatarios[{{ $destinatario->id }}][id]" value="{{ $destinatario->id }}">
                            {{ $destinatario->user?->name ?? $destinatario->nombre ?? 'Usuario no disponible' }}
                            <span class="text-gray-500">{{ $destinatario->user?->email }}</span>
                        </td>
                        <td class="px-3 py-2 text-center"><input type="checkbox" name="destinatarios[{{ $destinatario->id }}][activo]" value="1" class="rounded border-gray-300" @checked($destinatario->activo)></td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-3 py-4 text-center text-gray-400">Aún no hay destinatarios configurados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-end gap-3">
        <label class="flex-1 min-w-52 text-sm text-gray-700">Agregar usuario
            <select name="nuevo_user_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                <option value="">Selecciona un usuario</option>
                @foreach ($usuariosNotificables as $usuario)
                    <option value="{{ $usuario->id }}">{{ $usuario->name }} - {{ $usuario->email }}</option>
                @endforeach
            </select>
        </label>
        <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Guardar destinatarios</button>
    </div>
</form>
