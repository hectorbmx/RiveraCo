@extends('layouts.admin')
@section('title', 'Presupuesto ' . $presupuesto->codigo_proyecto)
@section('content')
@php
    $pestanas = ['pilas' => 'Pilas / VOL', 'apupi' => 'APUPI', 'presupuesto' => 'Presupuesto', 'previo' => 'PREVIO', 'gastos' => 'Relación de gastos'];
    $solicitada = request()->query('tab');
    $tabInicial = is_string($solicitada) && array_key_exists($solicitada, $pestanas) ? $solicitada : 'presupuesto';
    $revisionEnCurso = $presupuesto->version_origen_borrador_id !== null;
    $siguienteRevision = $versionGuardada ? $versionGuardada->revision + 1 : 0;
    $pdfBorrador = route('presupuesto.pdf', [$presupuesto, 'borrador' => 1]);
    $mostrarVersionUnica = $versionUnica && !request()->boolean('borrador');
    $pdfVersion = $mostrarVersionUnica ? route('presupuesto.versiones.pdf', [$presupuesto, $versionUnica]) : null;
    $totalesVersion = $versionUnica?->datos['presentacion'] ?? null;
    $directoVersion = $versionUnica?->datos['previo']['escenarios']['realista']['directo']
        ?? $versionUnica?->datos['apupi']['resumen']['realista']['total_directo']
        ?? $versionUnica?->datos['presupuesto']['total_costo_directo']
        ?? null;
@endphp
<div class="space-y-5" x-data="{
    tab: @js($tabInicial),
    presentacion: @js($presentacion),
    previo: @js($previo),
    relacionGastos: @js($relacionGastos),
    escenarioPrevio: @js($previo['escenario']),
    unidadesCapturadas: @js(old('unidades', $presupuesto->presentacion_comercial['unidades'] ?? [])),
    urlConcepto: @js(route('presupuesto.presentacion.conceptos.update', [$presupuesto, '__renglon__'])),
    urlNotaEjecucion: @js(route('presupuesto.presentacion.nota-ejecucion.update', $presupuesto)),
    notaEjecucion: JSON.stringify(@js($presentacion['nota_ejecucion'])),
    notaGuardada: JSON.stringify(@js($presentacion['nota_ejecucion'])),
    notaEnviada: null,
    turnoNota: 0,
    errorNota: '',
    errorEditorNota: false,
    urlSemanas: @js(route('presupuesto.gastos-semanales.update', $presupuesto)),
    encabezadoPerforacion: @js(\App\Support\PresentacionPresupuesto::ENCABEZADO_PERFORACION),
    csrf: @js(csrf_token()),
    cola: Promise.resolve(),
    cambiosSemanales: [],
    loteSemanal: null,
    restablecerSemanal: false,
    temporizadorSemanal: null,
    errorSemanal: '',
    mensajeGuardado: '',
    errorGuardado: false,
    temporizadorGuardado: null,
    guardandoVersion: false,
    guardandoEquipos: false,
    errorVersion: '',
    avisar(mensaje, error = false) {
        clearTimeout(this.temporizadorGuardado);
        this.mensajeGuardado = mensaje;
        this.errorGuardado = error;
        this.temporizadorGuardado = setTimeout(() => this.mensajeGuardado = '', error ? 5000 : 2600);
    },
    get semanasPendientes() {
        return this.restablecerSemanal || this.cambiosSemanales.length > 0;
    },
    get notaPendiente() {
        return this.notaEjecucion !== this.notaGuardada;
    },
    editarNotaEjecucion(operaciones) {
        this.notaEjecucion = JSON.stringify(operaciones);
        this.errorNota = '';
    },
    enviarNotaEjecucion() {
        const nota = this.notaEjecucion;
        if (nota === (this.notaEnviada ?? this.notaGuardada)) return;

        const turno = ++this.turnoNota;
        this.notaEnviada = nota;
        this.cola = this.cola.then(async () => {
            this.errorNota = '';
            try {
                const respuesta = await fetch(new URL(this.urlNotaEjecucion, location.href).pathname, {
                    method: 'PATCH',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ nota_ejecucion: nota })
                });
                const datos = await respuesta.json().catch(() => ({}));
                if (!respuesta.ok || !datos.ok) {
                    throw new Error(Object.values(datos.errors ?? {})[0]?.[0] || 'No se pudieron guardar las notas.');
                }
                this.notaGuardada = nota;
                if (!this.notaPendiente) this.avisar('Notas de ejecución guardadas.');
            } catch (error) {
                this.errorNota = error instanceof TypeError ? 'No se pudo conectar para guardar las notas.' : error.message;
                this.avisar(this.errorNota, true);
            } finally {
                if (this.turnoNota === turno) this.notaEnviada = null;
            }
        });
    },
    programarGastoSemanal(cambio) {
        this.cambiosSemanales = this.cambiosSemanales.filter(item => item.concepto !== cambio.concepto || item.semana !== cambio.semana);
        this.cambiosSemanales.push(cambio);
        this.errorSemanal = '';
        clearTimeout(this.temporizadorSemanal);
        this.temporizadorSemanal = setTimeout(() => this.enviarSemanas(), 700);
    },
    descartarSemanas() {
        clearTimeout(this.temporizadorSemanal);
        this.temporizadorSemanal = null;
        this.cambiosSemanales = [];
        this.restablecerSemanal = false;
        this.errorSemanal = '';
    },
    reiniciarSemanas() {
        if (this.loteSemanal) return;
        this.descartarSemanas();
        this.restablecerSemanal = true;
        this.enviarSemanas();
    },
    enviarSemanas() {
        clearTimeout(this.temporizadorSemanal);
        this.temporizadorSemanal = null;
        if (this.loteSemanal || !this.semanasPendientes) return;

        const cambios = this.cambiosSemanales;
        const restablecer = this.restablecerSemanal;
        const datos = restablecer ? {restablecer: true} : {};
        if (cambios.length) datos.cambios = cambios;
        this.loteSemanal = {cambios, restablecer};
        this.descartarSemanas();

        this.cola = this.cola.then(async () => {
            try {
                const respuesta = await fetch(this.urlSemanas, {
                    method: 'PATCH',
                    headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf},
                    body: JSON.stringify(datos)
                });
                const contenido = await respuesta.json().catch(() => ({}));
                if (!respuesta.ok) throw new Error(Object.values(contenido.errors ?? {})[0]?.[0] || 'No se pudo guardar la programación.');
                this.relacionGastos = contenido;
                this.avisar('Programación semanal guardada');
            } catch (error) {
                const posteriores = this.cambiosSemanales;
                this.cambiosSemanales = cambios.filter(item => !posteriores.some(nuevo => nuevo.concepto === item.concepto && nuevo.semana === item.semana)).concat(posteriores);
                this.restablecerSemanal = restablecer;
                this.errorSemanal = error.message || 'No se pudo guardar la programación.';
                clearTimeout(this.temporizadorSemanal);
                this.temporizadorSemanal = null;
                this.avisar(this.errorSemanal, true);
            } finally {
                this.loteSemanal = null;
                if (!this.errorSemanal && this.temporizadorSemanal === null && this.semanasPendientes) {
                    this.enviarSemanas();
                }
            }
        });
    },
    guardarConcepto(campo, clave) {
        const original = campo.value;
        const concepto = original.trim();
        this.cola = this.cola.then(async () => {
            if (!concepto || concepto.length > 1000) {
                campo.setAttribute('aria-invalid', 'true');
                this.avisar('Captura una descripción de hasta 1000 caracteres.', true);
                return;
            }
            if (concepto === campo.dataset.conceptoGuardado) {
                if (campo.value === original) campo.value = concepto;
                campo.removeAttribute('aria-invalid');
                return;
            }
            try {
                const respuesta = await fetch(this.urlConcepto.replace('__renglon__', encodeURIComponent(clave)), {
                    method: 'PATCH',
                    keepalive: true,
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify({ concepto })
                });
                if (!respuesta.ok) {
                    const datos = await respuesta.json().catch(() => ({}));
                    throw new Error(datos.errors?.concepto?.[0] || 'No se pudo guardar el concepto. Vuelve a salir del campo para reintentar.');
                }
                const datos = await respuesta.json();
                campo.dataset.conceptoGuardado = datos.concepto;
                if (campo.value === original) campo.value = datos.concepto;
                campo.removeAttribute('aria-invalid');
                if (clave === this.encabezadoPerforacion) {
                    this.presentacion.descripcion_perforacion = datos.concepto;
                } else if (this.presentacion.filas[clave]) {
                    this.presentacion.filas[clave].concepto = datos.concepto;
                }
                this.avisar('Concepto guardado');
            } catch (error) {
                campo.setAttribute('aria-invalid', 'true');
                this.avisar(error.message || 'No se pudo guardar el concepto.', true);
            }
        });
    },
    guardarPrevio(formulario) {
        const datos = new URLSearchParams(new FormData(formulario));
        const firma = datos.toString();
        this.cola = this.cola.then(async () => {
            if (firma === formulario.dataset.configuracionGuardada) return;
            if (!formulario.checkValidity()) {
                this.avisar('Revisa los datos de PREVIO antes de guardar.', true);
                return;
            }
            try {
                const respuesta = await fetch(formulario.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: datos
                });
                const contenido = await respuesta.json().catch(() => ({}));
                if (!respuesta.ok) {
                    throw new Error(Object.values(contenido.errors ?? {})[0]?.[0] || 'No se pudo guardar la simulación.');
                }
                this.previo = contenido;
                formulario.dataset.configuracionGuardada = firma;
                this.avisar('Simulación guardada');
            } catch (error) {
                this.avisar(error.message || 'No se pudo guardar la simulación.', true);
            }
        });
    },
    async esperarCambios() {
        this.errorVersion = '';
        if (this.$root.contains(document.activeElement)
            && document.activeElement.matches('.ql-editor, [data-guardado], [data-concepto-guardado], [data-gasto-semanal], [data-previo-config] :is(input, select, textarea)')) {
            document.activeElement.blur();
        }
        let pendiente;
        do {
            this.enviarSemanas();
            this.enviarNotaEjecucion();
            pendiente = this.cola;
            await pendiente;
        } while (pendiente !== this.cola || (!this.errorSemanal && this.semanasPendientes)
            || (!this.errorNota && this.notaPendiente));
        if (this.notaPendiente) {
            this.cambiar('presupuesto');
            this.errorVersion = 'Guarda las notas de ejecución antes de continuar.';
            return false;
        }
        if (this.errorSemanal) {
            this.cambiar('gastos');
            this.errorVersion = 'Corrige o reintenta los importes semanales pendientes antes de continuar.';
            return false;
        }
        const campos = this.$root.querySelectorAll('input[data-guardado]');
        if (Array.from(campos).some(campo => !campo.validity.valid
            || campo.getAttribute('aria-invalid') === 'true' || Number(campo.value) !== Number(campo.dataset.guardado))) {
            this.errorVersion = 'Revisa los campos de APUPI pendientes de guardar y vuelve a intentarlo.';
            return false;
        }
        const conceptos = this.$root.querySelectorAll('[data-concepto-guardado]');
        if (Array.from(conceptos).some(campo => campo.getAttribute('aria-invalid') === 'true'
            || campo.value.trim() !== campo.dataset.conceptoGuardado)) {
            this.cambiar('presupuesto');
            this.errorVersion = 'Revisa los conceptos pendientes de guardar y vuelve a intentarlo.';
            return false;
        }
        const comerciales = this.$root.querySelectorAll('[data-formato-comercial] input:not([type=hidden]), [data-formato-comercial] textarea');
        if (Array.from(comerciales).some(campo => campo.value !== (campo.dataset.inicial ?? campo.defaultValue))) {
            this.cambiar('presupuesto');
            this.$refs.datosImpresion.open = true;
            this.errorVersion = 'Guarda los datos para impresión antes de continuar.';
            return false;
        }
        const previoFormulario = this.$refs.formularioPrevio;
        if (previoFormulario && new URLSearchParams(new FormData(previoFormulario)).toString() !== previoFormulario.dataset.configuracionGuardada) {
            this.cambiar('previo');
            this.errorVersion = 'Guarda los datos de PREVIO antes de continuar.';
            return false;
        }
        return true;
    },
    async guardarVersion(formulario) {
        if (this.guardandoVersion) return;
        this.guardandoVersion = true;
        if (await this.esperarCambios()) {
            formulario.submit();
        } else {
            this.guardandoVersion = false;
        }
    },
    async guardarEquiposPlaneacion(formulario) {
        if (this.guardandoEquipos) return;
        this.guardandoEquipos = true;
        this.enviarSemanas();

        let pendiente;
        do {
            pendiente = this.cola;
            await pendiente;
        } while (!this.errorSemanal && (pendiente !== this.cola || this.semanasPendientes));

        if (this.errorSemanal) {
            this.guardandoEquipos = false;
            return;
        }

        formulario.submit();
    },
    async exportarPdf(url) {
        const ventana = window.open('about:blank', '_blank');
        if (ventana) ventana.opener = null;
        if (!await this.esperarCambios()) {
            ventana?.close();
            return;
        }
        if (ventana) {
            ventana.location.replace(url);
        } else {
            window.location.assign(url);
        }
    },
    async aplicarPrevio(formulario) {
        const escenario = this.escenarioPrevio;
        const objetivo = this.previo.escenarios[escenario].venta_objetivo;
        if (!await this.esperarCambios()) return;
        if (objetivo <= 0 || objetivo !== this.previo.escenarios[escenario].venta_objetivo) {
            this.avisar('Revisa la venta objetivo actual antes de aplicarla.', true);
            return;
        }
        formulario.elements.escenario.value = escenario;
        formulario.elements.objetivo.value = objetivo.toFixed(2);
        formulario.submit();
    },
    formatoImporte(valor) {
        return '$' + Number(valor).toLocaleString('en-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    },
    cambiar(id) {
        this.tab = id;
        const url = new URL(window.location.href);
        url.searchParams.set('tab', id);
        window.history.replaceState({}, '', url);
    },
    abrirPropuesta() {
        this.cambiar('apupi');
        this.$nextTick(() => document.getElementById('propuesta-apupi')?.focus());
    },
    mover(event, salto, extremo = null) {
        const botones = Array.from(this.$refs.pestanas.querySelectorAll('[role=tab]'));
        const actual = botones.indexOf(event.target);
        const siguiente = extremo === null ? (actual + salto + botones.length) % botones.length : extremo;
        botones[siguiente].click();
        botones[siguiente].focus();
    }
}" @beforeunload.window="if (semanasPendientes || loteSemanal || notaPendiente || notaEnviada !== null) { $event.preventDefault(); $event.returnValue = ''; }">
    <div role="status" aria-live="polite" x-show="mensajeGuardado" x-cloak x-text="mensajeGuardado"
         :class="errorGuardado ? 'bg-red-700' : 'bg-emerald-700'"
         class="fixed bottom-6 right-6 z-50 rounded-lg px-4 py-3 text-sm font-semibold text-white shadow-lg"></div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('presupuesto.index') }}" class="text-sm font-medium text-[#0B265A] hover:underline">← Volver a presupuestos</a>
        <div class="flex flex-wrap items-center gap-3">
            @if($versionGuardada)
                <a href="{{ route('presupuesto.versiones.index', $presupuesto) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-[#0B265A] hover:bg-slate-50">Historial de versiones</a>
            @endif
            <form method="POST" action="{{ route('presupuesto.versiones.store', $presupuesto) }}" @submit.prevent="guardarVersion($el)">
                @csrf
                <input type="hidden" name="revision" value="{{ $siguienteRevision }}">
                <button type="submit" :disabled="guardandoVersion" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-[#0B265A] hover:bg-slate-50 disabled:opacity-60"
                        x-text="guardandoVersion ? 'Guardando…' : 'Guardar versión'">Guardar versión</button>
            </form>
            <a href="{{ $pdfVersion ?? $pdfBorrador }}"
               target="_blank" rel="noopener" @click.prevent="exportarPdf($el.href)" class="inline-flex items-center gap-2 rounded-xl bg-[#0B265A] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Exportar PDF
            </a>
        </div>
    </div>
    <p role="alert" x-show="errorVersion" x-cloak x-text="errorVersion" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800"></p>
    @if(session('errorPdfPresupuesto'))
        <p role="alert" class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('errorPdfPresupuesto') }}</p>
    @endif
    @if($errors->versionPresupuesto->any())
        <p role="alert" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->versionPresupuesto->first() }}</p>
    @endif
    @if($revisionEnCurso)
        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            <p class="font-semibold">Borrador #{{ $presupuesto->consecutivo_cliente }}.{{ $siguienteRevision }}</p>
            @if($presupuesto->motivo_borrador_revision)
                <p class="mt-1">{{ $presupuesto->motivo_borrador_revision }}</p>
            @endif
        </div>
    @endif
    <header id="datos-generales" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div>
                <h1 class="text-lg font-semibold text-slate-800">Datos del presupuesto</h1>
                <p class="mt-1 text-2xl font-bold text-[#0B265A]">{{ $presupuesto->codigo_proyecto }}</p>
                <p class="mt-1 text-base text-slate-700">{{ $presupuesto->nombreClienteVisible() }}</p>
                @if($presupuesto->esImportado() && $presupuesto->cliente_id)
                    <p class="mt-1 text-sm text-slate-500">Cliente importado: {{ $presupuesto->nombre_cliente }}</p>
                @endif
                <p class="mt-1 text-sm text-slate-500">{{ $presupuesto->esImportado() ? 'Sincronizado desde Excel' : 'Creado en SIRICO' }}</p>
                <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <div>
                        <dt class="font-medium text-slate-600">Tipo de presupuesto</dt>
                        <dd class="flex items-center gap-2">
                            @include('presupuesto.partials.imagen-tipo', ['tipoImagen' => $presupuesto->tipo, 'clasesImagen' => 'h-7 w-7 object-contain'])
                            {{ $presupuesto->tipo ?? 'Sin clasificar' }}
                        </dd>
                    </div>
                    <div><dt class="font-medium text-slate-600">Nombre de obra</dt><dd>{{ $presupuesto->nombre_obra ?? 'Por definir' }}</dd></div>
                    <div><dt class="font-medium text-slate-600">Equipo propuesto</dt><dd>{{ \App\Models\Presupuesto::EQUIPOS_PROPUESTOS[$presupuesto->equipo_propuesto] ?? 'Por definir' }}</dd></div>
                </dl>
            </div>
            <div class="w-full lg:w-auto">
                @if($mostrarVersionUnica)
                    <div class="flex flex-wrap gap-6 lg:justify-end" x-show="tab === 'presupuesto'" @if($tabInicial !== 'presupuesto') x-cloak @endif>
                        <div><p class="text-xs uppercase text-slate-500">Costo directo realista</p><p class="mt-1 text-xl font-semibold text-slate-700">{{ $directoVersion === null ? '—' : '$' . number_format($directoVersion, 2) }}</p></div>
                        <div><p class="text-xs uppercase text-slate-500">{{ $totalesVersion ? 'Venta sin IVA' : 'Total venta' }}</p><p class="mt-1 text-xl font-bold text-emerald-700">${{ number_format($totalesVersion['subtotal'] ?? $versionUnica->total_venta, 2) }}</p></div>
                    </div>
                @endif
                <div class="flex flex-wrap gap-6 lg:justify-end" @if($mostrarVersionUnica) x-show="tab !== 'presupuesto'" @if($tabInicial === 'presupuesto') x-cloak @endif @endif>
                    @if($presupuesto->esImportado())
                        <div><p class="text-xs uppercase text-slate-500">Costo directo</p><p class="mt-1 text-xl font-semibold text-slate-700">${{ number_format($presupuesto->total_costo_directo, 2) }}</p></div>
                        <div><p class="text-xs uppercase text-slate-500">Total venta</p><p class="mt-1 text-xl font-bold text-emerald-700">${{ number_format($presupuesto->total_presupuesto, 2) }}</p></div>
                    @else
                        <div><p class="text-xs uppercase text-slate-500">Costo directo realista</p><p class="mt-1 text-xl font-semibold text-slate-700"
                            x-text="previo.escenarios.realista.directo == null ? '—' : formatoImporte(previo.escenarios.realista.directo)">{{ $previo['escenarios']['realista']['directo'] === null ? '—' : '$' . number_format($previo['escenarios']['realista']['directo'], 2) }}</p></div>
                        <div><p class="text-xs uppercase text-slate-500">Venta sin IVA</p><p class="mt-1 text-xl font-bold text-emerald-700"
                            x-text="formatoImporte(presentacion.subtotal)">${{ number_format($presentacion['subtotal'], 2) }}</p></div>
                    @endif
                </div>
                @include('presupuesto.partials.duracion-apupi')
            </div>
        </div>
        @if(session('estadoPresupuesto'))
            <p role="status" class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('estadoPresupuesto') }}</p>
        @endif
        <details class="mt-4 border-t border-slate-200 pt-4" @if($errors->datosPresupuesto->any() || $errors->versionPresupuesto->has('cliente_id')) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-[#0B265A]">Editar datos del presupuesto</summary>
            <div class="mt-4">
                @include('presupuesto.partials.datos')
            </div>
        </details>
    </header>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto border-b border-slate-200 bg-slate-50">
            <div role="tablist" aria-label="Secciones del presupuesto" x-ref="pestanas" class="flex min-w-max gap-1 px-3 pt-3">
                @foreach($pestanas as $id => $nombre)
                    <button type="button" role="tab" id="tab-{{ $id }}" aria-controls="panel-{{ $id }}" :aria-selected="tab === '{{ $id }}'" :tabindex="tab === '{{ $id }}' ? 0 : -1"
                            @click="cambiar('{{ $id }}')" @keydown.arrow-right.prevent="mover($event, 1)" @keydown.arrow-left.prevent="mover($event, -1)" @keydown.home.prevent="mover($event, 0, 0)" @keydown.end.prevent="mover($event, 0, {{ count($pestanas) - 1 }})"
                            :class="tab === '{{ $id }}' ? 'border-[#0B265A] bg-white text-[#0B265A]' : 'border-transparent text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
                            class="rounded-t-xl border-b-4 px-5 py-3 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#0B265A]">{{ $nombre }}</button>
                @endforeach
            </div>
        </div>
        <section id="panel-pilas" role="tabpanel" aria-labelledby="tab-pilas" tabindex="0" x-show="tab === 'pilas'" @if($tabInicial !== 'pilas') x-cloak @endif>
            @include('presupuesto.partials.pilas-vol')
        </section>
        <section id="panel-apupi" role="tabpanel" aria-labelledby="tab-apupi" tabindex="0" x-show="tab === 'apupi'" @if($tabInicial !== 'apupi') x-cloak @endif>
            <div class="space-y-3 p-5">
                <h2 class="text-lg font-semibold text-slate-800">APUPI · Análisis de costos</h2>
                @if($volumetria['filas']->isNotEmpty())
                    <h3 class="text-sm font-semibold text-slate-700">Base de Pilas / VOL</h3>
                    <dl class="flex flex-wrap gap-3 text-sm">
                        @foreach(['piezas' => ['Pilas', 0], 'jornales' => ['Jornales', 0], 'metros_lineales' => ['Perforación (ML)', 3], 'concreto' => ['Concreto (m³)', 3]] as $campo => [$etiqueta, $decimales])
                            <div class="rounded-lg bg-slate-100 px-3 py-2">
                                <dt class="text-slate-600">{{ $etiqueta }}</dt>
                                <dd class="font-semibold">{{ number_format($volumetria['totales'][$campo], $decimales) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <p class="text-sm text-slate-500">Captura los tipos de pila en Pilas / VOL para obtener la base de APUPI.</p>
                @endif
            </div>
            @include('presupuesto.partials.costos-apupi')
        </section>
        <section id="panel-presupuesto" role="tabpanel" aria-labelledby="tab-presupuesto" tabindex="0" x-show="tab === 'presupuesto'" @if($tabInicial !== 'presupuesto') x-cloak @endif>
            @if($mostrarVersionUnica)
                <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                    <h2 class="text-lg font-semibold text-slate-800">Propuesta 1 · Versión {{ $versionUnica->folio() }}</h2>
                    <div class="flex flex-wrap gap-4 text-sm font-semibold text-[#0B265A]">
                        <a class="hover:underline" href="{{ route('presupuesto.versiones.show', [$presupuesto, $versionUnica]) }}">Ver versión completa</a>
                        <a class="hover:underline" href="{{ route('presupuesto.show', [$presupuesto, 'tab' => 'presupuesto', 'borrador' => 1]) }}">Ver borrador</a>
                    </div>
                </div>
                @include('presupuesto.partials.propuesta-version', ['version' => $versionUnica])
                @if($totalesVersion)
                    <dl class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-2 bg-slate-100 px-5 py-4 text-right text-sm">
                        <dt>Subtotal</dt><dd>${{ number_format($totalesVersion['subtotal'], 2) }}</dd>
                        <dt>IVA {{ (float) $totalesVersion['tasa_iva'] }} %</dt><dd>${{ number_format($totalesVersion['iva'], 2) }}</dd>
                        <dt class="font-semibold">Total</dt><dd class="font-semibold">${{ number_format($totalesVersion['total'], 2) }}</dd>
                    </dl>
                @endif
            @else
                @if($versionUnica)
                    <div class="px-5 pt-5 text-sm font-semibold text-[#0B265A]">
                        <a class="hover:underline" href="{{ route('presupuesto.show', [$presupuesto, 'tab' => 'presupuesto']) }}">Volver a la versión {{ $versionUnica->folio() }}</a>
                    </div>
                @endif
                @include('presupuesto.partials.presentacion-comercial')
            @endif
        </section>
        <section id="panel-previo" role="tabpanel" aria-labelledby="tab-previo" tabindex="0" x-show="tab === 'previo'" @if($tabInicial !== 'previo') x-cloak @endif>
            @include('presupuesto.partials.previo', ['puedeEditarPrevio' => auth()->user()->hasAnyRole(\App\Support\PrevioPresupuesto::ADMINISTRADORES), 'puedeAbrirPropuesta' => true])
        </section>
        <section id="panel-gastos" role="tabpanel" aria-labelledby="tab-gastos" tabindex="0" x-show="tab === 'gastos'" @if($tabInicial !== 'gastos') x-cloak @endif>
            @include('presupuesto.partials.relacion-gastos', ['editarSemanas' => true])
        </section>
    </div>
</div>
@endsection
