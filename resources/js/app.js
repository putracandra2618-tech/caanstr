document.addEventListener('DOMContentLoaded', () => {
    initQtyStepper();
    initPasswordToggle();
    initReveal();
    initToasts();
    window.copyText = copyText;
});

function initQtyStepper() {
    document.querySelectorAll('[data-qty-stepper]').forEach((stepper) => {
        const input = stepper.querySelector('[data-qty-input]');
        if (!input) return;
        const min = parseInt(input.min) || 1;
        const max = parseInt(input.max) || 10;

        stepper.querySelectorAll('[data-qty-btn]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const delta = parseInt(btn.dataset.qtyBtn) || 0;
                const value = Math.max(min, Math.min(max, (parseInt(input.value) || min) + delta));
                input.value = value;
            });
        });
    });
}

function initPasswordToggle() {
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.togglePassword);
            if (!target) return;
            const visible = target.type === 'text';
            target.type = visible ? 'password' : 'text';
            btn.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
        });
    });
}

function initReveal() {
    const elements = document.querySelectorAll('.fade-in:not(.is-visible)');
    if (!elements.length) return;

    if (!('IntersectionObserver' in window)) {
        elements.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.08, rootMargin: '0px 0px -60px 0px' }
    );

    elements.forEach((el) => observer.observe(el));
}

const TOAST_STYLES = {
    success: {
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-4.5"/></svg>',
        color: 'text-forest-600',
    },
    error: {
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>',
        color: 'text-red-400',
    },
    info: {
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>',
        color: 'text-blue-400',
    },
};

function initToasts() {
    const region = document.getElementById('toast-region');
    if (!region) return;

    document.querySelectorAll('.toast-message').forEach((el) => {
        const type = el.dataset.toastType || 'info';
        const message = el.dataset.toastMessage || '';
        showToast(message, type);
        el.remove();
    });
}

function showToast(message, type = 'info') {
    const region = document.getElementById('toast-region');
    if (!region || !message) return;

    const style = TOAST_STYLES[type] || TOAST_STYLES.info;
    const toast = document.createElement('div');
    toast.className =
        'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-stone-200 bg-white px-4 py-3 shadow-lift sm:w-auto';

    toast.innerHTML = `
        <span class="mt-0.5 shrink-0 ${style.color}">${style.icon}</span>
        <p class="text-sm font-medium text-stone-800">${escapeHtml(message)}</p>
        <button type="button" class="ml-1 shrink-0 text-stone-400 transition hover:text-stone-700" aria-label="Tutup">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>`;

    const close = () => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(() => toast.remove(), 200);
    };

    toast.querySelector('button').addEventListener('click', close);
    region.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });
    });

    setTimeout(close, 4000);
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
            .writeText(text)
            .then(() => showToast('Berhasil disalin ke clipboard', 'success'))
            .catch(() => fallbackCopy(text));
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        showToast('Berhasil disalin ke clipboard', 'success');
    } catch (e) {
        showToast('Gagal menyalin', 'error');
    }
    document.body.removeChild(textarea);
}
