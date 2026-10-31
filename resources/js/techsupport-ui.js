/** Componentes compartidos de TechSupport: sin dependencias de UI adicionales. */
const sidebarKey = 'techsupport.sidebar.collapsed';

function icon(name, className = 'ts-icon') {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('class', className);
    svg.setAttribute('aria-hidden', 'true');
    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    use.setAttribute('href', `#ts-icon-${name}`);
    svg.appendChild(use);
    return svg;
}

function initSidebar() {
    const shell = document.querySelector('#tsShell');
    const toggle = document.querySelector('#tsSidebarToggle');
    if (!shell || !toggle) return;

    const update = (collapsed) => {
        shell.classList.toggle('ts-is-collapsed', collapsed);
        toggle.setAttribute('aria-pressed', String(collapsed));
        toggle.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
        toggle.title = collapsed ? 'Expandir menú' : 'Contraer menú';
    };
    try { update(window.localStorage.getItem(sidebarKey) === 'true'); }
    catch { update(false); }
    toggle.addEventListener('click', () => {
        const collapsed = !shell.classList.contains('ts-is-collapsed');
        update(collapsed);
        try { window.localStorage.setItem(sidebarKey, String(collapsed)); } catch { /* privacidad */ }
    });
}

function initNotifications() {
    const flashMessages = Array.from(document.querySelectorAll('[data-ts-flash]'));
    const stack = document.createElement('div');
    stack.className = 'ts-toast-stack';
    stack.setAttribute('aria-label', 'Notificaciones de TechSupport');
    document.body.append(stack);

    function notify(message, tone = 'success') {
        const allowed = ['success', 'danger', 'info'];
        tone = allowed.includes(tone) ? tone : 'info';
        const toast = document.createElement('div');
        toast.className = 'ts-toast';
        toast.dataset.tone = tone;
        toast.setAttribute('role', tone === 'danger' ? 'alert' : 'status');
        const symbol = document.createElement('div');
        symbol.className = 'ts-toast-icon';
        symbol.append(icon(tone === 'success' ? 'check' : tone === 'danger' ? 'alert' : 'info'));
        const content = document.createElement('div');
        const heading = document.createElement('strong');
        heading.className = 'ts-toast-title';
        heading.textContent = tone === 'success' ? 'Operación realizada' : tone === 'danger' ? 'Acción no completada' : 'Información';
        const description = document.createElement('p');
        description.className = 'ts-toast-message';
        description.textContent = String(message);
        content.append(heading, description);
        const closeButton = document.createElement('button');
        closeButton.className = 'ts-toast-dismiss';
        closeButton.type = 'button';
        closeButton.setAttribute('aria-label', 'Cerrar notificación');
        closeButton.append(icon('close'));
        const progress = document.createElement('div');
        progress.className = 'ts-toast-progress';
        toast.append(symbol, content, closeButton, progress);
        stack.append(toast);

        let remaining = 5000;
        let started = Date.now();
        let timeoutId;
        let dismissed = false;
        const dismiss = () => {
            if (dismissed) return;
            dismissed = true;
            window.clearTimeout(timeoutId);
            toast.classList.add('ts-toast--leaving');
            window.setTimeout(() => toast.remove(), 260);
        };
        const resume = () => {
            if (dismissed) return;
            started = Date.now();
            timeoutId = window.setTimeout(dismiss, remaining);
            progress.style.animationPlayState = 'running';
        };
        const pause = () => {
            window.clearTimeout(timeoutId);
            remaining = Math.max(0, remaining - (Date.now() - started));
            progress.style.animationPlayState = 'paused';
        };
        closeButton.addEventListener('click', dismiss);
        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', (event) => {
            if (!toast.contains(event.relatedTarget)) resume();
        });
        resume();
        return toast;
    }

    // Solo ocultamos el fallback cuando la notificación ha sido construida.
    for (const flash of flashMessages) {
        notify(flash.textContent.trim(), flash.dataset.tone || 'success');
        flash.remove();
    }
    return notify;
}

function initConfirmDialog() {
    const dialog = document.querySelector('#tsConfirmDialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const title = dialog.querySelector('#tsConfirmTitle');
    const message = dialog.querySelector('#tsConfirmMessage');
    const accept = dialog.querySelector('[data-ts-accept]');
    const cancel = dialog.querySelector('[data-ts-cancel]');
    let pendingForm = null;
    let pendingSubmitter = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-ts-confirm]')) return;
        if (form.dataset.tsConfirmed === 'true') {
            delete form.dataset.tsConfirmed;
            return;
        }
        event.preventDefault();
        pendingForm = form;
        pendingSubmitter = event.submitter || null;
        title.textContent = form.dataset.tsConfirmTitle || 'Confirmar acción';
        message.textContent = form.dataset.tsConfirm || '¿Deseas continuar?';
        accept.textContent = form.dataset.tsConfirmAction || 'Confirmar';
        accept.className = `btn ${form.dataset.tsConfirmVariant === 'success' ? 'btn-success' : 'btn-danger'}`;
        dialog.showModal();
        cancel.focus();
    }, true);

    cancel.addEventListener('click', () => dialog.close('cancel'));
    accept.addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        const submitter = pendingSubmitter;
        dialog.close('accepted');
        pendingForm = null;
        pendingSubmitter = null;
        form.dataset.tsConfirmed = 'true';
        if (submitter && submitter.form === form) form.requestSubmit(submitter);
        else form.requestSubmit();
    });
    dialog.addEventListener('close', () => {
        if (dialog.returnValue !== 'accepted') { pendingForm = null; pendingSubmitter = null; }
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close('cancel');
    });
}

function init() {
    initSidebar();
    const notify = initNotifications();
    initConfirmDialog();
    // Permite que los módulos posteriores muestren notificaciones sin duplicar componentes.
    window.TechSupport = Object.freeze({ notify });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
else init();
