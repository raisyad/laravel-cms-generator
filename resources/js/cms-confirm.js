/**
 * Central confirmation controller for the CMS.
 * Forms opt in by adding data-cms-confirm="[create|update|delete|logout]"
 * and optional data-cms-confirm-message for custom copy.
 */

const IGNORED_FIELDS = new Set(['_token', '_method']);
const ALWAYS_CONFIRM_ACTIONS = new Set(['create', 'delete', 'logout']);
const ACTION_MESSAGES = {
    create: 'Apakah Anda yakin ingin membuat data ini?',
    update: 'Ada perubahan pada data. Apakah Anda yakin ingin menyimpan perubahan?',
    delete: 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.',
    logout: 'Apakah Anda yakin ingin logout dari sistem?',
    default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
};

const MODAL_IDS = {
    overlay: 'cms-confirm-overlay',
    dialog: 'cms-confirm-dialog',
    message: 'cms-confirm-message',
    confirm: 'cms-confirm-confirm',
    cancel: 'cms-confirm-cancel',
};

const isDev = typeof import.meta !== 'undefined' && import.meta?.env?.DEV;

const modalRefs = {
    initialized: false,
    available: false,
    overlay: null,
    dialog: null,
    message: null,
    confirmBtn: null,
    cancelBtn: null,
    isVisible: false,
    previouslyFocused: null,
    handleKeydown: null,
    handleOverlayClick: null,
};

const formSnapshots = new WeakMap();

const logDevError = (message, error) => {
    if (isDev) {
        console.error(`[cms-confirm] ${message}`, error);
    }
};

const shouldIgnoreField = (name) => {
    if (!name) {
        return true;
    }

    if (IGNORED_FIELDS.has(name)) {
        return true;
    }

    return name.startsWith('_');
};

const normalizeValue = (value) => {
    if (value instanceof File) {
        return value.name || '';
    }

    return value ?? '';
};

const serializeForm = (form) => {
    const formData = new FormData(form);
    const entries = {};

    for (const [key, value] of formData.entries()) {
        if (shouldIgnoreField(key)) {
            continue;
        }

        const normalized = normalizeValue(value);

        if (Object.prototype.hasOwnProperty.call(entries, key)) {
            const current = entries[key];

            if (Array.isArray(current)) {
                current.push(normalized);
            } else {
                entries[key] = [current, normalized];
            }
        } else {
            entries[key] = normalized;
        }
    }

    return JSON.stringify(entries);
};

const hasFormChanged = (form, initialSnapshot) => {
    try {
        const currentSnapshot = serializeForm(form);
        return currentSnapshot !== initialSnapshot;
    } catch (error) {
        logDevError('Failed to compare form snapshot', error);
        return true;
    }
};

const getConfirmMessage = (actionType, customMessage) => {
    if (customMessage && customMessage.trim().length > 0) {
        return customMessage;
    }

    return ACTION_MESSAGES[actionType] || ACTION_MESSAGES.default;
};

const ensureModalElements = () => {
    if (modalRefs.initialized) {
        return modalRefs;
    }

    modalRefs.overlay = document.getElementById(MODAL_IDS.overlay);
    modalRefs.dialog = document.getElementById(MODAL_IDS.dialog);
    modalRefs.message = document.getElementById(MODAL_IDS.message);
    modalRefs.confirmBtn = document.getElementById(MODAL_IDS.confirm);
    modalRefs.cancelBtn = document.getElementById(MODAL_IDS.cancel);

    modalRefs.available = Boolean(
        modalRefs.overlay &&
            modalRefs.message &&
            modalRefs.confirmBtn &&
            modalRefs.cancelBtn
    );
    modalRefs.initialized = true;

    if (!modalRefs.available) {
        logDevError(
            'Confirmation modal markup not found. Falling back to native confirm.',
            null
        );
    }

    return modalRefs;
};

const hideModal = () => {
    if (!modalRefs.available || !modalRefs.isVisible) {
        return;
    }

    modalRefs.isVisible = false;
    modalRefs.overlay.classList.add('hidden');
    modalRefs.overlay.classList.remove('flex');
    modalRefs.overlay.setAttribute('aria-hidden', 'true');

    if (modalRefs.handleKeydown) {
        document.removeEventListener('keydown', modalRefs.handleKeydown);
    }

    if (modalRefs.handleOverlayClick) {
        modalRefs.overlay.removeEventListener('click', modalRefs.handleOverlayClick);
    }

    if (modalRefs.previouslyFocused && typeof modalRefs.previouslyFocused.focus === 'function') {
        modalRefs.previouslyFocused.focus();
    }

    modalRefs.previouslyFocused = null;
    modalRefs.handleKeydown = null;
    modalRefs.handleOverlayClick = null;
};

const showModal = (message) => {
    const refs = ensureModalElements();

    if (!refs.available) {
        return Promise.resolve(window.confirm(message));
    }

    refs.message.textContent = message;
    refs.overlay.classList.remove('hidden');
    refs.overlay.classList.add('flex');
    refs.overlay.setAttribute('aria-hidden', 'false');
    refs.isVisible = true;
    refs.previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    return new Promise((resolve) => {
        const settle = (status) => {
            hideModal();
            resolve(status);
        };

        refs.handleOverlayClick = (event) => {
            if (event.target === refs.overlay) {
                settle(false);
            }
        };

        refs.handleKeydown = (event) => {
            if (event.key === 'Escape' && refs.isVisible) {
                event.preventDefault();
                settle(false);
            } else if (event.key === 'Tab' && refs.isVisible) {
                // Basic trap between the two buttons
                const focusable = [refs.cancelBtn, refs.confirmBtn];
                const currentIndex = focusable.indexOf(document.activeElement);
                if (currentIndex === -1) {
                    focusable[0].focus();
                    event.preventDefault();
                    return;
                }

                const direction = event.shiftKey ? -1 : 1;
                const nextIndex = (currentIndex + direction + focusable.length) % focusable.length;
                focusable[nextIndex].focus();
                event.preventDefault();
            }
        };

        refs.overlay.addEventListener('click', refs.handleOverlayClick);
        document.addEventListener('keydown', refs.handleKeydown);

        const onConfirm = () => settle(true);
        const onCancel = () => settle(false);

        refs.confirmBtn.addEventListener('click', onConfirm, { once: true });
        refs.cancelBtn.addEventListener('click', onCancel, { once: true });

        requestAnimationFrame(() => {
            refs.confirmBtn.focus();
        });
    });
};

const captureInitialSnapshot = (form) => {
    try {
        const snapshot = serializeForm(form);
        formSnapshots.set(form, snapshot);
        return snapshot;
    } catch (error) {
        logDevError('Failed to capture initial snapshot', error);
        formSnapshots.delete(form);
        return null;
    }
};

const shouldConfirmSubmission = (form, actionType) => {
    if (!actionType) {
        return false;
    }

    if (actionType === 'update') {
        const snapshot = formSnapshots.get(form);
        if (!snapshot) {
            // Without a snapshot we err on the safe side.
            return true;
        }

        return hasFormChanged(form, snapshot);
    }

    if (ALWAYS_CONFIRM_ACTIONS.has(actionType)) {
        return true;
    }

    return true; // Unknown action types still confirm for safety.
};

const initCmsConfirm = () => {
    const forms = document.querySelectorAll('form[data-cms-confirm]');

    forms.forEach((form) => {
        const actionType = form.dataset.cmsConfirm?.trim();
        if (!actionType) {
            return;
        }

        if (actionType === 'update') {
            captureInitialSnapshot(form);
        }

        form.addEventListener('submit', async (event) => {
            if (!shouldConfirmSubmission(form, actionType)) {
                return;
            }

            event.preventDefault();

            const customMessage = form.dataset.cmsConfirmMessage;
            const message = getConfirmMessage(actionType, customMessage);

            try {
                const shouldProceed = await showModal(message);
                if (!shouldProceed) {
                    return;
                }

                if (actionType === 'update') {
                    captureInitialSnapshot(form);
                }

                form.submit();
            } catch (error) {
                logDevError('Modal confirmation failed. Fallback to native confirm.', error);
                if (window.confirm(message)) {
                    form.submit();
                }
            }
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initCmsConfirm());
} else {
    initCmsConfirm();
}

/**
 * Manual verification checklist:
 * - Create page: submitting any create form opens the modal every time.
 * - Edit page: submit without changes skips the modal; editing any field triggers it.
 * - Index page: deleting a row shows the modal before the request is sent.
 * - Navbar logout: clicking logout opens the modal and only proceeds after confirm.
 * - If the modal markup is removed, native confirm dialogs are used as a fallback.
 */

