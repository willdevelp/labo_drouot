<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<?php if ($success): ?>
<div class="alert-drouot alert-success mb-4">
    <i class="fas fa-circle-check"></i>
    <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert-drouot alert-danger mb-4">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Stocks & Réactifs</h1>
        <p class="page-sub">
            <?= $stats['total'] ?> réactif(s) ·
            <span style="color:#C62828;">
                <?= $stats['ruptures'] ?> rupture(s)
            </span> ·
            <span style="color:#F9A825;">
                <?= $stats['faibles'] ?> stock(s) faible(s)
            </span>
        </p>
    </div>
    <a href="<?= BASE_URL ?>admin/stocks/create"
       class="btn-primary-drouot">
        <i class="fas fa-plus"></i> Ajouter un réactif
    </a>
</div>

<!-- KPI alertes -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $stats['total'] ?></div>
                <div class="kpi-label">Total réactifs</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card <?= $stats['ruptures'] > 0
            ? 'kpi-urgent' : '' ?>">
            <div class="kpi-icon red">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="kpi-value"
                     style="color:<?= $stats['ruptures'] > 0
                         ? '#C62828' : 'inherit' ?>">
                    <?= $stats['ruptures'] ?>
                </div>
                <div class="kpi-label">Ruptures de stock</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card <?= $stats['faibles'] > 0
            ? 'kpi-warn' : '' ?>">
            <div class="kpi-icon orange">
                <i class="fas fa-battery-quarter"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $stats['faibles'] ?></div>
                <div class="kpi-label">Stocks faibles</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon orange">
                <i class="fas fa-calendar-xmark"></i>
            </div>
            <div>
                <div class="kpi-value">
                    <?= $stats['expirations'] ?>
                </div>
                <div class="kpi-label">Expirations proches</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search"
                   class="search-input"
                   placeholder="Nom du réactif..."
                   value="<?= htmlspecialchars(
                       $_GET['search'] ?? ''
                   ) ?>">
        </div>
        <select name="filtre" class="filter-select">
            <option value="">Tous les stocks</option>
            <option value="faible"
                <?= ($_GET['filtre']??'')==='faible'
                    ? 'selected':'' ?>>
                ⚠️ Stock faible
            </option>
            <option value="expire"
                <?= ($_GET['filtre']??'')==='expire'
                    ? 'selected':'' ?>>
                📅 Expiration proche
            </option>
            <option value="ok"
                <?= ($_GET['filtre']??'')==='ok'
                    ? 'selected':'' ?>>
                ✅ Stock OK
            </option>
        </select>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-filter"></i> Filtrer
        </button>
        <a href="<?= BASE_URL ?>admin/stocks"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Table stocks -->
<div class="card-drouot">
    <?php if (empty($stocks)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-boxes-stacked fa-3x mb-3"
               style="color:#dde3f0;"></i>
            <p>Aucun réactif trouvé.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Réactif</th>
                    <th>Quantité</th>
                    <th>Seuil alerte</th>
                    <th>Statut</th>
                    <th>Expiration</th>
                    <th>Ajuster</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stocks as $s): ?>
                <tr class="<?= in_array($s['statut'],
                    ['rupture','faible'])
                    ? 'row-alert' : '' ?>">
                    <td>
                        <div style="font-weight:500;
                            font-size:.88rem;">
                            <?= htmlspecialchars($s['name']) ?>
                        </div>
                    </td>
                    <td>
                        <div class="qty-display">
                            <div class="qty-bar-wrap">
                                <?php
                                $pct = $s['threshold'] > 0
                                    ? min(100, round(
                                        $s['quantity'] /
                                        ($s['threshold'] * 3) * 100
                                      ))
                                    : 100;
                                $barColor = $s['statut'] === 'rupture'
                                    ? '#C62828'
                                    : ($s['statut'] === 'faible'
                                        ? '#F9A825' : '#2E7D32');
                                ?>
                                <div class="qty-bar">
                                    <div class="qty-fill"
                                         style="width:<?= $pct ?>%;
                                         background:<?= $barColor ?>">
                                    </div>
                                </div>
                            </div>
                            <span class="qty-val"
                                  style="color:<?= $barColor ?>">
                                <?= $s['quantity'] ?>
                            </span>
                        </div>
                    </td>
                    <td style="font-size:.83rem;color:#6b7a99;">
                        <?= $s['threshold'] ?>
                    </td>
                    <td>
                        <?php
                        $statusConfig = [
                            'rupture'    => ['badge-critical','🔴 Rupture'],
                            'faible'     => ['badge-warn',   '⚠️ Faible'],
                            'expiration' => ['badge-warn',   '📅 Expire bientôt'],
                            'ok'         => ['badge-normal', '✅ OK'],
                        ];
                        [$cls,$lbl] = $statusConfig[$s['statut']]
                            ?? ['badge-pending',$s['statut']];
                        ?>
                        <span class="<?= $cls ?>"><?= $lbl ?></span>
                    </td>
                    <td style="font-size:.82rem;color:#6b7a99;">
                        <?= $s['expiry_date']
                            ? date('d/m/Y',
                                strtotime($s['expiry_date']))
                            : '—' ?>
                    </td>
                    <td>
                        <!-- Ajustement rapide -->
                        <div class="ajust-inline"
                             id="ajust_<?= $s['id'] ?>"
                             style="display:none;">
                            <form method="POST"
                                  action="<?= BASE_URL ?>admin/stocks/<?= $s['id'] ?>/ajuster"
                                  class="ajust-form"
                                  data-offline-store="stocks"
                                  data-entity-id="<?= $s['id'] ?>">
                                <select name="type"
                                        class="ajust-select">
                                    <option value="add">+ Ajouter</option>
                                    <option value="remove">- Retirer</option>
                                </select>
                                <input type="number"
                                       name="quantite"
                                       class="ajust-input"
                                       value="1" min="1">
                                <button type="submit"
                                        class="ajust-btn">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button"
                                        onclick="toggleAjust(<?= $s['id'] ?>)"
                                        class="ajust-cancel">
                                    <i class="fas fa-xmark"></i>
                                </button>
                            </form>
                        </div>
                        <button onclick="toggleAjust(<?= $s['id'] ?>)"
                                class="btn-ajust"
                                id="btnAjust_<?= $s['id'] ?>">
                            <i class="fas fa-sliders"></i>
                            Ajuster
                        </button>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>admin/stocks/<?= $s['id'] ?>/edit"
                               class="action-btn action-btn-edit"
                               title="Modifier">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST"
                                  action="<?= BASE_URL ?>admin/stocks/<?= $s['id'] ?>/delete"
                                  style="display:inline;"
                                  data-offline-store="stocks"
                                  data-offline-delete-id="<?= $s['id'] ?>"
                                  data-offline-label="Suppression stock — <?= htmlspecialchars($s['name']) ?>"
                                  data-offline-redirect="<?= BASE_URL ?>admin/stocks">
                                <button type="submit"
                                        class="action-btn action-btn-danger"
                                        title="Supprimer"
                                        onclick="return confirm(
                                            'Supprimer <?= addslashes($s['name']) ?> ?'
                                        )">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
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
.kpi-urgent { border-color:#FFCDD2 !important; background:#FFF5F5 !important; }
.kpi-warn   { border-color:#FFE082 !important; background:#FFFDE7 !important; }
.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap { position:relative; flex:1; min-width:200px; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.filter-select { padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; cursor:pointer; }
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.row-alert td { background:#fff9f9; }

/* Barre quantité */
.qty-display { display:flex; align-items:center; gap:8px; }
.qty-bar-wrap { flex:1; min-width:60px; }
.qty-bar { height:8px; background:#f0f2f8; border-radius:20px; overflow:hidden; }
.qty-fill { height:100%; border-radius:20px; transition:width .4s ease; }
.qty-val  { font-weight:700; font-size:.88rem; min-width:24px; }

/* Ajustement inline */
.btn-ajust { background:#E3F2FD; color:#1565C0; border:none; border-radius:8px; padding:4px 10px; font-size:.78rem; cursor:pointer; transition:all .2s; font-family:'DM Sans',sans-serif; }
.btn-ajust:hover { background:#1E88E5; color:#fff; }
.ajust-form { display:flex; align-items:center; gap:4px; }
.ajust-select { padding:4px 6px; border:1px solid #dde3f0; border-radius:6px; font-size:.75rem; outline:none; }
.ajust-input { width:52px; padding:4px 6px; border:1px solid #dde3f0; border-radius:6px; font-size:.75rem; outline:none; }
.ajust-btn { background:#2E7D32; color:#fff; border:none; border-radius:6px; width:26px; height:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.75rem; }
.ajust-cancel { background:#FFEBEE; color:#C62828; border:none; border-radius:6px; width:26px; height:26px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:.75rem; }

.action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; font-size:.78rem; transition:all .2s; border:none; cursor:pointer; text-decoration:none; }
.action-btn-edit   { background:#FFF8E1; color:#F57F17; }
.action-btn-edit:hover { background:#F9A825; color:#fff; }
.action-btn-danger { background:#FFEBEE; color:#C62828; }
.action-btn-danger:hover { background:#C62828; color:#fff; }
.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<script>
function toggleAjust(id) {
    const ajust = document.getElementById('ajust_' + id);
    const btn   = document.getElementById('btnAjust_' + id);
    const show  = ajust.style.display === 'none';
    ajust.style.display = show ? 'block' : 'none';
    btn.style.display   = show ? 'none' : 'inline-flex';
}
</script>

<div id="pendingStockBox" style="display:none;"></div>
<script>
OfflineOutbox.renderPendingWidget('pendingStockBox', ['stock']);
</script>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>