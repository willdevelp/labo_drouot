/**
 * offline-lists.js — Rendu listes offline-first + modales édition locale
 * Dépend de LocalDB.js
 */
const OfflineLists = (function () {

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function isLocalId(id) {
        return String(id).startsWith('local_');
    }

    function ensureModalRoot() {
        let root = document.getElementById('offline-modal-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'offline-modal-root';
            document.body.appendChild(root);
        }
        return root;
    }

    function openModal(html) {
        const root = ensureModalRoot();
        root.innerHTML = `
            <div id="off-modal-backdrop" style="
                position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:8000;
                display:flex;align-items:center;justify-content:center;padding:16px;">
                <div style="background:#fff;border-radius:14px;max-width:520px;width:100%;
                    max-height:90vh;overflow:auto;padding:20px;font-family:'DM Sans',sans-serif;
                    box-shadow:0 12px 40px rgba(0,0,0,.2);">
                    ${html}
                </div>
            </div>`;
        root.querySelector('#off-modal-backdrop').addEventListener('click', (e) => {
            if (e.target.id === 'off-modal-backdrop') closeModal();
        });
        return root;
    }

    function closeModal() {
        const root = document.getElementById('offline-modal-root');
        if (root) root.innerHTML = '';
    }

    // ── Édition examen local ──────────────────────────────────────────
    async function editExamen(id) {
        const all = await LocalDB.getAll('examens');
        const ex = all.find(x => String(x.id) === String(id));
        if (!ex) {
            LocalDB.toast('Examen introuvable en local', 'error');
            return;
        }

        openModal(`
            <h3 style="margin:0 0 12px;font-size:1.05rem;">Modifier l'examen</h3>
            <p style="font-size:.8rem;color:#6b7a99;margin:0 0 16px;">
                Modification locale ${isLocalId(id) ? '(pas encore sur le serveur)' : '— sera synchronisée'}.
            </p>
            <label style="font-size:.8rem;font-weight:600;">Code</label>
            <input id="oe-code" class="form-input-drouot" style="width:100%;margin:4px 0 12px;"
                   value="${esc(ex.code || '')}">
            <label style="font-size:.8rem;font-weight:600;">Nom *</label>
            <input id="oe-nom" class="form-input-drouot" style="width:100%;margin:4px 0 12px;"
                   value="${esc(ex.nom || '')}">
            <label style="font-size:.8rem;font-weight:600;">Catégorie *</label>
            <input id="oe-cat" class="form-input-drouot" style="width:100%;margin:4px 0 12px;"
                   value="${esc(ex.categorie || '')}">
            <label style="font-size:.8rem;font-weight:600;">Prix *</label>
            <input id="oe-prix" type="number" class="form-input-drouot" style="width:100%;margin:4px 0 12px;"
                   value="${esc(ex.prix || 0)}">
            <label style="font-size:.8rem;font-weight:600;">Description</label>
            <textarea id="oe-desc" class="form-input-drouot" style="width:100%;margin:4px 0 16px;min-height:70px;">${esc(ex.description || '')}</textarea>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" id="oe-cancel" class="btn-outline-drouot">Annuler</button>
                <button type="button" id="oe-save" class="btn-primary-drouot">Enregistrer</button>
            </div>
        `);

        document.getElementById('oe-cancel').onclick = closeModal;
        document.getElementById('oe-save').onclick = async () => {
            const nom = document.getElementById('oe-nom').value.trim();
            const cat = document.getElementById('oe-cat').value.trim();
            const prix = parseFloat(document.getElementById('oe-prix').value) || 0;
            if (!nom || !cat || prix <= 0) {
                alert('Nom, catégorie et prix obligatoires');
                return;
            }
            const payload = {
                code: document.getElementById('oe-code').value.trim(),
                nom,
                categorie: cat,
                prix,
                description: document.getElementById('oe-desc').value.trim()
            };
            const url = (window.BASE_URL || '/') + 'admin/examens/' + id + '/edit';
            await LocalDB.update('examens', id, payload, url, {
                type: 'examens',
                fields: Object.entries(payload).map(([k, v]) => [k, String(v)]),
                label: 'Modification examen — ' + nom
            });
            LocalDB.toast('Examen mis à jour localement', 'success');
            closeModal();
            window.dispatchEvent(new CustomEvent('localdb:examens-updated'));
        };
    }

    // ── Paramètres d'examen (locaux) ──────────────────────────────────
    async function manageParametres(examenId) {
        const allEx = await LocalDB.getAll('examens');
        const ex = allEx.find(x => String(x.id) === String(examenId));
        if (!ex) {
            LocalDB.toast('Examen introuvable', 'error');
            return;
        }

        let params = [];
        try {
            const allP = await LocalDB.getAll('parametres');
            params = allP.filter(p => String(p.examen_id) === String(examenId) && !p._deleted);
        } catch (e) {
            params = Array.isArray(ex.parametres) ? ex.parametres : [];
        }

        function renderList() {
            const box = document.getElementById('oe-param-list');
            if (!box) return;
            if (!params.length) {
                box.innerHTML = '<p style="color:#6b7a99;font-size:.85rem;">Aucun paramètre pour le moment.</p>';
                return;
            }
            box.innerHTML = params.map((p, i) => `
                <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f0f2f8;">
                    <div style="flex:1;">
                        <strong style="font-size:.85rem;">${esc(p.nom || p.name || 'Paramètre')}</strong>
                        <div style="font-size:.75rem;color:#6b7a99;">
                            ${esc(p.unite || p.unit || '')}
                            ${p.valeur_normale_min != null ? ` · ref ${esc(p.valeur_normale_min)}–${esc(p.valeur_normale_max)}` : ''}
                        </div>
                    </div>
                    <button type="button" data-rm="${i}" class="action-btn action-btn-danger" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
            box.querySelectorAll('[data-rm]').forEach(btn => {
                btn.onclick = async () => {
                    const i = Number(btn.getAttribute('data-rm'));
                    const p = params[i];
                    if (p && p.id) {
                        await LocalDB.remove('parametres', p.id,
                            (window.BASE_URL || '/') + 'admin/examens/' + examenId + '/parametres/delete',
                            { type: 'parametres', label: 'Suppression paramètre' });
                    }
                    params.splice(i, 1);
                    // maj compteur sur examen
                    await LocalDB.update('examens', examenId, { nb_parametres: params.length },
                        (window.BASE_URL || '/') + 'admin/examens/' + examenId + '/edit',
                        { type: 'examens', fields: [['nb_parametres', String(params.length)]] });
                    renderList();
                    window.dispatchEvent(new CustomEvent('localdb:examens-updated'));
                };
            });
        }

        openModal(`
            <h3 style="margin:0 0 4px;font-size:1.05rem;">Paramètres — ${esc(ex.nom)}</h3>
            <p style="font-size:.8rem;color:#6b7a99;margin:0 0 12px;">Ajout / suppression en local, sync à la reconnexion.</p>
            <div id="oe-param-list" style="margin-bottom:16px;"></div>
            <div style="border-top:1px solid #f0f2f8;padding-top:12px;">
                <label style="font-size:.8rem;font-weight:600;">Nom du paramètre *</label>
                <input id="op-nom" class="form-input-drouot" style="width:100%;margin:4px 0 8px;" placeholder="Ex: Hémoglobine">
                <div style="display:flex;gap:8px;">
                    <div style="flex:1;">
                        <label style="font-size:.8rem;font-weight:600;">Unité</label>
                        <input id="op-unite" class="form-input-drouot" style="width:100%;margin:4px 0 8px;" placeholder="g/dL">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:.8rem;font-weight:600;">Min</label>
                        <input id="op-min" class="form-input-drouot" style="width:100%;margin:4px 0 8px;">
                    </div>
                    <div style="flex:1;">
                        <label style="font-size:.8rem;font-weight:600;">Max</label>
                        <input id="op-max" class="form-input-drouot" style="width:100%;margin:4px 0 8px;">
                    </div>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px;">
                    <button type="button" id="op-close" class="btn-outline-drouot">Fermer</button>
                    <button type="button" id="op-add" class="btn-primary-drouot">Ajouter</button>
                </div>
            </div>
        `);

        renderList();
        document.getElementById('op-close').onclick = closeModal;
        document.getElementById('op-add').onclick = async () => {
            const nom = document.getElementById('op-nom').value.trim();
            if (!nom) { alert('Nom obligatoire'); return; }
            const payload = {
                examen_id: examenId,
                nom,
                unite: document.getElementById('op-unite').value.trim(),
                valeur_normale_min: document.getElementById('op-min').value.trim(),
                valeur_normale_max: document.getElementById('op-max').value.trim()
            };
            const url = (window.BASE_URL || '/') + 'admin/examens/' + examenId + '/parametres';
            const created = await LocalDB.create('parametres', payload, url, {
                type: 'parametres',
                fields: Object.entries(payload).map(([k, v]) => [k, String(v)]),
                label: 'Paramètre — ' + nom + ' (' + (ex.nom || '') + ')'
            });
            params.push(created);
            await LocalDB.update('examens', examenId, { nb_parametres: params.length },
                (window.BASE_URL || '/') + 'admin/examens/' + examenId + '/edit',
                { type: 'examens', fields: [['nb_parametres', String(params.length)]] });
            document.getElementById('op-nom').value = '';
            document.getElementById('op-unite').value = '';
            document.getElementById('op-min').value = '';
            document.getElementById('op-max').value = '';
            renderList();
            LocalDB.toast('Paramètre ajouté localement', 'success');
            window.dispatchEvent(new CustomEvent('localdb:examens-updated'));
        };
    }

    return {
        esc,
        isLocalId,
        openModal,
        closeModal,
        editExamen,
        manageParametres
    };
})();
