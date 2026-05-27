@extends('layouts.admin')

@section('title', 'Editar proveedor')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Editar proveedor</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $proveedor->nombre }} · ID {{ $proveedor->id }}
            </p>
        </div>

        <a href="{{ route('proveedores.show', $proveedor) }}"
           class="text-sm text-slate-600 hover:text-slate-900">
            Volver al proveedor
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow p-6">
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
                Hay errores en el formulario, revisa la informacion.
            </div>
        @endif

        <form action="{{ route('proveedores.update', $proveedor) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="space-y-4">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Datos generales</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre', $proveedor->nombre) }}" required
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('nombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Razon social</label>
                        <input type="text" name="razon_social" value="{{ old('razon_social', $proveedor->razon_social) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('razon_social')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Descripcion</label>
                    <textarea name="descripcion" rows="3"
                              class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">{{ old('descripcion', $proveedor->descripcion) }}</textarea>
                    @error('descripcion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">RFC</label>
                        <input type="text" name="rfc" value="{{ old('rfc', $proveedor->rfc) }}"
                               class="mt-1 block w-full uppercase rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('rfc')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Fecha de registro</label>
                        <input type="date" name="fecha_registro"
                               value="{{ old('fecha_registro', optional($proveedor->fecha_registro)->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('fecha_registro')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Domicilio</label>
                    <input type="text" name="domicilio" value="{{ old('domicilio', $proveedor->domicilio) }}"
                           class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                    @error('domicilio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="space-y-4 border-t pt-5">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Datos fiscales</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Codigo postal</label>
                        <input type="text" name="codigo_postal" value="{{ old('codigo_postal', $proveedor->codigo_postal) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('codigo_postal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Regimen fiscal</label>
                        <select name="regimen_fiscal"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                            <option value="">Selecciona regimen</option>
                            @foreach($regimenesFiscales as $clave => $nombre)
                                <option value="{{ $clave }}" @selected(old('regimen_fiscal', $proveedor->regimen_fiscal) == $clave)>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('regimen_fiscal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Uso CFDI default</label>
                        <select name="uso_cfdi_default"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
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
            </section>

            <section class="space-y-4 border-t pt-5">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Contacto</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Telefono general</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $proveedor->telefono) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('telefono')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Correo electronico</label>
                        <input type="email" name="email" value="{{ old('email', $proveedor->email) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nombre de contacto</label>
                        <input type="text" name="nombre_contacto" value="{{ old('nombre_contacto', $proveedor->nombre_contacto) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('nombre_contacto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Telefono de contacto</label>
                        <input type="text" name="telefono_contacto" value="{{ old('telefono_contacto', $proveedor->telefono_contacto) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('telefono_contacto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="space-y-4 border-t pt-5">
                <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Datos bancarios</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Banco</label>
                        <input type="text" name="banco" value="{{ old('banco', $proveedor->banco) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('banco')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Cuenta</label>
                        <input type="text" name="cuenta" value="{{ old('cuenta', $proveedor->cuenta) }}"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('cuenta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">CLABE</label>
                        <input type="text" name="clabe" value="{{ old('clabe', $proveedor->clabe) }}"
                               inputmode="numeric" minlength="18" maxlength="18" pattern="[0-9]{18}"
                               title="La CLABE debe tener exactamente 18 digitos"
                               oninput="this.value = this.value.replace(/\D/g, '').slice(0, 18)"
                               class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-[#FFC107] focus:ring-[#FFC107]">
                        @error('clabe')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="activo" value="1" id="activo"
                       class="rounded border-slate-300 text-[#0B265A] focus:ring-[#FFC107]"
                       {{ old('activo', $proveedor->activo) ? 'checked' : '' }}>
                <label for="activo" class="text-sm text-slate-700">Proveedor activo</label>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('proveedores.show', $proveedor) }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 transition">
                    Cancelar
                </a>

                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-[#0B265A] text-white font-medium hover:bg-[#163A7A] transition">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
