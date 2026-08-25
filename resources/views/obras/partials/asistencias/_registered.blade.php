<h3 class="text-sm font-semibold text-slate-700 mb-3">Asistencias registradas en campo</h3>

<div class="border rounded-xl overflow-hidden w-full">
    <table class="w-full text-sm table-fixed">
        <thead class="bg-slate-50">
            <tr class="border-b text-slate-500">
                <th class="py-2 px-3 text-left w-[40%]">Empleado</th>
                <th class="py-2 px-3 text-left w-[15%]">Dia</th>
                <th class="py-2 px-3 text-left w-[15%]">Entrada</th>
                <th class="py-2 px-3 text-left w-[15%]">Salida</th>
                <th class="py-2 px-3 text-center w-[7%]">Foto</th>
                <th class="py-2 px-3 text-center w-[8%]">Estado</th>
            </tr>
        </thead>

        <tbody>
            @forelse($asistencias as $a)
                @php
                    $estado = $a->entrada_hora && $a->salida_hora ? 'completo' : 'pendiente';
                @endphp
                <tr class="border-b hover:bg-slate-50">
                    <td class="py-2 px-3">
                        {{ $a->empleado->Nombre }} {{ $a->empleado->Apellidos }}<br>
                        <span class="text-[11px] text-slate-400">
                            {{ $a->empleado->Area }} | {{ $a->empleado->Puesto }}
                        </span>
                    </td>
                    <td class="py-2 px-3 whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($a->checked_date)->format('d/m/Y') }}
                    </td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ $a->entrada_hora ?? '-' }}</td>
                    <td class="py-2 px-3 whitespace-nowrap">{{ $a->salida_hora ?? '-' }}</td>
                    <td class="py-2 px-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            @if($a->entrada_foto)
                                <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border hover:bg-slate-50" data-photo-url="{{ asset('storage/'.$a->entrada_foto) }}" data-photo-title="Foto de entrada" onclick="openAsistenciaPhoto(this)" title="Ver foto de entrada">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 18 2H9z"/>
                                    </svg>
                                </button>
                            @endif

                            @if($a->salida_foto)
                                <button type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border hover:bg-slate-50" data-photo-url="{{ asset('storage/'.$a->salida_foto) }}" data-photo-title="Foto de salida" onclick="openAsistenciaPhoto(this)" title="Ver foto de salida">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-indigo-600" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M9 2a1 1 0 0 0-.894.553L7.382 4H5a3 3 0 0 0-3 3v11a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3h-2.382l-.724-1.447A1 1 0 0 0 18 2H9z"/>
                                    </svg>
                                </button>
                            @endif

                            @if(!$a->entrada_foto && !$a->salida_foto)
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-2 px-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $estado === 'completo' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $estado === 'completo' ? 'Completo' : 'Pendiente' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-4 text-center text-slate-500">
                        No hay asistencias registradas en campo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
