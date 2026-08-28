<?php
/**
 * Historique des actions du caissier — journal d'audit
 * Laboratoire Drouot
 *
 * Variables attendues (CaissierController::historique) :
 * $actions, $totalRows, $totalPages, $page, $typesActions, $caissiers, $user
 */

function actionLabel(string $action): array {
    $map = [
        'PAYMENT' => ['label' => 'Encaissement', 'icon' => 'fa-cash-register', 'color' => 'success'],
        'CREATE'  => ['label' => 'Création',      'icon' => 'fa-plus',           'color' => 'info'],
        'UPDATE'  => ['label' => 'Modification',  'icon' => 'fa-pen',            'color' => 'warning'],
        'DELETE'  => ['label' => 'Suppression',   'icon' => 'fa-trash',          'color' => 'danger'],
        'LOGIN'   => ['label' => 'Connexion',     'icon' => 'fa-right-to-bracket','color' => 'secondary'],
    ];
    return $map[$action] ?? ['label' => ucfirst(strtolower($action)), 'icon' => 'fa-circle-info', 'color' => 'secondary'];
}

$filtreParams = [
    'date_debut'  => $_GET['date_debut']  ?? '',
    'date_fin'    => $_GET['date_fin']    ?? '',
    'action'      => $_GET['action']      ?? '',
    'caissier_id' => $_GET['caissier_id'] ?? '',
];
$filtreParams = array_filter($filtreParams, fn($v) => $v !== '');
$qsBase = http_build_query($filtreParams);

$isAdmin = ($user['role'] === 'admin');
?>

<?php include __DIR__ . '/../../layouts/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Historique des actions</h1>
        <p class="page-sub">
            <?= $isAdmin ? 'Journal d\'activité de tous les caissiers' : 'Journal de vos actions en caisse' ?>
            — <?= $totalRows ?> action<?= $totalRows > 1 ? 's' : '' ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>caissier/paiements" class="btn-primary-drouot">
        <i class="fas fa-chart-line"></i> Suivi de caisse
    </a>
</div>

<!-- ── FILTRES ── -->
<div class="card-drouot mb-4">
    <form method="get" action="<?= BASE_URL ?>caissier/historique" class="filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="filter-label">Du</label>
                <input type="date" name="date_debut" class="filter-input"
                       value="<?= htmlspecialchars($_GET['date_debut'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label">Au</label>
                <input type="date" name="date_fin" class="filter-input"
                       value="<?= htmlspecialchars($_GET['date_fin'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label">Type d'action</label>
                <select name="action" class="filter-input">
                    <option value="">Toutes</option>
                    <?php foreach ($typesActions as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= ($_GET['action'] ?? '') === $t ? 'selected' : '' ?>>
                        <?= htmlspecialchars(actionLabel($t)['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($isAdmin && !empty($caissiers)): ?>
            <div class="col-md-3">
                <label class="filter-label">Caissier</label>
                <select name="caissier_id" class="filter-input">
                    <option value="">Tous les caissiers</option>
                    <?php foreach ($caissiers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($_GET['caissier_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-<?= $isAdmin && !empty($caissiers) ? 2 : 5 ?> d-flex gap-2">
                <button type="submit" class="btn-primary-drouot" style="padding:8px 14px;">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($filtreParams)): ?>
                <a href="<?= BASE_URL ?>caissier/historique" class="btn-reset-drouot" title="Réinitialiser">
                    <i class="fas fa-xmark"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="card-drouot">
    <?php if (empty($actions)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-clock-rotate-left fa-2x mb-2"></i>
            <p>Aucune action ne correspond à ces filtres.</p>
        </div>
    <?php else: ?>
        <div class="timeline-drouot">
            <?php foreach ($actions as $a):
                $meta = actionLabel($a['action']);
            ?>
            <div class="timeline-item">
                <div class="timeline-icon badge-<?= $meta['color'] ?>">
                    <i class="fas <?= $meta['icon'] ?>"></i>
                </div>
                <div class="timeline-content">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <span class="timeline-action"><?= htmlspecialchars($meta['label']) ?></span>
                            <?php if ($isAdmin): ?>
                            <span class="timeline-user">par <?= htmlspecialchars($a['user_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($a['facture_numero'])): ?>
                            <a href="<?= BASE_URL ?>caissier/factures/<?= $a['record_id'] ?>" class="facture-ref ms-1">
                                <?= htmlspecialchars($a['facture_numero']) ?>
                            </a>
                            <?php elseif (!empty($a['table_name'])): ?>
                            <span class="text-muted" style="font-size:0.78rem;">
                                (<?= htmlspecialchars($a['table_name']) ?> #<?= $a['record_id'] ?>)
                            </span>
                            <?php endif; ?>
                        </div>
                        <span class="timeline-date"><?= date('d/m/Y H:i:s', strtotime($a['created_at'])) ?></span>
                    </div>
                    <?php if (!empty($a['ip_address'])): ?>
                    <div class="timeline-ip">
                        <i class="fas fa-location-dot"></i> <?= htmlspecialchars($a['ip_address']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-drouot mt-3">
            <?php $qs = $qsBase !== '' ? $qsBase . '&' : ''; ?>
            <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
               href="<?= BASE_URL ?>caissier/historique?<?= $qs ?>page=<?= max(1, $page - 1) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="page-info">Page <?= $page ?> / <?= $totalPages ?></span>
            <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
               href="<?= BASE_URL ?>caissier/historique?<?= $qs ?>page=<?= min($totalPages, $page + 1) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .page-title { font-size: 1.3rem; font-weight: 600; color: #1a2340; margin: 0; }
    .page-sub { font-size: 0.85rem; color: #6b7a99; margin: 2px 0 0; }

    .filter-label {
        font-size: 0.72rem; font-weight: 600; color: #6b7a99;
        text-transform: uppercase; letter-spacing: 0.02em;
        margin-bottom: 4px; display: block;
    }
    .filter-input {
        width: 100%; padding: 7px 10px;
        border: 1px solid #dde3f0; border-radius: 8px;
        font-size: 0.82rem; color: #1a2340; background: #fff;
    }
    .filter-input:focus { outline: none; border-color: #1E88E5; }
    .btn-reset-drouot {
        display: inline-flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 8px;
        background: #F4F6F9; color: #6b7a99; text-decoration: none;
    }
    .btn-reset-drouot:hover { background: #FFEBEE; color: #C62828; }

    .facture-ref { font-weight: 600; color: #1E88E5; font-size: 0.8rem; text-decoration: none; }
    .facture-ref:hover { text-decoration: underline; }

    /* ── Timeline ── */
    .timeline-drouot { display: flex; flex-direction: column; }
    .timeline-item {
        display: flex; gap: 12px;
        padding: 12px 4px;
        border-bottom: 1px solid #f8f9fc;
    }
    .timeline-item:last-child { border-bottom: none; }
    .timeline-icon {
        flex-shrink: 0;
        width: 34px; height: 34px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem;
    }
    .timeline-icon.badge-success   { background: #E8F5E9; color: #2E7D32; }
    .timeline-icon.badge-warning   { background: #FFF8E1; color: #F57F17; }
    .timeline-icon.badge-danger    { background: #FFEBEE; color: #C62828; }
    .timeline-icon.badge-info      { background: #E3F2FD; color: #1565C0; }
    .timeline-icon.badge-secondary { background: #F4F6F9; color: #6b7a99; }

    .timeline-content { flex: 1; min-width: 0; }
    .timeline-action { font-size: 0.85rem; font-weight: 600; color: #1a2340; }
    .timeline-user { font-size: 0.8rem; color: #6b7a99; margin-left: 4px; }
    .timeline-date { font-size: 0.75rem; color: #6b7a99; white-space: nowrap; }
    .timeline-ip { font-size: 0.72rem; color: #9aa5bd; margin-top: 3px; }

    .pagination-drouot { display: flex; align-items: center; justify-content: center; gap: 12px; }
    .page-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px;
        background: #F4F6F9; color: #1a2340; text-decoration: none; font-size: 0.8rem;
    }
    .page-btn:hover:not(.disabled) { background: #1E88E5; color: #fff; }
    .page-btn.disabled { opacity: 0.4; pointer-events: none; }
    .page-info { font-size: 0.8rem; color: #6b7a99; }
</style>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
