<button type="button" @click="alternarPartida(@js($plegable))" :aria-expanded="!plegadas[@js($plegable)]"
        aria-label="Mostrar u ocultar filas de {{ $nombrePlegable }}"
        x-text="plegadas[@js($plegable)] ? 'Mostrar filas' : 'Ocultar filas'"
        class="rounded-md border border-slate-400 bg-white px-2.5 py-1 text-xs font-semibold normal-case text-[#0B265A] hover:bg-slate-100">
    Ocultar filas
</button>
