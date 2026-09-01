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

<!-- Conteneur client pour rendu offline-first -->
<div id="offlineExamensRoot"></div>

<script>
/**
 * Offline-first examens :
 * - En ligne  : hydrate IndexedDB avec les données serveur, affiche les créations locales en plus
 * - Hors ligne: la liste affichée vient entièrement d'IndexedDB (travail continu sans réseau)
 * - Sync auto au retour du réseau
 */
(function () {
    const SERVER_EXAMENS = <?= json_encode(array_map(function ($ex) {
        return [
            'id'            => (string)$ex['id'],
            'code'          => $ex['code'] ?? '',
            'nom'           => $ex['nom'] ?? '',
            'categorie'     => $ex['categorie'] ?? 'Autre',
            'prix'          => (float)($ex['prix'] ?? 0),
            'description'   => $ex['description'] ?? '',
            'is_active'     => (int)($ex['is_active'] ?? 1),
            'nb_parametres' => (int)($ex['nb_parametres'] ?? 0),
            'nb_realises'   => (int)($ex['nb_realises'] ?? 0),
            'synced'        => 1,
        ];
    }, $examens ?? []), JSON_UNESCAPED_UNICODE) ?>;

    const BASE = '<?= rtrim(BASE_URL, "/") ?>/';

    function esc(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatPrix(n) {
        return Number(n || 0).toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' F';
    }

    function isLocalId(id) {
        return String(id).startsWith('local_');
    }

    function cardHtml(ex) {
        const pending = isLocalId(ex.id) || ex.synced === 0;
        const active = ex.is_active === undefined || Number(ex.is_active) === 1;
        return `
        <div class="examen-card ${active ? '' : 'examen-inactive'}" data-id="${esc(ex.id)}">
            <div class="examen-card-top">
                <div>
                    <div class="examen-code">${esc(ex.code || '—')}</div>
                    <div class="examen-nom">${esc(ex.nom || '')}</div>
                </div>
                ${pending
                    ? '<span class="badge-pending">Local</span>'
                    : (active ? '<span class="badge-normal">Actif</span>' : '<span class="badge-critical">Inactif</span>')}
            </div>
            ${ex.description ? `<div class="examen-desc">${esc(String(ex.description).slice(0, 60))}</div>` : ''}
            <div class="examen-card-stats">
                <span class="examen-stat"><i class="fas fa-sliders"></i> ${ex.nb_parametres || 0} param.</span>
                <span class="examen-stat"><i class="fas fa-chart-bar"></i> ${ex.nb_realises || 0} réalisé(s)</span>
                <span class="examen-prix">${formatPrix(ex.prix)}</span>
            </div>
            <div class="examen-card-actions">
                <button type="button" class="action-btn action-btn-primary" title="Paramètres"
                    onclick="OfflineLists.manageParametres('${esc(ex.id)}')">
                    <i class="fas fa-sliders"></i>
                </button>
                <button type="button" class="action-btn action-btn-edit" title="Modifier"
                    onclick="OfflineLists.editExamen('${esc(ex.id)}')">
                    <i class="fas fa-pen"></i>
                </button>
                ${pending ? `
                    <button type="button" class="action-btn action-btn-danger" title="Supprimer localement"
                        data-local-delete="${esc(ex.id)}">
                        <i class="fas fa-trash"></i>
                    </button>
                ` : `
                    <a href="${BASE}admin/examens/${esc(ex.id)}/parametres" class="action-btn action-btn-primary" title="Paramètres (page)" style="opacity:.7">
                        <i class="fas fa-external-link-alt" style="font-size:.65rem"></i>
                    </a>
                `}
            </div>
        </div>`;
    }

    function renderGrouped(list, container) {
        const grouped = {};
        list.forEach(ex => {
            const cat = ex.categorie || 'Autre';
            if (!grouped[cat]) grouped[cat] = [];
            grouped[cat].push(ex);
        });
        const cats = Object.keys(grouped).sort((a, b) => a.localeCompare(b, 'fr'));
        container.innerHTML = cats.map(cat => `
            <div class="cat-section mb-3">
                <div class="cat-section-header">
                    <div class="cat-section-title">${esc(cat)}
                        <span class="badge-pending" style="margin-left:8px;">${grouped[cat].length}</span>
                    </div>
                </div>
                <div class="examens-grid">
                    ${grouped[cat].map(cardHtml).join('')}
                </div>
            </div>
        `).join('');
    }

    function hideServerList() {
        // Masque le rendu PHP serveur pour éviter le double affichage hors ligne
        document.querySelectorAll('.cat-section, .examens-grid').forEach(el => {
            if (!el.closest('#offlineExamensRoot')) {
                // ne masque que les sections serveur (hors notre root)
            }
        });
        // Approche simple : masquer les enfants directs du main hors offline root / alerts / title
        const main = document.querySelector('main.main-content') || document.querySelector('.main-content');
        if (!main) return;
        Array.from(main.children).forEach(child => {
            if (child.id === 'offlineExamensRoot') return;
            if (child.id === 'connectivity-banner') return;
            // garde titre + bouton nouvel examen
            if (child.querySelector && child.querySelector('.page-title')) return;
            if (child.classList && child.classList.contains('alert-drouot')) return;
            // masque filtres + grilles serveur
            if (child.querySelector('.search-form') || child.classList.contains('cat-section') ||
                child.querySelector('.examens-grid') || child.querySelector('.cat-section-header') ||
                child.id === 'localExamensSection') {
                child.style.display = 'none';
            }
            // sections cat
            if (child.querySelector && child.querySelector('[data-toggle-cat]')) {
                child.style.display = 'none';
            }
        });
        // Masque aussi toutes les cat-section serveur
        document.querySelectorAll('main .cat-section, .main-content .cat-section').forEach(el => {
            if (!el.closest('#offlineExamensRoot')) el.style.display = 'none';
        });
        document.querySelectorAll('main .card-drouot').forEach(el => {
            if (el.querySelector('.search-form') && !el.closest('#offlineExamensRoot')) {
                // garder les filtres visibles même offline (optionnel)
            }
        });
    }

    async function refresh() {
        if (typeof LocalDB === 'undefined') return;

        // Toujours hydrater avec les données serveur connues (page en cache)
        try {
            if (SERVER_EXAMENS && SERVER_EXAMENS.length) {
                await LocalDB.seedStore('examens', SERVER_EXAMENS);
            }
        } catch (e) {
            console.warn(e);
        }

        const all = (await LocalDB.getAll('examens'))
            .filter(ex => !ex._deleted)
            .sort((a, b) => String(a.categorie||'').localeCompare(String(b.categorie||''), 'fr')
                || String(a.nom||'').localeCompare(String(b.nom||''), 'fr'));

        const root = document.getElementById('offlineExamensRoot');
        if (!root) return;

        const pending = all.filter(ex => isLocalId(ex.id) || ex.synced === 0);
        const offline = !navigator.onLine;

        if (offline) {
            // Mode travail hors ligne : liste complète depuis IndexedDB
            hideServerList();
            root.innerHTML = `
                <div class="card-drouot mb-3" style="border-color:#FFE082;background:#FFFDE7;">
                    <strong style="color:#F57F17;">
                        <i class="fas fa-database me-1"></i> Mode hors ligne
                    </strong>
                    — ${all.length} examen(s) sur cet appareil
                    ${pending.length ? ` · <span style="color:#F57F17;">${pending.length} non synchronisé(s)</span>` : ''}
                </div>
                ${all.length === 0
                    ? `<div class="card-drouot text-center py-4 text-muted">
                         Aucun examen en local.
                         <a class="btn-primary-drouot mt-2" href="${BASE}admin/examens/create">
                           <i class="fas fa-plus"></i> Créer un examen
                         </a>
                       </div>`
                    : '<div id="offlineGrouped"></div>'}
            `;
            const g = document.getElementById('offlineGrouped');
            if (g) renderGrouped(all, g);
        } else {
            // En ligne : on laisse la liste PHP, on affiche seulement les locaux non sync
            if (pending.length === 0) {
                root.innerHTML = '';
                return;
            }
            root.innerHTML = `
                <div style="margin-top:1.5rem;">
                    <div class="cat-section-header" style="background:#FFF8E1;border-color:#FFE082;">
                        <div class="cat-section-title" style="color:#F57F17;">
                            <i class="fas fa-cloud-upload-alt me-2"></i>
                            Créés hors ligne — en attente de sync
                            <span class="badge-pending" style="margin-left:8px;">${pending.length}</span>
                        </div>
                    </div>
                    <div class="examens-grid" id="pendingLocalGrid"></div>
                </div>`;
            const grid = document.getElementById('pendingLocalGrid');
            if (grid) grid.innerHTML = pending.map(cardHtml).join('');
        }

        // Suppression locale
        root.querySelectorAll('[data-local-delete]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-local-delete');
                if (!confirm('Supprimer cet examen local ?')) return;
                await LocalDB.remove('examens', id, BASE + 'admin/examens/' + id + '/delete', {
                    type: 'examens',
                    label: 'Suppression locale ' + id
                });
                refresh();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', refresh);
    window.addEventListener('localdb:examens-updated', refresh);
    window.addEventListener('outbox:updated', refresh);
    window.addEventListener('online', async () => {
        if (typeof LocalDB !== 'undefined') {
            await LocalDB.syncAll(true);
            // Recharge la page pour récupérer les IDs serveur après sync
            setTimeout(() => { window.location.reload(); }, 600);
        }
    });
    window.addEventListener('offline', refresh);
})();
</script>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>
