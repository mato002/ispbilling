const pbModalManager = {
    pendingModalLoad: false,
    modalLoadSeq: 0,
    modalAbortController: null,

    overlay() {
        return document.getElementById('pb-modal-overlay');
    },

    modalFrame() {
        return document.getElementById('pb-form-modal');
    },

    toastHost() {
        return document.getElementById('pb-toast-host');
    },

    modalConfig() {
        return window.__pbModalForm ?? { blockedPathFragments: ['/saas-onboarding', '/profile'] };
    },

    isModalFormUrl(url) {
        try {
            const parsed = new URL(url, window.location.origin);

            if (parsed.origin !== window.location.origin) {
                return false;
            }

            const path = parsed.pathname.toLowerCase();
            const blocked = this.modalConfig().blockedPathFragments ?? [];

            if (blocked.some((fragment) => path.includes(String(fragment).toLowerCase()))) {
                return false;
            }

            return /\/(create|edit)(\/|$)/.test(path);
        } catch {
            return false;
        }
    },

    shouldOpenLinkAsModal(link) {
        if (! link?.href) {
            return false;
        }

        if (link.hasAttribute('data-pb-modal-open') || link.hasAttribute('data-no-modal')) {
            return false;
        }

        if (link.getAttribute('target') === '_blank' || link.getAttribute('data-turbo') === 'false') {
            return false;
        }

        if (link.closest('#pb-form-modal') || link.closest('.pb-form-shell')) {
            return false;
        }

        return this.isModalFormUrl(link.href);
    },

    shouldOpenFormModal(link) {
        if (! link?.href || link.hasAttribute('data-no-modal') || link.getAttribute('target') === '_blank') {
            return false;
        }

        if (link.closest('#pb-form-modal')) {
            return false;
        }

        if (link.hasAttribute('data-pb-modal-open')) {
            return true;
        }

        if (link.getAttribute('data-turbo') === 'false') {
            return false;
        }

        return this.shouldOpenLinkAsModal(link);
    },

    prepareModalFormContent(root, sourceUrl = null) {
        root.querySelectorAll('form').forEach((form) => {
            form.removeAttribute('data-turbo-frame');
            form.removeAttribute('data-turbo');

            if (! form.querySelector('[name="_pb_modal"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_pb_modal';
                input.value = '1';
                form.appendChild(input);
            }

            if (sourceUrl && ! form.querySelector('[name="_pb_modal_return"]')) {
                const returnInput = document.createElement('input');
                returnInput.type = 'hidden';
                returnInput.name = '_pb_modal_return';
                returnInput.value = sourceUrl;
                form.appendChild(returnInput);
            }
        });
    },

    extractModalPanel(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nativePanel = doc.querySelector('[data-pb-form-modal-panel]')
            ?? doc.querySelector('.pb-form-modal');

        if (nativePanel) {
            return nativePanel;
        }

        const main = doc.querySelector('turbo-frame#pb-main main') ?? doc.querySelector('#pb-main main') ?? doc.querySelector('main');
        const form = main?.querySelector('form');

        if (! form) {
            return null;
        }

        const title = doc.querySelector('h2')?.textContent?.trim()
            ?? doc.querySelector('h1')?.textContent?.trim()
            ?? doc.title.split('·')[0]?.trim()
            ?? 'Form';

        const container = form.closest('.pb-card') ?? form.parentElement;
        const wrapper = document.createElement('div');
        wrapper.className = 'pb-form-modal mx-auto w-full sm:max-w-2xl';
        wrapper.setAttribute('data-pb-form-modal-panel', '');
        wrapper.innerHTML = `
            <div class="pb-form-modal__header">
                <h2 class="pb-form-modal__title">${title}</h2>
                <button type="button" class="pb-form-modal__close" data-pb-form-modal-close aria-label="Close">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="pb-form-modal__body">${container?.innerHTML ?? form.outerHTML}</div>
        `;
        this.prepareModalFormContent(wrapper);

        return wrapper;
    },

    abortModalLoad() {
        if (this.modalAbortController) {
            this.modalAbortController.abort();
            this.modalAbortController = null;
        }
    },

    renderModalLoading() {
        const frame = this.modalFrame();
        if (! frame) {
            return;
        }

        frame.innerHTML = `
            <div class="pb-form-modal w-full" data-pb-form-modal-loading>
                <div class="pb-form-modal__header">
                    <h2 class="pb-form-modal__title">Loading…</h2>
                    <button type="button" class="pb-form-modal__close" data-pb-form-modal-close aria-label="Close">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="pb-form-modal__body pb-form-modal__body--loading">
                    <div class="pb-modal-spinner" role="status" aria-label="Loading form"></div>
                </div>
            </div>
        `;
        this.showOverlay();
    },

    async loadForm(url) {
        const frame = this.modalFrame();
        if (! frame || ! url) {
            return;
        }

        this.abortModalLoad();
        const loadId = ++this.modalLoadSeq;
        this.modalAbortController = new AbortController();
        this.pendingModalLoad = true;
        this.renderModalLoading();

        try {
            const response = await fetch(url, {
                signal: this.modalAbortController.signal,
                headers: {
                    'Turbo-Frame': 'pb-form-modal',
                    Accept: 'text/html, application/xhtml+xml',
                },
                credentials: 'same-origin',
            });

            if (loadId !== this.modalLoadSeq) {
                return;
            }

            if (! response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const panel = this.extractModalPanel(await response.text());
            if (! panel) {
                throw new Error('Modal form markup was not found in the response.');
            }

            this.prepareModalFormContent(panel, url);
            frame.replaceChildren(panel);
            this.pendingModalLoad = false;
            this.modalAbortController = null;
            this.showOverlay();

            await new Promise((resolve) => window.requestAnimationFrame(resolve));
            if (window.Alpine) {
                window.Alpine.initTree(frame);
            }
        } catch (error) {
            if (error?.name === 'AbortError' || loadId !== this.modalLoadSeq) {
                return;
            }

            console.error('pbModalManager.loadForm', error);
            this.pendingModalLoad = false;
            this.modalAbortController = null;
            this.closeModal();
            this.showToast('Unable to open form. Please try again.', 'error');
        }
    },

    closeModal() {
        this.modalLoadSeq += 1;
        this.abortModalLoad();
        this.pendingModalLoad = false;

        const frame = this.modalFrame();
        if (frame) {
            frame.innerHTML = '';
        }

        this.hideOverlay();
    },

    showOverlay() {
        const overlay = this.overlay();
        if (overlay) {
            overlay.hidden = false;
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        }
    },

    hideOverlay() {
        const overlay = this.overlay();
        if (overlay) {
            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        }
    },

    showToast(message, variant = 'success') {
        if (! message) {
            return;
        }

        if (window.Swal) {
            window.Swal.fire({
                icon: variant === 'error' ? 'error' : 'success',
                title: variant === 'error' ? 'Error' : 'Success',
                text: message,
                timer: variant === 'error' ? undefined : 3000,
                timerProgressBar: variant !== 'error',
            });

            return;
        }

        const host = this.toastHost();
        if (! host) {
            return;
        }

        const toast = document.createElement('div');
        toast.className = `pb-toast pb-toast--${variant}`;
        toast.setAttribute('role', 'status');
        toast.textContent = message;
        host.appendChild(toast);
        window.setTimeout(() => toast.remove(), 4500);
    },

    refreshTable() {
        const frame = document.getElementById('pb-main');
        if (! frame || ! window.Turbo) {
            window.location.reload();

            return Promise.resolve();
        }

        return window.Turbo.visit(window.location.href, {
            frame: 'pb-main',
            action: 'replace',
        });
    },

    async submitFormRequest(form) {
        if (! form) {
            return;
        }

        const formData = new FormData(form);
        const method = (formData.get('_method') || form.method || 'POST').toString().toUpperCase();
        const submitButton = form.querySelector('[type="submit"]');

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(form.action, {
                method: method === 'GET' ? 'GET' : 'POST',
                body: method === 'GET' ? null : formData,
                headers: {
                    'Turbo-Frame': 'pb-form-modal',
                    Accept: 'text/html, application/xhtml+xml',
                },
                credentials: 'same-origin',
                redirect: 'follow',
            });

            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const successMarker = doc.querySelector('[data-pb-modal-success]');

            if (successMarker) {
                this.handleSuccess({
                    message: successMarker.dataset.message ?? '',
                    refresh: successMarker.dataset.refresh !== '0',
                });

                return;
            }

            const panel = this.extractModalPanel(html);
            const frame = this.modalFrame();

            if (panel && frame) {
                this.prepareModalFormContent(panel);
                frame.replaceChildren(panel);
                this.showOverlay();
                if (window.Alpine) {
                    window.Alpine.initTree(frame);
                }

                const modalError = doc.querySelector('[data-pb-validation-errors] li')?.textContent?.trim();
                if (modalError) {
                    this.showToast(modalError, 'error');
                }

                return;
            }

            if (! response.ok) {
                this.showToast(`Unable to save form (${response.status}). Please try again.`, 'error');
            }
        } catch (error) {
            console.error('pbModalManager.submitFormRequest', error);
            this.showToast('Unable to save form. Please try again.', 'error');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    },

    handleSuccess({ message = '', refresh = true } = {}) {
        this.closeModal();

        if (message) {
            this.showToast(message);
        }

        if (refresh) {
            this.refreshTable();
        }
    },

    handleDocumentClick(event) {
        const modalLink = event.target.closest('a[href]');

        if (modalLink && this.shouldOpenFormModal(modalLink)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            this.loadForm(modalLink.href);

            return;
        }

        if (event.target.closest('[data-pb-form-modal-close]')) {
            event.preventDefault();
            event.stopPropagation();
            this.closeModal();
        }
    },

    bind() {
        document.addEventListener('turbo:before-visit', (event) => {
            const url = event.detail?.url?.toString();
            if (! url || event.detail?.action === 'restore') {
                return;
            }

            if (this.isModalFormUrl(url)) {
                event.preventDefault();
                this.loadForm(url);
            }
        });

        document.addEventListener('turbo:click', (event) => {
            const { url, originalEvent } = event.detail ?? {};
            const link = originalEvent?.target?.closest?.('a[href]');

            if (! url || ! link || ! this.shouldOpenFormModal(link)) {
                return;
            }

            event.preventDefault();
            originalEvent?.preventDefault?.();
            originalEvent?.stopImmediatePropagation?.();
            this.loadForm(url);
        });

        document.addEventListener('click', (event) => this.handleDocumentClick(event), true);

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (! (form instanceof HTMLFormElement) || ! form.closest('#pb-form-modal')) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            this.submitFormRequest(form);
        }, true);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeModal();
            }
        });
    },
};

window.pbModalManager = pbModalManager;
pbModalManager.bind();
pbModalManager.hideOverlay();

export default pbModalManager;
