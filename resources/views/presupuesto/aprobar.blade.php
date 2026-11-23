@extends('layouts.admin')
@section('title', 'Aprobar presupuesto')

@section('content')
@php
    $captura = $guardada->datos;
    $presentacion = $captura['presentacion'] ?? [];
    $planeacion = $captura['relacion_gastos']['planeacion'] ?? [];
    $subtotal = $presentacion['subtotal'] ?? $guardada->total_venta;
    $moneda = fn ($valor) => '$' . number_format($valor, 2);
    $datosCompletos = !empty($captura['cliente']['id']) && !empty($captura['presupuesto']['nombre_obra']) && $subtotal > 0;
@endphp
<div class="mx-auto max-w-3xl space-y-4">
    <a href="{{ route('presupuesto.versiones.show', [$presupuesto, $guardada]) }}"
       class="text-sm font-semibold text-[#0B265A] hover:underline">← Volver a la versión {{ $guardada->folio() }}</a>

    <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-xl font-bold text-slate-900">Aprobar y crear obra</h1>
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-slate-600">Versión</dt><dd class="font-semibold">{{ $guardada->folio() }} · {{ $captura['presupuesto']['codigo_proyecto'] }}</dd></div>
            <div><dt class="text-slate-600">Cliente</dt><dd class="font-semibold">{{ $captura['cliente']['nombre_comercial'] ?? $captura['presupuesto']['nombre_cliente'] }}</dd></div>
            <div><dt class="text-slate-600">Nombre de obra</dt><dd class="font-semibold">{{ $captura['presupuesto']['nombre_obra'] ?? 'Pendiente' }}</dd></div>
            <div><dt class="text-slate-600">Semanas de ejecución</dt><dd class="font-semibold">{{ $planeacion['numero_semanas'] ?? $captura['duracion']['realista']['semanas'] ?? 'Pendiente' }}</dd></div>
            <div><dt class="text-slate-600">Monto contratado sin IVA</dt><dd class="font-semibold">{{ $moneda($subtotal) }}</dd></div>
            @isset($presentacion['iva'], $presentacion['total'])
                <div><dt class="text-slate-600">IVA y total con IVA</dt><dd class="font-semibold">{{ $moneda($presentacion['iva']) }} · {{ $moneda($presentacion['total']) }}</dd></div>
            @endisset
        </dl>
        @if($errors->any())
            <p role="alert" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{{ $errors->first() }}</p>
        @endif
        @if($obrasVinculadas->isNotEmpty())
            <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                <p>Revisa la obra vinculada a este presupuesto antes de crear otra.</p>
                @foreach($obrasVinculadas as $obra)
                    <a class="block font-semibold underline" href="{{ route('obras.edit', $obra) }}">{{ $obra->clave_obra }} · {{ $obra->nombre }}</a>
                @endforeach
            </div>
        @elseif(!$datosCompletos)
            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Guarda una versión con cliente oficial, nombre de obra y subtotal de venta antes de aprobar.</p>
            <a class="text-sm font-semibold text-[#0B265A] underline" href="{{ route('presupuesto.show', [$presupuesto, 'tab' => 'apupi', 'borrador' => 1]) }}">Abrir borrador</a>
        @else
            <p class="text-sm text-slate-600">
                Al confirmar, el presupuesto quedará bloqueado y la obra aparecerá en Planeación.
                {{ ($planeacion['publicable'] ?? false) ? 'Se copiarán los importes semanales de esta versión.' : 'La programación semanal quedará pendiente en la obra.' }}
            </p>
            <form method="POST" action="{{ route('presupuesto.versiones.aprobar', [$presupuesto, $guardada]) }}" class="space-y-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="text-sm font-medium">Tipo de obra
                        <select name="tipo_obra" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Selecciona el tipo</option>
                            @foreach($tipos as $clave => $etiqueta)
                                <option value="{{ $clave }}" @selected(old('tipo_obra', 'PILAS') === $clave)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium">Responsable
                        <select name="responsable_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Por asignar</option>
                            @foreach($responsables as $empleado)
                                <option value="{{ $empleado->id_Empleado }}" @selected(old('responsable_id') == $empleado->id_Empleado)>{{ $empleado->Nombre }} {{ $empleado->Apellidos }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-medium">Fecha de inicio programada
                        <input type="date" name="fecha_inicio_programada" value="{{ old('fecha_inicio_programada') }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    </label>
                </div>
                <button type="submit" class="rounded-lg bg-[#0B265A] px-4 py-2 text-sm font-semibold text-white">Confirmar aprobación y crear obra</button>
            </form>
        @endif
    </section>
</div>
@endsection
