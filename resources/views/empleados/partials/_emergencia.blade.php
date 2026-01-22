<h2 class="text-lg font-semibold mb-4">Contactos de emergencia</h2>

<div class="grid grid-cols-1 lg:grid-cols-[2fr_1.2fr] gap-6">

    {{-- LISTA DE CONTACTOS --}}
    <div>
        @if($empleado->contactosEmergencia->isEmpty())
            <p class="text-sm text-slate-500">
                Este empleado aún no tiene contactos de emergencia registrados.
            </p>
        @else
            <div class="border rounded-2xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-slate-500 border-b">
                            <th class="py-2 px-3">Nombre</th>
                            <th class="py-2 px-3">Contacto</th>
                            <th class="py-2 px-3">Parentesco</th>
                            <th class="py-2 px-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($empleado->contactosEmergencia as $contacto)
                            <tr class="border-b last:border-b-0">
                                <td class="py-2 px-3 align-top">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-slate-800">
                                            {{ $contacto->nombre }}
                                        </span>
                                        @if($contacto->es_principal)
                                            <span class="px-2 py-0.5 rounded-full text-[11px] bg-[#FFC107] text-[#0B265A]">
                                                Principal
                                            </span>
                                        @endif
                                    </div>
                                    @if($contacto->notas)
                                        <div class="mt-1 text-[11px] text-slate-500">
                                            {{ $contacto->notas }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-2 px-3 align-top text-[13px]">
                                    @if($contacto->telefono)
                                        <div>Tel: {{ $contacto->telefono }}</div>
                                    @endif
                                    @if($contacto->celular)
                                        <div>Cel: {{ $contacto->celular }}</div>
                                    @endif
                                </td>
                                <td class="py-2 px-3 align-top text-[13px]">
                                    {{ $contacto->parentesco ?: '-' }}
                                </td>
                                <td class="py-2 px-3 align-top text-right text-xs space-y-1">

                                    {{-- Form editar rápido --}}
                                    <details class="mb-1">
                                        <summary class="cursor-pointer text-blue-600 hover:text-blue-800">
                                            Editar
                                        </summary>
                                        <form action="{{ route('empleados.contactos.update', [$empleado->id_Empleado, $contacto->id]) }}"
                                              method="POST"
                                              class="mt-2 space-y-2 text-left">
                                            @csrf
                                            @method('PUT')

                                            <input type="text" name="nombre"
                                                   value="{{ old('nombre_'.$contacto->id, $contacto->nombre) }}"
                                                   class="w-full rounded-xl border-slate-200 text-xs px-2 py-1"
                                                   placeholder="Nombre">

                                            <input type="text" name="parentesco"
                                                   value="{{ old('parentesco_'.$contacto->id, $contacto->parentesco) }}"
                                                   class="w-full rounded-xl border-slate-200 text-xs px-2 py-1"
                                                   placeholder="Parentesco">

                                            <input type="text" name="telefono"
                                                   value="{{ old('telefono_'.$contacto->id, $contacto->telefono) }}"
                                                   class="w-full rounded-xl border-slate-200 text-xs px-2 py-1"
                                                   placeholder="Teléfono">

                                            <input type="text" name="celular"
                                                   value="{{ old('celular_'.$contacto->id, $contacto->celular) }}"
                                                   class="w-full rounded-xl border-slate-200 text-xs px-2 py-1"
                                                   placeholder="Celular">

                                            <label class="inline-flex items-center gap-1 text-xs mt-1">
                                                <input type="checkbox" name="es_principal" value="1"
                                                       @checked($contacto->es_principal)>
                                                Principal
                                            </label>

                                            <textarea name="notas" rows="2"
                                                      class="w-full rounded-xl border-slate-200 text-xs px-2 py-1"
                                                      placeholder="Notas">{{ old('notas_'.$contacto->id, $contacto->notas) }}</textarea>

                                            <div class="text-right">
                                                <button class="px-3 py-1 rounded-xl bg-slate-800 text-white text-[11px]">
                                                    Guardar
                                                </button>
                                            </div>
                                        </form>
                                    </details>

                                    {{-- Eliminar --}}
                                    <form action="{{ route('empleados.contactos.destroy', [$empleado->id_Empleado, $contacto->id]) }}"
                                          method="POST"
                                          onsubmit="return confirm('¿Eliminar este contacto de emergencia?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-[11px] text-red-600 hover:text-red-800">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- FORM NUEVO CONTACTO --}}
    <div>
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Agregar contacto de emergencia</h3>

        <form action="{{ route('empleados.contactos.store', $empleado->id_Empleado) }}"
              method="POST"
              class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs font-medium text-slate-600">Nombre completo</label>
                <input type="text" name="nombre"
                       value="{{ old('nombre') }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]" required>
                @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Parentesco</label>
                <input type="text" name="parentesco"
                       value="{{ old('parentesco') }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       placeholder="Ej. Esposa, Hijo, Madre...">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600">Teléfono</label>
                    <input type="text" name="telefono"
                           value="{{ old('telefono') }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-600">Celular</label>
                    <input type="text" name="celular"
                           value="{{ old('celular') }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]">
                </div>
            </div>

            <div>
                <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                    <input type="checkbox" name="es_principal" value="1"
                           @checked(old('es_principal'))>
                    Marcar como contacto principal
                </label>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Notas</label>
                <textarea name="notas" rows="2"
                          class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm text-sm
                                 focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('notas') }}</textarea>
            </div>

            <div class="pt-2 text-right">
                <button type="submit"
                        class="px-4 py-2 bg-[#FFC107] text-[#0B265A] rounded-xl text-xs font-semibold shadow hover:bg-[#e0ac05]">
                    Guardar contacto
                </button>
            </div>
        </form>
    </div>

</div>
