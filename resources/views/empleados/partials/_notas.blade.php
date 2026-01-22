<h2 class="text-lg font-semibold mb-4">Notas del empleado</h2>

<div class="grid grid-cols-1 lg:grid-cols-[2fr_1.2fr] gap-6">

    {{-- LISTA DE NOTAS --}}
    <div>
        @if($empleado->notas->isEmpty())
            <p class="text-sm text-slate-500">
                Este empleado aún no tiene notas registradas.
            </p>
        @else
            <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
                @foreach($empleado->notas as $nota)
                    <div class="border border-slate-200 rounded-xl p-3 text-sm bg-slate-50">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-800">
                                        {{ $nota->titulo }}
                                    </span>
                                    @if($nota->tipo)
                                        <span class="px-2 py-0.5 rounded-full text-[11px] bg-slate-200 text-slate-700">
                                            {{ $nota->tipo }}
                                        </span>
                                    @endif>
                                </div>

                                <div class="text-[11px] text-slate-500 mt-1">
                                    {{ $nota->fecha_evento?->format('d/m/Y') }}
                                    @if($nota->autor)
                                        · por {{ $nota->autor->name }}
                                    @endif
                                    @if(!is_null($nota->monto))
                                        · monto: ${{ number_format($nota->monto, 2) }}
                                    @endif
                                </div>
                            </div>

                            <form action="{{ route('empleados.notas.destroy', [$empleado->id_Empleado, $nota->id]) }}"
                                  method="POST"
                                  onsubmit="return confirm('¿Eliminar esta nota?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs text-red-600 hover:text-red-800">
                                    Eliminar
                                </button>
                            </form>
                        </div>

                        @if($nota->descripcion)
                            <p class="mt-2 text-[13px] text-slate-700 whitespace-pre-line">
                                {{ $nota->descripcion }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- FORM PARA NUEVA NOTA --}}
    <div>
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Agregar nueva nota</h3>

        <form action="{{ route('empleados.notas.store', $empleado->id_Empleado) }}"
              method="POST"
              class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs font-medium text-slate-600">Tipo de nota</label>
                <select name="tipo"
                        class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                               focus:border-[#FFC107] focus:ring-[#FFC107]">
                    <option value="">(Sin especificar)</option>
                    @php
                        $tipos = ['Aumento', 'Cambio de puesto', 'Advertencia', 'Reconocimiento', 'Otro'];
                        $tipoOld = old('tipo');
                    @endphp
                    @foreach($tipos as $tipo)
                        <option value="{{ $tipo }}" @selected($tipoOld === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Título</label>
                <input type="text" name="titulo"
                       value="{{ old('titulo') }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]" required>
                @error('titulo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Descripción</label>
                <textarea name="descripcion" rows="3"
                          class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                                 focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('descripcion') }}</textarea>
                @error('descripcion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600">Fecha del evento</label>
                    <input type="date" name="fecha_evento"
                           value="{{ old('fecha_evento', now()->format('Y-m-d')) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">
                    @error('fecha_evento') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Monto asociado (opcional)</label>
                    <input type="number" step="0.01" name="monto"
                           value="{{ old('monto') }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">
                    @error('monto') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="pt-2 text-right">
                <button type="submit"
                        class="px-4 py-2 bg-[#FFC107] text-[#0B265A] rounded-xl text-xs font-semibold shadow hover:bg-[#e0ac05]">
                    Guardar nota
                </button>
            </div>
        </form>
    </div>

</div>
