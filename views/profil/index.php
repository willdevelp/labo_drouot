<?php
/**
 * Mon profil — Laboratoire Drouot
 * Variables attendues (ProfileController::index) :
 * $profil, $successInfo, $errorInfo, $successPwd, $errorPwd
 */

function roleLabel(string $role): string {
    $labels = [
        'admin'      => 'Administrateur',
        'secretaire' => 'Secrétaire',
        'caissier'   => 'Caissier',
        'technicien' => 'Technicien',
        'biologiste' => 'Biologiste',
        'comptable'  => 'Comptable',
        'directeur'  => 'Directeur',
        'infirmier'  => 'Infirmier',
    ];
    return $labels[$role] ?? ucfirst($role);
}

$initiales = strtoupper(mb_substr($profil['name'], 0, 1) . (mb_strpos($profil['name'], ' ') !== false
    ? mb_substr(trim(mb_strstr($profil['name'], ' ')), 0, 1)
    : ''));
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Mon profil</h1>
        <p class="page-sub">Consultez et modifiez vos informations personnelles</p>
    </div>
</div>

<div class="row g-3">

    <!-- Colonne gauche — carte identité -->
    <div class="col-xl-4">
        <div class="card-drouot text-center">
            <div class="profil-avatar mx-auto mb-3"><?= htmlspecialchars($initiales) ?></div>
            <h5 class="profil-name"><?= htmlspecialchars($profil['name']) ?></h5>
            <span class="role-badge"><?= htmlspecialchars(roleLabel($profil['role'])) ?></span>

            <div class="profil-meta mt-4">
                <div class="profil-meta-item">
                    <i class="fas fa-envelope"></i>
                    <span><?= htmlspecialchars($profil['email']) ?></span>
                </div>
                <div class="profil-meta-item">
                    <i class="fas fa-phone"></i>
                    <span><?= htmlspecialchars($profil['telephone'] ?: 'Non renseigné') ?></span>
                </div>
                <div class="profil-meta-item">
                    <i class="fas fa-circle-check" style="color:<?= $profil['is_active'] ? '#2E7D32' : '#C62828' ?>;"></i>
                    <span><?= $profil['is_active'] ? 'Compte actif' : 'Compte désactivé' ?></span>
                </div>
                <div class="profil-meta-item">
                    <i class="fas fa-clock-rotate-left"></i>
                    <span>
                        Dernière connexion :
                        <?= $profil['last_login'] ? date('d/m/Y H:i', strtotime($profil['last_login'])) : '—' ?>
                    </span>
                </div>
                <div class="profil-meta-item">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Membre depuis le <?= date('d/m/Y', strtotime($profil['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne droite — formulaires -->
    <div class="col-xl-8">

        <!-- Modifier informations -->
        <div class="card-drouot mb-3">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-user-pen text-primary me-2"></i>
                Informations personnelles
            </h6>

            <?php if ($successInfo): ?>
            <div class="alert-drouot alert-success mb-3">
                <i class="fas fa-circle-check"></i> <?= htmlspecialchars($successInfo) ?>
            </div>
            <?php endif; ?>
            <?php if ($errorInfo): ?>
            <div class="alert-drouot alert-danger mb-3">
                <i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($errorInfo) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>profil/update-info">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-drouot">Nom complet</label>
                        <input type="text" name="name" class="form-input-drouot"
                               value="<?= htmlspecialchars($profil['name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-drouot">Email</label>
                        <input type="email" name="email" class="form-input-drouot"
                               value="<?= htmlspecialchars($profil['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-drouot">Téléphone</label>
                        <input type="tel" name="telephone" class="form-input-drouot"
                               value="<?= htmlspecialchars($profil['telephone'] ?? '') ?>"
                               placeholder="+237 6XX XX XX XX">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-drouot">Rôle</label>
                        <input type="text" class="form-input-drouot" value="<?= htmlspecialchars(roleLabel($profil['role'])) ?>" disabled>
                        <p class="form-hint">Le rôle ne peut être modifié que par un administrateur.</p>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn-primary-drouot">
                        <i class="fas fa-floppy-disk"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>

        <!-- Modifier mot de passe -->
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-lock text-primary me-2"></i>
                Changer le mot de passe
            </h6>

            <?php if ($successPwd): ?>
            <div class="alert-drouot alert-success mb-3">
                <i class="fas fa-circle-check"></i> <?= htmlspecialchars($successPwd) ?>
            </div>
            <?php endif; ?>
            <?php if ($errorPwd): ?>
            <div class="alert-drouot alert-danger mb-3">
                <i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($errorPwd) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="<?= BASE_URL ?>profil/update-password" id="formPassword">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label-drouot">Mot de passe actuel</label>
                        <input type="password" name="current_password" class="form-input-drouot" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-drouot">Nouveau mot de passe</label>
                        <input type="password" name="new_password" id="newPassword" class="form-input-drouot" minlength="8" required>
                        <p class="form-hint">Minimum 8 caractères.</p>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-drouot">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" id="confirmPassword" class="form-input-drouot" minlength="8" required>
                        <p class="form-hint form-hint-error" id="pwdMismatch" style="display:none;">Ne correspond pas.</p>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn-outline-drouot">
                        <i class="fas fa-key"></i> Modifier le mot de passe
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

<style>
    .page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
    .page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
    .card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; }

    .profil-avatar {
        width:80px; height:80px; border-radius:50%;
        background:#E3F2FD; color:#1565C0;
        display:flex; align-items:center; justify-content:center;
        font-size:1.8rem; font-weight:700;
    }
    .profil-name { font-size:1.05rem; font-weight:600; color:#1a2340; margin-bottom:6px; }
    .role-badge {
        display:inline-block; background:#F4F6F9; color:#1a2340;
        padding:3px 12px; border-radius:20px; font-size:.75rem; font-weight:600;
    }
    .profil-meta { text-align:left; display:flex; flex-direction:column; gap:10px; }
    .profil-meta-item {
        display:flex; align-items:center; gap:10px;
        font-size:.82rem; color:#6b7a99;
        padding-bottom:8px; border-bottom:1px solid #f0f2f8;
    }
    .profil-meta-item:last-child { border-bottom:none; padding-bottom:0; }
    .profil-meta-item i { width:16px; color:#90CAF9; text-align:center; }

    .form-label-drouot {
        font-size:.78rem; font-weight:600; color:#6b7a99;
        margin-bottom:4px; display:block;
    }
    .form-input-drouot {
        width:100%; padding:.65rem .85rem;
        border:1.5px solid #dde3f0; border-radius:9px;
        font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340;
        background:#fafbfd; outline:none;
    }
    .form-input-drouot:focus { border-color:#1E88E5; }
    .form-input-drouot:disabled { background:#f0f2f8; color:#9aa5bd; cursor:not-allowed; }
    .form-hint { font-size:.72rem; color:#9aa5bd; margin:4px 0 0; }
    .form-hint-error { color:#C62828; font-weight:600; }

    .btn-outline-drouot {
        display:inline-flex; align-items:center; gap:6px;
        padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px;
        background:#fff; color:#1a2340; font-size:.88rem; font-weight:500;
        text-decoration:none; transition:all .2s;
        font-family:'DM Sans',sans-serif; cursor:pointer;
    }
    .btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }

    .alert-drouot {
        padding:.85rem 1rem; border-radius:10px;
        display:flex; align-items:center; gap:8px; font-size:.88rem;
    }
    .alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
    .alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var newPwd     = document.getElementById('newPassword');
    var confirmPwd = document.getElementById('confirmPassword');
    var mismatch   = document.getElementById('pwdMismatch');
    var form       = document.getElementById('formPassword');

    function checkMatch() {
        if (confirmPwd.value === '') { mismatch.style.display = 'none'; return true; }
        var match = newPwd.value === confirmPwd.value;
        mismatch.style.display = match ? 'none' : 'block';
        return match;
    }

    newPwd.addEventListener('input', checkMatch);
    confirmPwd.addEventListener('input', checkMatch);

    form.addEventListener('submit', function (e) {
        if (!checkMatch()) {
            e.preventDefault();
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>