/**
 * app-offline.js — Intercepteur unifié des formulaires et actions offline
 * S'appuie entièrement sur LocalDB.
 *
 * Règle : en ligne → soumission native (POST classique PHP).
 *         hors ligne → enregistrement local + outbox + sync au retour réseau.
 */
document.addEventListener('DOMContentLoaded', function () {

    async function softRedirect(url) {
        if (!url) return;
        try {
            const base = url.split('?')[0];
            const cached = (await caches.match(url)) || (await caches.match(base));
            if (cached) {
                window.location.href = url;
                return;
            }
        } catch (_) { /* caches API indisponible */ }
        // Page non en cache : on reste sur place (évite offline.html)
    }

    // 1. Interception globale (capture) pour passer avant les handlers des vues
    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        const storeName = form.dataset.offlineStore;
        if (!storeName) return;

        // ── EN LIGNE : POST classique ──────────────────────────────────
        if (navigator.onLine) {
            return;
        }

        // ── HORS LIGNE ────────────────────────────────────────────────
        e.preventDefault();
        e.stopImmediatePropagation();

        const deleteId    = form.dataset.offlineDeleteId;
        const entityId    = form.dataset.entityId;
        const toggleField = form.dataset.toggleField;
        const serverUrl   = form.action;
        const redirectUrl = form.dataset.redirect || '';
        const type        = form.dataset.offlineType || storeName;

        try {
            const fd = new FormData(form);
            const fields = [];
            fd.forEach((v, k) => fields.push([k, v]));
            const payload = Object.fromEntries(
                Array.from(fd.entries()).filter(([, v]) => typeof v === 'string')
            );

            if (deleteId) {
                await LocalDB.remove(storeName, deleteId, serverUrl, {
                    type,
                    fields,
                    label: 'Suppression ' + storeName + ' #' + deleteId
                });
                const row = form.closest('tr') || form.closest('.card-item');
                if (row) row.remove();
                LocalDB.toast('Hors ligne : suppression enregistrée localement', 'info');

            } else if (toggleField && entityId) {
                const toggleValue = parseInt(form.dataset.toggleValue, 10);
                const togglePayload = { [toggleField]: toggleValue };
                await LocalDB.update(storeName, entityId, togglePayload, serverUrl, {
                    type,
                    fields: Object.entries(togglePayload),
                    label: 'Mise à jour ' + storeName + ' #' + entityId
                });
                LocalDB.toast('Hors ligne : modification enregistrée localement', 'info');
                await softRedirect(redirectUrl);

            } else if (entityId && entityId !== '') {
                await LocalDB.update(storeName, entityId, payload, serverUrl, {
                    type,
                    fields,
                    label: 'Modification ' + storeName + ' #' + entityId
                });
                LocalDB.toast('Hors ligne : modification enregistrée localement', 'info');
                await softRedirect(redirectUrl);

            } else {
                await LocalDB.create(storeName, payload, serverUrl, {
                    type,
                    fields,
                    label: 'Création ' + storeName
                });
                LocalDB.toast(
                    'Hors ligne : enregistré localement. Synchronisation au retour du réseau.',
                    'info'
                );
                form.reset();
                await softRedirect(redirectUrl);
            }

        } catch (err) {
            console.error('Erreur traitement offline :', err);
            LocalDB.toast('Erreur lors de l\'enregistrement local : ' + (err.message || err), 'error');
        }
    }, true);

    // 2. Interception des boutons de suppression hors formulaire
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-offline-delete]');
        if (!btn) return;

        if (navigator.onLine) return;

        e.preventDefault();
        if (!confirm('Confirmer la suppression ?')) return;

        const storeName = btn.dataset.store;
        const id        = btn.dataset.id;
        const serverUrl = btn.dataset.url || btn.getAttribute('href') || '';

        try {
            await LocalDB.remove(storeName, id, serverUrl, {
                type: storeName,
                label: 'Suppression ' + storeName + ' #' + id
            });

            const row = btn.closest('tr') || btn.closest('.card-item');
            if (row) row.remove();
            LocalDB.toast('Hors ligne : suppression enregistrée localement', 'info');
        } catch (err) {
            console.error('Erreur suppression locale :', err);
            LocalDB.toast('Erreur lors de la suppression locale', 'error');
        }
    });
});
