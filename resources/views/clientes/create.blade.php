@extends('layouts.admin')

@section('title', 'Nuevo Cliente')

@section('content')

<div class="max-w-3xl mx-auto" x-data="clienteCreate()">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#0B265A]">Nuevo Cliente</h1>

        <a href="{{ route('clientes.index') }}"
           class="text-sm text-slate-600 hover:text-slate-900">
            ← Volver a la lista
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">

        {{-- MENSAJES DE ERROR GLOBAL --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
                Hay errores en el formulario, revisa la información.
            </div>
        @endif

        <form action="{{ route('clientes.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Nombre comercial --}}
            <div>
                <label for="nombre_comercial" class="block text-sm font-medium text-slate-700">
                    Nombre comercial <span class="text-red-500">*</span>
                </label>
                <input type="text" id="nombre_comercial" name="nombre_comercial"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('nombre_comercial') }}" required>
                @error('nombre_comercial')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Razón social --}}
            <div class="relative">
                <label for="razon_social" class="block text-sm font-medium text-slate-700">
                    Razón social
                </label>
                <input type="text" id="razon_social" name="razon_social" x-model="razonSocial" @input.debounce.500ms="checkRazonSocial"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('razon_social') }}">
                @error('razon_social')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                
                <!-- Buscador sugerencias -->
                <div x-cloak x-show="similarClients.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-yellow-200 rounded-md shadow-lg p-3">
                    <p class="text-xs text-yellow-700 font-semibold mb-2">⚠ Posibles clientes similares existentes:</p>
                    <ul class="space-y-1">
                        <template x-for="client in similarClients" :key="client.id">
                            <li class="text-sm text-slate-600">
                                <span x-text="client.nombre_comercial" class="font-medium"></span> 
                                <span x-show="client.razon_social" x-text="'(' + client.razon_social + ')'"></span>
                                <span x-show="client.rfc" x-text="'- RFC: ' + client.rfc" class="text-slate-400"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            {{-- RFC --}}
            <div class="relative">
                <label for="rfc" class="block text-sm font-medium text-slate-700">
                    RFC
                </label>
                <input type="text" id="rfc" name="rfc" x-model="rfc" @input.debounce.500ms="checkRfc"
                       class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                              focus:border-[#FFC107] focus:ring-[#FFC107]"
                       value="{{ old('rfc') }}" style="text-transform: uppercase;">
                @error('rfc')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                
                <!-- RFC duplicado alert -->
                <div x-cloak x-show="rfcDuplicate" class="mt-2 text-sm text-red-600 flex items-center bg-red-50 p-2 rounded-lg border border-red-100">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    Este RFC ya está registrado en otro cliente (<span x-text="rfcDuplicateName" class="font-semibold ml-1"></span>).
                </div>
            </div>

            {{-- Teléfono y correo (2 columnas en desktop) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label for="telefono" class="block text-sm font-medium text-slate-700">
                        Teléfono
                    </label>
                    <input type="text" id="telefono" name="telefono"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('telefono') }}">
                    @error('telefono')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">
                        Correo electrónico
                    </label>
                    <input type="email" id="email" name="email"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                                  focus:border-[#FFC107] focus:ring-[#FFC107]"
                           value="{{ old('email') }}">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Dirección detallada --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="calle" class="block text-sm font-medium text-slate-700">
            Calle
        </label>
        <input type="text" id="calle" name="calle"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('calle') }}">
        @error('calle')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="colonia" class="block text-sm font-medium text-slate-700">
            Colonia
        </label>
        <input type="text" id="colonia" name="colonia"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('colonia') }}">
        @error('colonia')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <label for="ciudad" class="block text-sm font-medium text-slate-700">
            Ciudad
        </label>
        <input type="text" id="ciudad" name="ciudad"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('ciudad') }}">
        @error('ciudad')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="estado" class="block text-sm font-medium text-slate-700">
            Estado
        </label>
        <select id="estado" name="estado"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
            <option value="">Seleccione un estado</option>
            @php
                $estados = [
                    'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua',
                    'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México', 'Guanajuato', 'Guerrero',
                    'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla',
                    'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas',
                    'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
                ];
            @endphp
            @foreach($estados as $est)
                <option value="{{ $est }}" {{ old('estado') == $est ? 'selected' : '' }}>{{ $est }}</option>
            @endforeach
        </select>
        @error('estado')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="pais" class="block text-sm font-medium text-slate-700">
            País
        </label>
        <input type="text" id="pais" name="pais"
               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm
                      focus:border-[#FFC107] focus:ring-[#FFC107]"
               value="{{ old('pais', 'México') }}">
        @error('pais')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">
            Código postal
        </label>
        <input type="text"
               name="codigo_postal"
               value="{{ old('codigo_postal', $cliente->codigo_postal ?? '') }}"
               class="w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]"
               maxlength="10">
        @error('codigo_postal')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">
            Régimen fiscal
        </label>
        <select name="regimen_fiscal"
               class="w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
            <option value="">Seleccione un régimen</option>
            @php
                $regimenes = [
                    '601' => '601 - General de Ley Personas Morales',
                    '603' => '603 - Personas Morales con Fines no Lucrativos',
                    '605' => '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios',
                    '606' => '606 - Arrendamiento',
                    '608' => '608 - Demás ingresos',
                    '611' => '611 - Ingresos por Dividendos',
                    '612' => '612 - Personas Físicas con Actividades Empresariales y Profesionales',
                    '614' => '614 - Ingresos por intereses',
                    '615' => '615 - Régimen de los ingresos por obtención de premios',
                    '616' => '616 - Sin obligaciones fiscales',
                    '620' => '620 - Sociedades Cooperativas de Producción',
                    '621' => '621 - Incorporación Fiscal',
                    '622' => '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
                    '623' => '623 - Opcional para Grupos de Sociedades',
                    '624' => '624 - Coordinados',
                    '625' => '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
                    '626' => '626 - Régimen Simplificado de Confianza (RESICO)',
                ];
            @endphp
            @foreach($regimenes as $codigo => $descripcion)
                <option value="{{ $codigo }}" {{ old('regimen_fiscal') == $codigo ? 'selected' : '' }}>{{ $descripcion }}</option>
            @endforeach
        </select>
        @error('regimen_fiscal')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">
            Uso CFDI default
        </label>
        <select name="uso_cfdi_default"
               class="w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
            <option value="">Seleccione uso de CFDI</option>
            @php
                $usosCfdi = [
                    'G01' => 'G01 - Adquisición de mercancias',
                    'G02' => 'G02 - Devoluciones, descuentos o bonificaciones',
                    'G03' => 'G03 - Gastos en general',
                    'I01' => 'I01 - Construcciones',
                    'I02' => 'I02 - Mobiliario y equipo de oficina por inversiones',
                    'I03' => 'I03 - Equipo de transporte',
                    'I04' => 'I04 - Equipo de computo y accesorios',
                    'I05' => 'I05 - Dados, troqueles, moldes, matrices y herramental',
                    'I06' => 'I06 - Comunicaciones telefónicas',
                    'I07' => 'I07 - Comunicaciones satelitales',
                    'I08' => 'I08 - Otra maquinaria y equipo',
                    'D01' => 'D01 - Honorarios médicos, dentales y gastos hospitalarios.',
                    'D02' => 'D02 - Gastos médicos por incapacidad o discapacidad',
                    'D03' => 'D03 - Gastos funerales.',
                    'D04' => 'D04 - Donativos.',
                    'D05' => 'D05 - Intereses reales efectivamente pagados por créditos hipotecarios.',
                    'D06' => 'D06 - Aportaciones voluntarias al SAR.',
                    'D07' => 'D07 - Primas por seguros de gastos médicos.',
                    'D08' => 'D08 - Gastos de transportación escolar obligatoria.',
                    'D09' => 'D09 - Depósitos en cuentas para el ahorro, primas base pensiones.',
                    'D10' => 'D10 - Pagos por servicios educativos (colegiaturas)',
                    'P01' => 'P01 - Por definir',
                    'S01' => 'S01 - Sin efectos fiscales',
                    'CP01' => 'CP01 - Pagos',
                    'CN01' => 'CN01 - Nómina',
                ];
            @endphp
            @foreach($usosCfdi as $codigo => $descripcion)
                <option value="{{ $codigo }}" {{ old('uso_cfdi_default') == $codigo ? 'selected' : '' }}>{{ $descripcion }}</option>
            @endforeach
        </select>
        @error('uso_cfdi_default')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

            {{-- Activo --}}
            <div class="flex items-center gap-2">
                <input type="checkbox" id="activo" name="activo" value="1"
                       class="rounded border-slate-300 text-[#FFC107] shadow-sm
                              focus:ring-[#FFC107]"
                       {{ old('activo', true) ? 'checked' : '' }}>
                <label for="activo" class="text-sm text-slate-700">
                    Cliente activo
                </label>
            </div>

            {{-- BOTONES --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('clientes.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-sm text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-[#FFC107] text-[#0B265A] text-sm font-semibold
                               shadow hover:bg-[#e0ac05]">
                    Guardar Cliente
                </button>
            </div>

        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('clienteCreate', () => ({
            razonSocial: '{{ old('razon_social') }}',
            rfc: '{{ old('rfc') }}',
            similarClients: [],
            rfcDuplicate: false,
            rfcDuplicateName: '',

            checkRazonSocial() {
                if (this.razonSocial.length < 3) {
                    this.similarClients = [];
                    return;
                }
                fetch(`{{ route('clientes.checkDuplicate') }}?razon_social=${encodeURIComponent(this.razonSocial)}`)
                    .then(res => res.json())
                    .then(data => {
                        this.similarClients = data.matches || [];
                    })
                    .catch(e => console.error(e));
            },

            checkRfc() {
                if (this.rfc.length < 12) {
                    this.rfcDuplicate = false;
                    return;
                }
                fetch(`{{ route('clientes.checkDuplicate') }}?rfc=${encodeURIComponent(this.rfc)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.matches && data.matches.length > 0) {
                            this.rfcDuplicate = true;
                            this.rfcDuplicateName = data.matches[0].nombre_comercial;
                        } else {
                            this.rfcDuplicate = false;
                            this.rfcDuplicateName = '';
                        }
                    })
                    .catch(e => console.error(e));
            }
        }));
    });
</script>
@endpush

@endsection
