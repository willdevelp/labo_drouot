<?php
/**
 * Validation biologique — Vue détaillée
 */
include __DIR__ . '/../../../views/layouts/header.php';

// Calculs patient
$age = $echantillon['date_naissance']
    ? date_diff(
        date_create($echantillon['date_naissance']),
        date_create('today')
    )->y
    : null;
$sexe = $echantillon['sexe'] === 'F' ? 'Féminin' : 'Masculin';
?>

<?php if ($success): ?>
<div class="alert-drouot alert-success mb-4">
    <i class="fas fa-circle-check"></i>
    Résultats validés avec succès ! Rapport généré et disponible pour le patient.
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert-drouot alert-danger mb-4">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Validation biologique
            <span class="ref-badge">
                <?= htmlspecialchars($echantillon['code_barre']) ?>
            </span>
        </h1>
        <p class="page-sub">
            Patient :
            <strong><?= htmlspecialchars($echantillon['patient_nom']) ?></strong>
            — <?= $sexe ?>
            <?= $age ? "— $age ans" : '' ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>biologiste/validations"
       class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<div class="row g-4">

    <!-- Colonne gauche — Résultats -->
    <div class="col-xl-8">

        <!-- Résumé anormaux (si présents) -->
        <?php
        $totalAnormaux = 0;
        foreach ($resultatsData as $params) {
            foreach ($params as $p) {
                if ($p['is_abnormal']) $totalAnormaux++;
            }
        }
        ?>
        <?php if ($totalAnormaux > 0): ?>
        <div class="alert-anormaux mb-4">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong><?= $totalAnormaux ?> valeur(s) anormale(s)
                détectée(s)</strong> — Vérifiez attentivement avant validation.
            </div>
        </div>
        <?php endif; ?>

        <!-- Résultats par examen -->
        <?php foreach ($examens as $ex): ?>
        <?php $params = $resultatsData[$ex['id']] ?? []; ?>

        <div class="card-drouot mb-4 examen-val-block">
            <div class="examen-val-header">
                <div>
                    <div class="examen-val-nom">
                        <?= htmlspecialchars($ex['nom']) ?>
                    </div>
                    <div class="examen-val-meta">
                        <span class="val-code-badge">
                            <?= htmlspecialchars($ex['code'] ?? '') ?>
                        </span>
                        <span class="val-cat-badge">
                            <?= htmlspecialchars($ex['categorie'] ?? '') ?>
                        </span>
                    </div>
                </div>
                <?php
                $abnCount = count(array_filter(
                    $params, fn($p) => $p['is_abnormal']
                ));
                ?>
                <?php if ($abnCount > 0): ?>
                    <span class="badge-critical">
                        ⚠️ <?= $abnCount ?> anormal(aux)
                    </span>
                <?php else: ?>
                    <span class="badge-normal">✅ Normal</span>
                <?php endif; ?>
            </div>

            <!-- Table résultats -->
            <?php if (empty($params)): ?>
                <div class="no-params">
                    <i class="fas fa-info-circle"></i>
                    Aucun paramètre configuré.
                </div>
            <?php else: ?>
            <table class="table-resultats">
                <thead>
                    <tr>
                        <th>Paramètre</th>
                        <th>Valeur</th>
                        <th>Unité</th>
                        <th>Norme</th>
                        <th>Interprétation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($params as $param): ?>
                    <tr class="<?= $param['is_abnormal']
                        ? 'row-abnormal' : '' ?>">
                        <td class="param-name">
                            <?= htmlspecialchars($param['libelle']) ?>
                        </td>
                        <td class="param-value
                            <?= $param['is_abnormal']
                                ? 'value-abnormal' : 'value-normal' ?>">
                            <?php if ($param['valeur'] !== null): ?>
                                <strong>
                                    <?= htmlspecialchars($param['valeur']) ?>
                                </strong>
                            <?php else: ?>
                                <span style="color:#b0b8cc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="param-unit">
                            <?= htmlspecialchars($param['unite'] ?? '') ?>
                        </td>
                        <td class="param-norme">
                            <?php if ($param['valeur_min'] !== null
                                   || $param['valeur_max'] !== null): ?>
                                <span class="norme-range">
                                    <?= $param['valeur_min'] ?? '?' ?>
                                    –
                                    <?= $param['valeur_max'] ?? '?' ?>
                                </span>
                            <?php else: ?>
                                <span style="color:#b0b8cc;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($param['valeur'] !== null): ?>
                            <?php
                            $interp = $param['interpretation'] ?? 'normal';
                            $interpConfig = [
                                'normal' => ['interp-normal','✅ Normal'],
                                'bas'    => ['interp-low',   '⬇️ Bas'],
                                'élevé'  => ['interp-high',  '⬆️ Élevé'],
                            ];
                            [$ic,$il] = $interpConfig[$interp]
                                ?? ['interp-normal','—'];
                            ?>
                            <span class="interp-badge <?= $ic ?>">
                                <?= $il ?>
                            </span>
                            <?php else: ?>
                                <span class="interp-badge interp-missing">
                                    ⚪ Non saisi
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- Colonne droite — Validation -->
    <div class="col-xl-4">

        <!-- Statut validation -->
        <?php if ($validationExist): ?>
        <div class="card-drouot mb-4 already-validated">
            <div style="text-align:center;padding:.5rem 0;">
                <div style="font-size:2.5rem;margin-bottom:8px;">✅</div>
                <div class="card-section-title justify-content-center mb-1">
                    Déjà validé
                </div>
                <p style="font-size:.8rem;color:#6b7a99;">
                    Par <?= htmlspecialchars(
                        $validationExist['biologiste_nom'] ?? '—'
                    ) ?><br>
                    Le <?= date('d/m/Y à H:i',
                        strtotime($validationExist['validated_at'])) ?>
                </p>
                <?php if ($validationExist['commentaire']): ?>
                <div class="existing-comment">
                    <i class="fas fa-quote-left"></i>
                    <?= htmlspecialchars($validationExist['commentaire']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulaire validation -->
        <?php if ($echantillon['status'] === 'analyse'
                  && !$success): ?>
        <div class="card-drouot mb-4 validation-card">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-signature text-primary me-2"></i>
                Validation biologique
            </h6>

            <!-- Commentaire -->
            <div class="mb-3">
                <label class="form-label-drouot">
                    Interprétation / Commentaire biologique
                </label>
                <textarea
                    id="commentaireText"
                    class="form-input-drouot"
                    rows="4"
                    placeholder="Ajouter une interprétation médicale, observations cliniques, recommandations..."></textarea>
            </div>

            <!-- Suggestions rapides -->
            <div class="suggestions mb-3">
                <p class="suggestions-label">Suggestions rapides :</p>
                <div class="d-flex flex-wrap gap-1">
                    <?php
                    $suggestions = [
                        'Résultats dans les normes.',
                        'Légère anomalie, surveillance recommandée.',
                        'Valeurs significativement anormales. Consultation urgente recommandée.',
                        'À corréler avec les données cliniques.',
                        'Contrôle à effectuer dans 3 mois.',
                    ];
                    foreach ($suggestions as $s):
                    ?>
                    <button type="button"
                            class="suggestion-btn"
                            onclick="addSuggestion('<?= addslashes($s) ?>')">
                        <?= htmlspecialchars($s) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bouton valider -->
            <form method="POST"
                  action="<?= BASE_URL ?>biologiste/validations/<?= $echantillon['id'] ?>/valider"
                  id="formValider">
                <input type="hidden" name="commentaire"
                       id="commentaireInput">
                <button type="submit"
                        class="btn-success-drouot w-100 justify-content-center mb-2"
                        style="padding:.85rem;"
                        onclick="submitValider(event)">
                    <i class="fas fa-check-double"></i>
                    Valider et signer
                </button>
            </form>

            <div class="val-divider">ou</div>

            <!-- Bouton rejeter -->
            <button type="button"
                    class="btn-danger-drouot w-100 justify-content-center"
                    style="padding:.75rem;"
                    onclick="showRejetModal()">
                <i class="fas fa-rotate-left"></i>
                Rejeter — Demander correction
            </button>
        </div>
        <?php endif; ?>

        <!-- Infos patient -->
        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-user text-primary me-2"></i>
                Patient
            </h6>
            <div class="info-row">
                <span class="info-label">Nom</span>
                <span class="info-value">
                    <?= htmlspecialchars($echantillon['patient_nom']) ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Sexe</span>
                <span class="info-value"><?= $sexe ?></span>
            </div>
            <?php if ($age): ?>
            <div class="info-row">
                <span class="info-label">Âge</span>
                <span class="info-value"><?= $age ?> ans</span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Téléphone</span>
                <span class="info-value">
                    <?= htmlspecialchars($echantillon['telephone']) ?>
                </span>
            </div>
        </div>

        <!-- Résumé -->
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-chart-simple text-primary me-2"></i>
                Résumé analyse
            </h6>
            <?php
            $totalParams  = 0;
            $totalSaisis  = 0;
            $totalAnorm   = 0;
            foreach ($resultatsData as $params) {
                foreach ($params as $p) {
                    $totalParams++;
                    if ($p['valeur'] !== null) $totalSaisis++;
                    if ($p['is_abnormal'])     $totalAnorm++;
                }
            }
            $pct = $totalParams > 0
                ? round($totalSaisis / $totalParams * 100) : 0;
            ?>
            <div class="info-row">
                <span class="info-label">Examens</span>
                <span class="info-value"><?= count($examens) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Paramètres</span>
                <span class="info-value">
                    <?= $totalSaisis ?>/<?= $totalParams ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Anormaux</span>
                <span class="info-value">
                    <?php if ($totalAnorm > 0): ?>
                        <span class="badge-critical">
                            <?= $totalAnorm ?> anormal(aux)
                        </span>
                    <?php else: ?>
                        <span class="badge-normal">Aucun</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Complétude</span>
                <span class="info-value">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div style="flex:1;height:6px;background:#f0f2f8;
                            border-radius:10px;overflow:hidden;">
                            <div style="width:<?= $pct ?>%;height:100%;
                                background:<?= $pct==100?'#2E7D32':'#F9A825' ?>;
                                border-radius:10px;"></div>
                        </div>
                        <span style="font-size:.75rem;font-weight:600;">
                            <?= $pct ?>%
                        </span>
                    </div>
                </span>
            </div>
        </div>

    </div>
</div>

<!-- Modal rejet -->
<div class="modal-overlay" id="rejetModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h6 class="modal-title">
                <i class="fas fa-rotate-left text-warning me-2"></i>
                Rejeter les résultats
            </h6>
            <button onclick="hideRejetModal()" class="modal-close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="font-size:.85rem;color:#6b7a99;margin-bottom:12px;">
                Les résultats seront renvoyés au technicien pour correction.
                Indiquez le motif ci-dessous.
            </p>
            <form method="POST"
                  action="<?= BASE_URL ?>biologiste/validations/<?= $echantillon['id'] ?>/rejeter"
                  id="formRejet">
                <label class="form-label-drouot mb-1">
                    Motif de rejet <span style="color:#C62828;">*</span>
                </label>
                <textarea name="motif_rejet"
                          class="form-input-drouot"
                          rows="3"
                          placeholder="Ex: Valeur de glycémie incohérente, échantillon à reprélever..."
                          required></textarea>
                <div class="d-flex gap-2 mt-3">
                    <button type="button"
                            onclick="hideRejetModal()"
                            class="btn-outline-drouot flex-1 justify-content-center">
                        Annuler
                    </button>
                    <button type="submit"
                            class="btn-danger-drouot flex-1 justify-content-center">
                        <i class="fas fa-rotate-left"></i>
                        Confirmer le rejet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0;
    display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.ref-badge  { background:#E3F2FD; color:#1565C0; padding:3px 12px;
    border-radius:20px; font-size:.82rem; font-family:monospace; }
.card-section-title {
    font-size:.88rem; font-weight:600; color:#1a2340; margin:0;
    display:flex; align-items:center;
}

/* Alert anormaux */
.alert-anormaux {
    display:flex; align-items:center; gap:12px;
    background:#FFF3E0; border:1.5px solid #FFB74D;
    border-radius:10px; padding:12px 16px;
    font-size:.88rem; color:#E65100;
}
.alert-anormaux i { font-size:1.2rem; flex-shrink:0; }

/* Examen block */
.examen-val-block { border-left:3px solid #1E88E5; }
.examen-val-header {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:1rem; padding-bottom:10px;
    border-bottom:1.5px solid #f0f2f8; flex-wrap:wrap; gap:8px;
}
.examen-val-nom  { font-size:.95rem; font-weight:600; color:#1a2340; }
.examen-val-meta { display:flex; gap:8px; margin-top:3px; }
.val-code-badge  { font-family:monospace; font-size:.72rem; color:#90CAF9; }
.val-cat-badge   { background:#F4F6F9; color:#6b7a99;
    padding:1px 8px; border-radius:6px; font-size:.72rem; }

/* Table résultats */
.table-resultats { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-resultats th { padding:8px 10px; text-align:left; font-size:.73rem;
    font-weight:600; color:#6b7a99; text-transform:uppercase;
    letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-resultats td { padding:9px 10px; border-bottom:1px solid #f0f2f8;
    vertical-align:middle; }
.table-resultats tr:last-child td { border-bottom:none; }
.row-abnormal td { background:#fff5f5; }
.param-name  { color:#1a2340; font-weight:500; }
.param-value { font-size:.9rem; }
.value-abnormal { color:#C62828; }
.value-normal   { color:#1a2340; }
.param-unit  { color:#6b7a99; font-size:.78rem; }
.norme-range { background:#E3F2FD; color:#1565C0;
    padding:2px 6px; border-radius:4px; font-size:.72rem; }
.interp-badge { display:inline-flex; align-items:center; gap:4px;
    padding:3px 8px; border-radius:20px; font-size:.72rem; font-weight:500; }
.interp-normal  { background:#E8F5E9; color:#2E7D32; }
.interp-low     { background:#E3F2FD; color:#1565C0; }
.interp-high    { background:#FFEBEE; color:#C62828; }
.interp-missing { background:#F4F6F9; color:#b0b8cc; }
.no-params { background:#FFF8E1; border-radius:8px; padding:10px 14px;
    font-size:.82rem; color:#F57F17; display:flex; align-items:center; gap:8px; }

/* Validation card */
.validation-card { border:2px solid #E8F5E9; }
.already-validated { border:2px solid #E8F5E9; background:#f9fff9; }
.existing-comment { background:#f0f7ff; border-radius:8px;
    padding:8px 12px; font-size:.8rem; color:#6b7a99;
    margin-top:8px; font-style:italic; }

.form-label-drouot { display:block; font-size:.78rem; font-weight:600;
    color:#6b7a99; text-transform:uppercase; letter-spacing:.05em;
    margin-bottom:5px; }
.form-input-drouot { width:100%; padding:.7rem .9rem;
    border:1.5px solid #dde3f0; border-radius:10px;
    font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340;
    background:#fafbfd; outline:none; transition:all .2s; resize:vertical; }
.form-input-drouot:focus { border-color:#1E88E5; background:#fff;
    box-shadow:0 0 0 3px rgba(30,136,229,.08); }

/* Suggestions */
.suggestions-label { font-size:.72rem; color:#6b7a99; margin-bottom:6px; }
.suggestion-btn { background:#f0f7ff; color:#1565C0;
    border:1px solid #BBDEFB; border-radius:6px;
    padding:3px 8px; font-size:.72rem; cursor:pointer;
    transition:all .15s; font-family:'DM Sans',sans-serif; }
.suggestion-btn:hover { background:#1E88E5; color:#fff; border-color:#1E88E5; }

.val-divider { text-align:center; color:#b0b8cc;
    font-size:.78rem; margin:8px 0; position:relative; }
.val-divider::before {
    content:''; position:absolute; top:50%; left:0; right:0;
    height:1px; background:#f0f2f8;
}
.val-divider { position:relative; }

/* Infos */
.info-row { display:flex; justify-content:space-between; align-items:center;
    padding:7px 0; border-bottom:1px solid #f0f2f8; font-size:.83rem; }
.info-row:last-child { border-bottom:none; }
.info-label { color:#6b7a99; }
.info-value { font-weight:500; flex:1; text-align:right; }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5);
    z-index:2000; display:flex; align-items:center; justify-content:center;
    backdrop-filter:blur(2px); }
.modal-box { background:#fff; border-radius:16px; padding:1.5rem;
    width:100%; max-width:480px; margin:1rem;
    box-shadow:0 20px 60px rgba(0,0,0,.2); }
.modal-header { display:flex; align-items:center; justify-content:space-between;
    margin-bottom:1rem; padding-bottom:10px; border-bottom:1.5px solid #f0f2f8; }
.modal-title { font-size:.95rem; font-weight:600; color:#1a2340;
    margin:0; display:flex; align-items:center; }
.modal-close { background:none; border:none; cursor:pointer;
    color:#6b7a99; font-size:1rem; padding:4px;
    border-radius:6px; transition:background .15s; }
.modal-close:hover { background:#f4f6f9; }
.modal-body { }

/* Alerts */
.alert-drouot { padding:.85rem 1rem; border-radius:10px;
    display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }

.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px;
    background:#fff; color:#1a2340; font-size:.88rem; font-weight:500;
    text-decoration:none; transition:all .2s;
    font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
.w-100 { width:100%; } .justify-content-center { justify-content:center; }
.flex-1 { flex:1; }
</style>

<script>
function addSuggestion(text) {
    const ta = document.getElementById('commentaireText');
    if (ta.value && !ta.value.endsWith(' ')) ta.value += ' ';
    ta.value += text;
    ta.focus();
}

const echantillonId   = <?= (int) $echantillon['id'] ?>;
const echantillonCode = <?= json_encode($echantillon['code_barre']) ?>;
const patientNom      = <?= json_encode($echantillon['patient_nom']) ?>;

// Signale si une validation/un rejet de CET échantillon est déjà en
// file, en attente de confirmation manuelle.
async function checkPendingValidation() {
    try {
        const items = await OfflineOutbox.getAll();
        const pending = items.filter(i => i.type === 'validation' && i.echantillonId === echantillonId);
        if (pending.length > 0) {
            OfflineOutbox.toast(
                'Une action de validation/rejet pour cet échantillon est en attente ' +
                'd\'envoi manuel (voir la liste des validations).'
            );
        }
    } catch (err) { /* IndexedDB indisponible, on ignore */ }
}
checkPendingValidation();

async function submitValider(e) {
    e.preventDefault();
    const comm = document.getElementById('commentaireText').value;
    document.getElementById('commentaireInput').value = comm;

    const nbAnormaux = <?= $totalAnormaux ?>;
    if (nbAnormaux > 0 && !comm.trim()) {
        if (!confirm(
            '⚠️ Il y a ' + nbAnormaux + ' valeur(s) anormale(s).\n\n' +
            'Vous n\'avez pas ajouté de commentaire.\n' +
            'Voulez-vous valider quand même ?'
        )) return;
    }

    if (!confirm('Confirmer la validation biologique ?\n\n' +
        'Cette action est définitive et génèrera le rapport patient.')) return;

    const form = document.getElementById('formValider');
    const fd = new FormData(form);
    const fields = [];
    fd.forEach((v, k) => fields.push([k, v]));

    if (navigator.onLine) {
        try {
            const res = await fetch(form.action, { method: 'POST', body: fd });
            window.location.href = res.url || form.action;
            return;
        } catch (err) {
            // tombe dans la mise en file ci-dessous
        }
    }

    // Hors ligne : on NE valide PAS automatiquement en tâche de fond.
    // La validation biologique reste une décision médicale — elle est
    // mise en attente et devra être confirmée explicitement au retour
    // du réseau, avec possibilité de vérifier que rien n'a changé.
    await OfflineOutbox.enqueue({
        type: 'validation',
        subtype: 'valider',
        manualOnly: true,
        echantillonId,
        url: form.action,
        label: 'Validation — ' + patientNom + ' (' + echantillonCode + ')',
        fields,
    });
    alert(
        'Hors ligne : cette validation a été mise en attente.\n\n' +
        'Elle NE sera PAS envoyée automatiquement — tu devras la confirmer ' +
        'manuellement depuis la liste des validations une fois reconnecté, ' +
        'après avoir vérifié que les résultats n\'ont pas changé entre-temps.'
    );
    window.location.href = '<?= BASE_URL ?>biologiste/validations';
}

function showRejetModal() {
    document.getElementById('rejetModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideRejetModal() {
    document.getElementById('rejetModal').style.display = 'none';
    document.body.style.overflow = '';
}

document.getElementById('formRejet').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = this;
    const motif = form.querySelector('[name="motif_rejet"]').value.trim();
    if (!motif) {
        alert('Veuillez indiquer un motif de rejet.');
        return;
    }

    const fd = new FormData(form);
    const fields = [];
    fd.forEach((v, k) => fields.push([k, v]));

    if (navigator.onLine) {
        try {
            const res = await fetch(form.action, { method: 'POST', body: fd });
            window.location.href = res.url || '<?= BASE_URL ?>biologiste/validations';
            return;
        } catch (err) {
            // tombe dans la mise en file ci-dessous
        }
    }

    await OfflineOutbox.enqueue({
        type: 'validation',
        subtype: 'rejeter',
        manualOnly: true,
        echantillonId,
        url: form.action,
        label: 'Rejet — ' + patientNom + ' (' + echantillonCode + ')',
        fields,
    });
    hideRejetModal();
    alert(
        'Hors ligne : ce rejet a été mis en attente.\n\n' +
        'Il devra être confirmé manuellement depuis la liste des validations ' +
        'une fois reconnecté.'
    );
    window.location.href = '<?= BASE_URL ?>biologiste/validations';
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') hideRejetModal();
});
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>