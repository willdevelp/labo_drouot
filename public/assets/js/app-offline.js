/**
 * app-offline.js — Intercepteur unifié des formulaires et actions offline
 * S'appuie entièrement sur LocalDB.
 */
document.addEventListener('DOMContentLoaded', function () {

    // 1. Interception globale de la soumission de formulaires
    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        const storeName = form.dataset.offlineStore;
        if (!storeName) return; // pas marqué offline → comportement natif

        e.preventDefault();

        const deleteId    = form.dataset.offlineDeleteId;
        const entityId    = form.dataset.entityId;
        const toggleField = form.dataset.toggleField;
        const serverUrl   = form.action;
        const redirectUrl = form.dataset.redirect || window.location.href;
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
                LocalDB.toast('Suppression enregistrée localement', 'info');

            } else if (toggleField && entityId) {
                const toggleValue = parseInt(form.dataset.toggleValue, 10);
                const togglePayload = { [toggleField]: toggleValue };
                await LocalDB.update(storeName, entityId, togglePayload, serverUrl, {
                    type,
                    fields: Object.entries(togglePayload),
                    label: 'Mise à jour ' + storeName + ' #' + entityId
                });
                LocalDB.toast('Modification enregistrée localement', 'info');
                window.location.href = redirectUrl;

            } else if (entityId && entityId !== '') {
                await LocalDB.update(storeName, entityId, payload, serverUrl, {
                    type,
                    fields,
                    label: 'Modification ' + storeName + ' #' + entityId
                });
                LocalDB.toast('Modification enregistrée localement', 'info');
                window.location.href = redirectUrl;

            } else {
                await LocalDB.create(storeName, payload, serverUrl, {
                    type,
                    fields,
                    label: 'Création ' + storeName
                });
                LocalDB.toast('Création enregistrée localement', 'info');
                window.location.href = redirectUrl;
            }

            LocalDB.syncAll();

        } catch (err) {
            console.error('Erreur traitement offline :', err);
            LocalDB.toast('Erreur lors de l\'enregistrement local', 'error');
        }
    });

    // 2. Interception des boutons de suppression hors formulaire
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('[data-offline-delete]');
        if (!btn) return;

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
            LocalDB.syncAll();

            const row = btn.closest('tr') || btn.closest('.card-item');
            if (row) row.remove();
            LocalDB.toast('Suppression enregistrée localement', 'info');
        } catch (err) {
            console.error('Erreur suppression locale :', err);
            LocalDB.toast('Erreur lors de la suppression locale', 'error');
        }
    });
});
