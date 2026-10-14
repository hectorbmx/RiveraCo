@extends('layouts.admin')

@section('title', 'Editar proveedor')

@section('content')
<div class="max-w-4xl mx-auto py-6">
    <!-- Encabezado -->
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-[#0B265A] tracking-tight">Editar proveedor</h1>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                <span class="font-semibold text-slate-700">{{ $proveedor->nombre }}</span> 
                <span class="inline-block w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md text-xs font-medium">ID {{ $proveedor->id }}</span>
            </p>
        </div>

        <a href="{{ route('proveedores.show', $proveedor) }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-[#0B265A] bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-sm hover:bg-slate-50 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Volver al proveedor
        </a>
    </div>

    <!-- Contenedor Principal -->
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-100 border border-slate-100 p-6 md:p-8">
        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Hay errores en el formulario, por favor revisa la información resaltada.</span>
            </div>
        @endif

        <form action="{{ route('proveedores.update', $proveedor) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Sección: Datos Generales -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-200/60 pb-3">
                    <div class="w-2 h-5 bg-[#0B265A] rounded-full"></div>
                    <h2 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Datos generales</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre', $proveedor->nombre) }}" required
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('nombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Razón social</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social', $proveedor->razon_social) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('razon_social')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">{{ old('descripcion', $proveedor->descripcion) }}</textarea>
                    @error('descripcion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">RFC</label>
                        <input type="text" name="rfc" value="{{ old('rfc', $proveedor->rfc) }}"
                               class="block w-full uppercase rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('rfc')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Fecha de registro</label>
                        <input type="date" name="fecha_registro"
                               value="{{ old('fecha_registro', optional($proveedor->fecha_registro)->format('Y-m-d')) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('fecha_registro')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Domicilio</label>
                    <input type="text" name="domicilio" value="{{ old('domicilio', $proveedor->domicilio) }}"
                           class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                    @error('domicilio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="max-w-xs">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Días de crédito</label>
                    <input type="number" name="dias_credito" min="0" max="3650" step="1"
                           value="{{ old('dias_credito', $proveedor->dias_credito ?? 0) }}"
                           class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                    <p class="mt-1 text-xs text-slate-500">Se usará para sugerir la fecha esperada de pago.</p>
                    @error('dias_credito')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Sección: Datos Fiscales -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-200/60 pb-3">
                    <div class="w-2 h-5 bg-[#0B265A] rounded-full"></div>
                    <h2 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Datos fiscales</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Código postal</label>
                        <input type="text" name="codigo_postal" value="{{ old('codigo_postal', $proveedor->codigo_postal) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('codigo_postal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Régimen fiscal</label>
                        <select name="regimen_fiscal"
                                class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                            <option value="">Selecciona régimen</option>
                            @foreach($regimenesFiscales as $clave => $nombre)
                                <option value="{{ $clave }}" @selected(old('regimen_fiscal', $proveedor->regimen_fiscal) == $clave)>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('regimen_fiscal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Uso CFDI default</label>
                        <select name="uso_cfdi_default"
                                class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                            <option value="">Selecciona uso CFDI</option>
                            @foreach($usosCfdi as $clave => $nombre)
                                <option value="{{ $clave }}" @selected(old('uso_cfdi_default', $proveedor->uso_cfdi_default) == $clave)>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('uso_cfdi_default')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Sección: Contacto -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-200/60 pb-3">
                    <div class="w-2 h-5 bg-[#0B265A] rounded-full"></div>
                    <h2 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Contacto</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Teléfono general</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $proveedor->telefono) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('telefono')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Correo electrónico</label>
                        <input type="email" name="email" value="{{ old('email', $proveedor->email) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Nombre de contacto</label>
                        <input type="text" name="nombre_contacto" value="{{ old('nombre_contacto', $proveedor->nombre_contacto) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('nombre_contacto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Teléfono de contacto</label>
                        <input type="text" name="telefono_contacto" value="{{ old('telefono_contacto', $proveedor->telefono_contacto) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('telefono_contacto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Sección: Datos Bancarios -->
            <div class="p-5 rounded-2xl bg-slate-50/70 border border-slate-100 space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-200/60 pb-3">
                    <div class="w-2 h-5 bg-[#0B265A] rounded-full"></div>
                    <h2 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Datos bancarios</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Banco</label>
                        <input type="text" name="banco" value="{{ old('banco', $proveedor->banco) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('banco')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">Cuenta</label>
                        <input type="text" name="cuenta" value="{{ old('cuenta', $proveedor->cuenta) }}"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5">
                        @error('cuenta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1">CLABE</label>
                        <input type="text" name="clabe" value="{{ old('clabe', $proveedor->clabe) }}"
                               inputmode="numeric" minlength="18" maxlength="18" pattern="[0-9]{18}"
                               title="La CLABE debe tener exactamente 18 dígitos"
                               oninput="this.value = this.value.replace(/\D/g, '').slice(0, 18)"
                               class="block w-full rounded-xl border-slate-300 bg-white text-slate-800 shadow-sm focus:border-[#0B265A] focus:ring-[#0B265A] text-sm py-2.5 tracking-wider font-mono">
                        @error('clabe')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Checkbox Activo -->
            <div class="flex items-center gap-3 pt-2 px-1">
                <input type="checkbox" name="activo" value="1" id="activo"
                       class="w-4 h-4 rounded border-slate-300 text-[#0B265A] focus:ring-[#0B265A]"
                       {{ old('activo', $proveedor->activo) ? 'checked' : '' }}>
                <label for="activo" class="text-sm font-medium text-slate-700 cursor-pointer select-none">Proveedor activo en el sistema</label>
            </div>

            <!-- Botones de Acción -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('proveedores.show', $proveedor) }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-medium hover:bg-slate-100 transition shadow-sm text-sm">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-[#0B265A] text-white font-medium hover:bg-[#163A7A] shadow-md shadow-[#0B265A]/20 transition text-sm">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection