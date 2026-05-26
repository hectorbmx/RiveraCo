    @extends('layouts.admin')

@section('title', 'Empresas SAT')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">
@if(session('error'))
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        {{ session('error') }}
    </div>
@endif
@if(session('success'))
    <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
        {{ session('success') }}
    </div>
@endif
 <div class="flex items-start justify-between gap-4">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Empresas SAT</h1>
        <p class="text-sm text-gray-600 mt-1">
            Configuración de credenciales SAT para descarga masiva de CFDIs.
        </p>
    </div>

    <div>
        <a href="{{ route('sat.empresas.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 shadow-sm">
            ➕ Nueva empresa
        </a>
    </div>
</div>
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Listado de empresas</h3>
        <p class="text-sm text-gray-500">Empresas configuradas para usar credenciales SAT.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Empresa</th>
                    <th class="px-4 py-3 text-left font-medium">RFC</th>
                    <th class="px-4 py-3 text-left font-medium">Certificados</th>
                    <th class="px-4 py-3 text-left font-medium">Estado</th>
                    <th class="px-4 py-3 text-right font-medium">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($empresas as $empresa)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">
                                {{ $empresa->nombre }}
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                {{ $empresa->rfc }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="space-y-1 text-sm text-gray-700">
                                <div class="flex items-center gap-2">
                                    <span class="w-10 text-gray-500">CER:</span>
                                    @if($empresa->cer_path)
                                        <span class="text-green-600 font-medium">✔ Cargado</span>
                                    @else
                                        <span class="text-red-500 font-medium">✖ Pendiente</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="w-10 text-gray-500">KEY:</span>
                                    @if($empresa->key_path)
                                        <span class="text-green-600 font-medium">✔ Cargado</span>
                                    @else
                                        <span class="text-red-500 font-medium">✖ Pendiente</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            @if($empresa->activo)
                                <span class="inline-flex rounded-lg bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 border border-green-200">
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex rounded-lg bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('sat.empresas.edit', $empresa->id) }}"
                                class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    Editar
                                </a>

                              <form action="{{ route('sat.empresas.destroy', $empresa->id) }}" method="POST"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar esta empresa SAT?');">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="text-sm font-medium text-red-600 hover:text-red-800">
                                        Eliminar
                                    </button>
                                </form>
                                <form action="{{ route('sat.empresas.solicitar-csf', $empresa) }}" method="POST" style="display:inline;" data-sat-request-form>
                                    @csrf
                                    <button type="submit"
                                        class="text-indigo-600 hover:text-indigo-800 font-medium ml-2">
                                        Solicitar CSF
                                    </button>
                                </form>
                                <form action="{{ route('sat.empresas.solicitar-d32', $empresa) }}" method="POST" style="display:inline;" data-sat-request-form>
                                    @csrf
                                    <button type="submit"
                                        class="text-violet-600 hover:text-violet-800 font-medium ml-2">
                                        Solicitar D32
                                    </button>
                                </form>
                                <a href="{{ route('sat.cfdis.estadisticas', $empresa->id) }}"
                                    class="text-sm font-medium text-emerald-600 hover:text-emerald-800">
                                        Estadísticas
                                    </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-500">
                            No hay empresas SAT registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <!-- TABLA LISTA DE SOLICITUDES DE CONSTANCIAS DE SITUACION FISCAL -->
         <div class="mt-8 bg-white rounded-xl shadow-sm border border-slate-200">
    <div class="px-6 py-4 border-b border-slate-200 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Solicitudes de documentos SAT</h3>
            <p class="text-sm text-slate-500 mt-1">Historial reciente de solicitudes de constancia y otros documentos.</p>
        </div>

        @if($documentRequests->contains('status', \App\Models\SatDocumentRequest::STATUS_ERROR))
            <form method="POST"
                  action="{{ route('sat.document-requests.failed.destroy') }}"
                  onsubmit="return confirm('¿Eliminar todas las solicitudes fallidas del historial?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                    Limpiar fallidas
                </button>
            </form>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Empresa</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Archivo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Solicitado por</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
    @forelse($documentRequests as $request)
        <tr>
            <td class="px-6 py-4 text-sm text-slate-700">{{ $request->id }}</td>
            <td class="px-6 py-4 text-sm text-slate-700">
                {{ $request->empresa?->nombre ?? '—' }}
            </td>
            <td class="px-6 py-4 text-sm text-slate-700">
                @if($request->type === \App\Models\SatDocumentRequest::TYPE_CSF)
                    Constancia de Situación Fiscal
                @elseif($request->type === \App\Models\SatDocumentRequest::TYPE_D32)
                    Opinion de cumplimiento 32-D
                @else
                    {{ strtoupper($request->type) }}
                @endif
            </td>
            <td class="px-6 py-4 text-sm">
                @if($request->status === \App\Models\SatDocumentRequest::STATUS_PENDING)
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        Pendiente
                    </span>
                @elseif($request->status === \App\Models\SatDocumentRequest::STATUS_PROCESSING)
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        Procesando
                    </span>
                @elseif($request->status === \App\Models\SatDocumentRequest::STATUS_COMPLETED)
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Completada
                    </span>
                @elseif($request->status === \App\Models\SatDocumentRequest::STATUS_ERROR)
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                        Error
                    </span>
                @else
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-700">
                        {{ $request->status }}
                    </span>
                @endif

                @if($request->error_message)
                    <div class="text-xs text-red-500 mt-2">
                        {{ $request->error_message }}
                    </div>
                @endif
            </td>
            <td class="px-6 py-4 text-sm text-slate-700">
                    @if($request->file_path)
                        <a href="{{ route('sat.document-requests.pdf', $request->id) }}" 
                        target="_blank" 
                        class="text-indigo-600 hover:text-indigo-800 font-medium">
                            Ver PDF
                        </a>
                @else
                    <span class="text-slate-400">Sin archivo</span>
                @endif
            </td>
            <td class="px-6 py-4 text-sm text-slate-700">
                {{ $request->requester?->name ?? '—' }}
            </td>
            <td class="px-6 py-4 text-sm text-slate-700">
                {{ $request->created_at?->format('d/m/Y H:i') }}
            </td>
            <td class="px-6 py-4 text-right text-sm">
                @if($request->status === \App\Models\SatDocumentRequest::STATUS_ERROR)
                    <form method="POST"
                          action="{{ route('sat.document-requests.destroy', $request) }}"
                          onsubmit="return confirm('¿Eliminar esta solicitud fallida?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                            Eliminar
                        </button>
                    </form>
                @elseif(in_array($request->status, [
                    \App\Models\SatDocumentRequest::STATUS_PENDING,
                    \App\Models\SatDocumentRequest::STATUS_PROCESSING,
                ], true))
                    <form method="POST"
                          action="{{ route('sat.document-requests.cancel', $request) }}"
                          onsubmit="return confirm('¿Cancelar esta solicitud? Si el proceso quedó esperando captcha, esto liberará la empresa para crear una nueva.');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-amber-600 hover:text-amber-800 font-medium">
                            Cancelar
                        </button>
                    </form>
                @else
                    <span class="text-slate-300">—</span>
                @endif
            </td>
        </tr>

@if($request->captcha_token && $request->status === \App\Models\SatDocumentRequest::STATUS_PROCESSING)
    <tr class="bg-slate-50" id="captcha-row-{{ $request->id }}">
        <td colspan="8" class="px-6 py-4">
            <div class="border border-slate-200 rounded-xl bg-white p-4">
                <div class="text-sm font-medium text-slate-800 mb-3">
                    Captcha requerido para continuar la solicitud
                </div>

                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <div class="shrink-0" id="captcha-img-wrap-{{ $request->id }}">
                        <span class="text-xs text-slate-400">Cargando imagen...</span>
                    </div>

                    <div class="flex-1">
                        <label class="block text-sm text-slate-700 mb-1">
                            Escribe el texto del captcha
                        </label>
                        <div class="flex gap-2">
                            <input type="text"
                                   id="captcha-input-{{ $request->id }}"
                                   class="flex-1 border rounded-lg px-3 py-2 text-sm"
                                   placeholder="Captura el texto que ves en la imagen"
                                   autocomplete="off">
                            <button
                                onclick="submitCaptcha('{{ $request->captcha_token }}', {{ $request->id }})"
                                class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm whitespace-nowrap">
                                Enviar captcha
                            </button>
                        </div>
                        <p id="captcha-msg-{{ $request->id }}" class="text-xs mt-2 text-slate-500"></p>
                    </div>
                </div>
            </div>
        </td>
    </tr>

    <script>
    (function () {
        const token   = '{{ $request->captcha_token }}';
        const reqId   = {{ $request->id }};
        const imgUrl  = '{{ route("sat.captcha.image", ":token") }}'.replace(':token', token);
        const postUrl = '{{ route("sat.captcha.submit", ":token") }}'.replace(':token', token);
        let attempts = 0;

        function pollImage() {
            fetch(imgUrl)
                .then(r => r.json())
                .then(data => {
                    if (data.available) {
                        const wrap = document.getElementById('captcha-img-wrap-' + reqId);
                        wrap.innerHTML = `<img src="${data.image}" 
                            class="border rounded-lg bg-white p-2 max-h-24" 
                            alt="Captcha SAT">`;
                    } else {
                        attempts++;
                        if (attempts >= 10) {
                            const wrap = document.getElementById('captcha-img-wrap-' + reqId);
                            wrap.innerHTML = '<span class="text-xs text-red-500">No se pudo cargar la imagen. Cancela esta solicitud y crea una nueva.</span>';
                            return;
                        }
                        setTimeout(pollImage, 3000);
                    }
                })
                .catch(() => {
                    attempts++;
                    if (attempts >= 10) {
                        const wrap = document.getElementById('captcha-img-wrap-' + reqId);
                        wrap.innerHTML = '<span class="text-xs text-red-500">No se pudo cargar la imagen. Cancela esta solicitud y crea una nueva.</span>';
                        return;
                    }
                    setTimeout(pollImage, 3000);
                });
        }

        function submitCaptcha(token, reqId) {
            const input = document.getElementById('captcha-input-' + reqId);
            const msg   = document.getElementById('captcha-msg-' + reqId);
            const answer = input.value.trim();

            if (!answer) {
                msg.textContent = 'Escribe el texto del captcha antes de enviar.';
                msg.className = 'text-xs mt-2 text-red-500';
                return;
            }

            fetch(postUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ answer }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    msg.textContent = 'Respuesta enviada, procesando...';
                    msg.className = 'text-xs mt-2 text-green-600';
                    input.disabled = true;
                    setTimeout(() => window.location.reload(), 8000);
                } else {
                    msg.textContent = data.error ?? 'Error al enviar.';
                    msg.className = 'text-xs mt-2 text-red-500';
                }
            })
            .catch(() => {
                msg.textContent = 'Error de conexión, intenta de nuevo.';
                msg.className = 'text-xs mt-2 text-red-500';
            });
        }

        window.submitCaptcha = submitCaptcha;
        pollImage();
    })();
    </script>
@endif
@empty
<tr>
            <td colspan="8" class="px-6 py-8 text-center text-sm text-slate-500">
                No hay solicitudes registradas todavía.
            </td>
        </tr>
    @endforelse
</tbody>
        </table>
    </div>
</div>
    </div>
</div>
    </div>

</div>
<div id="sat-request-loading" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40">
    <div class="rounded-xl bg-white px-6 py-5 shadow-xl border border-slate-200 text-center">
        <div class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600"></div>
        <div class="text-sm font-semibold text-slate-900">Enviando solicitud SAT</div>
        <div class="mt-1 text-xs text-slate-500">Espera un momento...</div>
    </div>
</div>
<script>
    document.querySelectorAll('[data-sat-request-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const overlay = document.getElementById('sat-request-loading');
            const button = form.querySelector('button[type="submit"]');

            if (overlay) {
                overlay.classList.remove('hidden');
                overlay.classList.add('flex');
            }

            if (button) {
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-wait');
            }
        });
    });
</script>
@endsection
