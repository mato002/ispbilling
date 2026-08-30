import { Html5QrcodeScanner } from 'html5-qrcode';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function resolvePayload(payload, resolveUrl) {
    const response = await fetch(resolveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ payload }),
        credentials: 'same-origin',
    });

    const data = await response.json().catch(() => ({}));

    if (! response.ok) {
        throw new Error(data.message ?? 'Could not resolve QR code.');
    }

    return data;
}

function showScanStatus(message, type = 'info') {
    const el = document.getElementById('inventory-scan-status');
    if (! el) {
        return;
    }

    el.classList.remove('hidden', 'text-rose-600', 'text-emerald-600', 'text-slate-600');
    el.textContent = message;
    el.classList.add(type === 'error' ? 'text-rose-600' : type === 'success' ? 'text-emerald-600' : 'text-slate-600');
}

function initInventoryScanner() {
    const root = document.getElementById('inventory-scan-root');
    if (! root || root.dataset.scannerReady === '1') {
        return;
    }

    root.dataset.scannerReady = '1';

    const resolveUrl = root.dataset.resolveUrl;
    if (! resolveUrl) {
        return;
    }

    let redirecting = false;

    const scanner = new Html5QrcodeScanner(
        'inventory-scan-reader',
        {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true,
            aspectRatio: 1,
        },
        false,
    );

    scanner.render(
        async (decodedText) => {
            if (redirecting) {
                return;
            }

            redirecting = true;
            showScanStatus('Device found. Opening details…', 'success');

            try {
                const data = await resolvePayload(decodedText, resolveUrl);
                window.location.assign(data.redirect);
            } catch (error) {
                redirecting = false;
                showScanStatus(error.message ?? 'Scan failed.', 'error');
            }
        },
        () => {},
    );

    document.addEventListener('turbo:before-cache', () => {
        scanner.clear().catch(() => {});
    }, { once: true });
}

document.addEventListener('DOMContentLoaded', initInventoryScanner);
document.addEventListener('turbo:load', initInventoryScanner);
document.addEventListener('turbo:render', initInventoryScanner);
