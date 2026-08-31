/**
 * OfflineOutbox — Laboratoire Drouot
 *
 * File d'attente générique pour les formulaires hors ligne.
 * Convertit les fichiers en ArrayBuffer pour garantir la compatibilité
 * IndexedDB sur tous les navigateurs (notamment Safari / iOS).
 */

const OfflineOutbox = (function () {
    const DB_NAME    = 'labo-drouot-outbox';
    const DB_VERSION = 1;
    const STORE      = 'requests';

    let syncing = false;

    function openDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, DB_VERSION);
            req.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(STORE)) {
                    const store = db.createObjectStore(STORE, {
                        keyPath: 'localId',
                        autoIncrement: true,
                    });
                    store.createIndex('type', 'type');
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    }

    // Convertit un File/Blob en ArrayBuffer pour un stockage IndexedDB sécurisé
    async function serializeFieldValue(value) {
        if (value instanceof File || value instanceof Blob) {
            const buffer = await value.arrayBuffer();
            return {
                __isFile: true,
                name: value.name || 'file',
                type: value.type || 'application/octet-stream',
                buffer: buffer
            };
        }
        return value;
    }

    // Reconstruit une valeur sérialisée en File si nécessaire
    function deserializeFieldValue(value) {
        if (value && typeof value === 'object' && value.__isFile) {
            return new File([value.buffer], value.name, { type: value.type });
        }
        return value;
    }

    async function enqueue(entry) {
        const db = await openDB();
        entry.createdAt = Date.now();
        entry.status    = 'pending';

        // Sérialisation des champs (conversion des fichiers)
        if (Array.isArray(entry.fields)) {
            entry.fields = await Promise.all(
                entry.fields.map(async ([k, v]) => [k, await serializeFieldValue(v)])
            );
        }

        return new Promise((resolve, reject) => {
            const tx  = db.transaction(STORE, 'readwrite');
            const req = tx.objectStore(STORE).add(entry);
            req.onsuccess = () => {
                resolve(req.result);
                window.dispatchEvent(new CustomEvent('outbox:updated'));
                registerBackgroundSync();
            };
            req.onerror = () => reject(req.error);
        });
    }

    async function getAll() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(STORE, 'readonly');
            const req = tx.objectStore(STORE).getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    }

    async function remove(localId) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(STORE, 'readwrite');
            tx.objectStore(STORE).delete(localId);
            tx.oncomplete = () => resolve();
            tx.onerror    = () => reject(tx.error);
        });
    }

    function buildFormData(fields) {
        const fd = new FormData();
        fields.forEach(([k, v]) => {
            const deserialized = deserializeFieldValue(v);
            if (deserialized instanceof File) {
                fd.append(k, deserialized, deserialized.name);
            } else {
                fd.append(k, deserialized);
            }
        });
        return fd;
    }

    function escapeHTML(str) {
        return String(str || '').replace(/[&<>"']/g, (match) => {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return map[match];
        });
    }

    async function count(type) {
        const items = await getAll();
        return type ? items.filter((i) => i.type === type).length : items.length;
    }

    async function syncAll() {
        if (syncing) return;
        syncing = true;
        let syncedAny = false;
        try {
            const items = (await getAll())
                .filter((i) => !i.manualOnly)
                .sort((a, b) => a.createdAt - b.createdAt);

            for (const item of items) {
                try {
                    const fd  = buildFormData(item.fields);
                    const res = await fetch(item.url, { method: 'POST', body: fd });
                    
                    // Suppression uniquement en cas de statut 2xx
                    if (res.ok) {
                        await remove(item.localId);
                        syncedAny = true;
                        window.dispatchEvent(new CustomEvent('outbox:synced', { detail: item }));
                    } else if (res.status >= 400 && res.status < 500) {
                        console.error(`Erreur client/serveur (${res.status}) pour l'élément ${item.localId}`);
                        break;
                    }
                } catch (err) {
                    // Erreur réseau : interruption du cycle de synchro
                    break;
                }
            }
        } finally {
            syncing = false;
            window.dispatchEvent(new CustomEvent('outbox:updated'));
            if (syncedAny) {
                toast('Synchronisation effectuée.', 'success');
            }
        }
    }

    async function syncOne(localId) {
        const items = await getAll();
        const item  = items.find((i) => i.localId === localId);
        if (!item) return { ok: false, reason: 'not_found' };
        try {
            const fd  = buildFormData(item.fields);
            const res = await fetch(item.url, { method: 'POST', body: fd });
            if (res.ok) {
                await remove(item.localId);
                window.dispatchEvent(new CustomEvent('outbox:synced', { detail: item }));
                window.dispatchEvent(new CustomEvent('outbox:updated'));
                return { ok: true, response: res };
            }
            return { ok: false, reason: 'server_error', status: res.status };
        } catch (err) {
            return { ok: false, reason: 'network' };
        }
    }

    async function registerBackgroundSync() {
        if (!('serviceWorker' in navigator) || !('SyncManager' in window)) return;
        try {
            const reg = await navigator.serviceWorker.ready;
            await reg.sync.register('sync-outbox');
        } catch (err) {
            // Repli silencieux
        }
    }

    function toast(message, kind) {
        let box = document.getElementById('outbox-toast-container');
        if (!box) {
            box = document.createElement('div');
            box.id = 'outbox-toast-container';
            box.style.cssText =
                'position:fixed;bottom:20px;right:20px;z-index:4000;' +
                'display:flex;flex-direction:column;gap:8px;max-width:320px;';
            document.body.appendChild(box);
        }
        const el = document.createElement('div');
        const bg = kind === 'success' ? '#2E7D32' : '#1E88E5';
        el.style.cssText =
            'background:' + bg + ';color:#fff;padding:12px 16px;' +
            'border-radius:10px;font-size:.85rem;font-family:"DM Sans",sans-serif;' +
            'box-shadow:0 4px 16px rgba(0,0,0,.15);animation:outboxFadeIn .2s ease;';
        el.textContent = message;
        box.appendChild(el);
        setTimeout(() => el.remove(), 5000);
    }

    window.addEventListener('online', syncAll);
    document.addEventListener('DOMContentLoaded', () => {
        syncAll();
        setInterval(() => {
            if (navigator.onLine) syncAll();
        }, 30000);
    });

    function bindForm(form, opts) {
        if (!form) return;
        opts = opts || {};
        form.addEventListener('submit', async function (e) {
            if (opts.confirmMessage && !window.__outboxSkipConfirm) {
                if (!confirm(opts.confirmMessage)) { e.preventDefault(); return; }
            }
            e.preventDefault();

            const fd = new FormData(form);
            const fields = [];
            fd.forEach((v, k) => fields.push([k, v]));

            const btn = form.querySelector('[type="submit"]');
            const originalHTML = btn ? btn.innerHTML : null;
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }

            try {
                if (!navigator.onLine) throw new Error('offline');
                const res = await fetch(form.action, { method: 'POST', body: fd });
                if (!res.ok) throw new Error('server_error');

                if (opts.redirect) {
                    window.location.href = opts.redirect;
                } else if (res.redirected) {
                    window.location.href = res.url;
                } else {
                    window.location.reload();
                }
                return;
            } catch (err) {
                const label = typeof opts.label === 'function' ? opts.label(form) : (opts.label || 'Action');
                await enqueue({
                    type: opts.type || 'admin',
                    manualOnly: !!opts.manualOnly,
                    url: form.action,
                    label,
                    fields,
                });
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
                toast(opts.offlineMessage ||
                    'Hors ligne : enregistré localement, sera synchronisé automatiquement au retour du réseau.');
                if (opts.redirect) window.location.href = opts.redirect;
            }
        });
    }

    function renderPendingWidget(containerId, filterTypes) {
        const container = document.getElementById(containerId);
        if (!container) return;
        const list = container.querySelector('.outbox-pending-list') || container;

        async function render() {
            const items = (await getAll()).filter((i) => filterTypes.includes(i.type));
            if (items.length === 0) {
                container.style.display = 'none';
                return;
            }
            container.style.display = 'block';
            list.innerHTML = items.map((i) => `
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;
                            border-bottom:1px solid #f0f2f8;font-size:.85rem;">
                    <i class="fas fa-clock" style="color:#F9A825;"></i>
                    <span style="flex:1;">${escapeHTML(i.label)}</span>
                    <span style="color:#6b7a99;font-size:.75rem;">
                        ${new Date(i.createdAt).toLocaleString('fr-FR')}
                    </span>
                </div>
            `).join('');
        }
        window.addEventListener('outbox:updated', render);
        render();
    }

    function autoBindDeclarativeForms() {
        document.querySelectorAll('form.js-offline-form:not([data-offline-bound])').forEach((form) => {
            form.setAttribute('data-offline-bound', '1');
            bindForm(form, {
                type: form.dataset.offlineType || 'admin',
                label: form.dataset.offlineLabel || 'Action',
                redirect: form.dataset.offlineRedirect || '',
                manualOnly: form.dataset.offlineManual === 'true',
                confirmMessage: form.dataset.offlineConfirm || '',
            });
        });
    }
    document.addEventListener('DOMContentLoaded', autoBindDeclarativeForms);

    return {
        enqueue, getAll, remove, syncAll, syncOne, count, toast,
        bindForm, renderPendingWidget,
    };
})();