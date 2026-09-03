@props([
    'submitLabel' => 'Filtrar',
    'clearUrl' => null,
    'span' => 'md:col-span-3',
])

<div class="{{ $span }} flex flex-wrap justify-start md:justify-end gap-2">
    {{ $slot }}

    <button type="submit" class="bg-[#FFC107] text-[#0B265A] font-semibold px-4 py-2 rounded-xl text-sm hover:opacity-90 shadow-sm">
        {{ $submitLabel }}
    </button>

    @if($clearUrl)
        <a href="{{ $clearUrl }}" class="px-4 py-2 rounded-xl text-sm border border-white/25 bg-white/10 text-white hover:bg-white/20 shadow-sm">
            Limpiar
        </a>
    @endif
</div>
