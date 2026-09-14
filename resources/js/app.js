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

function hideGlobalSubmitLoader() {
    const overlay = document.getElementById('global-submit-loader');
    if (!overlay) return;

    overlay.classList.add('hidden');
    overlay.classList.remove('flex');

    const buttons = document.querySelectorAll('button[type="submit"], button:not([type])');
    buttons.forEach((button) => {
        button.disabled = false;
        button.classList.remove('opacity-70', 'cursor-not-allowed');
    });
}

function isModifiedNavigationClick(event) {
    return event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey;
}

function shouldShowNavigationLoader(link) {
    if (!(link instanceof HTMLAnchorElement)) return false;
    if (link.closest('[data-no-loading="true"], [data-no-loader="true"]')) return false;
    if (link.hasAttribute('download')) return false;
    if (link.target && link.target !== '_self') return false;

    const rawHref = link.getAttribute('href') || '';
    const trimmedHref = rawHref.trim();

    if (!trimmedHref || trimmedHref === '#') return false;
    if (trimmedHref.startsWith('#')) return false;
    if (/^(javascript:|mailto:|tel:|sms:)/i.test(trimmedHref)) return false;

    let url;
    try {
        url = new URL(trimmedHref, window.location.href);
    } catch (_) {
        return false;
    }

    if (url.origin !== window.location.origin) return false;

    const current = new URL(window.location.href);
    const samePage = url.pathname === current.pathname && url.search === current.search;
    if (samePage && url.hash && url.hash !== current.hash) return false;
    if (samePage && url.hash === current.hash) return false;

    return true;
}

let globalNavigationLoading = false;
let globalNavigationTimeout = null;

function showGlobalNavigationLoader(message = 'Cargando pagina...') {
    showGlobalSubmitLoader(message);
}

window.showGlobalSubmitLoader = showGlobalSubmitLoader;
window.hideGlobalSubmitLoader = hideGlobalSubmitLoader;
window.showGlobalNavigationLoader = showGlobalNavigationLoader;
window.addEventListener('pageshow', () => {
    globalNavigationLoading = false;
    if (globalNavigationTimeout) {
        window.clearTimeout(globalNavigationTimeout);
        globalNavigationTimeout = null;
    }
    hideGlobalSubmitLoader();
});

document.addEventListener('click', (event) => {
    if (isModifiedNavigationClick(event)) return;

    const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (!shouldShowNavigationLoader(link)) return;

    if (globalNavigationLoading) {
        event.preventDefault();
        event.stopImmediatePropagation();
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    globalNavigationLoading = true;
    const message = link.dataset.loadingMessage || 'Cargando pagina...';
    showGlobalNavigationLoader(message);

    if (globalNavigationTimeout) {
        window.clearTimeout(globalNavigationTimeout);
    }

    globalNavigationTimeout = window.setTimeout(() => {
        if (!globalNavigationLoading) return;

        globalNavigationLoading = false;
        hideGlobalSubmitLoader();
    }, 10000);

    window.setTimeout(() => {
        window.location.assign(link.href);
    }, 30);
});

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

