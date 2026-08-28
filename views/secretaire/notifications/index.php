<?php
/**
 * Notifications — Secrétaire
 */
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            <i class="fas fa-bell text-primary me-2"></i>
            Notifications
        </h1>
        <p class="page-sub"><?= $stats['total'] ?> notification(s) à traiter</p>
    </div>
    <a href="<?= BASE_URL ?>dashboard/secretaire" class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<!-- Stats rapides -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="notif-stat-card warning">
            <i class="fas fa-hourglass-half"></i>
            <div>
                <div class="notif-stat-val"><?= $stats['paiement'] ?></div>
                <div class="notif-stat-label">Paiements en attente</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="notif-stat-card danger">
            <i class="fas fa-shield-heart"></i>
            <div>
                <div class="notif-stat-val"><?= $stats['assurance'] ?></div>
                <div class="notif-stat-label">Assurances sans réponse</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="notif-stat-card info">
            <i class="fas fa-circle-check"></i>
            <div>
                <div class="notif-stat-val"><?= $stats['resultats'] ?></div>
                <div class="notif-stat-label">Résultats à communiquer</div>
            </div>
        </div>
    </div>
</div>

<!-- Liste des notifications -->
<div class="card-drouot">
    <h6 class="card-section-title mb-3">
        <i class="fas fa-list text-primary me-2"></i>
        Toutes les notifications
    </h6>

    <?php if (empty($notifications)): ?>
    <div class="text-center py-5">
        <i class="fas fa-circle-check fa-2x mb-2" style="color:#2E7D32;"></i>
        <p class="text-muted">Aucune notification en attente. Tout est à jour ! ✅</p>
    </div>
    <?php else: ?>
    <div class="notif-list">
        <?php foreach ($notifications as $n): ?>
        <a href="<?= htmlspecialchars($n['lien']) ?>" class="notif-item notif-<?= $n['priorite'] ?>">
            <div class="notif-icon">
                <i class="fas <?= $n['icon'] ?>"></i>
            </div>
            <div class="notif-content">
                <div class="notif-titre"><?= htmlspecialchars($n['titre']) ?></div>
                <div class="notif-detail"><?= $n['detail'] /* déjà échappé en amont */ ?></div>
            </div>
            <?php if ($n['type'] === 'resultats_disponibles' && !empty($n['telephone'])): ?>
            <span class="notif-action-tag">
                <i class="fas fa-phone"></i> <?= htmlspecialchars($n['telephone']) ?>
            </span>
            <?php endif; ?>
            <i class="fas fa-chevron-right notif-arrow"></i>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; }

.notif-stat-card {
    background:#fff; border-radius:14px; padding:16px;
    display:flex; align-items:center; gap:14px;
    border:1.5px solid #dde3f0;
}
.notif-stat-card i { font-size:1.5rem; flex-shrink:0; }
.notif-stat-card.warning i { color:#F57F17; }
.notif-stat-card.danger i  { color:#C62828; }
.notif-stat-card.info i    { color:#1565C0; }
.notif-stat-card.warning { background:#FFF8E1; border-color:#FFE082; }
.notif-stat-card.danger  { background:#FFEBEE; border-color:#FFCDD2; }
.notif-stat-card.info    { background:#E3F2FD; border-color:#BBDEFB; }
.notif-stat-val { font-size:1.3rem; font-weight:700; color:#1a2340; }
.notif-stat-label { font-size:.78rem; color:#6b7a99; }

.notif-list { display:flex; flex-direction:column; gap:8px; }
.notif-item {
    display:flex; align-items:center; gap:14px;
    padding:14px; border-radius:12px; border:1.5px solid #f0f2f8;
    text-decoration:none; transition:all .15s;
}
.notif-item:hover { border-color:#1E88E5; background:#fafbff; }
.notif-icon {
    width:38px; height:38px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; font-size:1rem;
}
.notif-warning .notif-icon { background:#FFF8E1; color:#F57F17; }
.notif-danger .notif-icon  { background:#FFEBEE; color:#C62828; }
.notif-info .notif-icon    { background:#E3F2FD; color:#1565C0; }
.notif-content { flex:1; min-width:0; }
.notif-titre { font-size:.88rem; font-weight:600; color:#1a2340; }
.notif-detail { font-size:.8rem; color:#6b7a99; margin-top:2px; }
.notif-action-tag {
    font-size:.76rem; color:#2E7D32; background:#E8F5E9;
    padding:4px 10px; border-radius:8px; white-space:nowrap; flex-shrink:0;
}
.notif-arrow { color:#dde3f0; flex-shrink:0; }

.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>
