<div class="max-w-7xl mx-auto">
    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
        <span>Datos generales del empleado</span>
    </h2>

    <form action="{{ route('empleados.update', $empleado->id_Empleado) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- SECCIÓN 1: DATOS PERSONALES --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 md:p-6 transition-all hover:shadow-md">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                <div class="w-2 h-5 bg-blue-600 rounded-full"></div>
                <h3 class="text-base font-bold text-slate-800">Datos personales</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- FOTO --}}
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-slate-700 mb-2">Foto del empleado</label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 flex flex-col items-center">
                        <div class="w-36 h-36 rounded-2xl overflow-hidden bg-white border-2 border-slate-200 shadow-inner flex items-center justify-center relative group">
                            @if(!empty($empleado->foto))
                                <img src="{{ Storage::disk('public')->url(ltrim($empleado->foto, '/')) }}"
                                     alt="Foto del empleado"
                                     class="w-full h-full object-cover">
                            @else
                                <span class="text-xs font-medium text-slate-400">Sin foto</span>
                            @endif
                        </div>

                        <p class="text-[11px] text-slate-500 text-center leading-4 mt-3">
                            Formatos permitidos (JPG/PNG). Se guardará en <span class="font-mono bg-slate-200/60 px-1 py-0.5 rounded text-slate-700">storage/empleados</span>.
                        </p>

                        <input type="file"
                               name="foto"
                               accept="image/*"
                               class="mt-3 block w-full text-xs text-slate-500
                                      file:mr-2 file:py-2 file:px-3
                                      file:rounded-lg file:border-0
                                      file:text-xs file:font-semibold
                                      file:bg-blue-50 file:text-blue-700
                                      hover:file:bg-blue-100 cursor-pointer">

                        @error('foto') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- CAMPOS PERSONALES --}}
                <div class="md:col-span-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700">Nombre</label>
                            <input type="text" name="Nombre"
                                   value="{{ old('Nombre', $empleado->Nombre) }}"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm" required>
                            @error('Nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700">Apellidos</label>
                            <input type="text" name="Apellidos"
                                   value="{{ old('Apellidos', $empleado->Apellidos) }}"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm" required>
                            @error('Apellidos') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700">Email</label>
                            <input type="email" name="Email"
                                   value="{{ old('Email', $empleado->Email) }}"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                            @error('Email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700">Celular</label>
                            <input type="text" name="Celular"
                                   value="{{ old('Celular', $empleado->Celular) }}"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                            @error('Celular') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700">Teléfono</label>
                            <input type="text" name="Telefono"
                                   value="{{ old('Telefono', $empleado->Telefono) }}"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                            @error('Telefono') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700">Área</label>
                            <select name="Area"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
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
                            <label class="block text-xs font-medium text-slate-700">Puesto</label>
                            @php $puestoSeleccionado = old('Puesto', $empleado->Puesto ?? ''); @endphp
                            <select name="Puesto"
                                    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                                <option value="">Seleccione un puesto</option>
                                @foreach($roles as $rol)
                                    <option value="{{ $rol->nombre }}" @selected($puestoSeleccionado === $rol->nombre)>
                                        {{ $rol->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700">Fecha de nacimiento</label>
                            <input type="date" name="Fecha_nacimiento"
                                   value="{{ old('Fecha_nacimiento', optional($empleado->Fecha_nacimiento)->format('Y-m-d')) }}"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                            @error('Fecha_nacimiento') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700">Fecha de ingreso</label>
                            <input type="date" name="Fecha_ingreso"
                                   value="{{ old('Fecha_ingreso', optional($empleado->Fecha_ingreso)->format('Y-m-d')) }}"
                                   class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                            @error('Fecha_ingreso') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: DIRECCIÓN --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 md:p-6 transition-all hover:shadow-md">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                <div class="w-2 h-5 bg-emerald-500 rounded-full"></div>
                <h3 class="text-base font-bold text-slate-800">Dirección</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-700">Calle y número</label>
                    <input type="text" name="Direccion"
                           value="{{ old('Direccion', $empleado->Direccion) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Direccion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Colonia</label>
                    <input type="text" name="Colonia"
                           value="{{ old('Colonia', $empleado->Colonia) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Colonia') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Ciudad</label>
                    <input type="text" name="Ciudad"
                           value="{{ old('Ciudad', $empleado->Ciudad) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Ciudad') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Código postal</label>
                    <input type="text" name="CP"
                           value="{{ old('CP', $empleado->CP) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('CP') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- SECCIÓN 3: SEGURIDAD SOCIAL / FISCALES --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 md:p-6 transition-all hover:shadow-md">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                <div class="w-2 h-5 bg-purple-600 rounded-full"></div>
                <h3 class="text-base font-bold text-slate-800">Seguridad social / Fiscales</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700">RFC</label>
                    <input type="text" name="RFC"
                           value="{{ old('RFC', $empleado->RFC) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm uppercase">
                    @error('RFC') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">CURP</label>
                    <input type="text" name="CURP"
                           value="{{ old('CURP', $empleado->CURP) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm uppercase">
                    @error('CURP') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">NSS / IMSS</label>
                    <input type="text" name="IMSS"
                           value="{{ old('IMSS', $empleado->IMSS) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('IMSS') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Tipo de sangre</label>
                    <input type="text" name="Sangre"
                           value="{{ old('Sangre', $empleado->Sangre) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Sangre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Cuenta bancaria</label>
                    <input type="text" name="Cuenta_banco"
                           value="{{ old('Cuenta_banco', $empleado->Cuenta_banco) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Cuenta_banco') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Infonavit (descuento)</label>
                    <input type="number" step="0.01" name="infonavit"
                           value="{{ old('infonavit', $empleado->infonavit) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('infonavit') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- SECCIÓN 4: NÓMINA BASE --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 md:p-6 transition-all hover:shadow-md">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                <div class="w-2 h-5 bg-[#FFC107] rounded-full"></div>
                <h3 class="text-base font-bold text-slate-800">Nómina base</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700">Sueldo base</label>
                    <input type="number" step="0.01" name="Sueldo"
                           value="{{ old('Sueldo', $empleado->Sueldo) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Sueldo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Sueldo real</label>
                    <input type="number" step="0.01" name="Sueldo_real"
                           value="{{ old('Sueldo_real', $empleado->Sueldo_real) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Sueldo_real') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Complemento</label>
                    <input type="number" step="0.01" name="Complemento"
                           value="{{ old('Complemento', $empleado->Complemento) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Complemento') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Tipo de sueldo</label>
                    @php $tipo = old('Sueldo_tipo', $empleado->Sueldo_tipo ?? null); @endphp
                    <select name="Sueldo_tipo"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                        <option value="">-- Seleccionar --</option>
                        <option value="1" @selected((int)$tipo === 1)>Semanal</option>
                        <option value="2" @selected((int)$tipo === 2)>Quincenal</option>
                    </select>
                    @error('Sueldo_tipo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Lista de raya principal</label>
                    <select name="lista_raya_principal_id"
                            class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                        <option value="">-- Sin lista principal --</option>
                        @foreach(($listasRaya ?? collect()) as $lista)
                            <option value="{{ $lista->id }}"
                                @selected((string)old('lista_raya_principal_id', $empleado->lista_raya_principal_id ?? '') === (string)$lista->id)>
                                {{ $lista->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @if($empleado->listaraya)
                        <p class="text-[11px] text-slate-400 mt-1">Legacy: {{ $empleado->listaraya }}</p>
                    @endif
                    @error('lista_raya_principal_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700">Horas por semana</label>
                    <input type="text" name="Horassemana"
                           value="{{ old('Horassemana', $empleado->Horassemana) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">
                    @error('Horassemana') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- SECCIÓN 5: NOTAS INTERNAS --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 md:p-6 transition-all hover:shadow-md">
            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100">
                <div class="w-2 h-5 bg-slate-400 rounded-full"></div>
                <h3 class="text-base font-bold text-slate-800">Notas internas</h3>
            </div>

            <textarea name="Notas" rows="3"
                      placeholder="Escribe anotaciones o detalles relevantes del empleado..."
                      class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107] text-sm">{{ old('Notas', $empleado->Notas) }}</textarea>
            @error('Notas') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- BOTONES DE ACCIÓN --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('empleados.index') }}"
               class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                Cancelar
            </a>

            <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-[#FFC107] text-[#0B265A] text-xs font-bold shadow-md hover:bg-[#e0ac05] active:scale-[0.98] transition-all">
                Guardar cambios
            </button>
        </div>
    </form>
</div>