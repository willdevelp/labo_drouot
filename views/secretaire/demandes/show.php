<?php
/**
 * Détail demande d'examens — Récapitulatif + Facture proforma
 * views/secretaire/demandes/show.php
 */
include __DIR__ . '/../../../views/layouts/header.php';
?>

<?php if ($created): ?>
<div class="alert-drouot alert-success mb-4 no-print">
    <i class="fas fa-circle-check"></i>
    Demande créée avec succès ! Facture proforma et échantillon générés.
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4 no-print">
    <div>
        <h1 class="page-title">
            Demande
            <span class="ref-badge">
                DE-<?= str_pad($demande['id'], 4, '0', STR_PAD_LEFT) ?>
            </span>
        </h1>
        <p class="page-sub">
            Créée le <?= date('d/m/Y à H:i', strtotime($demande['created_at'])) ?>
            par <?= htmlspecialchars($demande['createur'] ?? '—') ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>secretaire/patients/<?= $demande['pid'] ?>"
           class="btn-outline-drouot">
            <i class="fas fa-user"></i> Dossier patient
        </a>
        <a href="<?= BASE_URL ?>secretaire/demandes"
           class="btn-outline-drouot">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- Colonne gauche — masquée à l'impression -->
    <div class="col-xl-8 no-print">

        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-user text-primary me-2"></i> Patient
            </h6>
            <div class="info-row-inline">
                <div>
                    <div class="info-val-big">
                        <?= htmlspecialchars($demande['patient_nom']) ?>
                    </div>
                    <div class="info-val-sub">
                        <i class="fas fa-phone"></i>
                        <?= htmlspecialchars($demande['telephone']) ?>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <?php
                    $statusBadges = [
                        'pending'    => ['class'=>'badge-pending',  'label'=>'En attente'],
                        'paid'       => ['class'=>'badge-paid',     'label'=>'Payé'],
                        'processing' => ['class'=>'badge-warn',     'label'=>'En cours'],
                        'completed'  => ['class'=>'badge-normal',   'label'=>'Terminé'],
                    ];
                    $sb = $statusBadges[$demande['status']] ??
                          ['class'=>'badge-pending','label'=>$demande['status']];
                    $pb = $demande['payment_status'] === 'paid'
                        ? ['class'=>'badge-normal','label'=>'Payé']
                        : ['class'=>'badge-critical','label'=>'Impayé'];
                    ?>
                    <span class="<?= $sb['class'] ?>"><?= $sb['label'] ?></span>
                    <span class="<?= $pb['class'] ?>"><?= $pb['label'] ?></span>
                </div>
            </div>
        </div>

        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-flask text-primary me-2"></i>
                Examens demandés (<?= count($examensItems) ?>)
            </h6>
            <table class="table-drouot">
                <thead>
                    <tr>
                        <th>Code</th><th>Examen</th><th>Catégorie</th>
                        <th style="text-align:right;">Prix</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($examensItems as $item): ?>
                    <tr>
                        <td><span style="font-family:monospace;font-size:.78rem;color:#90CAF9;">
                            <?= htmlspecialchars($item['code'] ?? '—') ?>
                        </span></td>
                        <td style="font-weight:500;"><?= htmlspecialchars($item['nom']) ?></td>
                        <td><span class="cat-badge"><?= htmlspecialchars($item['categorie'] ?? '—') ?></span></td>
                        <td style="text-align:right;font-weight:600;color:#1E88E5;">
                            <?= number_format((float)$item['price'], 0, ',', ' ') ?> F
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right;font-weight:700;padding:12px 10px;">TOTAL</td>
                        <td style="text-align:right;font-weight:700;color:#1E88E5;padding:12px 10px;">
                            <?= number_format((float)$demande['total_price'], 0, ',', ' ') ?> FCFA
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($echantillon): ?>
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-vial text-primary me-2"></i> Échantillon
            </h6>
            <div class="echantillon-box">
                <div>
                    <div class="ech-code"><?= htmlspecialchars($echantillon['code_barre']) ?></div>
                    <div class="ech-status">Statut : <span class="badge-pending"><?= ucfirst($echantillon['status']) ?></span></div>
                </div>
                <div class="ech-date">
                    Collecté le <?= $echantillon['collected_at']
                        ? date('d/m/Y H:i', strtotime($echantillon['collected_at'])) : '—' ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Colonne droite -->
    <div class="col-xl-4">

        <!-- Bouton imprimer — masqué à l'impression -->
        <div class="no-print mb-3">
            <button onclick="window.print()"
                    class="btn-primary-drouot w-100 justify-content-center"
                    style="padding:.85rem;">
                <i class="fas fa-print"></i> Imprimer la facture
            </button>
        </div>

        <!-- ════════════════════════════════════════
             FACTURE — visible écran ET impression
        ════════════════════════════════════════ -->
        <div id="facturePrint">

            <!-- En-tête -->
            <div class="fp-header">
                <div class="fp-logo-wrap">
                    <div class="fp-logo-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <div>
                        <div class="fp-labo-nom">LABORATOIRE DROUOT</div>
                        <div class="fp-labo-slogan">Votre santé, notre priorité</div>
                    </div>
                </div>
                <div class="fp-doc-info">
                    <div class="fp-doc-type">FACTURE PROFORMA</div>
                    <div class="fp-doc-num">N° FAC-<?= str_pad($facture['id'] ?? 0, 4, '0', STR_PAD_LEFT) ?></div>
                    <div class="fp-doc-date">Date : <?= date('d/m/Y') ?></div>
                </div>
            </div>

            <div class="fp-band"></div>

            <!-- Émetteur / Patient -->
            <div class="fp-parties">
                <div class="fp-partie">
                    <div class="fp-partie-titre">ÉMETTEUR</div>
                    <div class="fp-partie-nom">Laboratoire Drouot</div>
                    <div class="fp-partie-detail">Douala, Cameroun</div>
                    <div class="fp-partie-detail">Tél : +237 699 000 000</div>
                    <div class="fp-partie-detail">analyses@labodrouot.cm</div>
                    <div class="fp-partie-detail">N° Contrib : P0123456789</div>
                </div>
                <div class="fp-partie fp-partie-right">
                    <div class="fp-partie-titre">PATIENT / CLIENT</div>
                    <div class="fp-partie-nom"><?= htmlspecialchars($demande['patient_nom']) ?></div>
                    <div class="fp-partie-detail">Tél : <?= htmlspecialchars($demande['telephone']) ?></div>
                    <?php if (!empty($demande['email'])): ?>
                    <div class="fp-partie-detail"><?= htmlspecialchars($demande['email']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($demande['medecin_nom'])): ?>
                    <div class="fp-partie-detail">Prescrit par : Dr <?= htmlspecialchars($demande['medecin_nom']) ?></div>
                    <?php endif; ?>
                    <div class="fp-partie-detail">Réf. : DE-<?= str_pad($demande['id'], 4, '0', STR_PAD_LEFT) ?></div>
                </div>
            </div>

            <!-- Tableau examens -->
            <table class="fp-table">
                <thead>
                    <tr>
                        <th class="fp-th-code">Code</th>
                        <th class="fp-th-nom">Désignation</th>
                        <th class="fp-th-cat">Catégorie</th>
                        <th class="fp-th-prix">Montant (FCFA)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($examensItems as $i => $item): ?>
                    <tr class="<?= $i % 2 === 0 ? 'fp-tr-even' : 'fp-tr-odd' ?>">
                        <td class="fp-td-code"><?= htmlspecialchars($item['code'] ?? '—') ?></td>
                        <td class="fp-td-nom"><?= htmlspecialchars($item['nom']) ?></td>
                        <td class="fp-td-cat"><?= htmlspecialchars($item['categorie'] ?? '—') ?></td>
                        <td class="fp-td-prix"><?= number_format((float)$item['price'], 0, ',', ' ') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fp-tfoot-total">
                        <td colspan="3" class="fp-total-label">TOTAL À PAYER</td>
                        <td class="fp-total-val">
                            <?= number_format((float)$demande['total_price'], 0, ',', ' ') ?> FCFA
                        </td>
                    </tr>
                    <?php if (!empty($demande['remise']) && $demande['remise'] > 0): ?>
                    <tr>
                        <td colspan="3" class="fp-total-label" style="font-weight:400;color:#6b7a99;">Remise</td>
                        <td class="fp-total-val" style="color:#2E7D32;">
                            - <?= number_format((float)$demande['remise'], 0, ',', ' ') ?> FCFA
                        </td>
                    </tr>
                    <tr class="fp-tfoot-total">
                        <td colspan="3" class="fp-total-label">NET À PAYER</td>
                        <td class="fp-total-val">
                            <?= number_format((float)$demande['montant_net'], 0, ',', ' ') ?> FCFA
                        </td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>

            <!-- Statut -->
            <div class="fp-statut-row">
                <span class="fp-statut-label">Statut :</span>
                <?php if ($facture && $facture['statut'] === 'payee'): ?>
                    <span class="fp-statut-badge fp-statut-ok">✓ PAYÉE</span>
                <?php elseif ($facture && $facture['statut'] === 'partiellement_payee'): ?>
                    <span class="fp-statut-badge fp-statut-warn">◑ PARTIELLEMENT PAYÉE</span>
                <?php else: ?>
                    <span class="fp-statut-badge fp-statut-att">⏳ EN ATTENTE DE PAIEMENT</span>
                <?php endif; ?>
            </div>

            <!-- Modes paiement -->
            <div class="fp-modes">
                <span class="fp-modes-label">Modes acceptés :</span>
                <span class="fp-mode-pill">💵 Espèces</span>
                <span class="fp-mode-pill">📱 MTN MoMo</span>
                <span class="fp-mode-pill">📱 Orange Money</span>
                <span class="fp-mode-pill">💳 Carte</span>
            </div>

            <!-- Footer -->
            <div class="fp-footer">
                <div class="fp-footer-note">
                    Ce document est une facture proforma. Il vaut engagement de prise en charge
                    des examens listés ci-dessus. Toute contestation doit être soumise dans les
                    48h suivant la date d'émission.
                </div>
                <div class="fp-sig-line">
                    <span>Signature / Cachet du laboratoire</span>
                    <span class="fp-sig-blank"></span>
                </div>
                <div class="fp-footer-barcode">
                    FAC-<?= str_pad($facture['id'] ?? 0, 4, '0', STR_PAD_LEFT) ?>
                    · DE-<?= str_pad($demande['id'], 4, '0', STR_PAD_LEFT) ?>
                    · Émis le <?= date('d/m/Y à H:i') ?>
                </div>
            </div>

        </div>
        <!-- FIN #facturePrint -->

        <!-- Actions — masquées à l'impression -->
        <div class="card-drouot mt-3 no-print">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-bolt text-warning me-2"></i> Actions
            </h6>
            <div class="d-flex flex-column gap-2">
                <a href="<?= BASE_URL ?>secretaire/patients/<?= $demande['pid'] ?>"
                   class="btn-outline-drouot justify-content-center">
                    <i class="fas fa-user"></i> Dossier patient
                </a>
                <a href="<?= BASE_URL ?>secretaire/demandes/create?patient_id=<?= $demande['pid'] ?>"
                   class="btn-primary-drouot justify-content-center">
                    <i class="fas fa-plus"></i> Nouvelle demande
                </a>
                <a href="<?= BASE_URL ?>secretaire/demandes"
                   class="btn-outline-drouot justify-content-center">
                    <i class="fas fa-list"></i> Toutes les demandes
                </a>
            </div>
        </div>

    </div>
</div>

<!-- ══════════════════════════════════════
     STYLES ÉCRAN
══════════════════════════════════════ -->
<style>
/* ── Général ── */
.page-title { font-size:1.3rem;font-weight:600;color:#1a2340;margin:0;display:flex;align-items:center;gap:10px; }
.page-sub   { font-size:.85rem;color:#6b7a99;margin:2px 0 0; }
.ref-badge  { background:#E3F2FD;color:#1565C0;padding:3px 12px;border-radius:20px;font-size:.85rem;font-family:monospace; }
.card-section-title { font-size:.88rem;font-weight:600;color:#1a2340;margin:0; }
.info-row-inline { display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap; }
.info-val-big { font-size:1rem;font-weight:600;color:#1a2340; }
.info-val-sub { font-size:.8rem;color:#6b7a99;display:flex;align-items:center;gap:5px;margin-top:2px; }
.cat-badge { background:#F4F6F9;color:#6b7a99;padding:2px 8px;border-radius:6px;font-size:.75rem; }
.table-drouot { width:100%;border-collapse:collapse;font-size:.83rem; }
.table-drouot th { padding:8px 10px;text-align:left;font-size:.73rem;font-weight:600;color:#6b7a99;text-transform:uppercase;letter-spacing:.05em;border-bottom:1.5px solid #f0f2f8;background:#fafbfc; }
.table-drouot td { padding:10px;color:#1a2340;border-bottom:1px solid #f0f2f8;vertical-align:middle; }
.table-drouot tfoot td { border-top:2px solid #dde3f0;border-bottom:none; }
.echantillon-box { display:flex;align-items:center;justify-content:space-between;background:#f4f6f9;border-radius:10px;padding:12px 14px; }
.ech-code { font-family:monospace;font-size:.9rem;font-weight:600;color:#1E88E5; }
.ech-status { font-size:.78rem;color:#6b7a99;margin-top:3px; }
.ech-date { font-size:.75rem;color:#6b7a99; }
.alert-drouot { padding:.85rem 1rem;border-radius:10px;display:flex;align-items:center;gap:8px;font-size:.88rem; }
.alert-success { background:#E8F5E9;color:#2E7D32;border:1px solid #C8E6C9; }
.btn-outline-drouot { display:inline-flex;align-items:center;gap:6px;padding:.6rem 1.2rem;border:1.5px solid #dde3f0;border-radius:9px;background:#fff;color:#1a2340;font-size:.88rem;font-weight:500;text-decoration:none;transition:all .2s;cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5;color:#1E88E5; }
.w-100 { width:100%; } .justify-content-center { justify-content:center; } .ms-auto { margin-left:auto; }

/* ── Facture écran ── */
#facturePrint {
    background:#fff;
    border:2px solid #E3F2FD;
    border-radius:12px;
    padding:20px;
    font-family:'DM Sans',Arial,sans-serif;
}
.fp-header { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:0; }
.fp-logo-wrap { display:flex;align-items:center;gap:10px; }
.fp-logo-icon { width:44px;height:44px;border-radius:10px;background:#1E88E5;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.2rem; }
.fp-labo-nom { font-size:.9rem;font-weight:700;color:#0D47A1;letter-spacing:.04em; }
.fp-labo-slogan { font-size:.68rem;color:#6b7a99;font-style:italic; }
.fp-doc-info { text-align:right; }
.fp-doc-type { font-size:.62rem;font-weight:700;color:#6b7a99;text-transform:uppercase;letter-spacing:.1em; }
.fp-doc-num { font-size:.9rem;font-weight:700;color:#0D47A1;font-family:monospace; }
.fp-doc-date { font-size:.72rem;color:#6b7a99; }
.fp-band { height:4px;background:linear-gradient(90deg,#1E88E5,#0D47A1);border-radius:2px;margin:12px 0; }
.fp-parties { display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px; }
.fp-partie-titre { font-size:.62rem;font-weight:700;color:#6b7a99;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px; }
.fp-partie-nom { font-size:.85rem;font-weight:600;color:#1a2340; }
.fp-partie-detail { font-size:.72rem;color:#6b7a99;margin-top:1px; }
.fp-partie-right { border-left:2px solid #f0f2f8;padding-left:12px; }
.fp-table { width:100%;border-collapse:collapse;font-size:.75rem;margin-bottom:10px; }
.fp-table thead tr { background:#0D47A1; }
.fp-table thead th { color:#fff;padding:7px 8px;font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em; }
.fp-th-prix,.fp-td-prix { text-align:right; }
.fp-tr-even { background:#fff; }
.fp-tr-odd  { background:#F8FAFF; }
.fp-table td { padding:6px 8px;color:#1a2340;border-bottom:1px solid #f0f2f8; }
.fp-td-code { font-family:monospace;font-size:.7rem;color:#1E88E5; }
.fp-td-nom  { font-weight:500; }
.fp-td-cat  { color:#6b7a99;font-size:.7rem; }
.fp-td-prix { font-weight:600;color:#0D47A1; }
.fp-tfoot-total td { border-top:2px solid #0D47A1;padding:8px;font-weight:700; }
.fp-total-label { text-align:right;font-size:.78rem;color:#0D47A1;text-transform:uppercase;letter-spacing:.04em; }
.fp-total-val { text-align:right;font-size:.85rem;font-weight:700;color:#0D47A1; }
.fp-statut-row { display:flex;align-items:center;gap:10px;margin:10px 0 8px;font-size:.78rem; }
.fp-statut-label { color:#6b7a99;font-weight:500; }
.fp-statut-badge { padding:3px 12px;border-radius:20px;font-size:.7rem;font-weight:700;letter-spacing:.04em; }
.fp-statut-ok   { background:#E8F5E9;color:#2E7D32; }
.fp-statut-warn { background:#FFF8E1;color:#F57F17; }
.fp-statut-att  { background:#FFF8E1;color:#F57F17; }
.fp-modes { display:flex;align-items:center;flex-wrap:wrap;gap:5px;margin-bottom:12px; }
.fp-modes-label { font-size:.68rem;color:#6b7a99; }
.fp-mode-pill { font-size:.65rem;background:#F4F6F9;border:1px solid #dde3f0;border-radius:20px;padding:2px 8px;color:#1a2340; }
.fp-footer { border-top:1.5px solid #dde3f0;padding-top:8px; }
.fp-footer-note { font-size:.65rem;color:#9aa3b5;font-style:italic;line-height:1.5;margin-bottom:8px; }
.fp-sig-line { display:flex;justify-content:flex-end;align-items:flex-end;gap:10px;margin-bottom:4px;font-size:.72rem;color:#6b7a99; }
.fp-sig-blank { width:130px;border-bottom:1px solid #1a2340;height:20px; }
.fp-footer-barcode { font-family:monospace;font-size:.6rem;color:#b0b8cc;text-align:center;margin-top:6px; }
</style>

<!-- ══════════════════════════════════════
     @MEDIA PRINT — stratégie simple et fiable
     On masque tout avec .no-print,
     et on ne touche PAS au positionnement
     de #facturePrint pour qu'il reste dans le flux.
══════════════════════════════════════ -->
<style>
@media print {
    /* Forcer l'impression des couleurs de fond */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    /* Masquer tous les éléments UI de l'appli */
    .no-print,
    .sidebar,
    .main-sidebar,
    nav,
    header,
    .navbar,
    .main-header,
    footer,
    .main-footer,
    .content-header,
    .col-xl-8,
    .breadcrumb,
    [class*="sidebar"],
    [class*="navbar"],
    [class*="header"]:not(#facturePrint *),
    [class*="footer"]:not(#facturePrint *),
    [class*="nav"]:not(#facturePrint *) {
        display: none !important;
    }

    /* Reset body/html */
    html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    /* Masquer le row Bootstrap et la colonne wrapper */
    .row, .col-xl-4 {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        float: none !important;
    }

    /* La facture prend toute la page — dans le flux normal */
    #facturePrint {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 12mm 15mm !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: #fff !important;
        font-family: Arial, sans-serif !important;
        font-size: 10pt !important;
    }

    /* En-tête */
    .fp-header { display:flex !important; justify-content:space-between !important; margin-bottom:4px !important; }
    .fp-logo-wrap { display:flex !important; align-items:center !important; gap:8px !important; }
    .fp-logo-icon {
        width:36px !important; height:36px !important;
        background:#1E88E5 !important;
        border-radius:8px !important;
        display:flex !important; align-items:center !important; justify-content:center !important;
        font-size:16px !important; color:#fff !important;
    }
    .fp-labo-nom { font-size:13pt !important; font-weight:bold !important; color:#0D47A1 !important; }
    .fp-labo-slogan { font-size:8pt !important; color:#666 !important; font-style:italic !important; }
    .fp-doc-info { text-align:right !important; }
    .fp-doc-type { font-size:7pt !important; color:#888 !important; text-transform:uppercase !important; letter-spacing:.1em !important; }
    .fp-doc-num { font-size:12pt !important; font-weight:bold !important; color:#0D47A1 !important; font-family:monospace !important; }
    .fp-doc-date { font-size:8pt !important; color:#666 !important; }

    /* Bande */
    .fp-band {
        height:4px !important;
        background:#1E88E5 !important;
        margin:8px 0 !important;
        border-radius:0 !important;
    }

    /* Parties */
    .fp-parties {
        display:grid !important;
        grid-template-columns:1fr 1fr !important;
        gap:14px !important;
        margin-bottom:12px !important;
    }
    .fp-partie-titre { font-size:7pt !important; font-weight:bold !important; color:#888 !important; text-transform:uppercase !important; letter-spacing:.08em !important; margin-bottom:3px !important; }
    .fp-partie-nom   { font-size:10pt !important; font-weight:bold !important; color:#000 !important; }
    .fp-partie-detail { font-size:8pt !important; color:#555 !important; margin-top:1px !important; }
    .fp-partie-right { border-left:1.5px solid #ddd !important; padding-left:12px !important; }

    /* Tableau */
    .fp-table { width:100% !important; border-collapse:collapse !important; margin-bottom:8px !important; font-size:9pt !important; }
    .fp-table thead tr { background:#0D47A1 !important; }
    .fp-table thead th {
        color:#fff !important; padding:6px 8px !important;
        font-size:8pt !important; font-weight:bold !important;
        text-align:left !important; text-transform:uppercase !important;
    }
    .fp-th-prix, .fp-td-prix { text-align:right !important; }
    .fp-tr-even { background:#fff !important; }
    .fp-tr-odd  { background:#F0F4FF !important; }
    .fp-table td { padding:5px 8px !important; font-size:9pt !important; border-bottom:1px solid #e0e0e0 !important; }
    .fp-td-code { font-family:monospace !important; color:#1565C0 !important; font-size:8pt !important; }
    .fp-td-nom  { font-weight:500 !important; color:#000 !important; }
    .fp-td-cat  { color:#666 !important; font-size:8pt !important; }
    .fp-td-prix { font-weight:bold !important; color:#0D47A1 !important; }
    .fp-tfoot-total td { border-top:2px solid #0D47A1 !important; padding:7px 8px !important; }
    .fp-total-label { text-align:right !important; font-size:10pt !important; font-weight:bold !important; color:#0D47A1 !important; text-transform:uppercase !important; }
    .fp-total-val   { text-align:right !important; font-size:11pt !important; font-weight:bold !important; color:#0D47A1 !important; }

    /* Statut */
    .fp-statut-row { display:flex !important; align-items:center !important; gap:8px !important; margin:8px 0 !important; }
    .fp-statut-label { font-size:9pt !important; color:#555 !important; }
    .fp-statut-badge { padding:2px 10px !important; border-radius:20px !important; font-size:8pt !important; font-weight:bold !important; }
    .fp-statut-ok   { background:#E8F5E9 !important; color:#2E7D32 !important; }
    .fp-statut-att  { background:#FFF8E1 !important; color:#E65100 !important; }
    .fp-statut-warn { background:#FFF8E1 !important; color:#E65100 !important; }

    /* Modes */
    .fp-modes { display:flex !important; flex-wrap:wrap !important; gap:4px !important; align-items:center !important; margin-bottom:8px !important; }
    .fp-modes-label { font-size:8pt !important; color:#666 !important; }
    .fp-mode-pill { font-size:7pt !important; border:1px solid #ccc !important; border-radius:20px !important; padding:1px 7px !important; color:#333 !important; background:#f5f5f5 !important; }

    /* Footer */
    .fp-footer { border-top:1px solid #ccc !important; padding-top:8px !important; margin-top:8px !important; }
    .fp-footer-note { font-size:7pt !important; color:#999 !important; font-style:italic !important; line-height:1.4 !important; margin-bottom:10px !important; }
    .fp-sig-line { display:flex !important; justify-content:flex-end !important; align-items:flex-end !important; gap:8px !important; font-size:8pt !important; color:#444 !important; margin-bottom:4px !important; }
    .fp-sig-blank { width:120px !important; border-bottom:1px solid #333 !important; height:30px !important; }
    .fp-footer-barcode { font-family:monospace !important; font-size:7pt !important; color:#bbb !important; text-align:center !important; margin-top:6px !important; }

    /* Format A4 */
    @page {
        size: A4 portrait;
        margin: 0;
    }
}
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>