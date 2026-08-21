@extends('layouts.admin')

@section('title', 'Detalle de avance reportado')

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

    $statusLabel = str_replace('_', ' ', strtoupper($report->status ?: 'sin estado'));
    $item = $report->items->first();
    $concept = $item?->concept;
    $snapshot = $item?->concept_snapshot ?? [];
    $partida = $concept?->partida;
    $building = $partida?->building;
    $reviewNotes = $report->metadata['review_notes'] ?? null;
    $estimationId = $report->metadata['estimation_id'] ?? null;

    $galleryPhotos = $report->items
        ->flatMap(function ($item) use ($report) {
            $concept = $item->concept;
            $snapshot = $item->concept_snapshot ?? [];
            $code = $concept->excel_code ?? $snapshot['excel_code'] ?? '-';
            $unit = $item->unit ?: ($concept->unit ?? '');

            return $item->photos->map(function ($photo) use ($item, $report, $code, $unit) {
                return [
                    'url' => Storage::disk('public')->url($photo->path),
                    'title' => 'Reporte ' . optional($report->submitted_at ?? $item->created_at)->format('d/m/Y H:i'),
                    'caption' => trim($code . ' · ' . number_format((float) $item->quantity, 4) . ' ' . $unit),
                    'size' => $photo->size ? number_format($photo->size / 1024, 0) . ' KB' : 'Sin peso',
                ];
            });
        })
        ->values();
@endphp

<style>
    [x-cloak] { display: none !important; }
</style>

<div class="mx-auto max-w-6xl space-y-6" x-data="workReportGallery(@js($galleryPhotos))" @keydown.escape.window="close()" @keydown.arrow-left.window="previous()" @keydown.arrow-right.window="next()">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <div class="text-sm font-semibold text-slate-500">{{ $obra->clave_obra }} / {{ $obra->cliente->nombre_comercial ?? '-' }}</div>
            <h1 class="text-2xl font-bold text-[#0B265A]">Detalle de avance reportado</h1>
            <p class="text-sm text-slate-500">Registro individual capturado por el residente desde Ionic.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('obra_civil.work-reports.index', $obra) }}"
               class="rounded-lg border border-[#0B265A] px-4 py-2 text-sm font-semibold text-[#0B265A] hover:bg-blue-50">
                Volver a avances
            </a>
            {{--
            @if($concept)
                <a href="{{ route('obra_civil.concept.reports', [$obra, $concept]) }}"
                   class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Ver historico del concepto
                </a>
            @endif
            --}}
            @if(in_array($report->status, ['pendiente', 'en_revision'], true))
                <form method="POST"
                      action="{{ route('obra_civil.field-review.reports.approve', [$obra, $report]) }}"
                      data-loading-message="Aprobando avance..."
                      onsubmit="return confirm('Aprobar este reporte de avance?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                        Aprobar reporte
                    </button>
                </form>
            @else
                <span class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-500">
                    {{ $statusLabel }}
                </span>
            @endif
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-4">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Estado</div>
                <div class="mt-2">
                    <span class="rounded-full px-2 py-1 text-xs font-bold {{ $statusClass($report->status) }}">{{ $statusLabel }}</span>
                </div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Fecha envio</div>
                <div class="mt-1 font-semibold text-slate-900">{{ optional($report->submitted_at ?? $report->created_at)->format('d/m/Y H:i') }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Residente</div>
                <div class="mt-1 font-semibold text-slate-900">{{ $report->empleado->nombre ?? $report->user->name ?? 'Residente' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Fotos</div>
                <div class="mt-1 font-semibold text-slate-900">{{ number_format($galleryPhotos->count()) }}</div>
            </div>
        </div>

        @if($report->reviewed_at || $reviewNotes)
            <div class="border-t border-slate-100 px-5 py-3 text-sm text-slate-600">
                @if($report->reviewed_at)
                    Revisado por <span class="font-semibold text-slate-900">{{ $report->reviewedBy->name ?? '-' }}</span>
                    el {{ $report->reviewed_at->format('d/m/Y H:i') }}.
                @endif
                @if($reviewNotes)
                    <span class="font-semibold text-slate-900">Nota revision:</span> {{ $reviewNotes }}
                @endif
            </div>
        @endif
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Partida reportada</h2>
        </div>
        <div class="grid grid-cols-1 gap-4 p-5 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="font-mono text-sm font-bold text-blue-700">{{ $concept->excel_code ?? $snapshot['excel_code'] ?? '-' }}</div>
                <h3 class="mt-2 text-xl font-bold text-slate-900">{{ $concept->description ?? $snapshot['description'] ?? '-' }}</h3>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $building?->name ?: '-' }} / {{ $partida?->code }} {{ $partida?->name }}
                </p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="text-xs font-semibold uppercase text-slate-500">Cantidad reportada</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">
                    {{ number_format((float) ($item?->quantity ?? 0), 4) }}
                    <span class="text-base text-slate-500">{{ $item?->unit ?: $concept?->unit }}</span>
                </div>
                @if($report->status === 'convertido_a_estimacion' && $estimationId)
                    <a href="{{ route('obra_civil.estimations.show', [$obra, $estimationId]) }}" class="mt-3 inline-flex text-sm font-semibold text-blue-700 hover:underline">
                        {{ $report->metadata['estimation_folio'] ?? 'Ver estimacion' }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Notas del concepto</div>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $item?->notes ?: 'Sin notas del concepto.' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase text-slate-500">Notas generales</div>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $report->notes ?: 'Sin notas generales.' }}</p>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-slate-900">Fotos del reporte</h2>
        </div>
        <div class="p-5">
            @if($galleryPhotos->isEmpty())
                <div class="rounded-lg border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500">
                    Este reporte no tiene fotos.
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
                    @foreach($galleryPhotos as $photo)
                        <button type="button"
                                class="group overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-left shadow-sm transition hover:border-[#0B265A] hover:shadow-md"
                                @click="open({{ $loop->index }})">
                            <img src="{{ $photo['url'] }}"
                                 alt="Foto reporte {{ $loop->iteration }}"
                                 class="h-32 w-full object-cover transition group-hover:scale-105">
                            <div class="px-2 py-2 text-xs text-slate-600">
                                <div class="font-semibold text-slate-800">Foto {{ $loop->iteration }}</div>
                                <div>{{ $photo['size'] }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
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
function workReportGallery(photos) {
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

