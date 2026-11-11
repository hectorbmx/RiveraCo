<section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div><h2 class="text-lg font-semibold text-slate-800">Material entregado a obra</h2>
        <p class="text-sm text-slate-500">Consulta entregas aplicadas por obra. Una requisición autorizada no se cuenta hasta que exista una entrega.</p></div>
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <input type="hidden" name="tipo" value="devolucion">
        @if($datos['busqueda'] !== '')<input type="hidden" name="solicitudes_q" value="{{ $datos['busqueda'] }}">@endif
        <label class="min-w-64 flex-1 text-sm font-medium text-slate-700">Obra
            <select name="obra_material_id" class="mt-1 block w-full rounded-lg border-slate-300">
                <option value="">Selecciona una obra</option>
                @foreach($datos['obras'] as $obraOpcion)<option value="{{ $obraOpcion->id }}" @selected($datos['obra'] && $datos['obra']->id === $obraOpcion->id)>{{ $obraOpcion->clave_obra }} · {{ $obraOpcion->nombre }}</option>@endforeach
            </select>
        </label>
        <button class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Consultar material</button>
    </form>
    @if($datos['obra_no_disponible'] ?? false)
        <p class="text-sm text-slate-600">La obra seleccionada no tiene material pendiente de devolución ni movimientos que requieran revisión.</p>
    @endif
    @if($datos['finiquito'])
        @php($finiquitoMaterial = $datos['finiquito'])
        @if(count($finiquitoMaterial['materiales']['inconsistencias'] ?? []) || count($finiquitoMaterial['origenes_faltantes'] ?? []) || count($finiquitoMaterial['materiales']['origenes_ambiguos'] ?? []) || count($finiquitoMaterial['materiales']['devoluciones_sin_origen'] ?? []))
            <p class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">Hay movimientos o saldos que requieren revisión. Consulta el detalle en la obra antes de considerar sus cantidades disponibles.</p>
        @endif
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-100 text-slate-700"><tr>
            <th class="p-2">Material / origen</th><th class="p-2 text-right">Entregado</th><th class="p-2 text-right">Devuelto aplicado</th><th class="p-2 text-right">Recibido sin aplicar</th><th class="p-2 text-right">Pendiente de recibir</th><th class="p-2 text-right">Sin devolución física</th><th class="p-2 text-right">Pendiente evaluación</th><th class="p-2">Solicitudes</th>
        </tr></thead><tbody>
        @forelse($finiquitoMaterial['entregas'] as $fila)
            @php($entrega = $fila['entrega'])
            <tr class="border-t align-top"><td class="p-2"><strong>{{ $entrega->descripcion }}</strong><div class="text-xs text-slate-500">{{ $entrega->recepcion['folio'] ?? $entrega->salida['folio'] ?? 'Sin folio' }}</div>@foreach($entrega->requisiciones as $rq)<div class="text-xs">Requisición: {{ $rq['folio'] ?: '#'.$rq['id'] }}</div>@endforeach</td>
                <td class="p-2 text-right whitespace-nowrap">{{ number_format((float) $entrega->cantidad, 3) }} {{ $entrega->unidad }}</td>
                <td class="p-2 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($entrega->cantidad_devuelta_confirmada, 3) }}</td>
                <td class="p-2 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['fisico_pendiente_aplicar'], 3) }}</td>
                <td class="p-2 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['pendiente_recibir'], 3) }}</td>
                <td class="p-2 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($fila['sin_devolucion_fisica'], 3) }}</td>
                <td class="p-2 text-right">{{ $fila['requiere_revision'] ? 'Revisar' : number_format($entrega->cantidad_pendiente_evaluacion, 3) }}</td>
                <td class="p-2">{{ $fila['solicitudes'] ? implode(', ', $fila['solicitudes']) : 'Sin solicitud presentada' }}</td></tr>
        @empty<tr><td colspan="8" class="p-4 text-center text-slate-500">No hay entregas aplicadas identificadas para esta obra.</td></tr>@endforelse
        </tbody></table></div>
        <p class="text-xs text-slate-500">Lo pendiente de recibir está incluido en el saldo sin devolución física. Ese saldo puede contener material consumido o aún en obra.</p>
    @else
        <p class="text-sm text-slate-500">Selecciona una obra para consultar sus materiales entregados.</p>
    @endif
</section>

<section class="space-y-4">
    <div><h2 class="text-lg font-semibold text-slate-800">Solicitudes de devolución pendientes</h2><p class="text-sm text-slate-500">Recibe el material, aplica las partidas aptas o registra herramientas pendientes de evaluación.</p></div>
    @if(isset($errors) && $errors->any())<div role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="GET" class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-4">
        <input type="hidden" name="tipo" value="devolucion">
        @if($datos['obra'])<input type="hidden" name="obra_material_id" value="{{ $datos['obra']->id }}">@endif
        <label for="solicitudes_q" class="sr-only">Buscar solicitud por folio u obra</label>
        <input id="solicitudes_q" name="solicitudes_q" maxlength="120" value="{{ $datos['busqueda'] }}" placeholder="Buscar folio, obra o clave" class="min-w-64 flex-1 rounded-lg border-slate-300">
        <button class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Buscar solicitud</button>
        <a href="{{ route('inventario.documentos.index', array_filter(['tipo' => 'devolucion', 'obra_material_id' => $datos['obra']?->id])) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Limpiar</a>
    </form>
    @include('inventario.devoluciones-obra.partials.lista', ['solicitudes' => $datos['solicitudes']])
    @if($datos['solicitudes']->hasPages())<div>{{ $datos['solicitudes']->links() }}</div>@endif
</section>
