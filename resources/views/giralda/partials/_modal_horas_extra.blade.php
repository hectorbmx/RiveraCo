<div x-data="{ open: false }" class="inline-block text-left">
    <button type="button" @click="open = true" class="px-3 py-1.5 rounded bg-[#0B265A] text-white hover:bg-blue-900">
        Dar horas
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div @click.away="open = false" class="w-full max-w-xl bg-white rounded-lg shadow-xl">
            <div class="p-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-[#0B265A]">Horas extra</h3>
                    <p class="text-xs text-slate-500">{{ $empleado->nombre_completo }}</p>
                </div>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700">X</button>
            </div>

            <form method="POST" action="{{ route('giralda.horas-extras.store', ['tab' => 'horas_extras']) }}" class="p-4 space-y-3">
                @csrf
                <input type="hidden" name="empleado_id" value="{{ $empleado->id_Empleado }}">

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Fecha</label>
                        <input type="date" name="fecha" value="{{ now()->toDateString() }}" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Inicio</label>
                        <input type="time" name="hora_inicio" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Fin</label>
                        <input type="time" name="hora_fin" class="w-full border rounded p-2" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Motivo</label>
                    <input name="motivo" class="w-full border rounded p-2" required>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Solicita</label>
                        <input name="responsable_solicita" value="{{ auth()->user()->name ?? '' }}" class="w-full border rounded p-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Autoriza</label>
                        <input name="responsable_autoriza" class="w-full border rounded p-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="3" class="w-full border rounded p-2"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded bg-slate-200">Cancelar</button>
                    <button class="px-4 py-2 rounded bg-[#0B265A] text-white">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
