@php
  $canUploadClienteDocumentos = $canUploadClienteDocumentos ?? false;
  $canDeleteClienteDocumentos = $canDeleteClienteDocumentos ?? false;
@endphp

<div class="space-y-6">
  <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
    <div>
      <h2 class="text-lg font-semibold text-slate-800">Documentos del cliente</h2>
      <p class="text-sm text-slate-500">Carga y consulta el expediente legal/fiscal del cliente.</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
      <span class="font-semibold text-slate-800">Tipos disponibles:</span>
      {{ ($documentosTipos ?? collect())->count() }}
    </div>
  </div>

  @if(!$canUploadClienteDocumentos)
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
      Puedes consultar el expediente, pero no tienes permiso para cargar nuevos documentos.
    </div>
  @elseif(($documentosTipos ?? collect())->count())
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <h3 class="mb-4 text-sm font-semibold text-slate-700">Subir nuevo documento</h3>

      <form method="POST" action="{{ route('clientes.documentos.store', $cliente) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label for="documento_tipo_id" class="mb-1 block text-sm font-medium text-slate-700">Tipo de documento <span class="text-red-500">*</span></label>
            <select id="documento_tipo_id" name="documento_tipo_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
              <option value="">Selecciona el tipo de documento</option>
              @foreach($documentosTipos as $tipo)
                <option value="{{ $tipo->id }}" {{ old('documento_tipo_id') == $tipo->id ? 'selected' : '' }}>
                  {{ $tipo->nombre }}{{ $tipo->obligatorio ? ' - obligatorio' : '' }}
                </option>
              @endforeach
            </select>
            @error('documento_tipo_id')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="nombre_documento" class="mb-1 block text-sm font-medium text-slate-700">Nombre del documento</label>
            <input id="nombre_documento" type="text" name="nombre_documento" value="{{ old('nombre_documento') }}" placeholder="Ej. CSF cliente julio 2026" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
            @error('nombre_documento')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="fecha_documento" class="mb-1 block text-sm font-medium text-slate-700">Fecha del documento</label>
            <input id="fecha_documento" type="date" name="fecha_documento" value="{{ old('fecha_documento') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
            @error('fecha_documento')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="fecha_vencimiento" class="mb-1 block text-sm font-medium text-slate-700">Fecha de vencimiento</label>
            <input id="fecha_vencimiento" type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
            @error('fecha_vencimiento')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div class="md:col-span-2">
            <label for="observaciones" class="mb-1 block text-sm font-medium text-slate-700">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]" placeholder="Notas internas del documento">{{ old('observaciones') }}</textarea>
            @error('observaciones')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>

          <div class="md:col-span-2">
            <label for="archivo" class="mb-1 block text-sm font-medium text-slate-700">Archivo <span class="text-red-500">*</span></label>
            <input id="archivo" type="file" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-[#0B265A] file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[#091f47]">
            <p class="mt-1 text-xs text-slate-400">Formatos permitidos: PDF, JPG, JPEG, PNG, WEBP. Maximo 10 MB.</p>
            @error('archivo')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="pt-2">
          <button type="submit" class="inline-flex items-center rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-medium text-white hover:bg-[#091f47]">
            Guardar documento
          </button>
        </div>
      </form>
    </div>
  @else
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
      No hay tipos de documentos configurados para clientes. Agregalos en Configuracion empresa, tab Documentos.
    </div>
  @endif

  <div class="overflow-hidden rounded-xl border border-slate-200">
    <div class="border-b border-slate-200 bg-white px-4 py-3">
      <h3 class="text-sm font-semibold text-slate-700">Historial de documentos</h3>
    </div>

    @if(($documentos ?? collect())->count())
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
            <tr>
              <th class="px-4 py-3 text-left">Tipo</th>
              <th class="px-4 py-3 text-left">Nombre</th>
              <th class="px-4 py-3 text-left">Fecha</th>
              <th class="px-4 py-3 text-left">Vencimiento</th>
              <th class="px-4 py-3 text-left">Estado</th>
              <th class="px-4 py-3 text-left">Archivo</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            @foreach($documentos as $doc)
              @php
                $fileUrl = $doc->archivo_path ? asset('storage/' . $doc->archivo_path) : null;
                $nombreMostrar = $doc->nombre_documento ?: ($doc->documentoTipo->nombre ?? str_replace('_', ' ', $doc->tipo_documento));
              @endphp
              <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 align-top font-medium text-slate-800">
                  {{ $doc->documentoTipo->nombre ?? str_replace('_', ' ', $doc->tipo_documento) }}
                  @if($doc->documentoTipo?->obligatorio)
                    <div class="mt-1 text-xs font-normal text-red-600">Obligatorio</div>
                  @endif
                </td>
                <td class="px-4 py-3 align-top">
                  <div class="font-medium text-slate-800">{{ $nombreMostrar }}</div>
                  @if($doc->archivo_nombre_original)
                    <div class="mt-1 text-xs text-slate-500">Original: {{ $doc->archivo_nombre_original }}</div>
                  @endif
                  @if($doc->observaciones)
                    <div class="mt-1 text-xs text-slate-500">{{ $doc->observaciones }}</div>
                  @endif
                </td>
                <td class="px-4 py-3 align-top text-slate-700">{{ $doc->fecha_documento ? $doc->fecha_documento->format('d/m/Y') : '-' }}</td>
                <td class="px-4 py-3 align-top text-slate-700">{{ $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d/m/Y') : '-' }}</td>
                <td class="px-4 py-3 align-top">
                  @if($doc->vigente)
                    <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Vigente</span>
                  @else
                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">Historico</span>
                  @endif
                  <div class="mt-1">
                    @if($doc->estatus_validacion === 'validado')
                      <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">Validado</span>
                    @elseif($doc->estatus_validacion === 'rechazado')
                      <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Rechazado</span>
                    @else
                      <span class="inline-flex rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700">Pendiente</span>
                    @endif
                  </div>
                </td>
                <td class="px-4 py-3 align-top">
                  @if($fileUrl)
                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-blue-600 hover:underline">Ver archivo</a>
                    @if($doc->mime_type)
                      <div class="mt-1 text-xs text-slate-500">{{ $doc->mime_type }}</div>
                    @endif
                  @else
                    <span class="text-slate-400">-</span>
                  @endif
                </td>
                <td class="px-4 py-3 text-right align-top">
                  <div class="flex items-center justify-end gap-2">
                    @if($fileUrl)
                      <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50">Abrir</a>
                    @endif
                    @if($canDeleteClienteDocumentos)
                      <form method="POST" action="{{ route('clientes.documentos.destroy', [$cliente, $doc]) }}" onsubmit="return confirm('Seguro que deseas eliminar este documento?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50">Eliminar</button>
                      </form>
                    @elseif(!$fileUrl)
                      <span class="text-xs text-slate-400">-</span>
                    @endif
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="bg-white px-4 py-8 text-sm text-slate-500">
        Este cliente todavia no tiene documentos cargados.
      </div>
    @endif
  </div>
</div>