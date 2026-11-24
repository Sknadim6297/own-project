const toastRegion = document.querySelector('[data-yuktione-toast-region]');
const feedbackRoot = document.querySelector('[data-yuktione-feedback]');
const toastLimit = 4;
const toastEntries = new Map();

const icons = {
    success: '+',
    error: '!',
    warning: '!',
    info: 'i',
};

const titles = {
    success: 'Success',
    error: 'Something went wrong',
    warning: 'Please note',
    info: 'Information',
};

function normalizedType(type) {
    return Object.hasOwn(icons, type) ? type : 'info';
}

function removeToast(key, element) {
    const entry = toastEntries.get(key);
    if (!entry || entry.element !== element) {
        return;
    }

    clearTimeout(entry.timer);
    toastEntries.delete(key);
    element.classList.add('yk-toast--leaving');
    element.addEventListener('animationend', () => element.remove(), { once: true });
    window.setTimeout(() => element.remove(), 180);
}

function showToast(type, message, options = {}) {
    if (!toastRegion || typeof message !== 'string' || !message.trim()) {
        return null;
    }

    const safeType = normalizedType(type);
    const key = `${safeType}:${message.trim()}`;
    const existing = toastEntries.get(key);
    if (existing) {
        return existing.element;
    }

    while (toastEntries.size >= toastLimit) {
        const [oldestKey, oldest] = toastEntries.entries().next().value;
        removeToast(oldestKey, oldest.element);
    }

    const toast = document.createElement('section');
    toast.className = `yk-toast yk-toast--${safeType}`;
    toast.tabIndex = -1;
    toast.setAttribute('role', safeType === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-live', safeType === 'error' ? 'assertive' : 'polite');

    const icon = document.createElement('span');
    icon.className = 'yk-toast__icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = icons[safeType];

    const body = document.createElement('div');
    body.className = 'yk-toast__body';

    const title = document.createElement('p');
    title.className = 'yk-toast__title';
    title.textContent = typeof options.title === 'string' ? options.title : titles[safeType];

    const text = document.createElement('div');
    text.className = 'yk-toast__message';
    text.textContent = message;
    body.append(title, text);

    if (options.action && typeof options.action.label === 'string' && typeof options.action.onClick === 'function') {
        const action = document.createElement('button');
        action.type = 'button';
        action.className = 'yk-toast__action';
        action.textContent = options.action.label;
        action.addEventListener('click', () => {
            options.action.onClick();
            removeToast(key, toast);
        });
        body.append(action);
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'yk-feedback__close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.textContent = '×';
    close.addEventListener('click', () => removeToast(key, toast));

    toast.append(icon, body, close);
    toastRegion.append(toast);

    const duration = options.duration === 0
        ? 0
        : (Number.isFinite(options.duration) && options.duration > 0 ? options.duration : 5000);
    const entry = { element: toast, timer: null, remaining: duration, startedAt: Date.now() };
    const startTimer = () => {
        if (!entry.remaining || !toast.isConnected) {
            return;
        }
        entry.startedAt = Date.now();
        entry.timer = window.setTimeout(() => removeToast(key, toast), entry.remaining);
    };
    const pauseTimer = () => {
        if (!entry.timer) {
            return;
        }
        clearTimeout(entry.timer);
        entry.timer = null;
        entry.remaining = Math.max(0, entry.remaining - (Date.now() - entry.startedAt));
    };
    toast.addEventListener('mouseenter', pauseTimer);
    toast.addEventListener('mouseleave', startTimer);
    toast.addEventListener('focusin', pauseTimer);
    toast.addEventListener('focusout', (event) => {
        if (!toast.contains(event.relatedTarget)) {
            startTimer();
        }
    });
    toastEntries.set(key, entry);
    startTimer();
    return toast;
}

function createDialog(config = {}, mode = 'confirm') {
    if (!feedbackRoot) {
        return Promise.resolve(false);
    }

    const variant = ['primary', 'danger', 'success', 'warning', 'info'].includes(config.variant)
        ? config.variant
        : 'primary';
    const escapable = config.escapable ?? mode !== 'alert';
    const backdropDismiss = config.backdropDismiss ?? mode !== 'alert';
    const dialog = document.createElement('dialog');
    dialog.className = `yk-dialog yk-dialog--${variant}`;
    dialog.setAttribute('aria-labelledby', 'yk-dialog-title');
    dialog.setAttribute('aria-describedby', 'yk-dialog-message');
    dialog.setAttribute('aria-modal', 'true');
    if (mode === 'alert' || variant === 'danger') {
        dialog.setAttribute('role', 'alertdialog');
    }

    const panel = document.createElement('div');
    panel.className = 'yk-dialog__panel';
    const icon = document.createElement('div');
    icon.className = 'yk-dialog__icon';
    icon.setAttribute('aria-hidden', 'true');
    icon.textContent = variant === 'danger' ? '!' : icons[variant] || 'i';

    const title = document.createElement('h2');
    title.id = 'yk-dialog-title';
    title.className = 'yk-dialog__title';
    title.textContent = typeof config.title === 'string' ? config.title : (mode === 'alert' ? 'Notice' : 'Confirm action');

    const message = document.createElement('p');
    message.id = 'yk-dialog-message';
    message.className = 'yk-dialog__message';
    message.textContent = typeof config.message === 'string' ? config.message : '';

    const actions = document.createElement('div');
    actions.className = 'yk-dialog__actions';
    const cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.className = 'yk-dialog__button';
    cancel.textContent = typeof config.cancelText === 'string' ? config.cancelText : 'Cancel';

    const confirm = document.createElement('button');
    confirm.type = 'button';
    confirm.className = `yk-dialog__button yk-dialog__button--${variant === 'danger' ? 'danger' : 'primary'}`;
    confirm.textContent = typeof config.confirmText === 'string'
        ? config.confirmText
        : (mode === 'alert' ? 'Got it' : 'Continue');

    actions.append(cancel, confirm);
    panel.append(icon, title, message, actions);
    dialog.append(panel);
    feedbackRoot.append(dialog);

    return new Promise((resolve) => {
        let settled = false;
        let finished = false;
        let resultValue = false;
        const returnFocus = document.activeElement;
        const finish = () => {
            if (finished) {
                return;
            }
            finished = true;
            dialog.remove();
            if (returnFocus instanceof HTMLElement && returnFocus.isConnected) {
                returnFocus.focus();
            }
            resolve(resultValue);
        };
        const settle = (value) => {
            if (settled) {
                return;
            }
            settled = true;
            resultValue = value;
            if (dialog.open) {
                dialog.close();
            }
            finish();
        };

        cancel.hidden = mode === 'alert';
        cancel.addEventListener('click', () => settle(false));
        dialog.addEventListener('cancel', (event) => {
            if (!escapable) {
                event.preventDefault();
                return;
            }
            settle(false);
        });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog && backdropDismiss) {
                settle(false);
            }
        });
        dialog.addEventListener('close', () => {
            if (!settled) {
                settled = true;
            }
            finish();
        }, { once: true });

        confirm.addEventListener('click', async () => {
            if (typeof config.onConfirm !== 'function') {
                settle(true);
                return;
            }

            confirm.disabled = true;
            cancel.disabled = true;
            confirm.textContent = typeof config.loadingText === 'string' ? config.loadingText : 'Please wait…';
            try {
                await config.onConfirm();
                settle(true);
            } catch (error) {
                confirm.disabled = false;
                cancel.disabled = false;
                confirm.textContent = typeof config.confirmText === 'string'
                    ? config.confirmText
                    : (mode === 'alert' ? 'Got it' : 'Continue');
                showToast('error', error instanceof Error ? error.message : 'The action could not be completed.');
            }
        });

        try {
            dialog.showModal();
            confirm.focus();
        } catch (error) {
            dialog.remove();
            showToast('error', 'The dialog could not be opened in this browser.');
            resolve(false);
        }
    });
}

window.YuktioneToast = Object.freeze({
    show: showToast,
    success: (message, options) => showToast('success', message, options),
    error: (message, options) => showToast('error', message, options),
    warning: (message, options) => showToast('warning', message, options),
    info: (message, options) => showToast('info', message, options),
});

window.YuktioneDialog = Object.freeze({
    confirm: (options) => createDialog(options, 'confirm'),
    destructive: (options) => createDialog({ ...options, variant: 'danger' }, 'confirm'),
    alert: (options) => createDialog(options, 'alert'),
});

feedbackRoot?.addEventListener('click', (event) => {
    if (event.target instanceof Element) {
        event.target.closest('[data-yuktione-dismiss]')?.closest('.yk-inline-alert')?.remove();
    }
});

feedbackRoot?.querySelectorAll('[data-yuktione-flash]').forEach((flash) => {
    const type = flash.getAttribute('data-yuktione-flash');
    showToast(type, flash.textContent.trim(), {
        title: flash.getAttribute('data-yuktione-flash-title') || undefined,
    });
});
