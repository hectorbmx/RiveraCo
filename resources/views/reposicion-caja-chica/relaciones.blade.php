@extends('layouts.admin')

@section('title', 'Relaciones caja chica')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Relaciones semanales</h1>
            <p class="text-sm text-slate-500">Relaciones generadas para reposicion semanal e impresion.</p>
        </div>
        <a href="{{ route('reposicion-caja-chica.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Gastos</a>
    </div>
<div class="overflow-hidden rounded-lg bg-white shadow-sm border border-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left">Folio</th>
                    <th class="px-4 py-3 text-left">Semana</th>
                    <th class="px-4 py-3 text-left">Responsable</th>
                    <th class="px-4 py-3 text-right">Registrado</th>
                    <th class="px-4 py-3 text-right">Autorizado</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($relaciones as $relacion)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $relacion->folio }}</td>
                        <td class="px-4 py-3">{{ optional($relacion->fecha_inicio)->format('d/m/Y') }} - {{ optional($relacion->fecha_fin)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $relacion->responsable->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) $relacion->total_registrado, 2) }}</td>
                        <td class="px-4 py-3 text-right">${{ number_format((float) $relacion->total_autorizado, 2) }}</td>
                        <td class="px-4 py-3 text-center">{{ str_replace('_', ' ', $relacion->estado) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('reposicion-caja-chica.relaciones.imprimir', $relacion) }}" target="_blank" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Imprimir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Aun no hay relaciones semanales.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $relaciones->links() }}</div>
</div>
@endsection

