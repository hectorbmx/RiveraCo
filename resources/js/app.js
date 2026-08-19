import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
function showGlobalSubmitLoader(message = 'Procesando...') {
    let overlay = document.getElementById('global-submit-loader');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'global-submit-loader';
        overlay.className = 'fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/55 px-4';
        overlay.innerHTML = `
            <div class="w-full max-w-sm rounded-lg bg-white p-6 text-center shadow-xl">
                <div class="mx-auto h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-[#0B265A]"></div>
                <div class="mt-4 text-base font-semibold text-slate-900" data-loader-message>Procesando...</div>
                <div class="mt-1 text-sm text-slate-500">Espera un momento.</div>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    const text = overlay.querySelector('[data-loader-message]');
    if (text) text.textContent = message;

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
}

window.showGlobalSubmitLoader = showGlobalSubmitLoader;

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.noLoading === 'true') return;
    if ((form.getAttribute('method') || 'GET').toUpperCase() === 'GET') return;
    if (form.target && form.target !== '_self') return;

    window.setTimeout(() => {
        if (event.defaultPrevented) return;

        const message = form.dataset.loadingMessage || 'Procesando solicitud...';
        showGlobalSubmitLoader(message);

        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-not-allowed');
        });
    }, 0);
});

