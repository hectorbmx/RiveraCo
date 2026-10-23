@php
    $fotos = $fotosMaquina ?? $maquina->fotos;
@endphp

<section id="maquina-fotos" class="space-y-4" aria-labelledby="maquina-fotos-titulo">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="maquina-fotos-titulo" class="text-lg font-semibold text-[#0B265A]">Fotos de la máquina</h2>
                <p class="text-sm text-slate-500">Fotografías generales del equipo. Selecciona una foto para ampliarla.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                {{ $fotos->count() }} {{ $fotos->count() === 1 ? 'foto' : 'fotos' }}
            </span>
        </div>
    </div>

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="mb-1 font-semibold">Revisa las fotos:</p>
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @can('fotos_maquinas_create.access')
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-slate-800">Subir fotos</h3>
            <form method="POST" action="{{ route('maquinas.fotos.store', $maquina) }}" enctype="multipart/form-data" data-fotos-upload class="space-y-4">
                @csrf
                <div>
                    <label for="maquina-fotos-archivos" class="mb-1 block text-xs font-medium text-slate-600">Seleccionar imágenes</label>
                    <input id="maquina-fotos-archivos" name="fotos[]" type="file" multiple required
                           accept="image/jpeg,image/png,image/webp" aria-describedby="maquina-fotos-ayuda"
                           class="block w-full rounded-lg border border-slate-200 p-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-[#0B265A]">
                    <p id="maquina-fotos-ayuda" class="mt-2 text-xs text-slate-500">Hasta 5 fotos por envío, de máximo 5 MB cada una. Formatos JPG, PNG y WebP. Se optimizan automáticamente a WebP.</p>
                    @if($errors->has('fotos') || $errors->has('fotos.*'))
                        <p class="mt-1 text-xs text-slate-500">Vuelve a seleccionar las imágenes para intentar la carga.</p>
                    @endif
                </div>
                <div data-fotos-seleccionadas class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3" aria-live="polite"></div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:cursor-wait disabled:opacity-60">Subir fotos</button>
                </div>
            </form>
        </div>
    @endcan

    @if($fotos->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center">
            <p class="text-sm font-medium text-slate-700">Esta máquina aún no tiene fotos.</p>
            @can('fotos_maquinas_create.access')
                <p class="mt-1 text-sm text-slate-500">Selecciona las primeras imágenes en el formulario superior.</p>
            @endcan
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($fotos as $foto)
                @php
                    $fotoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($foto->ruta);
                    $fotoTexto = $foto->descripcion ?: 'Foto de ' . $maquina->nombre;
                @endphp
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <a href="{{ $fotoUrl }}" target="_blank" rel="noopener" data-foto-ampliar data-foto-texto="{{ $fotoTexto }}"
                       class="group relative block bg-slate-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#0B265A]"
                       aria-label="Ampliar: {{ $fotoTexto }}">
                        <img src="{{ $fotoUrl }}" alt="{{ $fotoTexto }}" loading="lazy" decoding="async" class="h-56 w-full object-contain">
                        <span class="absolute bottom-2 right-2 rounded-lg bg-slate-900/75 px-2 py-1 text-xs text-white group-hover:bg-slate-900">Ampliar foto</span>
                    </a>
                    <div class="space-y-3 p-4">
                        <p class="break-words text-sm text-slate-700">{{ $foto->descripcion ?: 'Sin descripción' }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $foto->usuario?->name ?? 'Usuario no disponible' }}
                            <span aria-hidden="true"> · </span>
                            {{ $foto->created_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                        </p>
                        @can('fotos_maquinas_edit.access')
                            <details class="border-t border-slate-100 pt-3">
                                <summary class="cursor-pointer text-sm font-medium text-[#0B265A]">Editar descripción</summary>
                                <form method="POST" action="{{ route('maquinas.fotos.update', [$maquina, $foto]) }}" class="mt-3 space-y-2" data-foto-accion>
                                    @csrf
                                    @method('PUT')
                                    <label for="maquina-foto-descripcion-{{ $foto->id }}" class="block text-xs font-medium text-slate-600">Descripción (opcional)</label>
                                    <textarea id="maquina-foto-descripcion-{{ $foto->id }}" name="descripcion" rows="3" maxlength="200"
                                              class="w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20">{{ $foto->descripcion }}</textarea>
                                    <p class="text-xs text-slate-500">Máximo 200 caracteres.</p>
                                    <button type="submit" class="rounded-lg bg-[#0B265A] px-3 py-2 text-sm font-medium text-white hover:opacity-90 disabled:cursor-wait disabled:opacity-60">Guardar descripción</button>
                                </form>
                            </details>
                        @endcan
                        @can('fotos_maquinas_delete.access')
                            <form method="POST" action="{{ route('maquinas.fotos.destroy', [$maquina, $foto]) }}" data-foto-eliminar data-foto-accion class="border-t border-slate-100 pt-3">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:cursor-wait disabled:opacity-60">Eliminar foto</button>
                            </form>
                        @endcan
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <dialog id="maquina-foto-ampliada" aria-labelledby="maquina-foto-ampliada-titulo"
            class="m-auto max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-xl bg-white p-4 shadow-2xl backdrop:bg-slate-950/75">
        <div class="mb-3 flex items-start justify-between gap-4">
            <h3 id="maquina-foto-ampliada-titulo" class="break-words text-sm font-semibold text-slate-800">Foto de la máquina</h3>
            <button type="button" data-foto-cerrar autofocus aria-label="Cerrar foto ampliada" class="shrink-0 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Cerrar</button>
        </div>
        <img data-foto-ampliada-imagen alt="" class="max-h-[72vh] w-full object-contain">
    </dialog>
</section>

<script>
(() => {
    const section = document.getElementById('maquina-fotos');
    if (!section) return;

    const dialog = section.querySelector('#maquina-foto-ampliada');
    const largeImage = section.querySelector('[data-foto-ampliada-imagen]');
    const title = section.querySelector('#maquina-foto-ampliada-titulo');
    section.querySelectorAll('[data-foto-ampliar]').forEach(link => {
        link.addEventListener('click', event => {
            if (!dialog.showModal || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            largeImage.src = link.href;
            largeImage.alt = link.dataset.fotoTexto;
            title.textContent = link.dataset.fotoTexto;
            dialog.showModal();
        });
    });
    section.querySelector('[data-foto-cerrar]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
    });

    section.querySelectorAll('[data-foto-accion]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.hasAttribute('data-foto-eliminar') && !window.confirm('¿Eliminar esta foto de la máquina? Esta acción no se puede deshacer.')) {
                event.preventDefault();
                return;
            }
            const button = form.querySelector('button[type="submit"]');
            button.dataset.originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Procesando…';
        });
    });

    const upload = section.querySelector('[data-fotos-upload]');
    if (!upload) return;
    const input = upload.querySelector('input[type="file"]');
    const selected = upload.querySelector('[data-fotos-seleccionadas]');
    let previewUrls = [];
    const oldDescriptions = @json(old('foto_descripciones', []));
    input.addEventListener('change', () => {
        previewUrls.forEach(url => URL.revokeObjectURL(url));
        previewUrls = [];
        selected.replaceChildren();
        input.setCustomValidity('');
        const files = Array.from(input.files);
        if (files.length > 5) {
            input.setCustomValidity('Puedes subir hasta 5 fotos por envío.');
        } else if (files.some(file => file.size > 5 * 1024 * 1024)) {
            input.setCustomValidity('Cada foto debe pesar como máximo 5 MB.');
        } else if (files.some(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type))) {
            input.setCustomValidity('Solo se permiten fotos JPG, PNG o WebP.');
        }
        if (!input.checkValidity()) {
            input.reportValidity();
            return;
        }
        files.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'space-y-2 rounded-lg border border-slate-200 p-3';
            const image = document.createElement('img');
            const url = URL.createObjectURL(file);
            previewUrls.push(url);
            image.src = url;
            image.alt = file.name;
            image.className = 'h-32 w-full rounded-lg bg-slate-100 object-contain';
            const name = document.createElement('p');
            name.className = 'break-words text-xs text-slate-600';
            name.textContent = file.name;
            const label = document.createElement('label');
            label.className = 'block text-xs font-medium text-slate-600';
            label.htmlFor = `maquina-foto-nueva-${index}`;
            label.textContent = 'Descripción (opcional, máximo 200 caracteres)';
            const description = document.createElement('textarea');
            description.id = label.htmlFor;
            description.name = `foto_descripciones[${index}]`;
            description.maxLength = 200;
            description.rows = 2;
            description.value = typeof oldDescriptions?.[index] === 'string' ? oldDescriptions[index] : '';
            description.className = 'w-full rounded-lg border-slate-200 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]/20';
            card.append(image, name, label, description);
            selected.append(card);
        });
    });
    upload.addEventListener('submit', () => {
        const button = upload.querySelector('button[type="submit"]');
        button.dataset.originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Subiendo fotos…';
    });
    window.addEventListener('pageshow', () => {
        section.querySelectorAll('button[type="submit"]').forEach(button => {
            button.disabled = false;
            if (button.dataset.originalText) button.textContent = button.dataset.originalText;
        });
    });
})();
</script>
