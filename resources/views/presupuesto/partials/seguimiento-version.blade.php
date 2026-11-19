<section id="seguimiento" class="scroll-mt-20 rounded-2xl border border-slate-200 bg-white p-5" aria-labelledby="titulo-seguimiento">
    <h2 id="titulo-seguimiento" class="text-lg font-semibold text-slate-800">Seguimiento de {{ $version->folio() }}</h2>
    @if(session('estadoSeguimiento'))
        <p role="status" class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ session('estadoSeguimiento') }}</p>
    @endif
    <div class="mt-3 grid gap-5 lg:grid-cols-2">
        <ol class="divide-y divide-slate-100">
            @forelse($version->eventos as $evento)
                <li class="py-2.5">
                    <p class="text-sm font-semibold text-slate-800">{{ $evento->etiqueta() }}</p>
                    <p class="text-xs text-slate-500">
                        <time datetime="{{ $evento->created_at->toIso8601String() }}">{{ $evento->created_at->format('d/m/Y H:i') }}</time>
                        @if($evento->autor) · {{ $evento->autor->name }} @endif
                    </p>
                    @if($evento->nota)
                        <p class="mt-1 whitespace-pre-line break-words text-sm text-slate-700">{{ $evento->nota }}</p>
                    @endif
                </li>
            @empty
                <li class="py-2.5 text-sm text-slate-500">Aún no hay movimientos registrados.</li>
            @endforelse
        </ol>
        <form method="POST" action="{{ route('presupuesto.versiones.seguimiento.store', [$presupuesto, $version]) }}" class="space-y-3 rounded-xl bg-slate-50 p-4">
            @csrf
            @if($errors->seguimientoPresupuesto->any())
                <p role="alert" class="text-sm text-red-700">{{ $errors->seguimientoPresupuesto->first() }}</p>
            @endif
            <label for="seguimiento-tipo" class="block text-sm font-medium text-slate-700">Movimiento</label>
            <select name="tipo" id="seguimiento-tipo" required class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">Selecciona el movimiento</option>
                @foreach(\App\Models\PresupuestoEvento::TIPOS_SEGUIMIENTO as $tipo => $etiqueta)
                    <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ $etiqueta }}</option>
                @endforeach
            </select>
            <label for="seguimiento-nota" class="block text-sm font-medium text-slate-700">Nota (opcional)</label>
            <textarea name="nota" id="seguimiento-nota" rows="3" maxlength="2000" class="w-full rounded-lg border-slate-300 text-sm">{{ old('nota') }}</textarea>
            <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900">
                Registrar movimiento
            </button>
        </form>
    </div>
</section>
