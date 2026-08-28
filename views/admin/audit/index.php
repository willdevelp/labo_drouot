<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';

$actionColors = [
    'CREATE'      => ['#E8F5E9', '#2E7D32'],
    'UPDATE'      => ['#E3F2FD', '#1565C0'],
    'DELETE'      => ['#FFEBEE', '#C62828'],
    'LOGIN'       => ['#F3E5F5', '#7B1FA2'],
    'PAYMENT'     => ['#FFF8E1', '#F57F17'],
    'VALIDATE'    => ['#E8F5E9', '#2E7D32'],
    'REJECT'      => ['#FFEBEE', '#C62828'],
    'TRANSMIT'    => ['#E3F2FD', '#1565C0'],
    'SAVE'        => ['#E8F5E9', '#2E7D32'],
    'TOGGLE'      => ['#FFF8E1', '#F57F17'],
    'RESET'       => ['#FFF3E0', '#E65100'],
    'ADJUST'      => ['#E0F7FA', '#006064'],
];

function getActionColor(string $action, array $map): array {
    foreach ($map as $key => $colors) {
        if (str_starts_with($action, $key)) return $colors;
    }
    return ['#F4F6F9', '#6b7a99'];
}

$roleLabels = [
    'admin'      => 'Admin',
    'directeur'  => 'Directeur',
    'biologiste' => 'Biologiste',
    'technicien' => 'Technicien',
    'secretaire' => 'Secrétaire',
    'caissier'   => 'Caissier',
    'comptable'  => 'Comptable',
    'infirmier'  => 'Infirmier',
];
?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Journal d'audit</h1>
        <p class="page-sub">
            <?= $statsAudit['total'] ?> action(s) ·
            <?= $statsAudit['nb_users'] ?> utilisateur(s) ·
            <?= $statsAudit['nb_jours'] ?> jour(s)
        </p>
    </div>
    <a href="<?= BASE_URL ?>admin/audit/export"
       class="btn-success-drouot">
        <i class="fas fa-download"></i> Exporter CSV
    </a>
</div>

<!-- Stats top actions + top users -->
<div class="row g-3 mb-4">

    <div class="col-xl-5">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-chart-bar text-primary me-2"></i>
                Actions les plus fréquentes
            </h6>
            <?php
            $maxAct = max(array_column($topActions,'total'));
            foreach ($topActions as $ta):
                $pct = $maxAct > 0
                    ? round($ta['total']/$maxAct*100) : 0;
                [$bg,$color] = getActionColor(
                    $ta['action'], $actionColors
                );
            ?>
            <div class="act-freq-item">
                <span class="act-tag"
                      style="background:<?= $bg ?>;
                      color:<?= $color ?>;">
                    <?= htmlspecialchars($ta['action']) ?>
                </span>
                <div class="act-bar-wrap">
                    <div class="act-bar">
                        <div class="act-fill"
                             style="width:<?= $pct ?>%;
                             background:<?= $color ?>;">
                        </div>
                    </div>
                </div>
                <span class="act-count"><?= $ta['total'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-users text-primary me-2"></i>
                Utilisateurs les plus actifs
            </h6>
            <table class="table-drouot">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                        <th>Dernière action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topUsers as $tu): ?>
                    <tr>
                        <td style="font-weight:500;
                            font-size:.85rem;">
                            <?= htmlspecialchars($tu['name']) ?>
                        </td>
                        <td>
                            <span class="role-badge-sm">
                                <?= $roleLabels[$tu['role']]
                                    ?? $tu['role'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-pending">
                                <?= $tu['nb_actions'] ?>
                            </span>
                        </td>
                        <td style="font-size:.78rem;color:#6b7a99;">
                            <?= $tu['derniere_action']
                                ? date('d/m/Y H:i',
                                    strtotime($tu['derniere_action']))
                                : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Filtres -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form flex-wrap">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search"
                   class="search-input"
                   placeholder="Action, table, utilisateur..."
                   value="<?= htmlspecialchars(
                       $_GET['search'] ?? ''
                   ) ?>">
        </div>
        <select name="action" class="filter-select">
            <option value="">Toutes actions</option>
            <?php
            $actionFilters = [
                'CREATE'   => 'Créations',
                'UPDATE'   => 'Modifications',
                'DELETE'   => 'Suppressions',
                'PAYMENT'  => 'Paiements',
                'VALIDATE' => 'Validations',
                'LOGIN'    => 'Connexions',
            ];
            foreach ($actionFilters as $k => $v):
            ?>
            <option value="<?= $k ?>"
                <?= ($_GET['action']??'')===$k?'selected':'' ?>>
                <?= $v ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="user_id" class="filter-select">
            <option value="">Tous utilisateurs</option>
            <?php foreach ($allUsers as $u): ?>
            <option value="<?= $u['id'] ?>"
                <?= ($_GET['user_id']??'')==$u['id']
                    ?'selected':'' ?>>
                <?= htmlspecialchars($u['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date"
               class="filter-select"
               value="<?= htmlspecialchars($_GET['date'] ?? '') ?>">
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-filter"></i> Filtrer
        </button>
        <a href="<?= BASE_URL ?>admin/audit"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Journal -->
<div class="card-drouot">
    <div class="d-flex align-items-center
                justify-content-between mb-3">
        <h6 class="card-section-title">
            <i class="fas fa-list text-primary me-2"></i>
            Journal des actions
            <span class="count-badge">
                <?= count($logs) ?>
            </span>
        </h6>
    </div>

    <?php if (empty($logs)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-shield-halved fa-3x mb-3"
               style="color:#dde3f0;"></i>
            <p>Aucun log trouvé.</p>
        </div>
    <?php else: ?>
    <div class="audit-timeline">
        <?php
        $currentDate = null;
        foreach ($logs as $log):
            $logDate = $log['created_at']
                ? date('d/m/Y', strtotime($log['created_at']))
                : '—';

            if ($logDate !== $currentDate):
                $currentDate = $logDate;
        ?>
        <div class="audit-date-sep">
            <i class="fas fa-calendar-day"></i>
            <?= $logDate ?>
        </div>
        <?php endif; ?>

        <?php
        [$bg,$color] = getActionColor(
            $log['action'] ?? '', $actionColors
        );
        ?>
        <div class="audit-item">
            <div class="audit-time">
                <?= $log['created_at']
                    ? date('H:i:s',
                        strtotime($log['created_at']))
                    : '—' ?>
            </div>
            <div class="audit-dot"
                 style="background:<?= $color ?>"></div>
            <div class="audit-content">
                <div class="audit-action-wrap">
                    <span class="audit-action-tag"
                          style="background:<?= $bg ?>;
                          color:<?= $color ?>;">
                        <?= htmlspecialchars(
                            $log['action'] ?? '—'
                        ) ?>
                    </span>
                    <?php if ($log['table_name']): ?>
                    <span class="audit-table">
                        <?= htmlspecialchars($log['table_name']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($log['record_id']): ?>
                    <span class="audit-id">
                        #<?= $log['record_id'] ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="audit-user">
                    <i class="fas fa-user"></i>
                    <?= htmlspecialchars(
                        $log['user_name'] ?? 'Système'
                    ) ?>
                    <?php if ($log['user_role']): ?>
                    <span class="role-badge-sm">
                        <?= $roleLabels[$log['user_role']]
                            ?? $log['user_role'] ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; display:flex; align-items:center; gap:8px; }
.count-badge { background:#E3F2FD; color:#1565C0; padding:2px 8px; border-radius:20px; font-size:.72rem; font-weight:600; }

/* Filtres */
.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap { position:relative; flex:1; min-width:180px; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.filter-select { padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; cursor:pointer; }

/* Actions fréquentes */
.act-freq-item { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
.act-tag { padding:2px 8px; border-radius:6px; font-size:.72rem; font-weight:600; white-space:nowrap; }
.act-bar-wrap { flex:1; }
.act-bar { height:7px; background:#f0f2f8; border-radius:20px; overflow:hidden; }
.act-fill { height:100%; border-radius:20px; }
.act-count { font-size:.8rem; font-weight:700; color:#1a2340; min-width:30px; text-align:right; }

/* Table */
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:9px 10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.role-badge-sm { background:#E3F2FD; color:#1565C0; padding:2px 7px; border-radius:6px; font-size:.72rem; font-weight:500; }

/* Timeline audit */
.audit-timeline { display:flex; flex-direction:column; }
.audit-date-sep {
    display:flex; align-items:center; gap:8px;
    padding:8px 0; margin:8px 0 4px;
    font-size:.75rem; font-weight:700;
    color:#6b7a99; text-transform:uppercase;
    letter-spacing:.06em;
    border-top:1px solid #f0f2f8;
}
.audit-date-sep i { color:#90CAF9; }
.audit-date-sep:first-child { border-top:none; margin-top:0; }

.audit-item {
    display:flex; align-items:flex-start; gap:10px;
    padding:8px 4px;
    border-bottom:1px dashed #f5f7fa;
}
.audit-item:last-child { border-bottom:none; }
.audit-time {
    font-family:monospace; font-size:.72rem;
    color:#b0b8cc; min-width:56px; padding-top:2px;
}
.audit-dot {
    width:8px; height:8px; border-radius:50%;
    flex-shrink:0; margin-top:5px;
}
.audit-content { flex:1; }
.audit-action-wrap {
    display:flex; align-items:center;
    gap:6px; flex-wrap:wrap; margin-bottom:3px;
}
.audit-action-tag {
    padding:2px 8px; border-radius:6px;
    font-size:.72rem; font-weight:700;
}
.audit-table {
    background:#F4F6F9; color:#6b7a99;
    padding:2px 6px; border-radius:4px;
    font-size:.7rem;
}
.audit-id {
    font-family:monospace; font-size:.7rem; color:#90CAF9;
}
.audit-user {
    font-size:.78rem; color:#6b7a99;
    display:flex; align-items:center; gap:6px;
}
.audit-user i { font-size:.7rem; }

.btn-success-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:none; border-radius:9px; background:#2E7D32; color:#fff; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-success-drouot:hover { background:#1B5E20; color:#fff; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>