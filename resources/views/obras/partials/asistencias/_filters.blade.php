<form method="GET" action="{{ route('obras.edit', $obra) }}" class="mb-4">
    <input type="hidden" name="tab" value="asistencias">

    <div class="flex flex-wrap items-end gap-4">
        <div class="flex flex-col">
            <label class="text-xs text-gray-500 mb-1">Desde</label>
            <input type="date" name="asist_desde" value="{{ old('asist_desde', $asist_desde ?? '') }}" class="w-44 rounded-md border-gray-300 text-sm focus:border-yellow-400 focus:ring-yellow-400">
        </div>

        <div class="flex flex-col">
            <label class="text-xs text-gray-500 mb-1">Hasta</label>
            <input type="date" name="asist_hasta" value="{{ old('asist_hasta', $asist_hasta ?? '') }}" class="w-44 rounded-md border-gray-300 text-sm focus:border-yellow-400 focus:ring-yellow-400">
        </div>

        <div class="flex items-center gap-2 mt-1">
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-yellow-400 text-sm font-semibold text-gray-900 hover:bg-yellow-500 transition">
                Filtrar
            </button>

            <a href="{{ route('obras.edit', [$obra, 'tab' => 'asistencias']) }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                Limpiar
            </a>
        </div>
    </div>

    @if(request('asist_desde') || request('asist_hasta'))
        <div class="mt-2 text-xs text-gray-500">
            Mostrando asistencias del
            <strong>{{ request('asist_desde') ?? request('asist_hasta') }}</strong>
            al
            <strong>{{ request('asist_hasta') ?? request('asist_desde') }}</strong>
        </div>
    @endif
</form>
