<?php
/**
 * Devis proforma — Sélection d'examens, calcul à la volée
 * Aucune donnée n'est enregistrée en base.
 */
include __DIR__ . '/../../../views/layouts/header.php';

$nbCategoriesDevis = count($examensGroupes);
// Peu de catégories -> tout ouvert par défaut. Sinon replié, pour
// éviter un mur de 200+ examens au chargement.
$openByDefaultDevis = $nbCategoriesDevis <= 5;
?>

<div class="d-flex align-items-center justify-content-between mb-4 no-print">
    <div>
        <h1 class="page-title">
            <i class="fas fa-file-invoice text-primary me-2"></i>
            Devis proforma
        </h1>
        <p class="page-sub">
            Estimation de prix à titre indicatif — sans valeur commerciale
        </p>
    </div>
    <a href="<?= BASE_URL ?>dashboard/secretaire" class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<div class="row g-4 no-print">

    <!-- Colonne gauche — Sélection examens -->
    <div class="col-xl-8">
        <div class="card-drouot">
            <h6 class="form-section-title">
                <i class="fas fa-microscope"></i>
                Examens
                <span class="examen-count-badge" id="examenCount">0 sélectionné(s)</span>
            </h6>

            <div class="search-input-wrap mb-3">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="examenSearch" class="search-input"
                       placeholder="Filtrer les examens...">
            </div>

            <!-- Barre d'outils catégories -->
            <div class="cat-toolbar mb-3">
                <div class="cat-toolbar-actions">
                    <button type="button" class="toolbar-btn" id="btnDeplierTout">
                        <i class="fas fa-chevron-down"></i> Tout déplier
                    </button>
                    <button type="button" class="toolbar-btn" id="btnReplierTout">
                        <i class="fas fa-chevron-up"></i> Tout replier
                    </button>
                </div>
                <div class="cat-quicknav">
                    <?php foreach ($examensGroupes as $categorie => $examens):
                        $slugDevis = 'devis-cat-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($categorie));
                    ?>
                    <a href="#<?= $slugDevis ?>" class="quicknav-pill" data-target="<?= $slugDevis ?>">
                        <?= htmlspecialchars($categorie) ?>
                        <span><?= count($examens) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php foreach ($examensGroupes as $categorie => $examens):
                $slugDevis = 'devis-cat-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($categorie));
            ?>
            <div class="examen-group" data-group="<?= htmlspecialchars($categorie) ?>" id="<?= $slugDevis ?>">
                <button type="button" class="examen-group-title" data-toggle-cat>
                    <i class="fas fa-chevron-right cat-chevron"></i>
                    <i class="fas fa-folder-open"></i>
                    <?= htmlspecialchars($categorie) ?>
                    <span class="group-count"><?= count($examens) ?></span>
                </button>
                <div class="examen-grid <?= $openByDefaultDevis ? '' : 'is-collapsed' ?>">
                    <?php foreach ($examens as $ex): ?>
                    <label class="examen-card" data-nom="<?= strtolower($ex['nom']) ?>">
                        <input type="checkbox"
                               class="examen-checkbox"
                               data-nom="<?= htmlspecialchars($ex['nom']) ?>"
                               data-prix="<?= $ex['prix'] ?>"
                               onchange="updateSelection(this)">
                        <div class="examen-card-content">
                            <div class="examen-nom"><?= htmlspecialchars($ex['nom']) ?></div>
                            <div class="examen-code"><?= htmlspecialchars($ex['code'] ?? '') ?></div>
                            <div class="examen-prix"><?= number_format((float)$ex['prix'], 0, ',', ' ') ?> FCFA</div>
                        </div>
                        <div class="examen-check-icon"><i class="fas fa-check"></i></div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Colonne droite — Récap -->
    <div class="col-xl-4">
        <div class="card-drouot recap-sticky">
            <h6 class="form-section-title mb-3">
                <i class="fas fa-receipt"></i> Récapitulatif
            </h6>

            <div class="mb-3">
                <label class="form-label-drouot">Nom du patient (optionnel)</label>
                <input type="text" id="nomPatient" class="form-input-drouot"
                       placeholder="Pour personnaliser le devis imprimé">
            </div>

            <div id="recapList">
                <div class="recap-empty">
                    <i class="fas fa-flask"></i>
                    <p>Aucun examen sélectionné</p>
                </div>
            </div>

            <div class="recap-divider"></div>
            <div class="recap-total">
                <span>Total estimé</span>
                <span id="recapTotal">0 FCFA</span>
            </div>
            <div class="recap-divider mb-3"></div>

            <button type="button" class="btn-primary-drouot w-100 justify-content-center"
                    id="btnImprimer" style="padding:.85rem;" onclick="imprimerDevis()" disabled>
                <i class="fas fa-print"></i> Imprimer le devis
            </button>
            <p class="recap-note">
                <i class="fas fa-info-circle"></i>
                Ce devis est purement indicatif et n'engage aucune réservation.
            </p>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════
     ZONE IMPRIMABLE (cachée à l'écran, visible à l'impression)
══════════════════════════════════════ -->
<div id="devisImprimable" class="devis-print-only">
    <div class="devis-header">
        <div class="devis-logo"><i class="fas fa-flask"></i></div>
        <div>
            <div class="devis-labo">Laboratoire Drouot</div>
            <div class="devis-sub">Douala, Cameroun</div>
        </div>
        <div class="devis-date" id="devisDate"></div>
    </div>

    <div class="devis-titre">DEVIS INFORMATIF</div>
    <p class="devis-mention">
        Ce document est une estimation de prix à titre indicatif.
        Il n'a aucune valeur commerciale ni contractuelle.
    </p>

    <div class="devis-patient-line" id="devisPatientLine" style="display:none;">
        Patient : <strong id="devisPatientNom"></strong>
    </div>

    <table class="devis-table">
        <thead>
            <tr><th>Examen</th><th style="text-align:right;">Prix</th></tr>
        </thead>
        <tbody id="devisTableBody"></tbody>
        <tfoot>
            <tr>
                <td style="font-weight:700;">TOTAL ESTIMÉ</td>
                <td style="text-align:right;font-weight:700;" id="devisTotalPrint"></td>
            </tr>
        </tfoot>
    </table>

    <p class="devis-footer">
        Document généré le <span id="devisDateFooter"></span> — Laboratoire Drouot.
        Les prix sont susceptibles d'évoluer. Ce devis ne constitue pas une réservation.
    </p>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.form-section-title {
    font-size:.88rem; font-weight:600; color:#1a2340; margin-bottom:1rem;
    display:flex; align-items:center; gap:8px; padding-bottom:8px; border-bottom:1.5px solid #f0f2f8;
}
.form-section-title i { color:#1E88E5; }
.form-label-drouot { display:block; font-size:.78rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
.form-input-drouot { width:100%; padding:.65rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; }
.form-input-drouot:focus { border-color:#1E88E5; background:#fff; }

.search-input-wrap { position:relative; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-size:.9rem; outline:none; }
.search-input:focus { border-color:#1E88E5; }

/* ── Barre d'outils catégories ── */
.cat-toolbar {
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:10px;
}
.cat-toolbar-actions { display:flex; gap:8px; flex-shrink:0; }
.toolbar-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 12px; border:1.5px solid #dde3f0; border-radius:8px;
    background:#fff; color:#1a2340; font-size:.78rem; font-weight:500;
    cursor:pointer; transition:all .2s;
}
.toolbar-btn:hover { border-color:#1E88E5; color:#1E88E5; background:#F4F9FE; }

.cat-quicknav { display:flex; gap:6px; flex-wrap:wrap; overflow-x:auto; padding-bottom:2px; }
.quicknav-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 10px; border-radius:20px;
    background:#F4F6F9; color:#1a2340; text-decoration:none;
    font-size:.74rem; font-weight:500; white-space:nowrap; transition:all .15s;
}
.quicknav-pill:hover { background:#E3F2FD; color:#1565C0; }
.quicknav-pill span { background:#dde3f0; color:#6b7a99; border-radius:20px; padding:0 6px; font-size:.68rem; font-weight:700; }
.quicknav-pill:hover span { background:#90CAF9; color:#0D47A1; }

/* ── Catégorie repliable ── */
.examen-group { margin-bottom:.75rem; scroll-margin-top:90px; }
.examen-group-title {
    width:100%; display:flex; align-items:center; gap:6px;
    font-size:.78rem; font-weight:600; color:#6b7a99;
    text-transform:uppercase; letter-spacing:.06em;
    margin-bottom:8px; padding:6px 4px;
    background:none; border:none; border-bottom:1.5px solid #f0f2f8;
    cursor:pointer; text-align:left; transition:color .15s;
}
.examen-group-title:hover { color:#1E88E5; }
.examen-group-title i.fa-folder-open { color:#90CAF9; font-size:.8rem; }
.cat-chevron { transition:transform .2s; font-size:.72rem; color:#b0b8cc; }
.examen-group-title[aria-expanded="true"] .cat-chevron { transform:rotate(90deg); }
.group-count { background:#E3F2FD; color:#1565C0; padding:1px 7px; border-radius:20px; font-size:.7rem; margin-left:auto; }

.examen-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:8px; overflow:hidden; }
.examen-grid.is-collapsed { display:none; }

.examen-card { position:relative; border:1.5px solid #dde3f0; border-radius:10px; padding:10px 12px; cursor:pointer; transition:all .18s; background:#fff; display:flex; align-items:flex-start; gap:8px; }
.examen-card:hover { border-color:#1E88E5; background:#f0f7ff; }
.examen-card.selected { border-color:#1E88E5; background:#E3F2FD; }
.examen-checkbox { display:none; }
.examen-card-content { flex:1; }
.examen-nom { font-size:.83rem; font-weight:500; color:#1a2340; line-height:1.3; }
.examen-code { font-size:.7rem; color:#90CAF9; font-family:monospace; margin-top:2px; }
.examen-prix { font-size:.82rem; font-weight:600; color:#1E88E5; margin-top:4px; }
.examen-check-icon { width:20px; height:20px; border-radius:50%; background:#dde3f0; color:#fff; display:flex; align-items:center; justify-content:center; font-size:.65rem; flex-shrink:0; transition:background .15s; }
.examen-card.selected .examen-check-icon { background:#1E88E5; }
.examen-count-badge { margin-left:auto; background:#E3F2FD; color:#1565C0; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600; }

.recap-sticky { position:sticky; top:80px; }
.recap-empty { text-align:center; padding:1.5rem 0; color:#b0b8cc; }
.recap-empty i { font-size:2rem; display:block; margin-bottom:6px; }
.recap-empty p { font-size:.82rem; margin:0; }
.recap-item { display:flex; align-items:center; justify-content:space-between; padding:6px 0; font-size:.83rem; }
.recap-item-nom { color:#1a2340; font-weight:500; flex:1; }
.recap-item-prix { color:#1E88E5; font-weight:600; white-space:nowrap; }
.recap-item-del { width:20px; height:20px; border-radius:5px; background:#FFEBEE; color:#C62828; border:none; cursor:pointer; font-size:.7rem; display:flex; align-items:center; justify-content:center; margin-left:6px; }
.recap-divider { border-top:1.5px solid #f0f2f8; margin:10px 0; }
.recap-total { display:flex; justify-content:space-between; align-items:center; font-size:1rem; font-weight:700; color:#1a2340; }
#recapTotal { color:#1E88E5; }
.recap-note { font-size:.72rem; color:#6b7a99; text-align:center; margin:8px 0 0; display:flex; align-items:center; justify-content:center; gap:5px; }

.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
.w-100 { width:100%; } .justify-content-center { justify-content:center; }

/* Zone imprimable — cachée à l'écran */
.devis-print-only { display:none; }

@media (max-width:768px) {
    .cat-toolbar { flex-direction:column; align-items:stretch; }
}

@media print {
    .no-print { display:none !important; }
    .devis-print-only { display:block !important; padding:20px; font-family:'DM Sans',Arial,sans-serif; }
    .devis-header { display:flex; align-items:center; gap:14px; margin-bottom:20px; border-bottom:2px solid #1E88E5; padding-bottom:14px; }
    .devis-logo { width:48px; height:48px; background:#0D47A1; color:#fff; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
    .devis-labo { font-size:1.1rem; font-weight:700; color:#0D47A1; }
    .devis-sub { font-size:.8rem; color:#6b7a99; }
    .devis-date { margin-left:auto; font-size:.82rem; color:#6b7a99; }
    .devis-titre { text-align:center; font-size:1.2rem; font-weight:700; color:#1a2340; margin:16px 0 8px; letter-spacing:.05em; }
    .devis-mention { text-align:center; font-size:.78rem; color:#C62828; margin-bottom:20px; font-style:italic; }
    .devis-patient-line { font-size:.92rem; margin-bottom:14px; color:#1a2340; }
    .devis-table { width:100%; border-collapse:collapse; margin-bottom:20px; }
    .devis-table th { background:#1E88E5; color:#fff; padding:8px 12px; text-align:left; font-size:.85rem; }
    .devis-table td { padding:8px 12px; border-bottom:1px solid #dde3f0; font-size:.85rem; }
    .devis-table tfoot td { border-top:2px solid #1a2340; font-size:.95rem; }
    .devis-footer { font-size:.72rem; color:#6b7a99; text-align:center; margin-top:30px; line-height:1.5; }
}
</style>

<script>
let selectedExamens = {};

function updateSelection(checkbox) {
    const card = checkbox.closest('.examen-card');
    const id = checkbox.dataset.nom; // identifiant simple ici, pas de DB
    const nom = checkbox.dataset.nom;
    const prix = parseFloat(checkbox.dataset.prix);

    if (checkbox.checked) {
        card.classList.add('selected');
        selectedExamens[id] = { nom, prix };
    } else {
        card.classList.remove('selected');
        delete selectedExamens[id];
    }
    renderRecap();
}

function renderRecap() {
    const list = document.getElementById('recapList');
    const count = Object.keys(selectedExamens).length;
    document.getElementById('examenCount').textContent = count + ' sélectionné(s)';
    document.getElementById('btnImprimer').disabled = count === 0;

    if (count === 0) {
        list.innerHTML = `<div class="recap-empty"><i class="fas fa-flask"></i><p>Aucun examen sélectionné</p></div>`;
        document.getElementById('recapTotal').textContent = '0 FCFA';
        return;
    }

    let html = '';
    let total = 0;
    for (const [id, ex] of Object.entries(selectedExamens)) {
        total += ex.prix;
        html += `<div class="recap-item">
            <span class="recap-item-nom">${ex.nom}</span>
            <span class="recap-item-prix">${ex.prix.toLocaleString('fr-FR')} F</span>
            <button type="button" class="recap-item-del" onclick="removeExamen('${id}')"><i class="fas fa-xmark"></i></button>
        </div>`;
    }
    list.innerHTML = html;
    document.getElementById('recapTotal').textContent = total.toLocaleString('fr-FR') + ' FCFA';
}

function removeExamen(id) {
    const cb = document.querySelector(`.examen-checkbox[data-nom="${id}"]`);
    if (cb) { cb.checked = false; cb.closest('.examen-card').classList.remove('selected'); }
    delete selectedExamens[id];
    renderRecap();
}

// ── Catégories repliables ────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var headers = document.querySelectorAll('[data-toggle-cat]');

    function setGroupState(header, expanded) {
        var grid = header.nextElementSibling;
        header.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        grid.classList.toggle('is-collapsed', !expanded);
    }

    headers.forEach(function (header) {
        var grid = header.nextElementSibling;
        var initiallyOpen = !grid.classList.contains('is-collapsed');
        header.setAttribute('aria-expanded', initiallyOpen ? 'true' : 'false');
        // Mémorise l'état "naturel" (hors recherche) pour pouvoir le
        // restaurer quand le champ de recherche est vidé.
        header.dataset.naturalState = initiallyOpen ? 'open' : 'closed';

        header.addEventListener('click', function () {
            var isOpen = header.getAttribute('aria-expanded') === 'true';
            setGroupState(header, !isOpen);
            header.dataset.naturalState = !isOpen ? 'open' : 'closed';
        });
    });

    document.getElementById('btnDeplierTout')?.addEventListener('click', function () {
        headers.forEach(function (h) { setGroupState(h, true); h.dataset.naturalState = 'open'; });
    });
    document.getElementById('btnReplierTout')?.addEventListener('click', function () {
        headers.forEach(function (h) { setGroupState(h, false); h.dataset.naturalState = 'closed'; });
    });

    // Nav rapide catégorie : déplie puis scrolle
    document.querySelectorAll('.quicknav-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            var section = document.getElementById(pill.getAttribute('data-target'));
            if (!section) return;
            var header = section.querySelector('[data-toggle-cat]');
            if (header && header.getAttribute('aria-expanded') !== 'true') {
                setGroupState(header, true);
            }
        });
    });

    // ── Recherche — déplie automatiquement les catégories qui ont un
    // résultat, et restaure l'état naturel une fois la recherche vidée.
    document.getElementById('examenSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();

        document.querySelectorAll('.examen-card').forEach(card => {
            card.style.display = (card.dataset.nom || '').includes(q) ? '' : 'none';
        });

        document.querySelectorAll('.examen-group').forEach(group => {
            const header = group.querySelector('[data-toggle-cat]');
            const visibleMatches = [...group.querySelectorAll('.examen-card')]
                .some(c => c.style.display !== 'none');
            group.style.display = visibleMatches ? '' : 'none';

            if (q === '') {
                // Recherche vidée : on revient à l'état naturel de chaque catégorie.
                setGroupState(header, header.dataset.naturalState === 'open');
            } else if (visibleMatches) {
                // Un résultat existe dans cette catégorie : on la déplie
                // temporairement pour qu'il soit visible.
                setGroupState(header, true);
            }
        });
    });
});

function imprimerDevis() {
    const nomPatient = document.getElementById('nomPatient').value.trim();
    const patientLine = document.getElementById('devisPatientLine');
    if (nomPatient) {
        document.getElementById('devisPatientNom').textContent = nomPatient;
        patientLine.style.display = 'block';
    } else {
        patientLine.style.display = 'none';
    }

    let total = 0;
    let rows = '';
    for (const ex of Object.values(selectedExamens)) {
        total += ex.prix;
        rows += `<tr><td>${ex.nom}</td><td style="text-align:right;">${ex.prix.toLocaleString('fr-FR')} F</td></tr>`;
    }
    document.getElementById('devisTableBody').innerHTML = rows;
    document.getElementById('devisTotalPrint').textContent = total.toLocaleString('fr-FR') + ' FCFA';

    const now = new Date();
    const dateStr = now.toLocaleDateString('fr-FR') + ' à ' + now.toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'});
    document.getElementById('devisDate').textContent = dateStr;
    document.getElementById('devisDateFooter').textContent = dateStr;

    window.print();
}
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>