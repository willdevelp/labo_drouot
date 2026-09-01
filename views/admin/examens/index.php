<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert-drouot alert-warning mb-4">
    <i class="fas fa-trash"></i>
    Examen supprimé ou désactivé avec succès.
</div>
<?php endif; ?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Gestion des examens</h1>
        <p class="page-sub">
            <?= $stats['total'] ?> examen(s) ·
            <?= $stats['actifs'] ?> actif(s) ·
            <?= $stats['nb_categories'] ?> catégorie(s)
        </p>
    </div>
    <a href="<?= BASE_URL ?>admin/examens/create"
       class="btn-primary-drouot">
        <i class="fas fa-plus"></i> Nouvel examen
    </a>
</div>

<!-- Filtres -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search"
                   class="search-input"
                   placeholder="Nom, code, description..."
                   value="<?= htmlspecialchars(
                       $_GET['search'] ?? ''
                   ) ?>">
        </div>
        <select name="categorie" class="filter-select">
            <option value="">Toutes catégories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>"
                <?= ($_GET['categorie']??'') === $c
                    ? 'selected' : '' ?>>
                <?= htmlspecialchars($c) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-filter"></i> Filtrer
        </button>
        <a href="<?= BASE_URL ?>admin/examens"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Grille examens groupés par catégorie -->
<?php
$grouped = [];
foreach ($examens as $ex) {
    $grouped[$ex['categorie'] ?? 'Autre'][] = $ex;
}
$nbCategoriesAffichees = count($grouped);
// Si peu de catégories, on ouvre tout par défaut. Sinon, replié,
// pour éviter un scroll interminable avec 200+ examens.
$openByDefault = $nbCategoriesAffichees <= 5;
?>

<?php if (empty($examens)): ?>
    <div class="card-drouot text-center py-5 text-muted">
        <i class="fas fa-flask fa-3x mb-3"
           style="color:#dde3f0;"></i>
        <p>Aucun examen trouvé.</p>
        <a href="<?= BASE_URL ?>admin/examens/create"
           class="btn-primary-drouot mt-2">
            <i class="fas fa-plus"></i> Créer le premier examen
        </a>
    </div>
<?php else: ?>

<!-- Barre d'outils : déplier/replier + nav rapide catégories -->
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
        <?php foreach ($grouped as $categorie => $items):
            $slug = 'cat-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($categorie));
        ?>
        <a href="#<?= $slug ?>" class="quicknav-pill" data-target="<?= $slug ?>">
            <?= htmlspecialchars($categorie) ?>
            <span><?= count($items) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php foreach ($grouped as $categorie => $items):
    $slug = 'cat-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($categorie));
    $nbActifsCat = count(array_filter($items, fn($e) => $e['is_active']));
?>
<div class="cat-section mb-3" id="<?= $slug ?>">
    <button type="button" class="cat-header" data-toggle-cat>
        <i class="fas fa-chevron-right cat-chevron"></i>
        <i class="fas fa-folder-open"></i>
        <span><?= htmlspecialchars($categorie) ?></span>
        <span class="cat-count"><?= count($items) ?></span>
        <?php if ($nbActifsCat < count($items)): ?>
        <span class="cat-count cat-count-inactive">
            <?= count($items) - $nbActifsCat ?> inactif(s)
        </span>
        <?php endif; ?>
    </button>
    <div class="examens-grid <?= $openByDefault ? '' : 'is-collapsed' ?>">
        <?php foreach ($items as $ex): ?>
        <div class="examen-card
            <?= !$ex['is_active'] ? 'examen-inactive' : '' ?>">
            <div class="examen-card-top">
                <div>
                    <div class="examen-code">
                        <?= htmlspecialchars($ex['code'] ?? '—') ?>
                    </div>
                    <div class="examen-nom">
                        <?= htmlspecialchars($ex['nom']) ?>
                    </div>
                </div>
                <?php if ($ex['is_active']): ?>
                    <span class="badge-normal">Actif</span>
                <?php else: ?>
                    <span class="badge-critical">Inactif</span>
                <?php endif; ?>
            </div>

            <?php if ($ex['description']): ?>
            <div class="examen-desc">
                <?= htmlspecialchars(
                    mb_strimwidth($ex['description'], 0, 60, '...')
                ) ?>
            </div>
            <?php endif; ?>

            <div class="examen-card-stats">
                <span class="examen-stat">
                    <i class="fas fa-sliders"></i>
                    <?= $ex['nb_parametres'] ?> param.
                </span>
                <span class="examen-stat">
                    <i class="fas fa-chart-bar"></i>
                    <?= $ex['nb_realises'] ?> réalisé(s)
                </span>
                <span class="examen-prix">
                    <?= number_format(
                        (float)$ex['prix'], 0, ',', ' '
                    ) ?> F
                </span>
            </div>

            <div class="examen-card-actions">
                <a href="<?= BASE_URL ?>admin/examens/<?= $ex['id'] ?>/parametres"
                   class="action-btn action-btn-primary"
                   title="Paramètres">
                    <i class="fas fa-sliders"></i>
                </a>
                <a href="<?= BASE_URL ?>admin/examens/<?= $ex['id'] ?>/edit"
                   class="action-btn action-btn-edit"
                   title="Modifier">
                    <i class="fas fa-pen"></i>
                </a>
<form method="POST"
      action="<?= BASE_URL ?>admin/examens/<?= $ex['id'] ?>/toggle"
      data-offline-store="examens"
      data-entity-id="<?= $ex['id'] ?>"
      data-toggle-field="is_active"
      data-toggle-value="<?= $ex['is_active'] ? 0 : 1 ?>"
      data-redirect="<?= BASE_URL ?>admin/examens"
      style="display:inline;">
      
    <button type="submit"
            class="action-btn <?= $ex['is_active'] ? 'action-btn-warn' : 'action-btn-green' ?>"
            title="<?= $ex['is_active'] ? 'Désactiver' : 'Activer' ?>">
        <i class="fas <?= $ex['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
    </button>
</form>
<form method="POST"
      action="<?= BASE_URL ?>admin/examens/<?= $ex['id'] ?>/delete"
      data-offline-store="examens"
      data-offline-delete-id="<?= $ex['id'] ?>"
      data-redirect="<?= BASE_URL ?>admin/examens"
      style="display:inline;">
      
    <button type="submit"
            class="action-btn action-btn-danger"
            title="Supprimer"
            onclick="return confirm('Supprimer <?= addslashes($ex['nom']) ?> ?')">
        <i class="fas fa-trash"></i>
    </button>
</form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap { position:relative; flex:1; min-width:200px; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.filter-select { padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; cursor:pointer; }

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

.cat-quicknav {
    display:flex; gap:6px; flex-wrap:wrap; overflow-x:auto;
    padding-bottom:2px;
}
.quicknav-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 10px; border-radius:20px;
    background:#F4F6F9; color:#1a2340; text-decoration:none;
    font-size:.74rem; font-weight:500; white-space:nowrap;
    transition:all .15s;
}
.quicknav-pill:hover { background:#E3F2FD; color:#1565C0; }
.quicknav-pill span {
    background:#dde3f0; color:#6b7a99;
    border-radius:20px; padding:0 6px; font-size:.68rem; font-weight:700;
}
.quicknav-pill:hover span { background:#90CAF9; color:#0D47A1; }

/* ── Catégorie repliable ── */
.cat-section { scroll-margin-top: 90px; }
.cat-header {
    width:100%; display:flex; align-items:center; gap:8px;
    font-size:.82rem; font-weight:700; color:#6b7a99;
    text-transform:uppercase; letter-spacing:.08em;
    margin-bottom:10px; padding:8px 4px;
    border-bottom:1.5px solid #f0f2f8;
    background:none; border-top:none; border-left:none; border-right:none;
    cursor:pointer; text-align:left; transition:color .15s;
}
.cat-header:hover { color:#1E88E5; }
.cat-header i.fa-folder-open { color:#90CAF9; }
.cat-chevron { transition:transform .2s; font-size:.75rem; color:#b0b8cc; }
.cat-header[aria-expanded="true"] .cat-chevron { transform:rotate(90deg); }
.cat-count { background:#E3F2FD; color:#1565C0; padding:1px 8px; border-radius:20px; font-size:.7rem; }
.cat-count-inactive { background:#FFF8E1; color:#F57F17; }

/* Grille examens */
.examens-grid {
    display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:12px;
    overflow:hidden;
}
.examens-grid.is-collapsed { display:none; }

.examen-card { background:#fff; border-radius:12px; border:1.5px solid #dde3f0; padding:14px; transition:all .18s; }
.examen-card:hover { border-color:#1E88E5; box-shadow:0 4px 16px rgba(13,71,161,.08); }
.examen-inactive { opacity:.65; border-style:dashed; }

.examen-card-top { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:8px; }
.examen-code { font-family:monospace; font-size:.72rem; color:#90CAF9; margin-bottom:2px; }
.examen-nom  { font-size:.88rem; font-weight:600; color:#1a2340; }
.examen-desc { font-size:.75rem; color:#6b7a99; margin-bottom:10px; line-height:1.4; }

.examen-card-stats { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:10px; }
.examen-stat { font-size:.72rem; color:#6b7a99; display:flex; align-items:center; gap:4px; }
.examen-stat i { color:#90CAF9; }
.examen-prix { margin-left:auto; font-size:.82rem; font-weight:700; color:#1E88E5; }

.examen-card-actions { display:flex; gap:5px; padding-top:10px; border-top:1px solid #f0f2f8; }
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; font-size:.78rem; transition:all .2s; border:none; cursor:pointer; text-decoration:none; }
.action-btn-primary { background:#E3F2FD; color:#1565C0; }
.action-btn-primary:hover { background:#1E88E5; color:#fff; }
.action-btn-edit  { background:#FFF8E1; color:#F57F17; }
.action-btn-edit:hover { background:#F9A825; color:#fff; }
.action-btn-warn  { background:#FFF8E1; color:#F57F17; }
.action-btn-warn:hover { background:#F9A825; color:#fff; }
.action-btn-green { background:#E8F5E9; color:#2E7D32; }
.action-btn-green:hover { background:#2E7D32; color:#fff; }
.action-btn-danger { background:#FFEBEE; color:#C62828; }
.action-btn-danger:hover { background:#C62828; color:#fff; }

.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-warning { background:#FFF8E1; color:#F57F17; border:1px solid #FFE082; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }

@media (max-width:768px) {
    .examens-grid { grid-template-columns:1fr; }
    .cat-toolbar { flex-direction:column; align-items:stretch; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var headers = document.querySelectorAll('[data-toggle-cat]');

    function setState(header, expanded) {
        var grid = header.nextElementSibling;
        header.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        grid.classList.toggle('is-collapsed', !expanded);
    }

    headers.forEach(function (header) {
        var grid = header.nextElementSibling;
        var initiallyOpen = !grid.classList.contains('is-collapsed');
        header.setAttribute('aria-expanded', initiallyOpen ? 'true' : 'false');

        header.addEventListener('click', function () {
            var isOpen = header.getAttribute('aria-expanded') === 'true';
            setState(header, !isOpen);
        });
    });

    var btnDeplier = document.getElementById('btnDeplierTout');
    var btnReplier = document.getElementById('btnReplierTout');

    if (btnDeplier) {
        btnDeplier.addEventListener('click', function () {
            headers.forEach(function (h) { setState(h, true); });
        });
    }
    if (btnReplier) {
        btnReplier.addEventListener('click', function () {
            headers.forEach(function (h) { setState(h, false); });
        });
    }

    // Nav rapide : cliquer sur une catégorie la déplie automatiquement
    // si elle était repliée, puis scrolle jusqu'à elle.
    document.querySelectorAll('.quicknav-pill').forEach(function (pill) {
        pill.addEventListener('click', function () {
            var targetId = pill.getAttribute('data-target');
            var section = document.getElementById(targetId);
            if (!section) return;
            var header = section.querySelector('[data-toggle-cat]');
            if (header && header.getAttribute('aria-expanded') !== 'true') {
                setState(header, true);
            }
        });
    });
});
</script>

<div id="pendingExamensBox" style="display:none;"></div>
<script>
OfflineOutbox.renderPendingWidget('pendingExamensBox', ['examen']);
</script>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>