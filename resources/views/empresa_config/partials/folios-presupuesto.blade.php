<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Folios de presupuesto</h2>
        <p class="text-sm text-slate-600">Cada tipo tiene un consecutivo mensual.</p>
        <p class="text-sm text-slate-600">Puedes subir imágenes JPG, PNG o WebP de hasta 5 MB.</p>
    </div>
    <form method="GET" action="{{ route('empresa_config.edit') }}" class="flex items-end gap-2">
        <input type="hidden" name="tab" value="folios">
        <input type="hidden" name="folio_seccion" value="presupuestos">
        <div>
            <label for="folio-periodo" class="mb-1 block text-xs font-medium text-slate-600">Mes y año</label>
            <input id="folio-periodo" type="month" name="folio_periodo" value="{{ $periodoFoliosPresupuesto }}" required
                   class="rounded-lg border-slate-300 text-sm">
        </div>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Consultar</button>
    </form>
</div>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-[#0B265A] text-white">
            <tr>
                <th scope="col" class="px-4 py-3 text-center">Tipo</th>
                <th scope="col" class="px-4 py-3">Prefijo</th>
                <th scope="col" class="px-4 py-3">Imagen</th>
                <th scope="col" class="px-4 py-3">Último usado</th>
                <th scope="col" class="px-4 py-3">Siguiente folio</th>
                <th scope="col" class="px-4 py-3 text-right">Acción</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($seriesPresupuesto as $tipo => $serie)
                @php
                    $rutaImagen = $imagenesTipoPresupuesto[$tipo] ?? null;
                    $urlImagen = $rutaImagen ? asset($rutaImagen) : null;
                    $quitarImagen = old('tipo') === $tipo && (bool) old('quitar_imagen');
                @endphp
                <tr x-data="{
                    original: @js($urlImagen),
                    vista: null,
                    nombre: '',
                    quitar: @js($quitarImagen),
                    get imagenVisible() { return this.quitar ? null : this.vista ?? this.original },
                    elegirImagen(evento) {
                        if (this.vista) URL.revokeObjectURL(this.vista);
                        const archivo = evento.target.files[0];
                        this.vista = archivo ? URL.createObjectURL(archivo) : null;
                        this.nombre = archivo?.name ?? '';
                        if (archivo) this.quitar = false;
                    }
                }">
                    <th scope="row" class="px-4 py-3 text-center align-middle">{{ $tipo }}</th>
                    <td class="px-4 py-3 font-mono">{{ $serie['prefijo'] }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md"
                                  :class="imagenVisible ? 'bg-[#0B265A]' : 'bg-slate-100'">
                                <img @if($urlImagen && !$quitarImagen) src="{{ $urlImagen }}" @else x-cloak @endif
                                     :src="imagenVisible" x-show="imagenVisible" alt="" class="h-8 w-8 object-contain">
                            </span>
                            <div>
                                <input id="imagen-presupuesto-{{ $tipo }}" type="file" name="imagen" form="folio-presupuesto-{{ $tipo }}"
                                       accept="image/jpeg,image/png,image/webp" aria-label="Subir imagen para {{ $tipo }}"
                                       @change="elegirImagen($event)" class="peer sr-only">
                                <label for="imagen-presupuesto-{{ $tipo }}"
                                       class="inline-flex cursor-pointer whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 hover:bg-slate-50 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-600">
                                    Subir imagen
                                </label>
                                <span x-show="nombre" x-text="nombre" x-cloak class="block max-w-40 truncate pt-1 text-xs text-slate-600"></span>
                                @if($urlImagen)
                                    <label class="mt-1 flex items-center gap-1.5 text-xs text-slate-600">
                                        <input type="checkbox" name="quitar_imagen" value="1" form="folio-presupuesto-{{ $tipo }}"
                                               @checked($quitarImagen) x-model="quitar" :disabled="Boolean(vista)" class="rounded border-slate-300">
                                        Quitar imagen actual
                                    </label>
                                @endif
                            </div>
                        </div>
                        @if(old('tipo') === $tipo)
                            @error('imagen', 'folioPresupuesto')
                                <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <form id="folio-presupuesto-{{ $tipo }}" method="POST" enctype="multipart/form-data" action="{{ route('empresa_config.folios-presupuesto.update') }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="tipo" value="{{ $tipo }}">
                            <input type="hidden" name="periodo" value="{{ $periodoFoliosPresupuesto }}">
                            <input name="ultimo_consecutivo" type="number" min="{{ $serie['ultimo'] }}" max="999999999" required
                                   value="{{ old('tipo') === $tipo ? old('ultimo_consecutivo', $serie['ultimo']) : $serie['ultimo'] }}"
                                   aria-label="Último consecutivo de {{ $tipo }}" class="w-28 rounded-lg border-slate-300 text-sm">
                            @if(old('tipo') === $tipo)
                                @error('ultimo_consecutivo', 'folioPresupuesto')
                                    <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                                @enderror
                            @endif
                        </form>
                    </td>
                    <td class="px-4 py-3 font-mono font-semibold">{{ $serie['siguiente'] }}</td>
                    <td class="px-4 py-3 text-right">
                        <button type="submit" form="folio-presupuesto-{{ $tipo }}"
                                class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs text-white hover:bg-slate-800">Guardar</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
