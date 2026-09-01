/**
 * LocalDB — Moteur CRUD Offline-First unifié pour Laboratoire Drouot
 *
 * Unique système hors-ligne :
 *  - IndexedDB labo-drouot-db
 *  - Stores métier + store outbox
 *  - Interception formulaires (via app-offline.js)
 *  - File d'attente (outbox) rejouée en POST FormData (compatible PHP)
 *  - Background Sync (service worker)
 *  - Widgets pending + toasts
 */
const LocalDB = (function () {
    const DB_NAME    = 'labo-drouot-db';
    const DB_VERSION = 4;

    // Stores métier (étendus pour couvrir toutes les vues)
    const STORES = [
        'users', 'resultats', 'stocks', 'patients', 'examens',
        'assurances', 'medecins', 'parametres', 'commissions', 'validations'
    ];

    // ── Helpers IndexedDB ──────────────────────────────────────────────
    function openDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, DB_VERSION);

            req.onupgradeneeded = (e) => {
                const db = e.target.result;

                STORES.forEach((storeName) => {
                    if (!db.objectStoreNames.contains(storeName)) {
                        const store = db.createObjectStore(storeName, { keyPath: 'id' });
                        store.createIndex('synced', 'synced', { unique: false });
                        store.createIndex('deleted', '_deleted', { unique: false });
                    }
                });

                if (!db.objectStoreNames.contains('outbox')) {
                    const outbox = db.createObjectStore('outbox', {
                        keyPath: 'localId',
                        autoIncrement: true
                    });
                    outbox.createIndex('type', 'type', { unique: false });
                    outbox.createIndex('createdAt', 'createdAt', { unique: false });
                    outbox.createIndex('manualOnly', 'manualOnly', { unique: false });
                }
            };

            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    }

    function txDone(tx) {
        return new Promise((resolve, reject) => {
            tx.oncomplete = () => resolve();
            tx.onerror    = () => reject(tx.error);
            tx.onabort    = () => reject(tx.error || new Error('transaction aborted'));
        });
    }

    // ── Toast ──────────────────────────────────────────────────────────
    function toast(message, type = 'info') {
        let el = document.getElementById('localdb-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'localdb-toast';
            el.style.cssText = `
                position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
                z-index:9999;max-width:92vw;padding:12px 20px;border-radius:12px;
                font-family:'DM Sans',-apple-system,sans-serif;font-size:.875rem;
                font-weight:500;box-shadow:0 8px 28px rgba(0,0,0,.18);
                color:#fff;opacity:0;transition:opacity .25s;pointer-events:none;
            `;
            document.body.appendChild(el);
        }
        const colors = {
            success: '#2E7D32',
            error:   '#C62828',
            info:    '#1E88E5',
            warn:    '#F9A825'
        };
        el.style.background = colors[type] || colors.info;
        el.textContent = message;
        el.style.opacity = '1';
        clearTimeout(el._timer);
        el._timer = setTimeout(() => { el.style.opacity = '0'; }, 4200);
    }

    // ── Outbox helpers ─────────────────────────────────────────────────
    /**
     * Ajoute une requête dans la file d'attente.
     * @param {Object} opts
     * @param {string} opts.url
     * @param {string} [opts.type]      - ex: 'patient', 'validation'
     * @param {string} [opts.subtype]
     * @param {string} [opts.label]
     * @param {Array<[string, any]>} [opts.fields]  - pour FormData
     * @param {Object} [opts.payload]   - alternative JSON
     * @param {boolean} [opts.manualOnly]
     * @param {any} [opts.echantillonId]
     * @param {string} [opts.storeName]
     * @param {string} [opts.action]    - CREATE|UPDATE|DELETE
     * @param {string|number} [opts.id]
     */
    async function enqueue(opts) {
        const db = await openDB();
        const item = {
            url:           opts.url,
            type:          opts.type || 'generic',
            subtype:       opts.subtype || null,
            label:         opts.label || 'Action hors ligne',
            fields:        opts.fields || null,
            payload:       opts.payload || null,
            manualOnly:    !!opts.manualOnly,
            echantillonId: opts.echantillonId || null,
            storeName:     opts.storeName || null,
            action:        opts.action || 'POST',
            id:            opts.id || null,
            createdAt:     Date.now()
        };

        return new Promise((resolve, reject) => {
            const tx = db.transaction('outbox', 'readwrite');
            const req = tx.objectStore('outbox').add(item);
            req.onsuccess = () => {
                item.localId = req.result;
                window.dispatchEvent(new CustomEvent('outbox:updated'));

                // Demande un Background Sync si supporté (et si non manualOnly)
                if (!item.manualOnly && 'serviceWorker' in navigator && 'SyncManager' in window) {
                    navigator.serviceWorker.ready.then(reg => {
                        return reg.sync.register('sync-outbox');
                    }).catch(() => { /* ignore */ });
                }

                resolve(item);
            };
            req.onerror = () => reject(req.error);
        });
    }

    async function getAllOutbox() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction('outbox', 'readonly');
            const req = tx.objectStore('outbox').getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror   = () => reject(req.error);
        });
    }

    async function count() {
        const items = await getAllOutbox();
        return items.length;
    }

    async function deleteOutboxItem(localId) {
        const db = await openDB();
        const key = normalizeOutboxKey(localId);
        // Si clé invalide, on tente quand même la valeur brute (nettoyage)
        const keysToTry = key !== null ? [key] : [localId];
        return new Promise((resolve, reject) => {
            const tx = db.transaction('outbox', 'readwrite');
            const store = tx.objectStore('outbox');
            keysToTry.forEach(k => {
                try { store.delete(k); } catch (_) { /* ignore invalid key */ }
            });
            tx.oncomplete = () => {
                window.dispatchEvent(new CustomEvent('outbox:updated'));
                resolve();
            };
            tx.onerror = () => reject(tx.error);
        });
    }

    /** Normalise une clé outbox (autoIncrement numérique). */
    function normalizeOutboxKey(key) {
        if (typeof key === 'number' && !Number.isNaN(key)) return key;
        if (typeof key === 'string' && /^\d+$/.test(key)) return Number(key);
        return null; // clé invalide (ex: "local_xxx")
    }

    /**
     * Rejoue une entrée outbox (POST FormData prioritaire, sinon JSON).
     * credentials: 'include' pour envoyer le cookie de session PHP.
     */
    async function syncOne(localId) {
        if (!navigator.onLine) {
            return { ok: false, error: 'offline' };
        }

        const key = normalizeOutboxKey(localId);
        if (key === null) {
            // Ancienne entrée corrompue : on la supprime pour ne pas bloquer la file
            try { await deleteOutboxItem(localId); } catch (_) { /* ignore */ }
            return { ok: false, error: 'invalid_key' };
        }

        const db = await openDB();
        const item = await new Promise((resolve, reject) => {
            const tx  = db.transaction('outbox', 'readonly');
            const req = tx.objectStore('outbox').get(key);
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });

        if (!item) return { ok: false, error: 'not_found' };

        try {
            let response;
            const fetchOpts = {
                method: 'POST',
                credentials: 'include',
                redirect: 'follow'
            };

            if (item.fields && Array.isArray(item.fields)) {
                const fd = new FormData();
                item.fields.forEach(([k, v]) => {
                    if (v instanceof File && v.size === 0) return;
                    fd.append(k, v);
                });
                response = await fetch(item.url, { ...fetchOpts, body: fd });
            } else {
                response = await fetch(item.url, {
                    ...fetchOpts,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action:  item.action,
                        id:      item.id,
                        payload: item.payload
                    })
                });
            }

            const finalUrl = response.url || '';
            const isLoginRedirect = /\/login/i.test(finalUrl);

            if (!isLoginRedirect && (response.ok || response.redirected || (response.status >= 200 && response.status < 400))) {
                await deleteOutboxItem(key);
                return { ok: true, response };
            }
            return { ok: false, error: isLoginRedirect ? 'auth' : ('http_' + response.status) };
        } catch (err) {
            return { ok: false, error: err.message || 'network' };
        }
    }

    /**
     * Synchronise toute la file (ignore manualOnly sauf si force=true).
     */
    async function syncAll(force = false) {
        if (!navigator.onLine) return { synced: 0, failed: 0 };

        const items = (await getAllOutbox())
            .filter(i => force || !i.manualOnly)
            .sort((a, b) => (a.createdAt || 0) - (b.createdAt || 0));

        let synced = 0;
        let failed = 0;

        for (const item of items) {
            // localId peut être absent sur d'anciennes entrées : utiliser la clé IDB
            const key = item.localId != null ? item.localId : item.id;
            const result = await syncOne(key);
            if (result.ok) {
                synced++;
            } else {
                failed++;
                if (result.error === 'offline' || result.error === 'network') break;
            }
        }

        if (synced > 0) {
            window.dispatchEvent(new CustomEvent('outbox:updated'));
            toast(synced + ' action(s) synchronisée(s)', 'success');
        }
        return { synced, failed };
    }

    // ── CRUD métier ────────────────────────────────────────────────────
    async function seedStore(storeName, itemsArray) {
        if (!Array.isArray(itemsArray) || itemsArray.length === 0) return;
        const db = await openDB();
        const tx = db.transaction(storeName, 'readwrite');
        const store = tx.objectStore(storeName);

        for (const item of itemsArray) {
            const existingReq = store.get(item.id);
            existingReq.onsuccess = () => {
                if (!existingReq.result || existingReq.result.synced === 1) {
                    store.put({ ...item, synced: 1, _deleted: false });
                }
            };
        }
        return txDone(tx);
    }

    async function getAll(storeName) {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).getAll();
            req.onsuccess = () => {
                const items = (req.result || []).filter(item => !item._deleted);
                resolve(items);
            };
            req.onerror = () => reject(req.error);
        });
    }

    async function create(storeName, data, serverUrl, options = {}) {
        const db = await openDB();
        const localId = 'local_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        const item = {
            ...data,
            id: localId,
            synced: 0,
            _deleted: false,
            updatedAt: new Date().toISOString()
        };

        const fields = options.fields || Object.entries(data || {});

        const tx = db.transaction([storeName, 'outbox'], 'readwrite');
        tx.objectStore(storeName).add(item);
        // Ne PAS fixer localId ici : c'est la clé autoIncrement de l'outbox.
        // L'id métier temporaire est conservé dans entityLocalId / payload.
        tx.objectStore('outbox').add({
            action:         'CREATE',
            storeName,
            url:            serverUrl,
            entityLocalId:  item.id,
            type:           options.type || storeName,
            label:          options.label || ('Création ' + storeName),
            fields,
            payload:        data,
            manualOnly:     !!options.manualOnly,
            createdAt:      Date.now()
        });

        await txDone(tx);
        window.dispatchEvent(new CustomEvent(`localdb:${storeName}-updated`));
        window.dispatchEvent(new CustomEvent('outbox:updated'));
        return item;
    }

    async function update(storeName, id, updatedData, serverUrl, options = {}) {
        const db = await openDB();
        const tx = db.transaction([storeName, 'outbox'], 'readwrite');
        const store = tx.objectStore(storeName);

        const current = await new Promise((resolve) => {
            const r = store.get(id);
            r.onsuccess = () => resolve(r.result || {});
            r.onerror   = () => resolve({});
        });

        const updatedItem = {
            ...current,
            ...updatedData,
            id,
            synced: 0,
            updatedAt: new Date().toISOString()
        };
        store.put(updatedItem);

        const fields = options.fields || Object.entries(updatedData || {});
        tx.objectStore('outbox').add({
            action:     'UPDATE',
            storeName,
            url:        serverUrl,
            id,
            type:       options.type || storeName,
            label:      options.label || ('Modification ' + storeName + ' #' + id),
            fields,
            payload:    updatedData,
            manualOnly: !!options.manualOnly,
            createdAt:  Date.now()
        });

        await txDone(tx);
        window.dispatchEvent(new CustomEvent(`localdb:${storeName}-updated`));
        window.dispatchEvent(new CustomEvent('outbox:updated'));
        return true;
    }

    async function remove(storeName, id, serverUrl, options = {}) {
        const db = await openDB();
        const tx = db.transaction([storeName, 'outbox'], 'readwrite');
        const store = tx.objectStore(storeName);

        if (String(id).startsWith('local_')) {
            store.delete(id);
        } else {
            const current = await new Promise((resolve) => {
                const r = store.get(id);
                r.onsuccess = () => resolve(r.result);
                r.onerror   = () => resolve(null);
            });
            if (current) {
                current._deleted = true;
                current.synced   = 0;
                store.put(current);
            }
        }

        const fields = options.fields || [];
        tx.objectStore('outbox').add({
            action:     'DELETE',
            storeName,
            url:        serverUrl,
            id,
            type:       options.type || storeName,
            label:      options.label || ('Suppression ' + storeName + ' #' + id),
            fields,
            manualOnly: !!options.manualOnly,
            createdAt:  Date.now()
        });

        await txDone(tx);
        window.dispatchEvent(new CustomEvent(`localdb:${storeName}-updated`));
        window.dispatchEvent(new CustomEvent('outbox:updated'));
        return true;
    }

    // ── Helpers UI ─────────────────────────────────────────────────────
    /**
     * Bind un formulaire pour le mode offline (création principalement).
     * Online → laisse le POST natif. Offline → enqueue + redirect.
     */
    function bindForm(form, options = {}) {
        if (!form) return;

        form.addEventListener('submit', async function (e) {
            // Si le formulaire a déjà data-offline-store, app-offline.js s'en charge
            if (form.dataset.offlineStore) return;

            if (navigator.onLine) {
                return; // POST classique
            }

            e.preventDefault();

            const fd = new FormData(form);
            const fields = [];
            fd.forEach((v, k) => fields.push([k, v]));

            const label = typeof options.label === 'function'
                ? options.label(form)
                : (options.label || 'Formulaire hors ligne');

            await enqueue({
                type:       options.type || 'generic',
                url:        form.action,
                label,
                fields,
                manualOnly: !!options.manualOnly
            });

            toast('Hors ligne : enregistré localement. Synchronisation au retour du réseau.', 'info');

            if (options.redirect) {
                window.location.href = options.redirect;
            }
        });
    }

    /**
     * Affiche un widget listant les éléments en attente pour certains types.
     */
    function renderPendingWidget(containerId, types = []) {
        const box = document.getElementById(containerId);
        if (!box) return;

        async function render() {
            let items = await getAllOutbox();
            if (types.length) {
                items = items.filter(i => types.includes(i.type));
            }
            if (items.length === 0) {
                box.style.display = 'none';
                box.innerHTML = '';
                return;
            }

            box.style.display = 'block';
            box.innerHTML = `
                <div style="background:#FFF8E1;border:1px solid #FFE082;border-radius:12px;
                            padding:14px 16px;margin-bottom:16px;font-family:'DM Sans',sans-serif;">
                    <div style="font-weight:600;font-size:.9rem;color:#F57F17;margin-bottom:8px;">
                        <i class="fas fa-cloud-upload-alt me-1"></i>
                        ${items.length} action(s) en attente de synchronisation
                    </div>
                    <div style="font-size:.8rem;color:#6b7a99;">
                        ${items.map(i => `
                            <div style="padding:4px 0;border-bottom:1px solid #ffe082;">
                                ${escapeHtml(i.label || i.type)}
                                <span style="opacity:.7;"> — ${timeAgo(i.createdAt)}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        window.addEventListener('outbox:updated', render);
        window.addEventListener('online', render);
        render();
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function timeAgo(ts) {
        const mins = Math.round((Date.now() - ts) / 60000);
        if (mins < 1) return 'à l\'instant';
        if (mins < 60) return 'il y a ' + mins + ' min';
        const hrs = Math.round(mins / 60);
        if (hrs < 24) return 'il y a ' + hrs + ' h';
        return 'il y a ' + Math.round(hrs / 24) + ' j';
    }

    // Auto-sync au retour du réseau
    window.addEventListener('online', () => {
        setTimeout(() => syncAll(false), 800);
    });

    /** Vide toute la file d'attente (utile si des items sont bloqués) */
    async function clearOutbox() {
        const db = await openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction('outbox', 'readwrite');
            tx.objectStore('outbox').clear();
            tx.oncomplete = () => {
                window.dispatchEvent(new CustomEvent('outbox:updated'));
                toast('File d\'attente vidée', 'info');
                resolve();
            };
            tx.onerror = () => reject(tx.error);
        });
    }

    // ── API publique ───────────────────────────────────────────────────
    return {
        openDB,
        seedStore,
        getAll,
        create,
        update,
        remove,
        enqueue,
        getAllOutbox,
        count,
        deleteOutboxItem,
        syncOne,
        syncAll,
        clearOutbox,
        toast,
        bindForm,
        renderPendingWidget,
        getAllPending: getAllOutbox
    };
})();

// Alias de compatibilité pour les vues qui appellent encore OfflineOutbox
window.OfflineOutbox = {
    enqueue:             (...a) => LocalDB.enqueue(...a),
    getAll:              ()     => LocalDB.getAllOutbox(),
    count:               ()     => LocalDB.count(),
    syncOne:             (id)   => LocalDB.syncOne(id),
    syncAll:             (f)    => LocalDB.syncAll(f),
    clearOutbox:         ()     => LocalDB.clearOutbox(),
    toast:               (...a) => LocalDB.toast(...a),
    bindForm:            (...a) => LocalDB.bindForm(...a),
    renderPendingWidget: (...a) => LocalDB.renderPendingWidget(...a)
};
