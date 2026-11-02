<section class="space-y-4" aria-labelledby="documentacion-titulo">
    <div class="rounded-xl border border-slate-200 bg-white p-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="documentacion-titulo" class="text-lg font-semibold text-[#0B265A]">Documentación técnica</h2>
            <p class="text-sm text-slate-500">Manuales, diagramas y otros documentos de esta máquina.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $documentosMaquina->count() }} documentos</span>
    </div>

    @if(session('success'))
        <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Revisa la documentación:</p>
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @can('documentos_maquinas_create.access')
        <form method="POST" action="{{ route('maquinas.documentos.store', $maquina) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white p-4 space-y-4">
            @csrf
            <h3 class="text-sm font-semibold text-[#0B265A]">Agregar documento</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="text-sm font-medium text-slate-700">Nombre *
                    <input name="nombre" value="{{ old('nombre') }}" maxlength="255" required class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Ej. Manual de operación">
                </label>
                <label class="text-sm font-medium text-slate-700">Tipo *
                    <input name="tipo" value="{{ old('tipo') }}" maxlength="50" required class="mt-1 w-full rounded-lg border-slate-300 text-sm" placeholder="Ej. Manual, diagrama o ficha técnica">
                </label>
            </div>
            <label class="block text-sm font-medium text-slate-700">Descripción
                <textarea name="descripcion" rows="2" maxlength="5000" class="mt-1 w-full rounded-lg border-slate-300 text-sm">{{ old('descripcion') }}</textarea>
            </label>
            <label class="block text-sm font-medium text-slate-700">Archivo *
                <input name="archivo" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp" required class="mt-1 block w-full rounded-lg border border-slate-200 p-2 text-sm">
                <span class="mt-1 block text-xs font-normal text-slate-500">PDF, JPG, PNG o WebP. Máximo 10 MB por archivo.</span>
            </label>
            <div class="flex justify-end"><button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-medium text-white">Guardar documento</button></div>
        </form>
    @endcan

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-[#0B265A] text-white"><tr>
                <th class="px-4 py-3">Documento</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Formato</th><th class="px-4 py-3">Carga</th><th class="px-4 py-3">Acciones</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($documentosMaquina as $documento)
                    <tr>
                        <td class="px-4 py-3 min-w-48"><span class="font-semibold text-slate-800 break-words">{{ $documento->nombre }}</span>
                            @if($documento->descripcion)<p class="mt-1 text-xs text-slate-500 whitespace-pre-line break-words">{{ $documento->descripcion }}</p>@endif
                            <p class="mt-1 text-xs text-slate-400 break-all">{{ $documento->nombre_original }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $documento->tipo }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ match($documento->mime_type) { 'application/pdf' => 'PDF', 'image/jpeg' => 'JPG', 'image/png' => 'PNG', 'image/webp' => 'WebP', default => 'Archivo' } }}</td>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $documento->created_at?->format('d/m/Y H:i') ?? '—' }}<br><span class="text-xs text-slate-400">{{ $documento->usuario?->name ?? 'Usuario no disponible' }}</span></td>
                        <td class="px-4 py-3 min-w-48">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('maquinas.documentos.ver', [$maquina, $documento]) }}" target="_blank" rel="noopener" class="text-[#0B265A] font-medium">Abrir</a>
                                <a href="{{ route('maquinas.documentos.descargar', [$maquina, $documento]) }}" class="text-[#0B265A] font-medium">Descargar</a>
                            </div>
                            @can('documentos_maquinas_edit.access')
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-sm text-[#0B265A]">Editar datos</summary>
                                    <form method="POST" action="{{ route('maquinas.documentos.update', [$maquina, $documento]) }}" class="mt-2 space-y-2">
                                        @csrf @method('PUT')
                                        <label class="block text-xs text-slate-600">Nombre <input name="nombre" value="{{ $documento->nombre }}" maxlength="255" required class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                                        <label class="block text-xs text-slate-600">Tipo <input name="tipo" value="{{ $documento->tipo }}" maxlength="50" required class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
                                        <label class="block text-xs text-slate-600">Descripción <textarea name="descripcion" maxlength="5000" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm">{{ $documento->descripcion }}</textarea></label>
                                        <button type="submit" class="rounded-lg bg-[#0B265A] px-3 py-2 text-xs text-white">Guardar cambios</button>
                                    </form>
                                </details>
                            @endcan
                            @can('documentos_maquinas_delete.access')
                                <form method="POST" action="{{ route('maquinas.documentos.destroy', [$maquina, $documento]) }}" class="mt-2" onsubmit="return confirm('¿Eliminar este documento? Esta acción no se puede deshacer.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-sm text-red-700">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Esta máquina todavía no tiene documentos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
