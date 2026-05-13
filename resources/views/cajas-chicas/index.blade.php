@extends('layouts.admin')

@section('content')
<div class="max-w-8xl mx-auto px-6 py-6">

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-200">
            <h1 class="text-xl font-bold text-slate-900">
                Cajas chicas
            </h1>
            <p class="text-sm text-slate-500">
                Reposiciones de gastos registradas en todas las obras.
            </p>
        </div>
        <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">

    <form method="GET" action="{{ route('cajas-chicas.index') }}" class="space-y-4">

        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">

            <div>
                <p class="text-sm text-slate-500">
                    Semana mostrada:
                    <span class="font-bold text-slate-800">
                        {{ $fechaInicio->format('d/m/Y') }}
                    </span>
                    al
                    <span class="font-bold text-slate-800">
                        {{ $fechaFin->format('d/m/Y') }}
                    </span>
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-3">

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">
                        Fecha inicio
                    </label>
                    <input
                        type="date"
                        name="fecha_inicio"
                        value="{{ $fechaInicio->format('Y-m-d') }}"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">
                        Fecha fin
                    </label>
                    <input
                        type="date"
                        name="fecha_fin"
                        value="{{ $fechaFin->format('Y-m-d') }}"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">
                        Obra
                    </label>
                    <select
                        name="obra_id"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Todas las obras</option>
                        @foreach($obras as $obra)
                            <option value="{{ $obra->id }}" @selected(request('obra_id') == $obra->id)>
                                {{ $obra->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">
                        Tipo
                    </label>
                    <select
                        name="tipo_reposicion"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Todos</option>
                        <option value="caja_chica" @selected(request('tipo_reposicion') === 'caja_chica')>
                            Caja chica
                        </option>
                        <option value="viaticos" @selected(request('tipo_reposicion') === 'viaticos')>
                            Viáticos
                        </option>
                        <option value="gastos_varios" @selected(request('tipo_reposicion') === 'gastos_varios')>
                            Gastos varios
                        </option>
                    </select>
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700"
                >
                    Buscar
                </button>

            </div>
        </div>
    </form>

    <div class="mt-4 flex flex-wrap justify-end gap-3">

        <a
            href="{{ route('cajas-chicas.index', [
                'fecha_inicio' => $semanaAnteriorInicio,
                'fecha_fin' => $semanaAnteriorFin,
                'obra_id' => request('obra_id'),
                'tipo_reposicion' => request('tipo_reposicion'),
            ]) }}"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100"
        >
            ← Semana anterior
        </a>

        <a
            href="{{ route('cajas-chicas.index') }}"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100"
        >
            Semana actual
        </a>

        <a
            href="{{ route('cajas-chicas.index', [
                'fecha_inicio' => $semanaSiguienteInicio,
                'fecha_fin' => $semanaSiguienteFin,
                'obra_id' => request('obra_id'),
                'tipo_reposicion' => request('tipo_reposicion'),
            ]) }}"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100"
        >
            Semana siguiente →
        </a>

    </div>
</div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="p-3 border text-left">Folio</th>
                        <th class="p-3 border text-left">Obra</th>
                        <th class="p-3 border text-left">Semana</th>
                        <th class="p-3 border text-left">Tipo</th>
                        <th class="p-3 border text-left">Partida</th>
                        <th class="p-3 border text-right">Monto</th>
                        <th class="p-3 border text-center">Evidencias</th>
                        <th class="p-3 border text-center">Estatus</th>
                        <th class="p-3 border text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($reposiciones as $reposicion)
                        @php
                            $estatusClasses = match($reposicion->estatus) {
                                'borrador' => 'bg-slate-100 text-slate-700',
                                'solicitado' => 'bg-yellow-100 text-yellow-700',
                                'en_revision_area' => 'bg-blue-100 text-blue-700',
                                'programado_area' => 'bg-indigo-100 text-indigo-700',
                                'en_revision_admin' => 'bg-cyan-100 text-cyan-700',
                                'pendiente_autorizacion' => 'bg-amber-100 text-amber-700',
                                'autorizado' => 'bg-green-100 text-green-700',
                                'rechazado' => 'bg-red-100 text-red-700',
                                'pagado' => 'bg-emerald-100 text-emerald-700',
                                'cerrado' => 'bg-slate-800 text-white',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="p-3 border font-semibold">
                                REP-{{ str_pad($reposicion->id, 4, '0', STR_PAD_LEFT) }}
                            </td>

                            <td class="p-3 border">
                                <div class="font-semibold text-slate-800">
                                    {{ $reposicion->obra->nombre ?? 'Sin obra' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $reposicion->obra->clave ?? '' }}
                                </div>
                            </td>

                            <td class="p-3 border">
                                {{ $reposicion->semana }}
                            </td>

                            <td class="p-3 border">
                                {{ ucfirst(str_replace('_', ' ', $reposicion->tipo_reposicion)) }}
                            </td>

                            <td class="p-3 border">
                                <div class="font-semibold text-slate-800">
                                    {{ $reposicion->partida->partida ?? 'Sin partida' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $reposicion->partida->concepto ?? '' }}
                                </div>
                            </td>

                            <td class="p-3 border text-right font-bold">
                                ${{ number_format($reposicion->total, 2) }}
                            </td>

                            <td class="p-3 border text-center">
                                {{ $reposicion->detalles_count ?? 0 }}
                            </td>
          @php
    $estatusClasses = match($reposicion->estatus) {

        'borrador' =>
            'bg-slate-100 text-slate-700 hover:bg-slate-200',

        'solicitado' =>
            'bg-yellow-100 text-yellow-700 hover:bg-yellow-200',

        'en_revision_area' =>
            'bg-blue-100 text-blue-700 hover:bg-blue-200',

        'programado_area' =>
            'bg-indigo-100 text-indigo-700 hover:bg-indigo-200',

        'en_revision_admin' =>
            'bg-cyan-100 text-cyan-700 hover:bg-cyan-200',

        'pendiente_autorizacion' =>
            'bg-amber-100 text-amber-700 hover:bg-amber-200',

        'autorizado' =>
            'bg-green-100 text-green-700 hover:bg-green-200',

        'rechazado' =>
            'bg-red-100 text-red-700 hover:bg-red-200',

        'pagado' =>
            'bg-emerald-100 text-emerald-700 hover:bg-emerald-200',

        'cerrado' =>
            'bg-slate-800 text-white hover:bg-slate-700',

        default =>
            'bg-slate-100 text-slate-700 hover:bg-slate-200',
    };
@endphp
                         <td class="p-3 border text-center">
    <button
        type="button"
        onclick="document.getElementById('modalEstatusReposicion{{ $reposicion->id }}').classList.remove('hidden')"
        class="px-3 py-1 rounded-full text-xs font-bold transition {{ $estatusClasses }}"
    >
        {{ str_replace('_', ' ', $reposicion->estatus) }}
    </button>
</td>


                            <td class="p-3 border text-center">
                                <a
                                    href="{{ route('obras.reposicion-gastos.show', [$reposicion->obra_id, $reposicion]) }}"
                                    class="text-blue-600 font-semibold hover:underline"
                                >
                                    Ver
                                </a>
                            </td>
                        </tr>
                        <tr class="bg-transparent">
    <td colspan="9" class="p-0 border-0">
        <div
            id="modalEstatusReposicion{{ $reposicion->id }}"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4 transition-all duration-300"
        >
            <div class="w-full max-w-3xl rounded-[2rem] bg-white shadow-2xl ring-1 ring-slate-200 overflow-hidden transform transition-all">

                {{-- Header Elegante --}}
                <div class="flex items-center justify-between bg-slate-50/50 border-b border-slate-100 px-8 py-6">
                    <div>
                        <div class="flex items-center gap-3">
                            <h3 class="text-xl font-black text-slate-900 tracking-tight">
                                Seguimiento de reposición
                            </h3>
                            <span class="rounded-full bg-blue-100 px-3 py-0.5 text-xs font-bold text-blue-700 ring-1 ring-blue-200">
                                {{ $reposicion->folio ?? 'REP-' . str_pad($reposicion->id, 4, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1 font-medium">
                            Historial operativo, financiero y administrativo de la solicitud.
                        </p>
                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('modalEstatusReposicion{{ $reposicion->id }}').classList.add('hidden')"
                        class="group rounded-full p-2 text-slate-400 hover:bg-red-50 hover:text-red-500 transition-all"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

               {{-- Contenido con Estilo de Timeline --}}
<div class="px-8 py-8 max-h-[70vh] overflow-y-auto custom-scrollbar">
    <div class="relative space-y-6 before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-slate-200 before:via-slate-200 before:to-transparent">
        
        {{-- 1. SOLICITUD --}}
        <div class="relative flex items-start gap-6 group">
            <div class="absolute left-0 mt-1.5 w-10 h-10 flex items-center justify-center rounded-full bg-white ring-4 ring-white shadow-md transition-all group-hover:scale-110 z-10">
                <div class="w-3 h-3 rounded-full bg-emerald-500 ring-4 ring-emerald-100"></div>
            </div>
            <div class="ml-12 flex-1 rounded-2xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:bg-slate-50">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-3">1. Registro de Solicitud</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Solicitante</p>
                        <p class="text-slate-700 font-bold text-sm">{{ $reposicion->solicitadoPor->name ?? 'Sistema' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Estatus Inicial</p>
                        <p class="text-slate-600 text-xs italic">Solicitud creada en sistema</p>
                    </div>
                    <div class="md:text-right">
                        <span class="inline-block rounded-lg bg-white px-3 py-1 text-[11px] font-bold text-slate-500 shadow-sm ring-1 ring-slate-200">
                            {{ optional($reposicion->solicitado_at)->format('d/m/Y H:i') ?? '---' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. REVISIÓN --}}
        <div class="relative flex items-start gap-6 group">
            <div class="absolute left-0 mt-1.5 w-10 h-10 flex items-center justify-center rounded-full bg-white ring-4 ring-white shadow-md transition-all group-hover:scale-110 z-10">
                <div class="w-3 h-3 rounded-full {{ $reposicion->revisado_at ? 'bg-blue-500 ring-4 ring-blue-100' : 'bg-slate-300 ring-4 ring-slate-100' }}"></div>
            </div>
            <div class="ml-12 flex-1 rounded-2xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:bg-slate-50">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-blue-600 mb-3">2. Primera Revisión Operativa</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Revisó</p>
                        <p class="text-slate-700 font-bold text-sm">{{ $reposicion->revisadoPor->name ?? '---' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Observaciones</p>
                        <p class="text-slate-600 text-xs truncate italic" title="{{ $reposicion->comentarios_revision }}">
                            {{ $reposicion->comentarios_revision ?? 'Sin observaciones' }}
                        </p>
                    </div>
                    <div class="md:text-right">
                        <span class="inline-block rounded-lg {{ $reposicion->revisado_at ? 'bg-blue-50 text-blue-600 ring-blue-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }} px-3 py-1 text-[11px] font-bold shadow-sm ring-1">
                            {{ optional($reposicion->revisado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. APROVISIONAMIENTO --}}
        <div class="relative flex items-start gap-6 group">
            <div class="absolute left-0 mt-1.5 w-10 h-10 flex items-center justify-center rounded-full bg-white ring-4 ring-white shadow-md transition-all group-hover:scale-110 z-10">
                <div class="w-3 h-3 rounded-full {{ $reposicion->aprovisionado_at ? 'bg-amber-500 ring-4 ring-amber-100' : 'bg-slate-300 ring-4 ring-slate-100' }}"></div>
            </div>
            <div class="ml-12 flex-1 rounded-2xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:bg-slate-50">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-amber-600 mb-3">3. Aprovisionamiento y Tesorería</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Asignado por</p>
                        <p class="text-slate-700 font-bold text-sm">{{ $reposicion->aprovisionadoPor->name ?? '---' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Programación</p>
                        <p class="text-amber-700 text-xs font-bold font-mono uppercase">
                            Pago: {{ optional($reposicion->fecha_programada_pago)->format('d/M/Y') ?? 'Sin fecha' }}
                        </p>
                    </div>
                    <div class="md:text-right">
                        <span class="inline-block rounded-lg {{ $reposicion->aprovisionado_at ? 'bg-amber-50 text-amber-600 ring-amber-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }} px-3 py-1 text-[11px] font-bold shadow-sm ring-1">
                            {{ optional($reposicion->aprovisionado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. AUTORIZACIÓN --}}
        <div class="relative flex items-start gap-6 group">
            <div class="absolute left-0 mt-1.5 w-10 h-10 flex items-center justify-center rounded-full bg-white ring-4 ring-white shadow-md transition-all group-hover:scale-110 z-10">
                <div class="w-3 h-3 rounded-full {{ $reposicion->aprobado_at ? 'bg-purple-500 ring-4 ring-purple-100' : 'bg-slate-300 ring-4 ring-slate-100' }}"></div>
            </div>
            <div class="ml-12 flex-1 rounded-2xl border border-slate-100 bg-slate-50/30 p-5 transition-all hover:bg-slate-50">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-purple-600 mb-3">4. Autorización Final (Dirección)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Autorizó</p>
                        <p class="text-slate-700 font-bold text-sm">{{ $reposicion->aprobadoPor->name ?? '---' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[9px] font-bold uppercase tracking-tight">Dictamen</p>
                        <p class="text-slate-600 text-xs italic truncate" title="{{ $reposicion->comentarios_autorizacion }}">
                            {{ $reposicion->comentarios_autorizacion ?? 'Esperando firma electrónica' }}
                        </p>
                    </div>
                    <div class="md:text-right">
                        <span class="inline-block rounded-lg {{ $reposicion->aprobado_at ? 'bg-purple-50 text-purple-600 ring-purple-100' : 'bg-slate-50 text-slate-400 ring-slate-100' }} px-3 py-1 text-[11px] font-bold shadow-sm ring-1">
                            {{ optional($reposicion->aprobado_at)->format('d/m/Y H:i') ?? 'Pendiente' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

                {{-- Footer --}}
                <div class="flex justify-end border-t border-slate-100 px-8 py-5 bg-slate-50/30">
                    <button
                        type="button"
                        onclick="document.getElementById('modalEstatusReposicion{{ $reposicion->id }}').classList.add('hidden')"
                        class="rounded-xl bg-slate-900 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-slate-900/20 hover:bg-slate-800 hover:scale-[1.02] transition-all active:scale-95"
                    >
                        Entendido
                    </button>
                </div>

            </div>
        </div>
    </td>
</tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-6 text-center text-slate-500">
                                No hay reposiciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
            </table>
        </div>

        <div class="px-6 py-4">
            {{ $reposiciones->links() }}
        </div>

    </div>
</div>
@endsection