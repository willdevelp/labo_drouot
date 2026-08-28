<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';

$roleOptions = [
    'admin'      => 'Administrateur',
    'directeur'  => 'Directeur',
    'biologiste' => 'Biologiste',
    'technicien' => 'Technicien de laboratoire',
    'secretaire' => 'Secrétaire médicale',
    'caissier'   => 'Caissier',
    'comptable'  => 'Comptable',
    'infirmier'  => 'Infirmier / Préleveur',
];
$icons = [
    'admin'=>'fa-shield-halved', 'directeur'=>'fa-chart-line',
    'biologiste'=>'fa-microscope', 'technicien'=>'fa-vial',
    'secretaire'=>'fa-user-nurse', 'caissier'=>'fa-cash-register',
    'comptable'=>'fa-calculator', 'infirmier'=>'fa-syringe',
];
?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Modifier — <?= htmlspecialchars($membre['name']) ?>
        </h1>
        <p class="page-sub">Modifier les informations du compte</p>
    </div>
    <a href="<?= BASE_URL ?>admin/users/<?= $membre['id'] ?>"
       class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert-drouot alert-danger mb-4">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="col-xl-8">
    <form method="POST"
          action="<?= BASE_URL ?>admin/users/<?= $membre['id'] ?>/edit">

        <!-- Identité -->
        <div class="card-drouot mb-4">
            <h6 class="form-section-title">
                <i class="fas fa-id-card"></i>
                Informations personnelles
            </h6>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label-drouot">
                        Nom complet <span class="required">*</span>
                    </label>
                    <input type="text" name="name"
                           class="form-input-drouot"
                           value="<?= htmlspecialchars($membre['name']) ?>"
                           required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-drouot">
                        Téléphone
                    </label>
                    <input type="tel" name="telephone"
                           class="form-input-drouot"
                           value="<?= htmlspecialchars(
                               $membre['telephone'] ?? ''
                           ) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label-drouot">
                        Email <span class="required">*</span>
                    </label>
                    <input type="email" name="email"
                           class="form-input-drouot"
                           value="<?= htmlspecialchars($membre['email']) ?>"
                           required>
                </div>
            </div>
        </div>

        <!-- Rôle -->
        <div class="card-drouot mb-4">
            <h6 class="form-section-title">
                <i class="fas fa-shield-halved"></i>
                Rôle & Permissions
            </h6>
            <div class="role-grid">
                <?php foreach ($roleOptions as $key => $lbl): ?>
                <label class="role-select-card
                    <?= $membre['role']===$key ? 'selected' : '' ?>"
                       id="role_card_<?= $key ?>">
                    <input type="radio" name="role"
                           value="<?= $key ?>"
                           class="role-radio"
                           <?= $membre['role']===$key
                               ? 'checked' : '' ?>
                           onchange="selectRole('<?= $key ?>')">
                    <div class="role-card-icon">
                        <i class="fas <?= $icons[$key] ?? 'fa-user' ?>"></i>
                    </div>
                    <div class="role-card-label"><?= $lbl ?></div>
                    <div class="role-check">
                        <i class="fas fa-check"></i>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/users/<?= $membre['id'] ?>"
               class="btn-outline-drouot">
                <i class="fas fa-xmark"></i> Annuler
            </a>
            <button type="submit"
                    class="btn-primary-drouot"
                    style="padding:.7rem 1.5rem;">
                <i class="fas fa-floppy-disk"></i>
                Sauvegarder
            </button>
        </div>

    </form>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.form-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin-bottom:1rem; display:flex; align-items:center; gap:8px; padding-bottom:8px; border-bottom:1.5px solid #f0f2f8; }
.form-section-title i { color:#1E88E5; }
.form-label-drouot { display:block; font-size:.78rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
.required { color:#C62828; }
.form-input-drouot { width:100%; padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; transition:all .2s; }
.form-input-drouot:focus { border-color:#1E88E5; background:#fff; box-shadow:0 0 0 3px rgba(30,136,229,.08); }
.role-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; }
.role-select-card { border:1.5px solid #dde3f0; border-radius:10px; padding:10px 8px; text-align:center; cursor:pointer; transition:all .18s; display:flex; flex-direction:column; align-items:center; gap:4px; position:relative; }
.role-select-card:hover { border-color:#1E88E5; background:#f0f7ff; }
.role-select-card.selected { border-color:#1E88E5; background:#E3F2FD; }
.role-radio { display:none; }
.role-card-icon { width:36px; height:36px; border-radius:8px; background:#f4f6f9; display:flex; align-items:center; justify-content:center; font-size:.95rem; color:#6b7a99; }
.role-select-card.selected .role-card-icon { background:#1E88E5; color:#fff; }
.role-card-label { font-size:.7rem; font-weight:500; color:#1a2340; line-height:1.3; }
.role-check { position:absolute; top:5px; right:5px; width:16px; height:16px; border-radius:50%; background:#1E88E5; color:#fff; display:none; align-items:center; justify-content:center; font-size:.6rem; }
.role-select-card.selected .role-check { display:flex; }
.form-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:1.5rem; padding-top:1.5rem; border-top:1.5px solid #f0f2f8; }
.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-danger { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
@media (max-width:768px) { .role-grid { grid-template-columns:repeat(2,1fr); } }
</style>

<script>
function selectRole(key) {
    document.querySelectorAll('.role-select-card')
        .forEach(c => c.classList.remove('selected'));
    document.getElementById('role_card_' + key)
        .classList.add('selected');
}
</script>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>