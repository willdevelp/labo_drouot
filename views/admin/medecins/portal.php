<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portail Médecin — Laboratoire Drouot</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { box-sizing:border-box; }
body {
    margin:0; font-family:'DM Sans', -apple-system, sans-serif;
    background:#f4f6fb; color:#1a2340;
}
.wrap { max-width:920px; margin:0 auto; padding:20px 16px 60px; }

/* Header */
.portal-header {
    display:flex; align-items:center; gap:14px;
    background:#fff; border-radius:16px; padding:20px;
    margin-bottom:18px; box-shadow:0 2px 10px rgba(0,0,0,.04);
}
.portal-logo {
    width:48px; height:48px; border-radius:12px;
    background:#1E88E5; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; flex-shrink:0;
}
.portal-title { font-size:1.05rem; font-weight:700; margin:0; }
.portal-sub { font-size:.82rem; color:#6b7a99; margin:2px 0 0; }
.medecin-badge {
    margin-left:auto; text-align:right;
}
.medecin-badge-name { font-size:.85rem; font-weight:600; color:#1a2340; }
.medecin-badge-spec { font-size:.74rem; color:#6b7a99; }

/* Stats row */
.stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px; }
.stat-card {
    background:#fff; border-radius:14px; padding:14px;
    text-align:center; box-shadow:0 2px 10px rgba(0,0,0,.04);
}
.stat-value { font-size:1.4rem; font-weight:700; color:#1a2340; }
.stat-label { font-size:.72rem; color:#6b7a99; margin-top:2px; }
.stat-card.green .stat-value { color:#2E7D32; }
.stat-card.orange .stat-value { color:#F57F17; }

/* Commission box */
.commission-box {
    background:linear-gradient(135deg, #1E88E5, #0D47A1);
    border-radius:16px; padding:20px; margin-bottom:18px;
    color:#fff;
}
.commission-title {
    font-size:.85rem; font-weight:600; opacity:.9;
    display:flex; align-items:center; gap:8px; margin-bottom:14px;
}
.commission-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.commission-item-value { font-size:1.5rem; font-weight:700; }
.commission-item-label { font-size:.75rem; opacity:.85; margin-top:2px; }

/* Patient list */
.section-title {
    font-size:.92rem; font-weight:700; color:#1a2340;
    margin:0 0 12px; display:flex; align-items:center; gap:8px;
}
.patient-card {
    background:#fff; border-radius:14px; padding:16px;
    margin-bottom:10px; box-shadow:0 2px 10px rgba(0,0,0,.04);
}
.patient-top {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:10px; margin-bottom:8px;
}
.patient-nom { font-size:.92rem; font-weight:600; color:#1a2340; }
.patient-date { font-size:.74rem; color:#6b7a99; margin-top:2px; }
.patient-examens { font-size:.8rem; color:#6b7a99; margin-bottom:10px; }

.status-badge {
    padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:600;
    white-space:nowrap;
}
.status-disponible { background:#E8F5E9; color:#2E7D32; }
.status-encours     { background:#FFF8E1; color:#F57F17; }

.patient-footer {
    display:flex; align-items:center; justify-content:space-between;
    padding-top:10px; border-top:1px solid #f0f2f8;
}
.commission-tag {
    font-size:.74rem; padding:2px 8px; border-radius:6px;
}
.commission-due  { background:#FFF8E1; color:#F57F17; }
.commission-paid { background:#E8F5E9; color:#2E7D32; }
.commission-none { color:#b0b8cc; }

.btn-voir {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:10px;
    background:#1E88E5; color:#fff; text-decoration:none;
    font-size:.82rem; font-weight:500; transition:background .2s;
}
.btn-voir:hover { background:#0D47A1; }
.btn-disabled {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:10px;
    background:#F4F6F9; color:#b0b8cc;
    font-size:.82rem; font-weight:500;
}

.empty-state {
    text-align:center; padding:50px 20px; color:#6b7a99;
}
.empty-state i { font-size:2.5rem; color:#dde3f0; margin-bottom:12px; display:block; }

.footer-note {
    text-align:center; font-size:.72rem; color:#b0b8cc; margin-top:24px;
}
</style>
</head>
<body>
<div class="wrap">

    <!-- Header -->
    <div class="portal-header">
        <div class="portal-logo"><i class="fas fa-flask"></i></div>
        <div>
            <p class="portal-title">Laboratoire Drouot</p>
            <p class="portal-sub">Portail médecin prescripteur</p>
        </div>
        <div class="medecin-badge">
            <div class="medecin-badge-name">
                Dr <?= htmlspecialchars($medecin['nom']) ?> <?= htmlspecialchars($medecin['prenom']) ?>
            </div>
            <?php if ($medecin['specialite']): ?>
            <div class="medecin-badge-spec"><?= htmlspecialchars($medecin['specialite']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Patient(s) envoyé(s)</div>
        </div>
        <div class="stat-card green">
            <div class="stat-value"><?= $stats['disponible'] ?></div>
            <div class="stat-label">Résultat(s) disponible(s)</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-value"><?= $stats['en_cours'] ?></div>
            <div class="stat-label">En cours d'analyse</div>
        </div>
    </div>

    <!-- Commissions -->
    <div class="commission-box">
        <div class="commission-title">
            <i class="fas fa-hand-holding-dollar"></i>
            Récapitulatif de vos commissions
        </div>
        <div class="commission-grid">
            <div>
                <div class="commission-item-value">
                    <?= number_format((float)$commissionStats['total_du'], 0, ',', ' ') ?> F
                </div>
                <div class="commission-item-label">En attente de paiement</div>
            </div>
            <div>
                <div class="commission-item-value">
                    <?= number_format((float)$commissionStats['total_paye'], 0, ',', ' ') ?> F
                </div>
                <div class="commission-item-label">Déjà versées</div>
            </div>
        </div>
    </div>

    <!-- Liste patients -->
    <h6 class="section-title">
        <i class="fas fa-users text-primary"></i>
        Patients envoyés
    </h6>

    <?php if (empty($patients)): ?>
        <div class="empty-state">
            <i class="fas fa-user-injured"></i>
            <p>Aucun patient envoyé pour le moment.</p>
        </div>
    <?php else: ?>
        <?php foreach ($patients as $p): ?>
        <div class="patient-card">
            <div class="patient-top">
                <div>
                    <div class="patient-nom"><?= htmlspecialchars($p['patient_nom']) ?></div>
                    <div class="patient-date">
                        <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                    </div>
                </div>
                <?php if ($p['status'] === 'terminee'): ?>
                    <span class="status-badge status-disponible">Disponible</span>
                <?php else: ?>
                    <span class="status-badge status-encours">En cours</span>
                <?php endif; ?>
            </div>

            <div class="patient-examens">
                <i class="fas fa-flask"></i>
                <?= htmlspecialchars($p['examens'] ?? '—') ?>
                (<?= $p['nb_examens'] ?> examen(s))
            </div>

            <div class="patient-footer">
                <?php if ($p['commission_statut'] === 'due'): ?>
                    <span class="commission-tag commission-due">
                        <?= number_format((float)$p['montant_commission'], 0, ',', ' ') ?> F — due
                    </span>
                <?php elseif ($p['commission_statut'] === 'payee'): ?>
                    <span class="commission-tag commission-paid">
                        <?= number_format((float)$p['montant_commission'], 0, ',', ' ') ?> F — payée
                    </span>
                <?php else: ?>
                    <span class="commission-tag commission-none">
                        Commission en attente de paiement patient
                    </span>
                <?php endif; ?>

                <?php if ($p['status'] === 'terminee'): ?>
                    <a href="<?= BASE_URL ?>medecin/portal/<?= $token ?>/resultat/<?= $p['id'] ?>"
                       class="btn-voir">
                        <i class="fas fa-eye"></i> Voir résultats
                    </a>
                <?php else: ?>
                    <span class="btn-disabled">
                        <i class="fas fa-clock"></i> En attente
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p class="footer-note">
        Laboratoire Drouot — Douala · Lien personnel, ne pas partager
    </p>

</div>
</body>
</html>
