<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<?php if (isset($_GET['rejected'])): ?>
<div class="alert-drouot alert-warning mb-4">
    <i class="fas fa-rotate-left"></i>
    Résultats rejetés — renvoyés au technicien pour correction.
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Résultats à valider</h1>
        <p class="page-sub"><?= count($validations) ?> en attente</p>
    </div>
</div>

<!-- Validations/rejets saisis hors ligne, en attente de confirmation manuelle -->
<div class="card-drouot mb-4" id="pendingValidationsBox" style="display:none; border-left:3px solid #C62828;">
    <h6 class="card-section-title mb-2" style="display:flex;align-items:center;gap:8px;">
        <i class="fas fa-triangle-exclamation" style="color:#C62828;"></i>
        Décisions en attente d'envoi — à confirmer manuellement
    </h6>
    <p style="font-size:.8rem;color:#6b7a99;margin-bottom:12px;">
        Ces actions ont été saisies hors ligne. Elles ne sont pas envoyées
        automatiquement : vérifie d'abord que les résultats de l'échantillon
        n'ont pas changé sur le serveur, puis confirme l'envoi.
    </p>
    <div id="pendingValidationsList"></div>
</div>

<script>
(function () {
    const box  = document.getElementById('pendingValidationsBox');
    const list = document.getElementById('pendingValidationsList');
    if (!box || typeof OfflineOutbox === 'undefined') return;

    function timeAgo(ts) {
        const mins = Math.round((Date.now() - ts) / 60000);
        if (mins < 1) return 'à l\'instant';
        if (mins < 60) return 'il y a ' + mins + ' min';
        const hrs = Math.round(mins / 60);
        return 'il y a ' + hrs + ' h';
    }

    async function render() {
        const items = (await OfflineOutbox.getAll()).filter(i => i.type === 'validation');
        if (items.length === 0) { box.style.display = 'none'; return; }
        box.style.display = 'block';

        list.innerHTML = items.map(i => {
            const ageWarning = (Date.now() - i.createdAt) > 10 * 60 * 1000;
            const actionLabel = i.subtype === 'rejeter' ? 'Rejet' : 'Validation';
            return `
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;
                            border-bottom:1px solid #f0f2f8;flex-wrap:wrap;">
                    <i class="fas ${i.subtype === 'rejeter' ? 'fa-rotate-left' : 'fa-check-double'}"
                       style="color:${i.subtype === 'rejeter' ? '#C62828' : '#2E7D32'};"></i>
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:500;font-size:.85rem;">${i.label}</div>
                        <div style="font-size:.75rem;color:${ageWarning ? '#C62828' : '#6b7a99'};">
                            ${actionLabel} saisi(e) ${timeAgo(i.createdAt)}
                            ${ageWarning ? ' — vérifie bien que les données n\'ont pas changé' : ''}
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>biologiste/validations/${i.echantillonId}"
                       class="btn-outline-drouot" style="font-size:.78rem;padding:.4rem .8rem;">
                        Vérifier l'échantillon
                    </a>
                    <button type="button" class="btn-success-drouot"
                            style="font-size:.78rem;padding:.4rem .8rem;"
                            onclick="confirmAndSyncValidation(${i.localId})">
                        Confirmer et envoyer
                    </button>
                </div>
            `;
        }).join('');
    }

    window.confirmAndSyncValidation = async function (localId) {
        if (!navigator.onLine) {
            alert('Toujours hors ligne : impossible d\'envoyer pour le moment.');
            return;
        }
        if (!confirm(
            'As-tu vérifié que les résultats de cet échantillon n\'ont pas ' +
            'changé depuis la saisie hors ligne ?\n\n' +
            'Confirme uniquement si tu es sûr(e) de vouloir envoyer cette décision.'
        )) return;

        const result = await OfflineOutbox.syncOne(localId);
        if (result.ok) {
            OfflineOutbox.toast('Décision envoyée avec succès.', 'success');
        } else {
            alert('Échec de l\'envoi (réseau indisponible). Réessaie plus tard.');
        }
        render();
    };

    window.addEventListener('outbox:updated', render);
    render();
})();
</script>

<!-- Recherche -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search" class="search-input"
                   placeholder="Patient, code barre..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-search"></i> Rechercher
        </button>
        <a href="<?= BASE_URL ?>biologiste/validations"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Liste -->
<div class="card-drouot">
    <?php if (empty($validations)): ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-3x mb-3"
               style="color:#2E7D32;"></i>
            <p class="text-muted">
                Aucun résultat en attente. Tout est validé ! ✅
            </p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Priorité</th>
                    <th>Code barre</th>
                    <th>Patient</th>
                    <th>Examens</th>
                    <th>Résultats</th>
                    <th>Anormaux</th>
                    <th>Collecté</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($validations as $v): ?>
                <tr class="<?= $v['nb_anormaux'] > 0 ? 'row-urgent' : '' ?>">
                    <td>
                        <?php if ($v['nb_anormaux'] > 0): ?>
                            <div class="priority-dot dot-red"
                                 title="Valeurs anormales"></div>
                        <?php else: ?>
                            <div class="priority-dot dot-green"
                                 title="Normal"></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="ref-code">
                            <?= htmlspecialchars($v['code_barre']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:500;font-size:.88rem;">
                            <?= htmlspecialchars($v['patient_nom']) ?>
                        </div>
                        <div style="font-size:.75rem;color:#6b7a99;">
                            <?= htmlspecialchars($v['telephone']) ?>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;max-width:160px;">
                        <?= htmlspecialchars(
                            mb_strimwidth($v['examens'] ?? '—', 0, 40, '...')
                        ) ?>
                    </td>
                    <td>
                        <span class="badge-pending">
                            <?= $v['nb_resultats'] ?> param.
                        </span>
                    </td>
                    <td>
                        <?php if ($v['nb_anormaux'] > 0): ?>
                            <span class="badge-critical">
                                ⚠️ <?= $v['nb_anormaux'] ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-normal">✅ OK</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= $v['collected_at']
                            ? date('d/m H:i', strtotime($v['collected_at']))
                            : '—' ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>biologiste/validations/<?= $v['id'] ?>"
                           class="btn-valider
                           <?= $v['nb_anormaux'] > 0
                               ? 'btn-valider-urgent' : '' ?>">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap { position:relative; flex:1; min-width:200px; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.row-urgent td { background:#fff9f9; }
.row-urgent:hover td { background:#fff5f5 !important; }
.priority-dot { width:12px; height:12px; border-radius:50%; }
.dot-red   { background:#C62828; box-shadow:0 0 0 3px rgba(198,40,40,.15); }
.dot-green { background:#2E7D32; box-shadow:0 0 0 3px rgba(46,125,50,.15); }
.ref-code { font-family:monospace; font-size:.78rem; color:#1E88E5; font-weight:600; }
.btn-valider { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:8px; background:#1E88E5; color:#fff; font-size:.78rem; font-weight:500; text-decoration:none; transition:background .2s; }
.btn-valider:hover { background:#0D47A1; color:#fff; }
.btn-valider-urgent { background:#C62828 !important; animation:pulse-red 2s infinite; }
@keyframes pulse-red { 0%,100%{box-shadow:0 0 0 0 rgba(198,40,40,.3);} 50%{box-shadow:0 0 0 6px rgba(198,40,40,0);} }
.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-warning { background:#FFF8E1; color:#F57F17; border:1px solid #FFE082; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>