@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm text-slate-600 mb-1">Nombre comercial *</label>
    <input name="nombre_comercial" value="{{ old('nombre_comercial', $cliente->nombre_comercial ?? '') }}"
           class="w-full border rounded px-3 py-2" required>
    @error('nombre_comercial') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Razón social</label>
    <input name="razon_social" value="{{ old('razon_social', $cliente->razon_social ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('razon_social') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">RFC</label>
    <input name="rfc" value="{{ old('rfc', $cliente->rfc ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('rfc') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Teléfono</label>
    <input name="telefono" value="{{ old('telefono', $cliente->telefono ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('telefono') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Email</label>
    <input type="email" name="email" value="{{ old('email', $cliente->email ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">
            Código postal
        </label>
        <input type="text"
               name="codigo_postal"
               value="{{ old('codigo_postal', $cliente->codigo_postal ?? '') }}"
               class="w-full rounded-lg border-slate-300"
               maxlength="10">
        @error('codigo_postal')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

   <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">
        Régimen fiscal
    </label>

    <select name="regimen_fiscal"
            class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
        <option value="">Selecciona régimen</option>

        @foreach([
            '601' => '601 - General de Ley Personas Morales',
            '603' => '603 - Personas Morales con Fines no Lucrativos',
            '605' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
            '606' => '606 - Arrendamiento',
            '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
            '616' => '616 - Sin obligaciones fiscales',
            '621' => '621 - Incorporación Fiscal',
            '626' => '626 - Régimen Simplificado de Confianza',
        ] as $clave => $nombre)
            <option value="{{ $clave }}" @selected(old('regimen_fiscal', $cliente->regimen_fiscal) == $clave)>
                {{ $nombre }}
            </option>
        @endforeach
    </select>
</div>

   <div>
    <label class="block text-sm font-medium text-slate-700 mb-1">
        Uso CFDI default
    </label>

    <select name="uso_cfdi_default"
            class="w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">
        <option value="">Selecciona uso CFDI</option>

        @foreach([
            'G01' => 'G01 - Adquisición de mercancías',
            'G02' => 'G02 - Devoluciones, descuentos o bonificaciones',
            'G03' => 'G03 - Gastos en general',
            'I01' => 'I01 - Construcciones',
            'I02' => 'I02 - Mobiliario y equipo de oficina por inversiones',
            'I03' => 'I03 - Equipo de transporte',
            'D01' => 'D01 - Honorarios médicos, dentales y gastos hospitalarios',
            'D02' => 'D02 - Gastos médicos por incapacidad o discapacidad',
            'D03' => 'D03 - Gastos funerales',
            'D04' => 'D04 - Donativos',
            'D10' => 'D10 - Pagos por servicios educativos',
            'S01' => 'S01 - Sin efectos fiscales',
            'CP01' => 'CP01 - Pagos',
            'CN01' => 'CN01 - Nómina',
        ] as $clave => $nombre)
            <option value="{{ $clave }}" @selected(old('uso_cfdi_default', $cliente->uso_cfdi_default) == $clave)>
                {{ $nombre }}
            </option>
        @endforeach
    </select>
</div>
</div>
  <div class="flex items-center gap-2 mt-6 md:mt-0">
    <input type="checkbox" id="activo" name="activo" value="1"
           @checked(old('activo', ($cliente->activo ?? 1)) == 1)>
    <label for="activo" class="text-sm text-slate-700">Activo</label>
    @error('activo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm text-slate-600 mb-1">Dirección (texto libre)</label>
    <input name="direccion" value="{{ old('direccion', $cliente->direccion ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('direccion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Calle</label>
    <input name="calle" value="{{ old('calle', $cliente->calle ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('calle') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Colonia</label>
    <input name="colonia" value="{{ old('colonia', $cliente->colonia ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('colonia') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Ciudad</label>
    <input name="ciudad" value="{{ old('ciudad', $cliente->ciudad ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('ciudad') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">Estado</label>
    <input name="estado" value="{{ old('estado', $cliente->estado ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('estado') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="block text-sm text-slate-600 mb-1">País</label>
    <input name="pais" value="{{ old('pais', $cliente->pais ?? '') }}"
           class="w-full border rounded px-3 py-2">
    @error('pais') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
  </div>
</div>

<div class="mt-6 flex items-center gap-2">
  <button class="px-4 py-2 rounded bg-[#0B265A] text-white hover:opacity-90">
    Guardar
  </button>
  <a href="{{ route('clientes.index') }}" class="px-4 py-2 rounded bg-slate-100 hover:bg-slate-200">
    Cancelar
  </a>
</div>