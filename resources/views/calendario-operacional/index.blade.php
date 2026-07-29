@extends('layouts.admin')

@section('title', 'Calendario operacional')

@section('content')
@php
    $categoriaColores = [
        'obras' => '#2563eb',
        'vehiculos' => '#059669',
        'maquinaria' => '#7c3aed',
        'seguros' => '#dc2626',
        'ordenes_compra' => '#d97706',
        'rh' => '#0891b2',
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-5" x-data="calendarioOperacional({
    eventsUrl: @js(route('calendario-operacional.events')),
    categorias: @js($categorias),
    colores: @js($categoriaColores),
})" x-init="init()">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Calendario operacional</h1>
            <p class="text-sm text-slate-500">Fechas clave de obras, servicios, seguros, compras y RH.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="goToday()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Hoy</button>
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 bg-white">
                <button type="button" @click="previousMonth()" class="px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" aria-label="Mes anterior">&lt;</button>
                <button type="button" @click="nextMonth()" class="border-l border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" aria-label="Mes siguiente">&gt;</button>
            </div>
            <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 bg-white">
                <button type="button" @click="viewMode = 'month'" :class="viewMode === 'month' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'" class="px-3 py-2 text-sm font-semibold">Mes</button>
                <button type="button" @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-50'" class="border-l border-slate-300 px-3 py-2 text-sm font-semibold">Lista</button>
            </div>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[280px_1fr]">
        <aside class="space-y-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Categorias</div>
                <div class="mt-3 space-y-2">
                    <template x-for="(label, key) in categorias" :key="key">
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
                            <input type="checkbox" class="rounded border-slate-300" :value="key" x-model="activeCategories" @change="loadEvents()">
                            <span class="h-3 w-3 rounded-full" :style="`background-color: ${colores[key] || '#64748b'}`"></span>
                            <span class="text-sm font-medium text-slate-700" x-text="label"></span>
                        </label>
                    </template>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resumen</div>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <div class="text-xs text-slate-500">Eventos</div>
                        <div class="text-xl font-semibold text-slate-900" x-text="events.length"></div>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <div class="text-xs text-slate-500">Visibles</div>
                        <div class="text-xl font-semibold text-slate-900" x-text="visibleEvents.length"></div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="calendar-type-filter">Tipo</label>
                <select id="calendar-type-filter" x-model="typeFilter" @change="syncTypeFilter()" class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-[#0B265A] focus:ring-[#0B265A]">
                    <option value="">Todos</option>
                    <template x-for="type in availableTypes" :key="type">
                        <option :value="type" x-text="typeLabel(type)"></option>
                    </template>
                </select>
            </div>
        </aside>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900" x-text="monthLabel"></h2>
                    <p class="text-sm text-slate-500" x-text="rangeLabel"></p>
                </div>
                <div class="text-sm font-medium" :class="loading ? 'text-slate-500' : (error ? 'text-red-700' : 'text-slate-500')" x-text="loading ? 'Cargando eventos' : (error || 'Actualizado')"></div>
            </div>

            <div x-show="viewMode === 'month'" class="overflow-x-auto">
                <div class="min-w-[880px]">
                    <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <template x-for="day in weekDays" :key="day">
                            <div class="px-3 py-2" x-text="day"></div>
                        </template>
                    </div>
                    <div class="grid grid-cols-7">
                        <template x-for="day in calendarDays" :key="day.key">
                            <div class="min-h-[132px] border-b border-r border-slate-100 p-2" :class="day.inMonth ? 'bg-white' : 'bg-slate-50 text-slate-400'">
                                <div class="flex items-center justify-between">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold" :class="day.isToday ? 'bg-slate-900 text-white' : 'text-slate-700'" x-text="day.day"></span>
                                    <span class="text-[11px] text-slate-400" x-show="eventsByDate[day.date]?.length" x-text="eventsByDate[day.date]?.length"></span>
                                </div>
                                <div class="mt-2 space-y-1">
                                    <template x-for="event in (eventsByDate[day.date] || []).slice(0, 4)" :key="event.id">
                                        <button type="button" @click="openEvent(event)" class="block w-full rounded-md border-l-4 bg-slate-50 px-2 py-1 text-left text-xs hover:bg-slate-100" :style="`border-left-color: ${event.color}`" :title="event.title">
                                            <div class="truncate font-semibold text-slate-800" x-text="event.title"></div>
                                            <div class="truncate text-[11px] text-slate-500" x-text="categoryLabel(event.category) + ' / ' + typeLabel(event.type)"></div>
                                        </button>
                                    </template>
                                    <button type="button" x-show="(eventsByDate[day.date] || []).length > 4" @click="viewMode = 'list'; selectedDate = day.date" class="text-xs font-semibold text-blue-700 hover:underline" x-text="'Ver ' + ((eventsByDate[day.date] || []).length - 4) + ' mas'"></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div x-show="viewMode === 'list'" class="divide-y divide-slate-100">
                <template x-if="selectedDate">
                    <div class="flex items-center justify-between bg-slate-50 px-4 py-3">
                        <div class="text-sm font-semibold text-slate-700" x-text="'Eventos del ' + formatLongDate(selectedDate)"></div>
                        <button type="button" @click="selectedDate = null" class="text-sm font-semibold text-blue-700 hover:underline">Ver todo el mes</button>
                    </div>
                </template>
                <template x-for="event in listEvents" :key="event.id">
                    <button type="button" @click="openEvent(event)" class="block w-full px-4 py-3 text-left hover:bg-slate-50">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-900" x-text="event.title"></div>
                                <div class="mt-1 text-xs text-slate-500" x-text="categoryLabel(event.category) + ' / ' + typeLabel(event.type)"></div>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                <span class="h-2.5 w-2.5 rounded-full" :style="`background-color: ${event.color}`"></span>
                                <span x-text="formatLongDate(event.starts_at)"></span>
                            </div>
                        </div>
                    </button>
                </template>
                <div x-show="!loading && listEvents.length === 0" class="px-4 py-10 text-center text-sm text-slate-500">
                    No hay eventos con los filtros seleccionados.
                </div>
            </div>
        </section>
    </div>

    <div x-show="selectedEvent" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" @keydown.escape.window="closeEventModal()">
        <div class="absolute inset-0" @click="closeEventModal()"></div>
        <div class="relative w-full max-w-lg rounded-lg bg-white shadow-xl" x-show="selectedEvent" x-transition.scale>
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div class="min-w-0">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500" x-text="selectedEvent ? categoryLabel(selectedEvent.category) + ' / ' + typeLabel(selectedEvent.type) : ''"></div>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900" x-text="selectedEvent?.title"></h3>
                </div>
                <button type="button" @click="closeEventModal()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cerrar</button>
            </div>

            <div class="space-y-3 px-5 py-4 text-sm">
                <div class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 px-3 py-2">
                    <span class="font-medium text-slate-600">Fecha</span>
                    <span class="text-right font-semibold text-slate-900" x-text="selectedEvent ? formatLongDate(selectedEvent.starts_at) : ''"></span>
                </div>

                <template x-for="row in selectedEventMetaRows" :key="row.label">
                    <div class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 px-3 py-2">
                        <span class="font-medium text-slate-600" x-text="row.label"></span>
                        <span class="text-right font-semibold text-slate-900" x-text="row.value"></span>
                    </div>
                </template>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" @click="closeEventModal()" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cerrar</button>
                <a x-show="selectedEvent?.url" :href="selectedEvent?.url" class="rounded-lg bg-slate-900 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-slate-700">Ver empleado</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function calendarioOperacional(config) {
        return {
            eventsUrl: config.eventsUrl,
            categorias: config.categorias,
            colores: config.colores,
            activeCategories: Object.keys(config.categorias),
            events: [],
            loading: false,
            error: '',
            currentDate: new Date(),
            viewMode: 'month',
            typeFilter: '',
            selectedDate: null,
            selectedEvent: null,
            weekDays: ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'],

            init() {
                this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
                this.loadEvents();
            },

            get monthStart() {
                return new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
            },

            get monthEnd() {
                return new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);
            },

            get monthLabel() {
                return new Intl.DateTimeFormat('es-MX', { month: 'long', year: 'numeric' }).format(this.monthStart);
            },

            get rangeLabel() {
                return this.formatLongDate(this.toDateString(this.monthStart)) + ' - ' + this.formatLongDate(this.toDateString(this.monthEnd));
            },

            get availableTypes() {
                return [...new Set(this.events.map((event) => event.type).filter(Boolean))].sort();
            },

            get visibleEvents() {
                return this.events.filter((event) => !this.typeFilter || event.type === this.typeFilter);
            },

            get eventsByDate() {
                return this.visibleEvents.reduce((grouped, event) => {
                    const key = String(event.starts_at || '').slice(0, 10);
                    if (!grouped[key]) grouped[key] = [];
                    grouped[key].push(event);
                    return grouped;
                }, {});
            },

            get calendarDays() {
                const first = this.monthStart;
                const offset = (first.getDay() + 6) % 7;
                const start = new Date(first);
                start.setDate(first.getDate() - offset);
                const days = [];
                const today = this.toDateString(new Date());

                for (let i = 0; i < 42; i++) {
                    const date = new Date(start);
                    date.setDate(start.getDate() + i);
                    const key = this.toDateString(date);
                    days.push({
                        key,
                        date: key,
                        day: date.getDate(),
                        inMonth: date.getMonth() === this.currentDate.getMonth(),
                        isToday: key === today,
                    });
                }

                return days;
            },

            get listEvents() {
                const events = this.selectedDate
                    ? this.visibleEvents.filter((event) => String(event.starts_at || '').slice(0, 10) === this.selectedDate)
                    : this.visibleEvents;

                return [...events].sort((a, b) => String(a.starts_at).localeCompare(String(b.starts_at)) || String(a.title).localeCompare(String(b.title)));
            },

            async loadEvents() {
                this.loading = true;
                this.error = '';
                this.selectedDate = null;

                if (this.activeCategories.length === 0) {
                    this.events = [];
                    this.loading = false;
                    return;
                }

                const params = new URLSearchParams({
                    start: this.toDateString(this.monthStart),
                    end: this.toDateString(this.monthEnd),
                });

                this.activeCategories.forEach((category) => params.append('categories[]', category));
                if (this.typeFilter) params.append('types[]', this.typeFilter);

                try {
                    const response = await fetch(`${this.eventsUrl}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) throw new Error('No se pudieron cargar los eventos');

                    const payload = await response.json();
                    this.events = payload.events || [];
                    if (this.typeFilter && !this.availableTypes.includes(this.typeFilter)) {
                        this.typeFilter = '';
                    }
                } catch (error) {
                    this.events = [];
                    this.error = error.message || 'No se pudieron cargar los eventos';
                } finally {
                    this.loading = false;
                }
            },

            syncTypeFilter() {
                this.selectedDate = null;
            },

            get selectedEventMetaRows() {
                if (!this.selectedEvent) return [];

                const meta = this.selectedEvent.meta || {};
                const rows = [];

                if (meta.puesto) rows.push({ label: 'Puesto', value: meta.puesto });
                if (meta.area) rows.push({ label: 'Area', value: meta.area });
                if (meta.fecha_ingreso) rows.push({ label: 'Fecha de ingreso', value: this.formatLongDate(meta.fecha_ingreso) });
                if (meta.anios) rows.push({ label: 'Anios cumplidos', value: meta.anios });

                return rows;
            },

            openEvent(event) {
                if (event.category !== 'rh' && event.url) {
                    window.location.href = event.url;
                    return;
                }

                this.selectedEvent = event;
            },

            closeEventModal() {
                this.selectedEvent = null;
            },

            previousMonth() {
                this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
                this.loadEvents();
            },

            nextMonth() {
                this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
                this.loadEvents();
            },

            goToday() {
                const now = new Date();
                this.currentDate = new Date(now.getFullYear(), now.getMonth(), 1);
                this.loadEvents();
            },

            categoryLabel(category) {
                return this.categorias[category] || category;
            },

            typeLabel(type) {
                const labels = {
                    inicio_programado: 'Inicio programado',
                    inicio_real: 'Inicio real',
                    fin_programado: 'Fin programado',
                    fin_real: 'Fin real',
                    servicio_programado: 'Servicio programado',
                    inicio_vigencia: 'Inicio vigencia',
                    vencimiento: 'Vencimiento',
                    fecha_oc: 'Fecha OC',
                    autorizacion: 'Autorizacion',
                    cumpleanos: 'Cumpleanos',
                    aniversario_laboral: 'Aniversario laboral',
                };

                return labels[type] || String(type || '').replaceAll('_', ' ');
            },

            formatLongDate(value) {
                const date = this.parseDate(value);
                return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
            },

            parseDate(value) {
                if (value instanceof Date) return value;
                const text = String(value || '').slice(0, 10);
                const parts = text.split('-').map(Number);
                return new Date(parts[0], (parts[1] || 1) - 1, parts[2] || 1);
            },

            toDateString(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },
        };
    }
</script>
@endpush