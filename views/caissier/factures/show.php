<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<?php if ($success): ?>
<div class="alert-drouot alert-success mb-4">
    <i class="fas fa-circle-check"></i>
    Paiement enregistré avec succès ! Analyses transmises au laboratoire.
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert-drouot alert-danger mb-4">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Facture
            <span class="ref-badge">
                <?= htmlspecialchars($facture['numero'] ?? 'FAC-' . str_pad($facture['id'], 4, '0', STR_PAD_LEFT)) ?>
            </span>
        </h1>
        <p class="page-sub">
            <?= date('d/m/Y à H:i', strtotime($facture['created_at'])) ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>caissier/factures"
       class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<div class="row g-4">

    <!-- Colonne gauche -->
    <div class="col-xl-7">

        <!-- Patient -->
        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-user text-primary me-2"></i> Patient
            </h6>
            <div class="patient-info-row">
                <div class="patient-big-av">
                    <?= strtoupper(substr($facture['patient_nom'], 0, 2)) ?>
                </div>
                <div>
                    <div class="patient-full">
                        <?= htmlspecialchars($facture['patient_nom']) ?>
                    </div>
                    <div class="patient-phone">
                        <i class="fas fa-phone"></i>
                        <?= htmlspecialchars($facture['telephone']) ?>
                    </div>
                    <?php if (!empty($facture['adresse'])): ?>
                    <div class="patient-phone">
                        <i class="fas fa-location-dot"></i>
                        <?= htmlspecialchars($facture['adresse']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="ms-auto">
                    <?php if ($facture['statut'] === 'payee'): ?>
                        <span class="badge-normal" style="font-size:.85rem;padding:5px 14px;">
                            ✅ Payé
                        </span>
                    <?php elseif ($facture['statut'] === 'partiellement_payee'): ?>
                        <span class="badge-warning" style="font-size:.85rem;padding:5px 14px;">
                            ⏳ Partiel
                        </span>
                    <?php else: ?>
                        <span class="badge-critical" style="font-size:.85rem;padding:5px 14px;">
                            ⏳ En attente
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Couverture assurance -->
        <?php if (!empty($facture['assurance_id'])): ?>
        <div class="card-drouot mb-4 assurance-status-card status-<?= $facture['assurance_statut'] ?>">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-shield-heart text-primary me-2"></i>
                Couverture assurance
            </h6>
            <div class="assurance-status-row">
                <div>
                    <div class="assurance-status-nom">
                        <?= htmlspecialchars($facture['assurance_nom']) ?>
                    </div>
                    <?php if ($facture['numero_police']): ?>
                    <div class="assurance-status-police">
                        N° police : <?= htmlspecialchars($facture['numero_police']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($facture['assurance_statut'] === 'validee'): ?>
                        <span class="badge-normal">✅ Validée</span>
                    <?php elseif ($facture['assurance_statut'] === 'en_attente'): ?>
                        <span class="badge-warning">⏳ En attente assureur</span>
                    <?php elseif ($facture['assurance_statut'] === 'rejetee'): ?>
                        <span class="badge-critical">❌ Rejetée</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($facture['assurance_statut'] === 'validee'): ?>
            <div class="assurance-breakdown">
                <div class="breakdown-row">
                    <span>Montant total des examens</span>
                    <span><?= number_format((float)$facture['montant_total'], 0, ',', ' ') ?> F</span>
                </div>
                <div class="breakdown-row breakdown-assurance">
                    <span><i class="fas fa-shield-heart"></i> Pris en charge assurance</span>
                    <span>- <?= number_format((float)$facture['montant_total'] - (float)$facture['montant_patient'], 0, ',', ' ') ?> F</span>
                </div>
                <div class="breakdown-row breakdown-patient">
                    <span>Reste à charge patient</span>
                    <span><?= number_format((float)$facture['montant_patient'], 0, ',', ' ') ?> F</span>
                </div>
            </div>
            <?php elseif ($facture['assurance_statut'] === 'en_attente'): ?>
            <div class="info-note warn">
                <i class="fas fa-clock"></i>
                En attente de validation par l'assureur sur son portail.
                Le patient règle le montant total en attendant.
            </div>
            <?php elseif ($facture['assurance_statut'] === 'rejetee'): ?>
            <div class="info-note danger">
                <i class="fas fa-circle-exclamation"></i>
                Prise en charge rejetée — le patient règle le montant total.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Examens -->
        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-flask text-primary me-2"></i>
                Examens (<?= count($examensItems) ?>)
            </h6>
            <table class="table-drouot">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Examen</th>
                        <th>Catégorie</th>
                        <th style="text-align:right;">Prix</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($examensItems as $item): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:.75rem;color:#90CAF9;">
                            <?= htmlspecialchars($item['code'] ?? '—') ?>
                        </td>
                        <td style="font-weight:500;">
                            <?= htmlspecialchars($item['nom']) ?>
                        </td>
                        <td>
                            <span class="cat-badge">
                                <?= htmlspecialchars($item['categorie'] ?? '—') ?>
                            </span>
                        </td>
                        <td style="text-align:right;font-weight:600;color:#1E88E5;">
                            <?= number_format((float)$item['price'], 0, ',', ' ') ?> F
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right;font-weight:700;padding:12px 10px;">
                            TOTAL EXAMENS
                        </td>
                        <td style="text-align:right;font-weight:700;font-size:1.1rem;
                            color:#0D47A1;padding:12px 10px;">
                            <?= number_format((float)$facture['montant_total'], 0, ',', ' ') ?> FCFA
                        </td>
                    </tr>
                    <?php if (!empty($facture['assurance_id']) && $facture['assurance_statut'] === 'validee'): ?>
                    <tr>
                        <td colspan="3" style="text-align:right;font-weight:700;padding:8px 10px;color:#2E7D32;">
                            DONT À LA CHARGE DU PATIENT
                        </td>
                        <td style="text-align:right;font-weight:700;font-size:1.1rem;
                            color:#2E7D32;padding:8px 10px;">
                            <?= number_format((float)$facture['montant_patient'], 0, ',', ' ') ?> FCFA
                        </td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>

        <!-- Historique paiements (si paiements partiels) -->
        <?php if (!empty($paiements)): ?>
        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-history text-primary me-2"></i>
                Historique des paiements
            </h6>
            <table class="table-drouot">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Mode</th>
                        <th>Caissier</th>
                        <th style="text-align:right;">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $modesLabels = [
                        'cash'      => '💵 Espèces',
                        'momo'      => '📱 Mobile Money',
                        'card'      => '💳 Carte',
                        'insurance' => '🏥 Assurance',
                        'virement'  => '🏦 Virement',
                    ];
                    foreach ($paiements as $p): ?>
                    <tr>
                        <td style="font-size:.8rem;color:#6b7a99;">
                            <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                        </td>
                        <td>
                            <span class="cat-badge">
                                <?= $modesLabels[$p['mode_paiement']] ?? $p['mode_paiement'] ?>
                            </span>
                        </td>
                        <td style="font-size:.82rem;">
                            <?= htmlspecialchars($p['caissier_name']) ?>
                        </td>
                        <td style="text-align:right;font-weight:600;color:#1E88E5;">
                            <?= number_format((float)$p['montant'], 0, ',', ' ') ?> F
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

    <!-- Colonne droite — Encaissement -->
    <div class="col-xl-5">

        <?php if (in_array($facture['statut'], ['emise', 'partiellement_payee'])): ?>
        <!-- Formulaire paiement -->
        <div class="card-drouot paiement-card mb-4">
            <h6 class="card-section-title mb-4">
                <i class="fas fa-cash-register text-primary me-2"></i>
                Encaisser le paiement
            </h6>

            <!-- Montant -->
            <div class="montant-display">
                <div class="montant-label">
                    Montant à encaisser
                    <?php if (!empty($facture['assurance_id']) && $facture['assurance_statut'] === 'validee'): ?>
                    <span class="montant-sub-label">(reste à charge patient)</span>
                    <?php endif; ?>
                </div>
                <div class="montant-value">
                    <?= number_format((float)$facture['montant_patient'], 0, ',', ' ') ?>
                    <span>FCFA</span>
                </div>
                <?php if (!empty($facture['assurance_id']) && $facture['assurance_statut'] === 'validee'): ?>
                <div class="montant-original">
                    Montant total : <?= number_format((float)$facture['montant_total'], 0, ',', ' ') ?> FCFA
                    — <?= htmlspecialchars($facture['assurance_nom']) ?> prend en charge
                    <?= number_format((float)$facture['montant_total'] - (float)$facture['montant_patient'], 0, ',', ' ') ?> FCFA
                </div>
                <?php endif; ?>
            </div>

            <!-- Modes de paiement -->
            <div class="mode-grid mb-4">
                <label class="mode-card">
                    <input type="radio" name="mode_selected"
                           value="cash" class="mode-radio">
                    <div class="mode-icon">💵</div>
                    <div class="mode-label">Espèces</div>
                </label>
                <label class="mode-card">
                    <input type="radio" name="mode_selected"
                           value="momo" class="mode-radio">
                    <div class="mode-icon">📱</div>
                    <div class="mode-label">Mobile Money</div>
                </label>
                <label class="mode-card">
                    <input type="radio" name="mode_selected"
                           value="card" class="mode-radio">
                    <div class="mode-icon">💳</div>
                    <div class="mode-label">Carte</div>
                </label>
                <label class="mode-card">
                    <input type="radio" name="mode_selected"
                           value="insurance" class="mode-radio">
                    <div class="mode-icon">🏥</div>
                    <div class="mode-label">Assurance</div>
                </label>
            </div>

            <form method="POST"
                  action="<?= BASE_URL ?>caissier/factures/<?= $facture['id'] ?>/payer"
                  id="formPaiement">
                <input type="hidden" name="mode_paiement" id="modePaiementInput">
                <input type="hidden" name="montant"
                       value="<?= (float)$facture['montant_patient'] ?>">
                <button type="submit"
                        class="btn-pay w-100"
                        id="btnPayer" disabled>
                    <i class="fas fa-check-circle"></i>
                    Valider le paiement
                </button>
            </form>

            <p class="pay-note">
                <i class="fas fa-lock"></i>
                Paiement sécurisé — Cette action est irréversible.
            </p>
        </div>

        <?php else: ?>
        <!-- Déjà payé -->
        <div class="card-drouot mb-4 text-center">
            <div style="font-size:3rem;margin-bottom:12px;">✅</div>
            <h6 class="card-section-title justify-content-center mb-2">
                Paiement effectué
            </h6>
            <?php if (!empty($facture['mode_paiement'])): ?>
            <p style="font-size:.85rem;color:#6b7a99;margin-bottom:1rem;">
                Mode :
                <strong>
                    <?php $modes = [
                        'cash'      => '💵 Espèces',
                        'momo'      => '📱 Mobile Money',
                        'card'      => '💳 Carte bancaire',
                        'insurance' => '🏥 Assurance',
                        'virement'  => '🏦 Virement',
                    ]; ?>
                    <?= $modes[$facture['mode_paiement']] ?? htmlspecialchars($facture['mode_paiement']) ?>
                </strong>
            </p>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>caissier/recus/<?= $facture['id'] ?>"
               class="btn-success-drouot w-100 justify-content-center">
                <i class="fas fa-receipt"></i> Imprimer le reçu
            </a>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-bolt text-warning me-2"></i> Actions
            </h6>
            <div class="d-flex flex-column gap-2">
                <a href="<?= BASE_URL ?>secretaire/patients/<?= $facture['patient_id'] ?>"
                   class="btn-outline-drouot justify-content-center">
                    <i class="fas fa-user"></i> Dossier patient
                </a>
                <a href="<?= BASE_URL ?>secretaire/demandes/<?= $facture['demande_id'] ?>"
                   class="btn-outline-drouot justify-content-center">
                    <i class="fas fa-flask"></i> Voir la demande
                </a>
                <?php if ($facture['statut'] === 'payee'): ?>
                <a href="<?= BASE_URL ?>caissier/recus/<?= $facture['id'] ?>"
                   class="btn-success-drouot justify-content-center">
                    <i class="fas fa-receipt"></i> Voir le reçu
                </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>caissier/factures"
                   class="btn-outline-drouot justify-content-center">
                    <i class="fas fa-list"></i> Toutes les factures
                </a>
            </div>
        </div>

    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0;
    display:flex; align-items:center; gap:10px; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.ref-badge  { background:#E3F2FD; color:#1565C0; padding:3px 12px;
    border-radius:20px; font-size:.85rem; font-family:monospace; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340;
    margin:0; display:flex; align-items:center; }

/* Patient */
.patient-info-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
.patient-big-av { width:52px; height:52px; border-radius:50%;
    background:#E3F2FD; color:#1565C0;
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; font-weight:600; flex-shrink:0; }
.patient-full  { font-size:1rem; font-weight:600; color:#1a2340; }
.patient-phone { font-size:.78rem; color:#6b7a99;
    display:flex; align-items:center; gap:5px; margin-top:2px; }

/* Assurance status */
.assurance-status-card { border-left:3px solid #8E24AA; }
.assurance-status-card.status-rejetee { border-left-color:#C62828; }
.assurance-status-card.status-en_attente { border-left-color:#F9A825; }
.assurance-status-row {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:10px; flex-wrap:wrap;
}
.assurance-status-nom { font-size:.92rem; font-weight:600; color:#1a2340; }
.assurance-status-police { font-size:.78rem; color:#6b7a99; margin-top:2px; font-family:monospace; }

.assurance-breakdown { margin-top:14px; padding-top:12px; border-top:1px solid #f0f2f8; }
.breakdown-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:5px 0; font-size:.85rem; color:#1a2340;
}
.breakdown-assurance { color:#8E24AA; font-weight:600; }
.breakdown-assurance i { margin-right:4px; }
.breakdown-patient {
    font-weight:700; font-size:.95rem; color:#2E7D32;
    border-top:1.5px solid #f0f2f8; margin-top:4px; padding-top:8px;
}

.info-note {
    display:flex; align-items:flex-start; gap:8px;
    font-size:.82rem; padding:10px 12px; border-radius:8px; margin-top:10px;
}
.info-note.warn { color:#F57F17; background:#FFF8E1; }
.info-note.danger { color:#C62828; background:#FFEBEE; }
.info-note i { margin-top:2px; flex-shrink:0; }

/* Table */
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem;
    font-weight:600; color:#6b7a99; text-transform:uppercase;
    letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340;
    border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tfoot td { border-top:2px solid #dde3f0; border-bottom:none; background:#f8fafc; }
.cat-badge { background:#F4F6F9; color:#6b7a99;
    padding:2px 8px; border-radius:6px; font-size:.75rem; }

/* Paiement card */
.paiement-card { border:2px solid #E3F2FD; }
.montant-display { text-align:center; padding:1rem;
    background:#f0f7ff; border-radius:12px; margin-bottom:1.25rem; }
.montant-label { font-size:.75rem; color:#6b7a99;
    text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
.montant-sub-label { display:block; font-size:.7rem; color:#8E24AA; text-transform:none; letter-spacing:0; margin-top:2px; }
.montant-value { font-size:1.8rem; font-weight:700; color:#0D47A1; }
.montant-value span { font-size:1rem; font-weight:500; }
.montant-original { font-size:.75rem; color:#6b7a99; margin-top:8px; line-height:1.4; }

/* Mode de paiement */
.mode-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.mode-card { border:2px solid #dde3f0; border-radius:12px;
    padding:12px 8px; text-align:center; cursor:pointer;
    transition:all .18s; display:flex; flex-direction:column;
    align-items:center; gap:4px; }
.mode-card:hover { border-color:#1E88E5; background:#f0f7ff; }
.mode-card.selected { border-color:#1E88E5; background:#E3F2FD; }
.mode-radio { display:none; }
.mode-icon  { font-size:1.5rem; }
.mode-label { font-size:.78rem; font-weight:500; color:#1a2340; }

.btn-pay {
    width:100%; padding:1rem; border:none; border-radius:12px;
    background:linear-gradient(135deg, #1E88E5, #0D47A1);
    color:#fff; font-size:1rem; font-weight:600;
    cursor:pointer; display:flex; align-items:center;
    justify-content:center; gap:8px;
    transition:all .2s; font-family:'DM Sans',sans-serif;
    box-shadow:0 4px 16px rgba(13,71,161,.25);
}
.btn-pay:disabled { background:#b0b8cc; cursor:not-allowed; box-shadow:none; }
.btn-pay:not(:disabled):hover { transform:translateY(-1px);
    box-shadow:0 8px 24px rgba(13,71,161,.3); }

.pay-note { font-size:.72rem; color:#6b7a99; text-align:center;
    margin:8px 0 0; display:flex; align-items:center;
    justify-content:center; gap:5px; }

/* Alerts */
.alert-drouot { padding:.85rem 1rem; border-radius:10px;
    display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }

/* Badges */
.badge-normal   { background:#E8F5E9; color:#2E7D32;
    padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:600; }
.badge-critical { background:#FFEBEE; color:#C62828;
    padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:600; }
.badge-warning  { background:#FFF8E1; color:#F57F17;
    padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:600; }

/* Boutons */
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px;
    background:#fff; color:#1a2340; font-size:.88rem; font-weight:500;
    text-decoration:none; transition:all .2s;
    font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }

.btn-success-drouot { display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:none; border-radius:9px;
    background:#E8F5E9; color:#2E7D32; font-size:.88rem; font-weight:500;
    text-decoration:none; transition:all .2s;
    font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-success-drouot:hover { background:#2E7D32; color:#fff; }

.w-100 { width:100%; }
.justify-content-center { justify-content:center; }
.ms-auto { margin-left:auto; }
.text-center { text-align:center; }
.d-flex { display:flex; }
.flex-column { flex-direction:column; }
.gap-2 { gap:.5rem; }
</style>

<script>
// Sélection mode paiement
document.querySelectorAll('.mode-card').forEach(card => {
    card.addEventListener('click', () => {
        document.querySelectorAll('.mode-card').forEach(c =>
            c.classList.remove('selected'));
        card.classList.add('selected');
        const val = card.querySelector('.mode-radio').value;
        document.getElementById('modePaiementInput').value = val;
        document.getElementById('btnPayer').disabled = false;
    });
});

// Confirmation paiement
document.getElementById('formPaiement')?.addEventListener('submit', function(e) {
    const mode = document.getElementById('modePaiementInput').value;
    if (!mode) { e.preventDefault(); return; }
    const modes = {
        cash      : 'Espèces',
        momo      : 'Mobile Money',
        card      : 'Carte bancaire',
        insurance : 'Assurance',
    };
    if (!confirm(`Confirmer le paiement par ${modes[mode]} ?\n\nCette action est irréversible.`)) {
        e.preventDefault();
        return;
    }
    const btn = document.getElementById('btnPayer');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    btn.disabled  = true;
});
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>