@extends('layouts.admin')
@section('title', 'Notas base de presupuestos')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <a href="{{ route('presupuesto.index') }}" class="text-sm font-semibold text-[#0B265A] hover:underline">← Volver a presupuestos</a>
    <h1 class="text-2xl font-bold text-[#0B265A]">Notas base de presupuestos</h1>
    <nav aria-label="Tipos de presupuesto" class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        @foreach($grupos as $clave => $nombre)
            <a href="{{ route('presupuesto.notas.edit', $clave) }}" @if($clave === $grupo) aria-current="page" @endif
               @class(['rounded-lg px-4 py-2 text-sm font-semibold', 'bg-[#0B265A] text-white' => $clave === $grupo, 'text-[#0B265A] hover:bg-slate-100' => $clave !== $grupo])>{{ $nombre }}</a>
        @endforeach
    </nav>

    @if(session('estadoPlantillaNotas'))
        <p role="status" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('estadoPlantillaNotas') }}</p>
    @endif
    @if($errors->any())
        <p role="alert" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('presupuesto.notas.update', $grupo) }}"
          class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 sm:p-6"
          x-data="{ filas: @js($filas).map((fila, id) => ({ ...fila, id })), siguiente: {{ count($filas) }}, regla: '' }">
        @csrf
        @method('PUT')
        <input type="hidden" name="notas" :value="JSON.stringify(filas.map(({ id, ...fila }) => fila))">
        <p class="text-sm text-slate-600">Los cambios se aplican a los presupuestos nuevos y a los borradores que vuelvan a cargar la plantilla.</p>
        @if($patente)
            <p class="rounded-lg bg-slate-50 p-3 text-sm">
                <strong>Nota 1 · Patente</strong>
                <span class="mt-1 block">{{ $patente }}</span>
            </p>
        @endif
        <ol start="{{ $patente ? 2 : 1 }}" class="list-decimal space-y-3 pl-6">
            <template x-for="(fila, indice) in filas" :key="fila.id">
                <li class="pl-1">
                    <div class="flex flex-wrap items-start gap-2 sm:flex-nowrap">
                        <template x-if="fila.regla">
                            <p class="min-w-0 flex-1 rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-800">
                                <strong>Automática</strong>
                                <span class="block" x-text="@js($reglas)[fila.regla]"></span>
                            </p>
                        </template>
                        <template x-if="!fila.regla">
                            <textarea x-model="fila.texto" :aria-label="'Nota ' + (indice + {{ $patente ? 2 : 1 }})"
                                      required maxlength="10000" rows="2" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm"></textarea>
                        </template>
                        <div class="flex shrink-0 gap-1">
                            <button type="button" @click="[filas[indice - 1], filas[indice]] = [filas[indice], filas[indice - 1]]"
                                    :disabled="indice === 0" aria-label="Subir nota" class="rounded-md px-2 py-1 disabled:opacity-30">↑</button>
                            <button type="button" @click="[filas[indice], filas[indice + 1]] = [filas[indice + 1], filas[indice]]"
                                    :disabled="indice === filas.length - 1" aria-label="Bajar nota" class="rounded-md px-2 py-1 disabled:opacity-30">↓</button>
                            <button type="button" @click="filas.splice(indice, 1)" aria-label="Quitar nota"
                                    class="rounded-md px-2 py-1 text-red-700 hover:bg-red-50">×</button>
                        </div>
                    </div>
                </li>
            </template>
        </ol>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" @click="filas.push({ id: siguiente++, texto: '' })" :disabled="filas.length >= 100"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-[#0B265A] disabled:opacity-60">+ Agregar nota</button>
            @if($reglas)
                <label class="text-sm">
                    <span class="sr-only">Nota automática</span>
                    <select x-model="regla" class="rounded-md border-slate-300 text-sm">
                        <option value="">Selecciona una nota automática</option>
                        @foreach($reglas as $clave => $etiqueta)
                            <option value="{{ $clave }}" :disabled="filas.some(fila => fila.regla === @js($clave))">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="button" @click="filas.push({ id: siguiente++, regla }); regla = ''" :disabled="!regla || filas.length >= 100"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-[#0B265A] disabled:opacity-60">+ Agregar automática</button>
            @endif
        </div>
        <div class="border-t border-slate-200 pt-4">
            <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2.5 text-sm font-semibold text-white">Guardar plantilla</button>
        </div>
    </form>
</div>
@endsection
