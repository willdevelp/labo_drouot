<?php
/**
 * Configuration des taux de prise en charge par examen
 * Laboratoire Drouot
 */
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<?php if ($success): ?>
<div class="alert-drouot alert-success mb-3">
    <i class="fas fa-circle-check"></i>
    Taux sauvegardés avec succès !
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Taux de prise en charge —
            <span style="color:#1E88E5;"><?= htmlspecialchars($assurance['nom']) ?></span>
        </h1>
        <p class="page-sub">
            Taux par défaut : <?= number_format((float)$assurance['taux_defaut'], 1) ?>%
            · Définissez ici des taux spécifiques par examen (laissez vide pour appliquer le taux par défaut)
        </p>
    </div>
    <a href="<?= BASE_URL ?>admin/assurances" class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>admin/assurances/<?= $assurance['id'] ?>/taux/store"
      class="js-offline-form"
      data-offline-type="assurance"
      data-offline-label="<?= htmlspecialchars('Taux — ' . $assurance['nom']) ?>"
      data-offline-redirect="<?= BASE_URL ?>admin/assurances/<?= $assurance['id'] ?>/taux">

<div class="card-drouot">
    <h6 class="card-section-title mb-3">
        <i class="fas fa-percent text-primary me-2"></i>
        Examens et taux applicables
    </h6>

    <?php if (empty($examensGroupes)): ?>
        <div class="empty-params">
            <i class="fas fa-flask fa-2x mb-2" style="color:#dde3f0;"></i>
            <p>Aucun examen actif configuré dans le système.</p>
        </div>
    <?php else: ?>

    <?php foreach ($examensGroupes as $categorie => $examens): ?>
    <div class="examen-group">
        <div class="examen-group-title">
            <i class="fas fa-folder-open"></i>
            <?= htmlspecialchars($categorie) ?>
            <span class="group-count"><?= count($examens) ?></span>
        </div>

        <div class="taux-table">
            <div class="taux-header">
                <span>Examen</span>
                <span>Prix</span>
                <span>Taux spécifique (%)</span>
            </div>
            <?php foreach ($examens as $ex): ?>
            <div class="taux-row">
                <div class="taux-examen-info">
                    <div class="taux-examen-nom"><?= htmlspecialchars($ex['nom']) ?></div>
                    <?php if ($ex['code']): ?>
                    <div class="taux-examen-code"><?= htmlspecialchars($ex['code']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="taux-examen-prix">
                    <?= number_format((float)$ex['prix'], 0, ',', ' ') ?> F
                </div>
                <div class="taux-input-wrap">
                    <input type="number"
                           name="taux[<?= $ex['id'] ?>]"
                           step="0.1" min="0" max="100"
                           class="taux-input"
                           placeholder="Défaut: <?= number_format((float)$assurance['taux_defaut'], 1) ?>%"
                           value="<?= $ex['taux'] !== null ? $ex['taux'] : '' ?>">
                    <span class="taux-pct">%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="form-actions">
        <button type="submit" class="btn-primary-drouot" style="padding:.8rem 1.6rem;">
            <i class="fas fa-floppy-disk"></i>
            Sauvegarder les taux
        </button>
    </div>

    <?php endif; ?>
</div>

</form>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; display:flex; align-items:center; gap:8px; }

.examen-group { margin-bottom:1.5rem; }
.examen-group-title {
    font-size:.78rem; font-weight:600; color:#6b7a99;
    text-transform:uppercase; letter-spacing:.06em;
    display:flex; align-items:center; gap:6px;
    margin-bottom:8px; padding:4px 0;
}
.examen-group-title i { color:#90CAF9; font-size:.8rem; }
.group-count { background:#E3F2FD; color:#1565C0; padding:1px 7px; border-radius:20px; font-size:.7rem; margin-left:auto; }

.taux-table { display:flex; flex-direction:column; }
.taux-header {
    display:grid; grid-template-columns:2fr 1fr 1.2fr; gap:10px;
    padding:6px 10px; background:#fafbfc; border-radius:8px;
    margin-bottom:4px; font-size:.7rem; font-weight:600; color:#6b7a99;
    text-transform:uppercase; letter-spacing:.05em;
}
.taux-row {
    display:grid; grid-template-columns:2fr 1fr 1.2fr; gap:10px;
    align-items:center; padding:8px 10px;
    border-bottom:1px solid #f0f2f8;
}
.taux-row:last-child { border-bottom:none; }
.taux-row:hover { background:#fafbff; }
.taux-examen-nom { font-size:.85rem; font-weight:500; color:#1a2340; }
.taux-examen-code { font-size:.7rem; color:#90CAF9; font-family:monospace; }
.taux-examen-prix { font-size:.82rem; color:#6b7a99; }

.taux-input-wrap { display:flex; align-items:center; gap:5px; }
.taux-input {
    width:80px; padding:.5rem .6rem; border:1.5px solid #dde3f0; border-radius:8px;
    font-family:'DM Sans',sans-serif; font-size:.85rem; color:#1a2340;
    background:#fafbfd; outline:none; transition:all .2s;
}
.taux-input:focus { border-color:#1E88E5; background:#fff; box-shadow:0 0 0 3px rgba(30,136,229,.08); }
.taux-input::placeholder { font-size:.68rem; color:#b0b8cc; }
.taux-pct { font-size:.8rem; color:#6b7a99; }

.form-actions { margin-top:1.5rem; padding-top:1rem; border-top:1.5px solid #f0f2f8; }
.empty-params { text-align:center; padding:2rem; color:#6b7a99; }

.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include dirname(__DIR__) . '/../../views/layouts/footer.php'; ?>
