<?php
/**
 * Reçu de paiement — imprimable
 */
$aUneAssurance = !empty($facture['assurance_id']) && $facture['assurance_statut'] === 'validee';
$partAssurance = $aUneAssurance
    ? (float)$facture['montant_total'] - (float)$facture['montant_patient']
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu FAC-<?= str_pad($facture['id'], 4, '0', STR_PAD_LEFT) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans', Arial, sans-serif;
            background:#f4f6f9; display:flex;
            justify-content:center; padding:2rem; }
        .recu { background:#fff; width:380px; padding:2rem;
            border-radius:16px; box-shadow:0 4px 24px rgba(0,0,0,.08); }

        /* Header */
        .recu-header { text-align:center; margin-bottom:1.5rem; }
        .recu-logo { width:52px; height:52px; background:#0D47A1;
            border-radius:14px; display:flex; align-items:center;
            justify-content:center; margin:0 auto 10px; font-size:1.4rem; }
        .recu-labo { font-size:1rem; font-weight:700; color:#0D47A1; }
        .recu-sub  { font-size:.75rem; color:#6b7a99; }
        .recu-ref  { margin-top:12px; background:#f0f7ff; border-radius:8px;
            padding:6px 12px; display:inline-block;
            font-family:monospace; font-size:.9rem;
            font-weight:700; color:#0D47A1; }

        /* Divider */
        .divider { border:none; border-top:1.5px dashed #dde3f0; margin:14px 0; }

        /* Patient */
        .section-title { font-size:.72rem; font-weight:700; color:#6b7a99;
            text-transform:uppercase; letter-spacing:.08em; margin-bottom:8px; }
        .row-info { display:flex; justify-content:space-between;
            font-size:.83rem; padding:4px 0; }
        .row-info .label { color:#6b7a99; }
        .row-info .value { font-weight:500; color:#1a2340; }

        /* Examens */
        .exam-line { display:flex; justify-content:space-between;
            font-size:.8rem; padding:5px 0;
            border-bottom:1px solid #f0f2f8; }
        .exam-line:last-child { border-bottom:none; }
        .exam-nom  { color:#1a2340; }
        .exam-prix { font-weight:600; color:#1E88E5; }

        /* Sous-total / assurance */
        .subtotal-box { margin-top:10px; }
        .subtotal-row { display:flex; justify-content:space-between;
            font-size:.82rem; padding:4px 0; color:#1a2340; }
        .subtotal-row.assurance { color:#8E24AA; font-weight:600; }
        .subtotal-row.assurance .icon { margin-right:4px; }

        /* Total */
        .total-box { background:#0D47A1; border-radius:10px;
            padding:12px 14px; display:flex;
            justify-content:space-between; align-items:center;
            margin-top:12px; }
        .total-label { color:rgba(255,255,255,.8); font-size:.8rem; }
        .total-value { color:#fff; font-size:1.2rem; font-weight:700; }

        /* Mode */
        .mode-box { text-align:center; margin-top:12px;
            font-size:.82rem; color:#6b7a99; }
        .mode-val { font-weight:600; color:#1a2340; font-size:.9rem; }

        /* Assurance info */
        .assurance-box { background:#F3E5F5; border-radius:8px;
            padding:8px 12px; margin-top:10px; text-align:center;
            font-size:.76rem; color:#6A1B9A; }

        /* Footer */
        .recu-footer { text-align:center; margin-top:1.5rem;
            font-size:.72rem; color:#b0b8cc; line-height:1.6; }
        .paid-stamp { text-align:center; margin:12px 0; }
        .paid-stamp span { border:3px solid #2E7D32; color:#2E7D32;
            padding:4px 20px; border-radius:8px; font-size:1rem;
            font-weight:700; letter-spacing:.1em; transform:rotate(-5deg);
            display:inline-block; }

        /* Print */
        @media print {
            body { background:none; padding:0; }
            .recu { box-shadow:none; border-radius:0; }
            .no-print { display:none; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="recu">

    <!-- En-tête -->
    <div class="recu-header">
        <div class="recu-logo">🧪</div>
        <div class="recu-labo">Laboratoire Drouot</div>
        <div class="recu-sub">Douala, Cameroun</div>
        <div class="recu-ref">
            REÇU FAC-<?= str_pad($facture['id'], 4, '0', STR_PAD_LEFT) ?>
        </div>
    </div>

    <hr class="divider">

    <!-- Patient -->
    <div class="section-title">Patient</div>
    <div class="row-info">
        <span class="label">Nom</span>
        <span class="value"><?= htmlspecialchars($facture['patient_nom']) ?></span>
    </div>
    <div class="row-info">
        <span class="label">Téléphone</span>
        <span class="value"><?= htmlspecialchars($facture['telephone']) ?></span>
    </div>
    <div class="row-info">
        <span class="label">Date</span>
        <span class="value"><?= date('d/m/Y H:i') ?></span>
    </div>

    <hr class="divider">

    <!-- Examens -->
    <div class="section-title">Examens réalisés</div>
    <?php foreach ($examensItems as $item): ?>
    <div class="exam-line">
        <span class="exam-nom"><?= htmlspecialchars($item['nom']) ?></span>
        <span class="exam-prix">
            <?= number_format((float)$item['price'], 0, ',', ' ') ?> F
        </span>
    </div>
    <?php endforeach; ?>

    <!-- Détail assurance si prise en charge validée -->
    <?php if ($aUneAssurance): ?>
    <div class="subtotal-box">
        <div class="subtotal-row">
            <span>Montant total des examens</span>
            <span><?= number_format((float)$facture['montant_total'], 0, ',', ' ') ?> F</span>
        </div>
        <div class="subtotal-row assurance">
            <span class="icon">🛡️</span>
            <span>Pris en charge — <?= htmlspecialchars($facture['assurance_nom']) ?></span>
            <span>- <?= number_format($partAssurance, 0, ',', ' ') ?> F</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Total réellement payé par le patient -->
    <div class="total-box">
        <span class="total-label">
            <?= $aUneAssurance ? 'MONTANT PAYÉ PAR LE PATIENT' : 'TOTAL PAYÉ' ?>
        </span>
        <span class="total-value">
            <?= number_format((float)$facture['montant_patient'], 0, ',', ' ') ?> FCFA
        </span>
    </div>

    <!-- Mode paiement -->
    <?php
    $modes = [
        'cash'      => '💵 Espèces',
        'momo'      => '📱 Mobile Money',
        'card'      => '💳 Carte bancaire',
        'insurance' => '🏥 Assurance',
    ];
    ?>
    <div class="mode-box">
        Mode de paiement :
        <span class="mode-val">
           <?= !empty($facture['mode_paiement'])
    ? ($modes[$facture['mode_paiement']] ?? htmlspecialchars($facture['mode_paiement']))
    : '—' ?>
        </span>
    </div>

    <?php if ($aUneAssurance): ?>
    <div class="assurance-box">
        Le solde de <?= number_format($partAssurance, 0, ',', ' ') ?> FCFA est
        directement réclamé à <?= htmlspecialchars($facture['assurance_nom']) ?>
        par le laboratoire.
    </div>
    <?php endif; ?>

    <!-- Tampon PAYÉ -->
    <div class="paid-stamp"><span>✓ PAYÉ</span></div>

    <hr class="divider">

    <!-- Footer -->
    <div class="recu-footer">
        Merci de votre confiance.<br>
        Laboratoire Drouot · Douala · Cameroun<br>
        Ce reçu fait foi de paiement.
    </div>

    <!-- Boutons no-print -->
    <div class="no-print" style="margin-top:1.5rem;display:flex;gap:8px;">
        <button onclick="window.print()"
                style="flex:1;padding:10px;background:#0D47A1;color:#fff;
                    border:none;border-radius:8px;cursor:pointer;
                    font-family:'DM Sans',sans-serif;font-weight:500;">
            🖨️ Imprimer
        </button>
        <a href="<?= BASE_URL ?>caissier/factures"
           style="flex:1;padding:10px;background:#f4f6f9;color:#1a2340;
               border:none;border-radius:8px;cursor:pointer;text-decoration:none;
               font-family:'DM Sans',sans-serif;font-weight:500;
               display:flex;align-items:center;justify-content:center;">
            ← Retour
        </a>
    </div>

</div>
</body>
</html>