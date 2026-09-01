<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';

$roleLabels = [
    'admin'      => ['Administrateur', '#C62828', '#FFEBEE'],
    'directeur'  => ['Directeur',      '#0D47A1', '#E3F2FD'],
    'biologiste' => ['Biologiste',     '#1B5E20', '#E8F5E9'],
    'technicien' => ['Technicien',     '#E65100', '#FFF3E0'],
    'secretaire' => ['Secrétaire',     '#4A148C', '#F3E5F5'],
    'caissier'   => ['Caissier',       '#F57F17', '#FFF8E1'],
    'comptable'  => ['Comptable',      '#006064', '#E0F7FA'],
    'infirmier'  => ['Infirmier',      '#880E4F', '#FCE4EC'],
];
[$lbl,$color,$bg] = $roleLabels[$membre['role']]
    ?? [$membre['role'],'#6b7a99','#F4F6F9'];

$initials = strtoupper(
    substr($membre['name'],0,1) .
    (strpos($membre['name'],' ')!==false
        ? substr(strrchr($membre['name'],' '),1,1)
        : substr($membre['name'],1,1))
);
?>

<?php if ($created): ?>
<div class="alert-drouot alert-success mb-4">
    <i class="fas fa-circle-check"></i>
    Utilisateur créé avec succès !
</div>
<?php endif; ?>

<?php if ($updated): ?>
<div class="alert-drouot alert-success mb-4">
    <i class="fas fa-circle-check"></i>
    Informations mises à jour avec succès !
</div>
<?php endif; ?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Fiche utilisateur</h1>
        <p class="page-sub">
            Détails du compte et activités récentes
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/users/<?= $membre['id'] ?>/edit"
           class="btn-primary-drouot">
            <i class="fas fa-pen"></i> Modifier
        </a>
        <a href="<?= BASE_URL ?>admin/users"
           class="btn-outline-drouot">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- Infos -->
    <div class="col-xl-4">

        <!-- Profil -->
        <div class="card-drouot mb-4 text-center">
            <div class="user-big-avatar mx-auto mb-3"
                 style="background:<?= $bg ?>;
                 color:<?= $color ?>;">
                <?= $initials ?>
            </div>
            <h2 class="user-full-name">
                <?= htmlspecialchars($membre['name']) ?>
            </h2>
            <span class="role-badge"
                  style="background:<?= $bg ?>;
                  color:<?= $color ?>;">
                <?= $lbl ?>
            </span>
            <div class="mt-3">
                <?php if ($membre['is_active']): ?>
                    <span class="badge-normal">✅ Compte actif</span>
                <?php else: ?>
                    <span class="badge-critical">🔴 Compte inactif</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coordonnées -->
        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-address-card text-primary me-2"></i>
                Coordonnées
            </h6>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-envelope"></i> Email
                </span>
                <span class="info-value">
                    <?= htmlspecialchars($membre['email']) ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-phone"></i> Téléphone
                </span>
                <span class="info-value">
                    <?= htmlspecialchars(
                        $membre['telephone'] ?? '—'
                    ) ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-calendar"></i> Créé le
                </span>
                <span class="info-value">
                    <?= date('d/m/Y',
                        strtotime($membre['created_at'])) ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">
                    <i class="fas fa-clock"></i> Dernière connexion
                </span>
                <span class="info-value">
                    <?= $membre['last_login']
                        ? date('d/m/Y H:i',
                            strtotime($membre['last_login']))
                        : 'Jamais' ?>
                </span>
            </div>
        </div>

        <!-- Actions dangereuses -->
        <?php
        $currentUser = Auth::user();
        if ($currentUser['id'] != $membre['id']):
        ?>
        <div class="card-drouot danger-zone">
            <h6 class="card-section-title mb-3"
                style="color:#C62828;">
                <i class="fas fa-triangle-exclamation me-2"></i>
                Zone de danger
            </h6>
            <div class="d-flex flex-column gap-2">
                <!-- Toggle actif -->
                <form method="POST"
                      action="<?= BASE_URL ?>admin/users/<?= $membre['id'] ?>/toggle"
                      data-offline-store="users"
                      data-entity-id="<?= $membre['id'] ?>">
                    <button type="submit"
                            class="<?= $membre['is_active']
                                ? 'btn-warn-drouot'
                                : 'btn-success-drouot' ?>
                                w-100 justify-content-center"
                            onclick="return confirm(
                                '<?= $membre['is_active']
                                    ? 'Désactiver'
                                    : 'Activer' ?> ce compte ?'
                            )">
                        <i class="fas <?= $membre['is_active']
                            ? 'fa-toggle-off'
                            : 'fa-toggle-on' ?>"></i>
                        <?= $membre['is_active']
                            ? 'Désactiver le compte'
                            : 'Activer le compte' ?>
                    </button>
                </form>
                <!-- Supprimer -->
                <form method="POST"
                      action="<?= BASE_URL ?>admin/users/<?= $membre['id'] ?>/delete"
                      data-offline-store="users"
                      data-offline-delete-id="<?= $membre['id'] ?>">
                    <button type="submit"
                            class="btn-danger-drouot w-100
                                   justify-content-center"
                            onclick="return confirm(
                                'Supprimer définitivement '
                                + '<?= addslashes($membre['name']) ?>'
                                + ' ?\n\nCette action est irréversible.'
                            )">
                        <i class="fas fa-trash"></i>
                        Supprimer le compte
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Activités -->
    <div class="col-xl-8">

        <!-- Activités récentes -->
        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-clock-rotate-left
                           text-primary me-2"></i>
                Activités récentes
            </h6>
            <?php if (empty($activites)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-clock fa-2x mb-2"
                       style="color:#dde3f0;"></i>
                    <p>Aucune activité enregistrée.</p>
                </div>
            <?php else: ?>
            <div class="activity-timeline">
                <?php foreach ($activites as $act): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-action">
                            <?= htmlspecialchars($act['action']) ?>
                            <?php if ($act['table_name']): ?>
                            <span class="timeline-table">
                                <?= htmlspecialchars(
                                    $act['table_name']
                                ) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-date">
                            <?= $act['created_at']
                                ? date('d/m/Y H:i',
                                    strtotime($act['created_at']))
                                : '—' ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Réinitialiser mot de passe -->
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-lock text-primary me-2"></i>
                Réinitialiser le mot de passe
            </h6>
            <form method="POST"
                  action="<?= BASE_URL ?>admin/users/<?= $membre['id'] ?>/reset-password"
                  data-offline-store="users"
                  data-entity-id="<?= $membre['id'] ?>"
                  id="formReset">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label-drouot">
                            Nouveau mot de passe
                        </label>
                        <div class="input-pw-wrap">
                            <input type="password"
                                   name="new_password"
                                   id="newPw"
                                   class="form-input-drouot"
                                   placeholder="Min. 6 caractères">
                            <button type="button"
                                    onclick="togglePw()"
                                    class="pw-toggle">
                                <i class="fas fa-eye"
                                   id="pwEye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit"
                                class="btn-primary-drouot w-100
                                       justify-content-center"
                                style="padding:.72rem;"
                                onclick="return confirm(
                                    'Réinitialiser le mot de passe ?'
                                )">
                            <i class="fas fa-key"></i>
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; display:flex; align-items:center; }
.user-big-avatar { width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:700; }
.user-full-name { font-size:1.1rem; font-weight:600; color:#1a2340; margin-bottom:8px; }
.role-badge { padding:3px 12px; border-radius:20px; font-size:.78rem; font-weight:600; }
.info-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f0f2f8; font-size:.83rem; }
.info-row:last-child { border-bottom:none; }
.info-label { color:#6b7a99; display:flex; align-items:center; gap:8px; }
.info-label i { width:14px; text-align:center; color:#90CAF9; }
.info-value { font-weight:500; color:#1a2340; text-align:right; }
.danger-zone { border:1.5px solid #FFCDD2; }
.form-label-drouot { display:block; font-size:.78rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
.form-input-drouot { width:100%; padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; transition:all .2s; }
.form-input-drouot:focus { border-color:#1E88E5; background:#fff; box-shadow:0 0 0 3px rgba(30,136,229,.08); }
.input-pw-wrap { position:relative; }
.input-pw-wrap .form-input-drouot { padding-right:2.5rem; }
.pw-toggle { position:absolute; right:.75rem; top:50%; transform:translateY(-50%); background:none; border:none; color:#b0b8cc; cursor:pointer; font-size:.85rem; }

/* Timeline */
.activity-timeline { display:flex; flex-direction:column; gap:0; }
.timeline-item { display:flex; align-items:flex-start; gap:12px; padding:8px 0; border-bottom:1px solid #f0f2f8; }
.timeline-item:last-child { border-bottom:none; }
.timeline-dot { width:8px; height:8px; border-radius:50%; background:#1E88E5; flex-shrink:0; margin-top:5px; }
.timeline-content { flex:1; display:flex; justify-content:space-between; align-items:center; gap:8px; }
.timeline-action { font-size:.82rem; color:#1a2340; }
.timeline-table { background:#E3F2FD; color:#1565C0; padding:1px 6px; border-radius:4px; font-size:.7rem; margin-left:4px; }
.timeline-date { font-size:.75rem; color:#6b7a99; white-space:nowrap; }

/* Boutons */
.btn-warn-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:none; border-radius:9px; background:#FFF8E1; color:#F57F17; font-size:.88rem; font-weight:500; cursor:pointer; transition:all .2s; font-family:'DM Sans',sans-serif; width:100%; justify-content:center; }
.btn-warn-drouot:hover { background:#F9A825; color:#fff; }
.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
.w-100 { width:100%; } .justify-content-center { justify-content:center; }
</style>

<script>
function togglePw() {
    const f = document.getElementById('newPw');
    const i = document.getElementById('pwEye');
    if (f.type === 'password') {
        f.type = 'text';
        i.classList.replace('fa-eye','fa-eye-slash');
    } else {
        f.type = 'password';
        i.classList.replace('fa-eye-slash','fa-eye');
    }
}
</script>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>