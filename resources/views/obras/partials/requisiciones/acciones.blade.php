<div class="flex items-center justify-end gap-2">
    <a href="{{ route('obras.requisiciones.imprimir', [$obra, $requisicion]) }}"
       title="Imprimir requisición" aria-label="Imprimir requisición {{ $requisicion->folio }}"
       class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-[#0B265A] transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8V3h10v5M7 17H4V8h16v9h-3M7 14h10v7H7zM17 11h.01" />
        </svg>
    </a>
    <a href="{{ route('obras.requisiciones.show', [$obra, $requisicion]) }}"
       title="Ver / editar requisición" aria-label="Ver o editar requisición {{ $requisicion->folio }}"
       class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-[#0B265A] transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m16 4 4 4M4 20l5-1L20 8a2.83 2.83 0 0 0-4-4L5 15l-1 5Z" />
        </svg>
    </a>
</div>
