@extends('layouts.admin')

@section('content')
<div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- COLUMNA IZQUIERDA: DATOS DEL USUARIO --}}
        <div class="lg:col-span-1 space-y-6">
            <h1 class="text-xl font-semibold mb-4">Editar usuario App</h1>
            
            @php
                $empleado = $usuario->usuarioApp?->empleado;
            @endphp

            <div class="border rounded-lg p-4 bg-white shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Empleado asignado</h2>

                    @if($empleado)
                        <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">
                            Vinculado
                        </span>
                    @else
                        <span class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-800">
                            Sin vínculo
                        </span>
                    @endif
                </div>

                @if($empleado)
                    <div class="mt-3 grid grid-cols-1 gap-2 text-sm">
                        <div><span class="text-gray-500">ID:</span> <span class="font-medium">{{ $empleado->id_Empleado }}</span></div>
                        <div>
                            <span class="text-gray-500">Nombre:</span>
                            <span class="font-medium">{{ $empleado->Nombre }} {{ $empleado->Apellidos }}</span>
                        </div>
                        <div><span class="text-gray-500">Email:</span> <span class="font-medium">{{ $empleado->Email }}</span></div>
                        <div><span class="text-gray-500">Área:</span> <span class="font-medium">{{ $empleado->Area }}</span></div>
                        <div><span class="text-gray-500">Puesto:</span> <span class="font-medium">{{ $empleado->Puesto }}</span></div>
                    </div>
                @else
                    <p class="mt-3 text-sm text-gray-600">
                        Este usuario no tiene un empleado ligado en <code>usuarios_app</code>.
                    </p>
                @endif
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <form method="POST" action="{{ route('usuarios.update', $usuario->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium mb-1">Nombre</label>
                        <input type="text" name="name"
                               class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                               value="{{ old('name', $usuario->name) }}" required>
                        @error('name')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email"
                               class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                               value="{{ old('email', $usuario->email) }}" required>
                        @error('email')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <hr class="my-4">

                    <div>
                        <label class="block text-sm font-medium mb-1">Nueva contraseña (opcional)</label>
                        <input type="password" name="password"
                               class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                               autocomplete="new-password">
                        @error('password')
                            <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">Déjalo vacío para mantener la contraseña actual.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirmation"
                               class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                               autocomplete="new-password">
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition shadow-sm">
                            Guardar cambios
                        </button>

                        <a href="{{ route('usuarios.index') }}"
                           class="px-4 py-2 rounded border hover:bg-gray-50 transition">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- COLUMNA DERECHA: HISTORIAL DE MOVIMIENTOS --}}
        <div class="lg:col-span-2" x-data="{ tab: @js(in_array(request('tab'), ['autorizaciones', 'compras', 'operaciones', 'bitacora', 'permisos', 'pilas']) ? request('tab') : 'autorizaciones') }">
            <div class="bg-white rounded-lg shadow-sm border min-h-[600px] flex flex-col">
                
                {{-- TABS NAVIGATION --}}
                <div class="flex border-b overflow-x-auto">
                    <button @click="tab = 'autorizaciones'" 
                            :class="tab === 'autorizaciones' ? 'border-blue-600 text-blue-600 bg-blue-50/30' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                        🛡️ Autorizaciones
                    </button>
                    <button @click="tab = 'compras'" 
                            :class="tab === 'compras' ? 'border-blue-600 text-blue-600 bg-blue-50/30' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                        🛒 Compras y Gastos
                    </button>
                    <button @click="tab = 'operaciones'" 
                            :class="tab === 'operaciones' ? 'border-blue-600 text-blue-600 bg-blue-50/30' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                        🏗️ Operaciones
                    </button>
                    <button @click="tab = 'bitacora'" 
                            :class="tab === 'bitacora' ? 'border-blue-600 text-blue-600 bg-blue-50/30' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                        📝 Bitácora
                    </button>
                    <button @click="tab = 'permisos'" 
                            :class="tab === 'permisos' ? 'border-blue-600 text-blue-600 bg-blue-50/30' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                        Permisos
                    </button>
                    <button @click="tab = 'pilas'" 
                            :class="tab === 'pilas' ? 'border-blue-600 text-blue-600 bg-blue-50/30' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="px-6 py-4 text-sm font-medium border-b-2 whitespace-nowrap transition-colors">
                        🏗️ Pilas (Comisiones)
                    </button>
                </div>

                {{-- TABS CONTENT --}}
                <div class="p-6 flex-1">
                    
                    {{-- TAB: AUTORIZACIONES --}}
                    <div x-show="tab === 'autorizaciones'" x-transition>
                        <h3 class="font-semibold text-gray-800 mb-4">Últimas autorizaciones registradas</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border">
                                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                                    <tr>
                                        <th class="px-4 py-2 border-b">Tipo</th>
                                        <th class="px-4 py-2 border-b">Referencia</th>
                                        <th class="px-4 py-2 border-b text-right">Monto</th>
                                        <th class="px-4 py-2 border-b text-center">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($autorizaciones->sortByDesc('fecha') as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 border-b">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-800">
                                                    {{ $item['tipo'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 border-b font-medium text-gray-700">{{ $item['referencia'] }}</td>
                                            <td class="px-4 py-2 border-b text-right font-mono">
                                                {{ $item['monto'] ? '$' . number_format($item['monto'], 2) : '-' }}
                                            </td>
                                            <td class="px-4 py-2 border-b text-center text-gray-500">
                                                {{ $item['fecha'] ? \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y H:i') : '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">No se encontraron autorizaciones recientes.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB: COMPRAS Y GASTOS --}}
                    <div x-show="tab === 'compras'" x-transition>
                        <h3 class="font-semibold text-gray-800 mb-4">Compras y solicitudes creadas</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border">
                                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                                    <tr>
                                        <th class="px-4 py-2 border-b">Tipo</th>
                                        <th class="px-4 py-2 border-b">Referencia</th>
                                        <th class="px-4 py-2 border-b text-right">Monto</th>
                                        <th class="px-4 py-2 border-b text-center">Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($comprasGastos->sortByDesc('fecha') as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 border-b italic text-gray-500">{{ $item['tipo'] }}</td>
                                            <td class="px-4 py-2 border-b font-medium text-gray-700">{{ $item['referencia'] }}</td>
                                            <td class="px-4 py-2 border-b text-right font-mono">${{ number_format($item['monto'], 2) }}</td>
                                            <td class="px-4 py-2 border-b text-center">
                                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 uppercase font-bold">
                                                    {{ $item['status'] ?? 'Pendiente' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">No se han registrado compras o gastos.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- TAB: OPERACIONES --}}
                    <div x-show="tab === 'operaciones'" x-transition>
                        <h3 class="font-semibold text-gray-800 mb-4">Actividad en obra e inventarios</h3>
                        <div class="space-y-3">
                            @forelse($operaciones->sortByDesc('fecha') as $item)
                                <div class="flex items-start gap-3 p-3 border rounded hover:bg-gray-50 transition shadow-sm">
                                    <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center 
                                        {{ $item['tipo'] === 'Inventario' ? 'bg-orange-100 text-orange-600' : 'bg-green-100 text-green-600' }}">
                                        {!! $item['tipo'] === 'Inventario' ? '📦' : '👥' !!}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $item['referencia'] }}</p>
                                            <span class="text-[10px] text-gray-400 whitespace-nowrap">
                                                {{ \Carbon\Carbon::parse($item['fecha'])->diffForHumans() }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500">{{ $item['detalle'] }}</p>
                                        <p class="text-[10px] text-blue-500 font-medium mt-1">{{ $item['tipo'] }} — {{ \Carbon\Carbon::parse($item['fecha'])->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="py-8 text-center text-gray-400 italic border rounded">No hay actividad operativa reciente.</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- TAB: BITÁCORA --}}
                    <div x-show="tab === 'bitacora'" x-transition>
                        <h3 class="font-semibold text-gray-800 mb-4">Notas y actividad registrada por el usuario</h3>
                        <div class="space-y-4">
                            @forelse($bitacora as $item)
                                <div class="bg-gray-50 p-4 rounded border-l-4 {{ $item->tipo === 'nota' ? 'border-blue-400' : 'border-green-400' }} relative">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-bold {{ $item->tipo === 'nota' ? 'text-blue-600' : 'text-green-600' }} uppercase">
                                            {{ $item->titulo }}
                                        </span>
                                        <span class="text-[10px] text-gray-400">
                                            {{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-700 leading-relaxed">{{ $item->contenido }}</p>
                                </div>
                            @empty
                                <div class="py-8 text-center text-gray-400 italic border rounded">El usuario no ha registrado notas o actividad reciente en la bitácora.</div>
                            @endforelse
                        </div>
                    </div>
                    {{-- TAB: PERMISOS --}}
                    <div x-show="tab === 'permisos'" x-transition>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                            <div>
                                <h3 class="font-semibold text-gray-800">Permisos del usuario</h3>
                                <p class="text-sm text-gray-500 mt-1">Ajusta permisos directos sin cambiar los permisos del rol.</p>
                            </div>
                            <div class="flex flex-wrap gap-2 text-xs">
                                <span class="px-2 py-1 rounded bg-blue-100 text-blue-700">{{ count($rolePermissionIds ?? []) }} por rol</span>
                                <span class="px-2 py-1 rounded bg-green-100 text-green-700">{{ count($directPermissionIds ?? []) }} agregados</span>
                                <span class="px-2 py-1 rounded bg-red-100 text-red-700">{{ count($deniedPermissionIds ?? []) }} quitados</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('usuarios.permissions.sync', $usuario->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="overflow-x-auto border rounded">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                                        <tr>
                                            <th class="px-4 py-3 border-b">Permiso</th>
                                            <th class="px-4 py-3 border-b text-center">Estado efectivo</th>
                                            <th class="px-4 py-3 border-b text-center">Heredar</th>
                                            <th class="px-4 py-3 border-b text-center">Agregar</th>
                                            <th class="px-4 py-3 border-b text-center">Quitar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($permissions as $permission)
                                            @php
                                                $isFromRole = in_array($permission->id, $rolePermissionIds ?? []);
                                                $isDirect = in_array($permission->id, $directPermissionIds ?? []);
                                                $isDenied = in_array($permission->id, $deniedPermissionIds ?? []);
                                                $isEffective = in_array($permission->id, $effectivePermissionIds ?? []);
                                                $currentEffect = $isDenied ? 'deny' : ($isDirect ? 'grant' : 'inherit');
                                            @endphp
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 border-b">
                                                    <div class="font-medium text-gray-800">{{ $permission->name }}</div>
                                                    <div class="text-xs text-gray-400">Guard: {{ $permission->guard_name }}</div>
                                                </td>
                                                <td class="px-4 py-3 border-b text-center">
                                                    @if($isDenied)
                                                        <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-700">Quitado</span>
                                                    @elseif($isDirect && !$isFromRole)
                                                        <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">Agregado</span>
                                                    @elseif($isEffective)
                                                        <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">Activo</span>
                                                    @else
                                                        <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-500">Sin acceso</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 border-b text-center">
                                                    <label class="inline-flex items-center justify-center cursor-pointer">
                                                        <input type="radio"
                                                               name="permission_overrides[{{ $permission->id }}]"
                                                               value="inherit"
                                                               class="text-blue-600 focus:ring-blue-500"
                                                               {{ $currentEffect === 'inherit' ? 'checked' : '' }}>
                                                        <span class="sr-only">Heredar {{ $permission->name }}</span>
                                                    </label>
                                                    <div class="text-[11px] text-gray-400 mt-1">{{ $isFromRole ? 'Rol' : 'Base' }}</div>
                                                </td>
                                                <td class="px-4 py-3 border-b text-center">
                                                    <label class="inline-flex items-center justify-center cursor-pointer">
                                                        <input type="radio"
                                                               name="permission_overrides[{{ $permission->id }}]"
                                                               value="grant"
                                                               class="text-green-600 focus:ring-green-500"
                                                               {{ $currentEffect === 'grant' ? 'checked' : '' }}>
                                                        <span class="sr-only">Agregar {{ $permission->name }}</span>
                                                    </label>
                                                </td>
                                                <td class="px-4 py-3 border-b text-center">
                                                    <label class="inline-flex items-center justify-center cursor-pointer">
                                                        <input type="radio"
                                                               name="permission_overrides[{{ $permission->id }}]"
                                                               value="deny"
                                                               class="text-red-600 focus:ring-red-500"
                                                               {{ $currentEffect === 'deny' ? 'checked' : '' }}>
                                                        <span class="sr-only">Quitar {{ $permission->name }}</span>
                                                    </label>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-8 text-center text-gray-400 italic">No hay permisos registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition shadow-sm">
                                    Guardar permisos
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- TAB: PILAS (COMISIONES) --}}
                    <div x-show="tab === 'pilas'" x-transition>
                        <h3 class="font-semibold text-gray-800 mb-4">Registro de Pilas Culminadas (Comisiones)</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border">
                                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                                    <tr>
                                        <th class="px-4 py-2 border-b">Obra</th>
                                        <th class="px-4 py-2 border-b">Pila</th>
                                        <th class="px-4 py-2 border-b">Folio / Formato</th>
                                        <th class="px-4 py-2 border-b text-center">Fecha</th>
                                        <th class="px-4 py-2 border-b text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pilas as $pila)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2 border-b font-medium text-gray-700">{{ $pila['obra'] }}</td>
                                            <td class="px-4 py-2 border-b">
                                                <span class="font-bold text-blue-700">{{ $pila['pila'] }}</span>
                                            </td>
                                            <td class="px-4 py-2 border-b text-gray-600 font-mono text-xs">{{ $pila['folio'] ?? 'S/F' }}</td>
                                            <td class="px-4 py-2 border-b text-center text-gray-500">
                                                {{ $pila['fecha'] ? \Carbon\Carbon::parse($pila['fecha'])->format('d/m/Y') : '-' }}
                                            </td>
                                            <td class="px-4 py-2 border-b text-center">
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase
                                                    {{ ($pila['estado'] ?? '') === 'cerrada' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                                    {{ $pila['estado'] ?? 'Abierta' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 italic">No se han registrado culminaciones de pilas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
@endsection

