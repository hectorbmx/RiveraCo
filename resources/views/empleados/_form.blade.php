@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Nombre y apellidos --}}
    <div>
        <label class="block text-xs font-medium text-slate-700">Nombre</label>
        <input type="text" name="Nombre"
               value="{{ old('Nombre', $empleado->Nombre ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]" required>
        @error('Nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-700">Apellidos</label>
        <input type="text" name="Apellidos"
               value="{{ old('Apellidos', $empleado->Apellidos ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]" required>
        @error('Apellidos') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Área y Puesto --}}
   {{-- Área --}}
<div>
  <label class="block text-xs font-medium text-slate-700">Área</label>

  <select name="Area"
    class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
           focus:border-[#FFC107] focus:ring-[#FFC107]">
    <option value="">-- Seleccionar área --</option>

    @foreach($areas as $a)
      <option value="{{ $a->id }}"
        {{ (string)old('Area', $empleado->Area ?? '') === (string)$a->id ? 'selected' : '' }}>
        {{ $a->nombre }}
      </option>
    @endforeach
  </select>

  @error('Area') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
</div>

    <div>
        <label class="block text-xs font-medium text-slate-700">Puesto</label>
        <input type="text" name="Puesto"
               value="{{ old('Puesto', $empleado->Puesto ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    {{-- Contacto --}}
    <div>
        <label class="block text-xs font-medium text-slate-700">Email</label>
        <input type="email" name="Email"
               value="{{ old('Email', $empleado->Email ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-700">Celular</label>
        <input type="text" name="Celular"
               value="{{ old('Celular', $empleado->Celular ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    {{-- Fechas --}}
    <div>
        <label class="block text-xs font-medium text-slate-700">Fecha de ingreso</label>
        <input type="date" name="Fecha_ingreso"
               value="{{ old('Fecha_ingreso', optional($empleado->Fecha_ingreso ?? null)->format('Y-m-d')) }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-700">Fecha de nacimiento</label>
        <input type="date" name="Fecha_nacimiento"
               value="{{ old('Fecha_nacimiento', optional($empleado->Fecha_nacimiento ?? null)->format('Y-m-d')) }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    {{-- Sueldos --}}
    <div>
        <label class="block text-xs font-medium text-slate-700">Sueldo base</label>
        <input type="number" step="0.01" name="Sueldo"
               value="{{ old('Sueldo', $empleado->Sueldo ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-700">Sueldo real</label>
        <input type="number" step="0.01" name="Sueldo_real"
               value="{{ old('Sueldo_real', $empleado->Sueldo_real ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    {{-- RFC / CURP --}}
    <div>
        <label class="block text-xs font-medium text-slate-700">RFC</label>
        <input type="text" name="RFC"
               value="{{ old('RFC', $empleado->RFC ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-700">CURP</label>
        <input type="text" name="CURP"
               value="{{ old('CURP', $empleado->CURP ?? '') }}"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]">
    </div>

    {{-- Estatus --}}
    <div>
        <label class="block text-xs font-medium text-slate-700">Estatus</label>
        <select name="Estatus"
                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                       focus:border-[#FFC107] focus:ring-[#FFC107]">
            @php
                $estatus = old('Estatus', $empleado->Estatus ?? 'ACTIVO');
            @endphp
            <option value="ACTIVO" @selected($estatus === 'ACTIVO')>ACTIVO</option>
            <option value="BAJA" @selected($estatus === 'BAJA')>BAJA</option>
        </select>
    </div>

    {{-- Notas --}}
    <div class="md:col-span-2">
        <label class="block text-xs font-medium text-slate-700">Notas</label>
        <textarea name="Notas" rows="2"
                  class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                         focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('Notas', $empleado->Notas ?? '') }}</textarea>
    </div>

</div>

<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('empleados.index') }}"
       class="px-4 py-2 rounded-xl border border-slate-200 text-xs text-slate-600 hover:bg-slate-50">
        Cancelar
    </a>

    <button type="submit"
            class="px-4 py-2 rounded-xl bg-[#FFC107] text-[#0B265A] text-xs font-semibold shadow hover:bg-[#e0ac05]">
        Guardar
    </button>
</div>
