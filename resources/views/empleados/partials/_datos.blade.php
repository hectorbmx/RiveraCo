<h2 class="text-lg font-semibold mb-4">Datos generales del empleado</h2>

<form action="{{ route('empleados.update', $empleado->id_Empleado) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- DATOS PERSONALES --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Datos personales</h3>

        {{-- 3 columnas en md: Foto (1) + Campos (2) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- FOTO --}}
{{-- FOTO --}}
<div class="md:col-span-1">
    <label class="block text-xs font-medium text-slate-600 mb-2">Foto del empleado</label>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4">
        {{-- Foto grande --}}
        <div class="w-full">
            <div class="w-36 h-36 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 flex items-center justify-center">
                @if(!empty($empleado->foto))
                    <img
                        src="{{ asset('storage/' . $empleado->foto) }}"
                        alt="Foto del empleado"
                        class="w-full h-full object-cover"
                    >
                @else
                    <span class="text-xs text-slate-400">Sin foto</span>
                @endif
            </div>

            <p class="text-xs text-slate-600 leading-5 mt-3">
                Sube una foto (JPG/PNG). Se guardará en <span class="font-mono">storage/empleados</span>.
            </p>

            {{-- Botón abajo --}}
            <input
                type="file"
                name="foto"
                accept="image/*"
                class="mt-3 block w-full text-xs
                       file:w-full file:py-2 file:px-3
                       file:rounded-xl file:border-0
                       file:bg-slate-100 file:text-slate-700
                       hover:file:bg-slate-200"
            >

            @error('foto') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

            {{-- CAMPOS --}}
            <div class="md:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Nombre</label>
                        <input type="text" name="Nombre"
                               value="{{ old('Nombre', $empleado->Nombre) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]" required>
                        @error('Nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Apellidos</label>
                        <input type="text" name="Apellidos"
                               value="{{ old('Apellidos', $empleado->Apellidos) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]" required>
                        @error('Apellidos') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Email</label>
                        <input type="email" name="Email"
                               value="{{ old('Email', $empleado->Email) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('Email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Celular</label>
                        <input type="text" name="Celular"
                               value="{{ old('Celular', $empleado->Celular) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('Celular') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Teléfono</label>
                        <input type="text" name="Telefono"
                               value="{{ old('Telefono', $empleado->Telefono) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('Telefono') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Área</label>
                        <select name="Area"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                       focus:border-[#FFC107] focus:ring-[#FFC107]">
                            <option value="">-- Seleccionar área --</option>
                            @foreach($areas as $a)
                                <option value="{{ $a->id }}"
                                    {{ (string)old('Area', $empleado->Area) === (string)$a->id ? 'selected' : '' }}>
                                    {{ $a->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('Area') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Puesto</label>
                        <input type="text" name="Puesto"
                               value="{{ old('Puesto', $empleado->Puesto) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('Puesto') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Fecha de nacimiento</label>
                        <input type="date" name="Fecha_nacimiento"
                               value="{{ old('Fecha_nacimiento', optional($empleado->Fecha_nacimiento)->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('Fecha_nacimiento') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600">Fecha de ingreso</label>
                        <input type="date" name="Fecha_ingreso"
                               value="{{ old('Fecha_ingreso', optional($empleado->Fecha_ingreso)->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                      focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('Fecha_ingreso') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

        </div>
    </div>

    <hr class="my-4">

    {{-- DIRECCIÓN --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Dirección</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-slate-600">Calle y número</label>
                <input type="text" name="Direccion"
                       value="{{ old('Direccion', $empleado->Direccion) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Direccion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Colonia</label>
                <input type="text" name="Colonia"
                       value="{{ old('Colonia', $empleado->Colonia) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Colonia') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Ciudad</label>
                <input type="text" name="Ciudad"
                       value="{{ old('Ciudad', $empleado->Ciudad) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Ciudad') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Código postal</label>
                <input type="text" name="CP"
                       value="{{ old('CP', $empleado->CP) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('CP') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- SEGURIDAD SOCIAL --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Seguridad social / Fiscales</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600">RFC</label>
                <input type="text" name="RFC"
                       value="{{ old('RFC', $empleado->RFC) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('RFC') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">CURP</label>
                <input type="text" name="CURP"
                       value="{{ old('CURP', $empleado->CURP) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('CURP') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">NSS / IMSS</label>
                <input type="text" name="IMSS"
                       value="{{ old('IMSS', $empleado->IMSS) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('IMSS') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Tipo de sangre</label>
                <input type="text" name="Sangre"
                       value="{{ old('Sangre', $empleado->Sangre) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Sangre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Cuenta bancaria</label>
                <input type="text" name="Cuenta_banco"
                       value="{{ old('Cuenta_banco', $empleado->Cuenta_banco) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Cuenta_banco') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Infonavit (descuento)</label>
                <input type="number" step="0.01" name="infonavit"
                       value="{{ old('infonavit', $empleado->infonavit) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('infonavit') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- NÓMINA BASE --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Nómina base</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600">Sueldo base</label>
                <input type="number" step="0.01" name="Sueldo"
                       value="{{ old('Sueldo', $empleado->Sueldo) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Sueldo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Sueldo real</label>
                <input type="number" step="0.01" name="Sueldo_real"
                       value="{{ old('Sueldo_real', $empleado->Sueldo_real) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Sueldo_real') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Complemento</label>
                <input type="number" step="0.01" name="Complemento"
                       value="{{ old('Complemento', $empleado->Complemento) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Complemento') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

          <div>
    <label class="block text-xs font-medium text-slate-600">Tipo de sueldo</label>

    @php
        $tipo = old('Sueldo_tipo', $empleado->Sueldo_tipo ?? null);
    @endphp

    <select name="Sueldo_tipo"
        class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
               focus:border-[#FFC107] focus:ring-[#FFC107]">

        <option value="">-- Seleccionar --</option>

        <option value="1" @selected((int)$tipo === 1)>
            Semanal
        </option>

        <option value="2" @selected((int)$tipo === 2)>
            Quincenal
        </option>

    </select>

    @error('Sueldo_tipo')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Lista raya</label>
                <input type="number" name="listaraya"
                       value="{{ old('listaraya', $empleado->listaraya) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('listaraya') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600">Horas por semana</label>
                <input type="text" name="Horassemana"
                       value="{{ old('Horassemana', $empleado->Horassemana) }}"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]">
                @error('Horassemana') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- NOTAS GENERALES --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Notas internas</h3>

        <textarea name="Notas" rows="3"
                  class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                         focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('Notas', $empleado->Notas) }}</textarea>
        @error('Notas') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- BOTONES --}}
    <div class="mt-6 flex justify-end gap-3">
        <a href="{{ route('empleados.index') }}"
           class="px-4 py-2 rounded-xl border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">
            Cancelar
        </a>

        <button type="submit"
                class="px-4 py-2 rounded-xl bg-[#FFC107] text-[#0B265A] text-xs font-semibold shadow hover:bg-[#e0ac05]">
            Guardar cambios
        </button>
    </div>

</form>
