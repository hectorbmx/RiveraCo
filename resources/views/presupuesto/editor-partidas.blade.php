@extends('layouts.admin')
@section('title', 'Presupuesto ' . $presupuesto->codigo_proyecto)

@push('scripts')
    <script src="{{ asset('js/presupuesto-partidas.js') }}"></script>
@endpush

@section('content')
<div class="space-y-5 pb-16"
     x-data="editorPresupuestoPartidas(@js($estadoEditor), @js(route('presupuesto.partidas.update', $presupuesto)),
         @js(csrf_token()), @js(array_merge(array_keys($unidades), $unidadesGuardadas->all())))">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('presupuesto.index') }}" class="text-sm font-medium text-[#0B265A] hover:underline">← Volver a presupuestos</a>
        <div class="flex flex-wrap items-center gap-3">
            @if($ultimaVersion)
                <a class="text-sm font-semibold text-[#0B265A] hover:underline" href="{{ route('presupuesto.versiones.index', $presupuesto) }}">Historial de versiones</a>
            @endif
            <form method="POST" action="{{ route('presupuesto.versiones.store', $presupuesto) }}" @submit.prevent="guardarVersion($el)">
                @csrf
                <input type="hidden" name="revision" value="{{ $ultimaVersion ? $ultimaVersion->revision + 1 : 0 }}">
                <input type="hidden" name="revision_partidas" value="{{ $estadoEditor['revision'] }}">
                <button type="submit" :disabled="guardando" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-[#0B265A] disabled:opacity-60">Guardar versión</button>
            </form>
            <a x-cloak x-show="!hayCambios && partidas.some(partida => partida.subpartidas.length)"
               href="{{ route('presupuesto.pdf', [$presupuesto, 'borrador' => 1]) }}" target="_blank" rel="noopener"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-[#0B265A]">PDF del borrador</a>
        </div>
    </div>
    <header class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <div class="flex items-center gap-2 text-sm font-medium text-slate-600">
            @include('presupuesto.partials.imagen-tipo', ['tipoImagen' => $presupuesto->tipo, 'clasesImagen' => 'h-7 w-7 object-contain'])
            <span>{{ $presupuesto->tipo }} · Borrador</span>
        </div>
        <h1 class="mt-1 text-2xl font-bold text-[#0B265A]">Presupuesto {{ $presupuesto->codigo_proyecto }}</h1>
        <p class="mt-2 text-slate-800">{{ $presupuesto->nombre_obra }}</p>
        <p class="text-sm text-slate-600">{{ $presupuesto->nombreClienteVisible() }}</p>
        <details class="mt-4" @if($errors->datosPresupuesto->any()) open @endif x-data="{ tab: 'presupuesto' }">
            <summary class="cursor-pointer text-sm font-semibold text-[#0B265A]">Editar datos generales</summary>
            <div class="mt-4">
                @include('presupuesto.partials.datos', ['tabInicial' => 'presupuesto'])
            </div>
        </details>
    </header>

    @if(session('estadoPresupuesto'))
        <p role="status" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('estadoPresupuesto') }}</p>
    @endif
    @if(session('errorPdfPresupuesto') || $errors->versionPresupuesto->any())
        <p role="alert" class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('errorPdfPresupuesto') ?? $errors->versionPresupuesto->first() }}</p>
    @endif

    <form x-ref="tabla" @submit.prevent="guardar()" class="rounded-2xl border border-slate-200 bg-white" x-cloak>
        <fieldset :disabled="guardando">
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-800" id="titulo-partidas">Partidas y subpartidas</h2>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="abrirImportacion()" aria-haspopup="dialog"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-[#0B265A]">Importar Excel</button>
                    <button type="button" @click="agregarPartida()"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-[#0B265A]">+ Agregar partida</button>
                </div>
            </div>

            <div class="overflow-x-auto border-y border-slate-200">
                <table class="w-full table-fixed text-left text-sm" style="min-width: 850px" aria-labelledby="titulo-partidas">
                    <thead class="bg-[#0B265A] text-white">
                        <tr>
                            <th scope="col" class="w-16 px-3 py-3">Clave</th>
                            <th scope="col" class="px-3 py-3">Partida o concepto</th>
                            <th scope="col" class="w-32 px-3 py-3">Unidad</th>
                            <th scope="col" class="w-28 px-3 py-3 text-right">Cantidad</th>
                            <th scope="col" class="w-28 px-3 py-3 text-right">P. U.</th>
                            <th scope="col" class="w-32 px-3 py-3 text-right">Importe</th>
                            <th scope="col" class="w-28 px-3 py-3 text-right">Orden / Eliminar</th>
                        </tr>
                    </thead>
                    <tbody x-show="!partidas.length">
                        <tr><td colspan="7" class="px-5 py-8 text-center text-slate-600">Agrega una partida para empezar.</td></tr>
                    </tbody>
                    <template x-for="(partida, i) in partidas" :key="partida.clave">
                        <tbody class="border-b border-slate-200">
                            <tr class="bg-[#0B265A] text-white">
                                <th scope="rowgroup" class="px-3 py-2" x-text="letra(i)"></th>
                                <td colspan="4" class="px-2 py-2">
                                    <label class="sr-only" :for="'nombre-' + partida.clave" x-text="'Nombre de partida ' + letra(i)"></label>
                                    <input :id="'nombre-' + partida.clave" x-model="partida.nombre" required maxlength="255"
                                           placeholder="Nombre de la partida"
                                           class="w-full rounded-md border-slate-300 bg-white px-2 py-1.5 text-sm font-semibold text-[#0B265A]">
                                </td>
                                <td colspan="2" class="px-3 py-2">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <button type="button" @click="agregarSubpartida(partida)" class="font-semibold hover:underline">+ Subpartida</button>
                                        <button type="button" @click="mover(partidas, i, -1)" :disabled="i === 0" :aria-label="'Subir partida ' + letra(i)" class="px-1 disabled:opacity-30">↑</button>
                                        <button type="button" @click="mover(partidas, i, 1)" :disabled="i === partidas.length - 1" :aria-label="'Bajar partida ' + letra(i)" class="px-1 disabled:opacity-30">↓</button>
                                        <button type="button" @click="quitarPartida(i)" :aria-label="'Eliminar partida ' + letra(i)" class="px-1 text-red-200 hover:text-white">×</button>
                                    </div>
                                </td>
                            </tr>
                            <template x-for="(fila, j) in partida.subpartidas" :key="fila.clave">
                                <tr class="border-t border-slate-200">
                                    <th scope="row" class="px-3 py-2 align-top text-slate-500" x-text="j + 1"></th>
                                    <td class="px-2 py-2 align-top">
                                        <textarea :id="'concepto-' + fila.clave" :aria-label="'Concepto ' + letra(i) + (j + 1)"
                                                  x-model="fila.concepto"
                                                  required maxlength="16000" rows="2"
                                                  class="w-full rounded-md border-slate-300 px-2 py-1.5 text-sm"></textarea>
                                    </td>
                                    <td class="px-2 py-2 align-top">
                                        <select :aria-label="'Unidad ' + letra(i) + (j + 1)"
                                                x-model="fila.unidad" required
                                                class="w-full rounded-md border-slate-300 py-1.5 pl-2 pr-7 text-sm">
                                            <option value="" disabled>Selecciona</option>
                                            @foreach($unidades as $codigo => $descripcion)
                                                <option value="{{ $codigo }}">{{ $codigo }} · {{ $descripcion }}</option>
                                            @endforeach
                                            @if($unidadesGuardadas->isNotEmpty())
                                                <optgroup label="Unidades de este presupuesto">
                                                    @foreach($unidadesGuardadas as $unidad)
                                                        <option value="{{ $unidad }}">{{ $unidad }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        </select>
                                    </td>
                                    <td class="px-2 py-2 align-top">
                                        <input :aria-label="'Cantidad ' + letra(i) + (j + 1)" type="number" min="0" max="9999999999999.99" step="0.01"
                                               x-model="fila.cantidad" required class="w-full rounded-md border-slate-300 px-2 py-1.5 text-right text-sm">
                                    </td>
                                    <td class="px-2 py-2 align-top">
                                        <input :aria-label="'Precio unitario ' + letra(i) + (j + 1)" type="number" min="0" max="9999999999999.99" step="0.01"
                                               x-model="fila.precio_unitario" required class="w-full rounded-md border-slate-300 px-2 py-1.5 text-right text-sm">
                                    </td>
                                    <td class="px-3 py-3 text-right align-top font-semibold tabular-nums" x-text="moneda(importe(fila))"></td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right align-top">
                                        <button type="button" @click="mover(partida.subpartidas, j, -1)" :disabled="j === 0" :aria-label="'Subir subpartida ' + letra(i) + (j + 1)" class="px-1 disabled:opacity-30">↑</button>
                                        <button type="button" @click="mover(partida.subpartidas, j, 1)" :disabled="j === partida.subpartidas.length - 1" :aria-label="'Bajar subpartida ' + letra(i) + (j + 1)" class="px-1 disabled:opacity-30">↓</button>
                                        <button type="button" @click="partida.subpartidas.splice(j, 1)" :aria-label="'Eliminar subpartida ' + letra(i) + (j + 1)" class="px-1 text-red-700">×</button>
                                    </td>
                                </tr>
                            </template>
                            <tr class="bg-slate-50 font-semibold text-slate-700">
                                <th colspan="5" scope="row" class="px-3 py-2 text-right" x-text="'Subtotal ' + (partida.nombre || letra(i))"></th>
                                <td class="px-3 py-2 text-right tabular-nums" x-text="moneda(subtotal(partida))"></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </template>
                </table>
            </div>

            <section class="space-y-2 border-b border-slate-200 p-5" aria-labelledby="titulo-notas-adicionales">
                <h3 id="titulo-notas-adicionales" class="font-semibold text-slate-800">Notas adicionales</h3>
                <div class="bg-white [&_.ql-editor]:min-h-24" :inert="guardando">
                    <div x-init="$iniciarNotaEjecucion($el, notaAdicional, operaciones => notaAdicional = operaciones, {
                        etiqueta: 'titulo-notas-adicionales',
                        placeholder: 'Escribe las condiciones particulares de este presupuesto…'
                    }).catch(() => errorEditorNota = true)"></div>
                </div>
                <p x-show="errorEditorNota" role="alert" class="text-sm text-red-700">No se pudo cargar el editor de notas adicionales.</p>
            </section>

            <div class="flex flex-wrap justify-between gap-4 p-5">
                <div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                                x-text="guardando ? 'Guardando…' : 'Guardar partidas'">Guardar partidas</button>
                        <button type="button" @click="abrirNotas()" aria-haspopup="dialog"
                                class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-[#0B265A] hover:bg-slate-50">Notas importantes</button>
                    </div>
                    <p x-show="mensaje" x-text="mensaje" role="status" class="mt-2 text-sm text-emerald-700"></p>
                    <p x-show="error" x-text="error" role="alert" class="mt-2 text-sm text-red-700"></p>
                </div>
                <dl class="grid grid-cols-2 items-center gap-x-6 gap-y-2 text-sm text-slate-800">
                    <dt>Subtotal general</dt><dd class="text-right tabular-nums" x-text="moneda(totales.subtotal)"></dd>
                    <dt>
                        <label for="tasa-iva">IVA (%)</label>
                        <input id="tasa-iva" type="number" min="0" max="100" step="0.01" x-model="tasaIva" required
                               class="ml-1 w-20 rounded-md border-slate-300 px-2 py-1 text-sm">
                    </dt>
                    <dd class="text-right tabular-nums" x-text="moneda(totales.iva, 100000000n)"></dd>
                    <dt class="font-bold">Total</dt><dd class="text-right font-bold tabular-nums" x-text="moneda(totales.total, 100000000n)"></dd>
                </dl>
            </div>

            <dialog x-ref="notasImportantes" aria-labelledby="titulo-notas-importantes"
                    class="m-auto max-h-[85vh] w-11/12 max-w-3xl rounded-xl p-0 text-slate-800 shadow-xl backdrop:bg-slate-900/55">
                <div class="flex max-h-[85vh] flex-col">
                    <header class="border-b border-slate-200 px-5 py-4">
                        <h3 id="titulo-notas-importantes" class="text-lg font-semibold text-[#0B265A]">Notas importantes</h3>
                        <p class="mt-1 text-sm text-slate-600">Revisa las condiciones para este presupuesto. Los cambios se incluyen al guardar las partidas.</p>
                    </header>
                    <div class="overflow-y-auto overscroll-contain px-5 py-4">
                        @if($estadoEditor['nota_fija'])
                            <p class="mb-3 rounded-lg bg-slate-50 p-3 text-sm">
                                <strong>Nota 1 · Patente</strong>
                                <span class="mt-1 block">{{ $estadoEditor['nota_fija'] }}</span>
                            </p>
                        @endif
                        <ol start="{{ $estadoEditor['nota_fija'] ? 2 : 1 }}" class="list-decimal space-y-3 pl-6">
                            <template x-for="(nota, indice) in notasEnEdicion" :key="indice">
                                <li class="pl-1">
                                    <div class="flex items-start gap-2">
                                        <textarea x-model="notasEnEdicion[indice]" :aria-label="'Nota importante ' + (indice + {{ $estadoEditor['nota_fija'] ? 2 : 1 }})"
                                                  maxlength="10000" rows="2" class="w-full rounded-md border-slate-300 text-sm"></textarea>
                                        <button type="button" @click="notasEnEdicion.splice(indice, 1)" :aria-label="'Eliminar nota ' + (indice + {{ $estadoEditor['nota_fija'] ? 2 : 1 }})"
                                                class="rounded px-2 py-1 text-red-700 hover:bg-red-50">×</button>
                                    </div>
                                </li>
                            </template>
                        </ol>
                        <div class="mt-3 flex flex-wrap gap-4 text-sm font-semibold text-[#0B265A]">
                            <button type="button" @click="notasEnEdicion.push('')" :disabled="notasEnEdicion.length >= 100"
                                    class="hover:underline disabled:opacity-60">+ Agregar nota</button>
                            @if($estadoEditor['nota_fija'])
                                <button type="button" class="hover:underline"
                                        @click="if (window.confirm('¿Reemplazar las notas editables por las notas base de Pozos?')) notasEnEdicion = @js($estadoEditor['notas_base'])">
                                    Cargar notas base de Pozos
                                </button>
                            @endif
                        </div>
                    </div>
                    <footer class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-5 py-4">
                        <button type="button" @click="$refs.notasImportantes.close()"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Cancelar</button>
                        <button type="button" @click="aplicarNotas()"
                                class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Aplicar cambios</button>
                    </footer>
                </div>
            </dialog>
            <button type="button" @click="agregarPartida()"
                    class="fixed bottom-5 right-5 z-30 rounded-full bg-[#0B265A] px-4 py-3 text-sm font-semibold text-white shadow-lg ring-2 ring-white hover:bg-blue-900 sm:bottom-6 sm:right-6">+ Agregar partida</button>
        </fieldset>
    </form>
    @include('presupuesto.partials.importar-partidas')
</div>
@endsection
