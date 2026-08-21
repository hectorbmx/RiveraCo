@extends('layouts.admin')

@section('title', 'Reportes de campo del concepto')

@section('content')
@php
    $statusClass = function (?string $status): string {
        return match ($status) {
            'aprobado' => 'bg-emerald-100 text-emerald-800',
            'rechazado' => 'bg-red-100 text-red-800',
            'convertido_a_estimacion' => 'bg-blue-100 text-blue-800',
            'en_revision' => 'bg-amber-100 text-amber-800',
            'pendiente' => 'bg-slate-100 text-slate-800',
            default => 'bg-slate-100 text-slate-600',
        };
    };

    $galleryPhotos = $items
        ->flatMap(function ($item) use ($concept) {
            $report = $item->report;

            return $item->photos->map(function ($photo) use ($item, $report, $concept) {
                return [
                    'url' => Storage::disk('public')->url($photo->path),
                    'title' => 'Reporte ' . optional($report?->submitted_at ?? $item->created_at)->format('d/m/Y H:i'),
                    'caption' => trim(($concept->excel_code ?? '') . ' · ' . number_format((float) $item->quantity, 4) . ' ' . ($item->unit ?: $concept->unit)),
                    'size' => $photo->size ? number_format($photo->size / 1024, 0) . ' KB' : 'Sin peso',
                ];
            });
        })
        ->values();
@endphp

<style>
    [x-cloak] { display: none !important; }
</style>

<div class="mx-auto max-w-7xl space-y-6" x-data="conceptReportGallery(@js($galleryPhotos))" @keydown.escape.window="close()" @keydown.arrow-left.window="previous()" @keydown.arrow-right.window="next()">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">
                {{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? $obra->nombre }}
            </div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Reportes de campo del concepto</h1>
            <p class="mt-1 max-w-4xl text-sm text-slate-600">
                <span class="font-mono font-semibold text-slate-900">{{ $concept->excel_code }}</span>
                — {{ $concept->description }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('obra_civil.field-review.index', $obra) }}"
               class="rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                Revision campo
            </a>
            <a href="{{ route('obra_civil.details', $obra) }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Volver a detalles
            </a>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-5">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Cantidad</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format((float) $balance['budget_quantity'], 4) }} {{ $concept->unit }}</div>
                <div class="text-xs text-slate-500">Presupuesto base</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Reportado vigente</div>
                <div class="mt-1 font-semibold text-blue-700">{{ number_format((float) $reportedTotal, 4) }} {{ $concept->unit }}</div>
                <div class="text-xs text-slate-500">Pendiente/en revision/aprobado</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Estimado</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format((float) $balance['used_quantity'], 4) }} {{ $concept->unit }}</div>
                <div class="text-xs text-slate-500">{{ number_format((int) ($balance['estimations_count'] ?? 0)) }} estimacion(es)</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Disponible</div>
                <div class="mt-1 font-semibold {{ (float) $balance['available_quantity'] < 0 ? 'text-red-700' : 'text-slate-900' }}">
                    {{ number_format((float) $balance['available_quantity'], 4) }} {{ $concept->unit }}
                </div>
                <div class="text-xs text-slate-500">${{ number_format((float) $balance['available_amount'], 2) }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Evidencia</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format($items->count()) }} reporte(s)</div>
                <div class="text-xs text-slate-500">{{ number_format($photosCount) }} foto(s)</div>
            </div>
        </div>

        <div class="border-t border-slate-100 px-5 py-3 text-sm text-slate-600">
            <span class="font-semibold text-slate-900">Partida:</span>
            {{ $concept->partida?->building?->name ?: '-' }} / {{ $concept->partida?->code }} {{ $concept->partida?->name }}
        </div>
    </section>

    @if($statusTotals->isNotEmpty())
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Resumen por estado</h2>
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-5">
                @foreach($statusTotals as $status => $total)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClass($status) }}">
                            {{ str_replace('_', ' ', strtoupper($status)) }}
                        </span>
                        <div class="mt-3 text-lg font-bold text-slate-900">{{ number_format((float) $total['quantity'], 4) }}</div>
                        <div class="text-xs font-semibold text-slate-500">{{ number_format((int) $total['items']) }} partida(s)</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Reportes relacionados</h2>
        </div>

        <div class="divide-y divide-slate-100">
            @php $photoIndex = 0; @endphp
            @forelse($items as $item)
                @php
                    $report = $item->report;
                    $metadata = $report?->metadata ?? [];
                    $reviewNotes = $metadata['review_notes'] ?? null;
                @endphp
                <article class="space-y-4 px-5 py-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClass($report?->status) }}">
                                    {{ str_replace('_', ' ', strtoupper($report?->status ?? 'sin_estado')) }}
                                </span>
                                <span class="font-semibold text-slate-900">
                                    {{ $report?->empleado?->nombre ?? $report?->user?->name ?? 'Residente' }}
                                </span>
                                <span class="text-sm font-semibold text-blue-700">
                                    {{ number_format((float) $item->quantity, 4) }} {{ $item->unit ?: $concept->unit }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Enviado: {{ optional($report?->submitted_at ?? $item->created_at)->format('d/m/Y H:i') }}
                                @if($report?->reviewed_at)
                                    / Revisado por {{ $report->reviewedBy->name ?? '-' }} el {{ $report->reviewed_at->format('d/m/Y H:i') }}
                                @endif
                            </div>
                        </div>

                        @if($report?->status === 'aprobado')
                            <a href="{{ route('obra_civil.field-review.index', $obra) }}"
                               class="rounded-lg border border-[#0B265A] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:bg-blue-50">
                                Gestionar conversion
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="text-xs font-semibold uppercase text-slate-500">Notas del concepto</div>
                            <p class="mt-1 text-sm text-slate-700">{{ $item->notes ?: 'Sin notas del concepto.' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="text-xs font-semibold uppercase text-slate-500">Notas generales / revision</div>
                            <p class="mt-1 text-sm text-slate-700">{{ $report?->notes ?: 'Sin notas generales.' }}</p>
                            @if($reviewNotes)
                                <p class="mt-2 text-xs font-semibold text-slate-500">Revision: {{ $reviewNotes }}</p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 text-xs font-semibold uppercase text-slate-500">Fotos</div>
                        @if($item->photos->isEmpty())
                            <div class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                                Este reporte no tiene fotos.
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
                                @foreach($item->photos as $photo)
                                    @php $currentPhotoIndex = $photoIndex++; @endphp
                                    <button type="button"
                                            class="group overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-left shadow-sm transition hover:border-[#0B265A] hover:shadow-md"
                                            @click="open({{ $currentPhotoIndex }})">
                                        <img src="{{ Storage::disk('public')->url($photo->path) }}"
                                             alt="Foto reporte {{ $loop->iteration }}"
                                             class="h-32 w-full object-cover transition group-hover:scale-105">
                                        <div class="px-2 py-2 text-xs text-slate-600">
                                            <div class="font-semibold text-slate-800">Foto {{ $loop->iteration }}</div>
                                            <div>{{ $photo->size ? number_format($photo->size / 1024, 0) . ' KB' : 'Sin peso' }}</div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-sm text-slate-500">
                    Este concepto todavia no tiene reportes de campo.
                </div>
            @endforelse
        </div>
    </section>

    <div x-show="isOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4"
         role="dialog"
         aria-modal="true"
         @click.self="close()">
        <div class="relative flex h-full w-full max-w-6xl flex-col">
            <div class="mb-3 flex items-center justify-between gap-3 text-white">
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold" x-text="current()?.title || 'Foto del reporte'"></div>
                    <div class="truncate text-xs text-slate-300" x-text="current()?.caption || ''"></div>
                </div>
                <button type="button"
                        class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20"
                        @click="close()">
                    Cerrar
                </button>
            </div>

            <div class="relative flex min-h-0 flex-1 items-center justify-center">
                <button type="button"
                        class="absolute left-0 z-10 rounded-full bg-white/10 px-4 py-3 text-4xl leading-none text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-30"
                        @click="previous()"
                        :disabled="photos.length <= 1"
                        aria-label="Foto anterior">
                    ‹
                </button>

                <img :src="current()?.url"
                     :alt="current()?.caption || 'Foto del reporte'"
                     class="max-h-full max-w-full rounded-lg object-contain shadow-2xl">

                <button type="button"
                        class="absolute right-0 z-10 rounded-full bg-white/10 px-4 py-3 text-4xl leading-none text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-30"
                        @click="next()"
                        :disabled="photos.length <= 1"
                        aria-label="Foto siguiente">
                    ›
                </button>
            </div>

            <div class="mt-3 flex items-center justify-center gap-3 text-xs font-semibold text-slate-200">
                <span x-text="photos.length ? `${index + 1} / ${photos.length}` : '0 / 0'"></span>
                <span aria-hidden="true">·</span>
                <span x-text="current()?.size || ''"></span>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
function conceptReportGallery(photos) {
    return {
        photos: photos || [],
        index: 0,
        isOpen: false,
        open(index) {
            this.index = index;
            this.isOpen = true;
            document.body.classList.add('overflow-hidden');
        },
        close() {
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
        },
        current() {
            return this.photos[this.index] || null;
        },
        next() {
            if (!this.isOpen || this.photos.length <= 1) {
                return;
            }

            this.index = (this.index + 1) % this.photos.length;
        },
        previous() {
            if (!this.isOpen || this.photos.length <= 1) {
                return;
            }

            this.index = (this.index - 1 + this.photos.length) % this.photos.length;
        },
    };
}
</script>
@endpush




