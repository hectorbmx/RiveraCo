<div class="flex items-center justify-between mb-4">
    <h2 class="text-lg font-semibold text-[#0B265A]">Datos generales</h2>

    <a href="{{ route('proveedores.edit', $proveedor) }}"
       class="text-sm text-blue-600 hover:underline">
        Editar proveedor
    </a>
</div>

<div class="grid md:grid-cols-2 gap-4 text-sm">
    <div>
        <p class="text-xs font-semibold text-slate-500">Nombre</p>
        <p class="font-semibold text-slate-900">{{ $proveedor->nombre }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">RFC</p>
        <p class="text-slate-900">{{ $proveedor->rfc ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Razon social</p>
        <p class="text-slate-900">{{ $proveedor->razon_social ?? '-' }}</p>
    </div>

    <div class="md:col-span-2">
        <p class="text-xs font-semibold text-slate-500">Domicilio</p>
        <p class="text-slate-900">{{ $proveedor->domicilio ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Codigo postal</p>
        <p class="text-slate-900">{{ $proveedor->codigo_postal ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Regimen fiscal</p>
        <p class="text-slate-900">{{ $proveedor->regimen_fiscal ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Uso CFDI default</p>
        <p class="text-slate-900">{{ $proveedor->uso_cfdi_default ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Teléfono</p>
        <p class="text-slate-900">{{ $proveedor->telefono ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Email</p>
        <p class="text-slate-900">{{ $proveedor->email ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Contacto</p>
        <p class="text-slate-900">{{ $proveedor->nombre_contacto ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Telefono de contacto</p>
        <p class="text-slate-900">{{ $proveedor->telefono_contacto ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Banco</p>
        <p class="text-slate-900">{{ $proveedor->banco ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">CLABE</p>
        <p class="text-slate-900">{{ $proveedor->clabe ?? '-' }}</p>
    </div>

    <div>
        <p class="text-xs font-semibold text-slate-500">Cuenta</p>
        <p class="text-slate-900">{{ $proveedor->cuenta ?? '-' }}</p>
    </div>
</div>
