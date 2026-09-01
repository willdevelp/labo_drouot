<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';

$roleLabels = [
    'admin'      => ['Admin',       '#C62828', '#FFEBEE'],
    'directeur'  => ['Directeur',   '#0D47A1', '#E3F2FD'],
    'biologiste' => ['Biologiste',  '#1B5E20', '#E8F5E9'],
    'technicien' => ['Technicien',  '#E65100', '#FFF3E0'],
    'secretaire' => ['Secrétaire',  '#4A148C', '#F3E5F5'],
    'caissier'   => ['Caissier',    '#F57F17', '#FFF8E1'],
    'comptable'  => ['Comptable',   '#006064', '#E0F7FA'],
    'infirmier'  => ['Infirmier',   '#880E4F', '#FCE4EC'],
];
?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert-drouot alert-warning mb-4">
    <i class="fas fa-trash"></i>
    Utilisateur supprimé avec succès.
</div>
<?php endif; ?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Gestion des utilisateurs</h1>
        <p class="page-sub">
            <?= count($users) ?> utilisateur(s) trouvé(s)
        </p>
    </div>
    <a href="<?= BASE_URL ?>admin/users/create"
       class="btn-primary-drouot">
        <i class="fas fa-user-plus"></i> Nouvel utilisateur
    </a>
</div>

<!-- Stats par rôle -->
<div class="row g-2 mb-4">
    <?php foreach ($statsRoles as $stat): ?>
    <?php
    [$lbl, $color, $bg] = $roleLabels[$stat['role']]
        ?? [$stat['role'], '#6b7a99', '#F4F6F9'];
    ?>
    <div class="col-xl col-md-4 col-6">
        <div class="role-stat-card"
             style="border-top:3px solid <?= $color ?>;
             background:<?= $bg ?>;">
            <div class="role-stat-nom"
                 style="color:<?= $color ?>">
                <?= $lbl ?>
            </div>
            <div class="role-stat-val"><?= $stat['total'] ?></div>
            <div class="role-stat-actif">
                <?= $stat['actifs'] ?> actif(s)
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtres -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search"
                   class="search-input"
                   placeholder="Nom, email, téléphone..."
                   value="<?= htmlspecialchars(
                       $_GET['search'] ?? ''
                   ) ?>">
        </div>
        <select name="role" class="filter-select">
            <option value="">Tous les rôles</option>
            <?php foreach ($roleLabels as $key => [$lbl]): ?>
            <option value="<?= $key ?>"
                <?= ($_GET['role']??'')===$key ? 'selected':'' ?>>
                <?= $lbl ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-filter"></i> Filtrer
        </button>
        <a href="<?= BASE_URL ?>admin/users"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Table -->
<div class="card-drouot">
    <?php if (empty($users)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-users fa-3x mb-3"
               style="color:#dde3f0;"></i>
            <p>Aucun utilisateur trouvé.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th>Téléphone</th>
                    <th>Statut</th>
                    <th>Dernière connexion</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <?php
                [$lbl,$color,$bg] = $roleLabels[$u['role']]
                    ?? [$u['role'],'#6b7a99','#F4F6F9'];
                $initials = strtoupper(
                    substr($u['name'],0,1) .
                    (strpos($u['name'],' ')!==false
                        ? substr(strrchr($u['name'],' '),1,1)
                        : substr($u['name'],1,1))
                );
                ?>
                <tr class="<?= !$u['is_active']
                    ? 'row-inactive' : '' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar"
                                 style="background:<?= $bg ?>;
                                 color:<?= $color ?>;">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div style="font-weight:500;
                                    font-size:.88rem;">
                                    <?= htmlspecialchars($u['name']) ?>
                                </div>
                                <div style="font-size:.75rem;
                                    color:#6b7a99;">
                                    <?= htmlspecialchars($u['email']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge"
                              style="background:<?= $bg ?>;
                              color:<?= $color ?>;">
                            <?= $lbl ?>
                        </span>
                    </td>
                    <td style="font-size:.82rem;color:#6b7a99;">
                        <?= htmlspecialchars(
                            $u['telephone'] ?? '—'
                        ) ?>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge-normal">✅ Actif</span>
                        <?php else: ?>
                            <span class="badge-critical">
                                🔴 Inactif
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= $u['last_login']
                            ? date('d/m/Y H:i',
                                strtotime($u['last_login']))
                            : 'Jamais' ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= date('d/m/Y',
                            strtotime($u['created_at'])) ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>admin/users/<?= $u['id'] ?>"
                               class="action-btn"
                               title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= BASE_URL ?>admin/users/<?= $u['id'] ?>/edit"
                               class="action-btn action-btn-edit"
                               title="Modifier">
                                <i class="fas fa-pen"></i>
                            </a>
                            <?php
                            $currentUser = Auth::user();
                            if ($currentUser['id'] != $u['id']):
                            ?>
                            <form method="POST"
                                  action="<?= BASE_URL ?>admin/users/<?= $u['id'] ?>/toggle"
                                  data-offline-store="users"
                                  data-entity-id="<?= $u['id'] ?>"
                                  style="display:inline;">
                                <button type="submit"
                                        class="action-btn
                                        <?= $u['is_active']
                                            ? 'action-btn-warn'
                                            : 'action-btn-green' ?>"
                                        title="<?= $u['is_active']
                                            ? 'Désactiver'
                                            : 'Activer' ?>">
                                    <i class="fas <?= $u['is_active']
                                        ? 'fa-toggle-on'
                                        : 'fa-toggle-off' ?>"></i>
                                </button>
                            </form>
                            <?php endif; ?>
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

/* Stats rôles */
.role-stat-card {
    border-radius:10px; padding:12px 14px;
    border:1px solid rgba(0,0,0,.06);
}
.role-stat-nom {
    font-size:.72rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.06em;
    margin-bottom:4px;
}
.role-stat-val {
    font-size:1.4rem; font-weight:700; color:#1a2340;
    line-height:1;
}
.role-stat-actif { font-size:.72rem; color:#6b7a99; margin-top:2px; }

/* Filtres */
.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap { position:relative; flex:1; min-width:200px; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.filter-select { padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; cursor:pointer; }

/* Table */
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.row-inactive td { opacity:.6; }

/* Avatar */
.user-avatar {
    width:34px; height:34px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.78rem; font-weight:700; flex-shrink:0;
}
.role-badge {
    padding:3px 10px; border-radius:20px;
    font-size:.75rem; font-weight:600;
}

/* Actions */
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; background:#E3F2FD; color:#1565C0; text-decoration:none; font-size:.78rem; transition:all .2s; border:none; cursor:pointer; }
.action-btn:hover { background:#1E88E5; color:#fff; }
.action-btn-edit  { background:#FFF8E1; color:#F57F17; }
.action-btn-edit:hover { background:#F9A825; color:#fff; }
.action-btn-warn  { background:#FFF8E1; color:#F57F17; }
.action-btn-warn:hover { background:#F9A825; color:#fff; }
.action-btn-green { background:#E8F5E9; color:#2E7D32; }
.action-btn-green:hover { background:#2E7D32; color:#fff; }

.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-warning { background:#FFF8E1; color:#F57F17; border:1px solid #FFE082; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>