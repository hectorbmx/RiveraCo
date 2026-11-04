@if(session('success'))
    <div id="resultado-guardado" tabindex="-1" role="status" aria-live="polite"
         class="mb-5 rounded-xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-emerald-900">
        <p class="font-semibold">Guardado correcto</p>
        <p class="mt-1 text-sm">{{ session('success') }}</p>
    </div>
@endif
