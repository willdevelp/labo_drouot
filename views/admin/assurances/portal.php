<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portail Assurance — Laboratoire Drouot</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { box-sizing:border-box; }
body {
    margin:0; font-family:'DM Sans', -apple-system, sans-serif;
    background:#f4f6fb; color:#1a2340;
}
.wrap { max-width:920px; margin:0 auto; padding:20px 16px 60px; }

.portal-header {
    display:flex; align-items:center; gap:14px;
    background:#fff; border-radius:16px; padding:20px;
    margin-bottom:18px; box-shadow:0 2px 10px rgba(0,0,0,.04);
}
.portal-logo {
    width:48px; height:48px; border-radius:12px;
    background:#8E24AA; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; flex-shrink:0;
}
.portal-title { font-size:1.05rem; font-weight:700; margin:0; }
.portal-sub { font-size:.82rem; color:#6b7a99; margin:2px 0 0; }
.assurance-badge { margin-left:auto; text-align:right; }
.assurance-badge-name { font-size:.85rem; font-weight:600; color:#1a2340; }
.assurance-badge-context { font-size:.7rem; color:#8E24AA; margin-top:1px; }

.alert-box {
    padding:.85rem 1rem; border-radius:10px; margin-bottom:18px;
    display:flex; align-items:center; gap:8px; font-size:.85rem;
}
.alert-success { background:#E8F5E9; color:#2E7D32; }
.alert-error { background:#FFEBEE; color:#C62828; }

.stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px; }
.stat-card {
    background:#fff; border-radius:14px; padding:14px;
    text-align:center; box-shadow:0 2px 10px rgba(0,0,0,.04);
}
.stat-value { font-size:1.4rem; font-weight:700; color:#1a2340; }
.stat-label { font-size:.72rem; color:#6b7a99; margin-top:2px; }
.stat-card.orange .stat-value { color:#F57F17; }

.creance-box {
    background:linear-gradient(135deg, #8E24AA, #6A1B9A);
    border-radius:16px; padding:20px; margin-bottom:18px; color:#fff;
}
.creance-title {
    font-size:.85rem; font-weight:600; opacity:.9;
    display:flex; align-items:center; gap:8px; margin-bottom:14px;
}
.creance-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.creance-item-value { font-size:1.5rem; font-weight:700; }
.creance-item-label { font-size:.75rem; opacity:.85; margin-top:2px; }

.section-title {
    font-size:.92rem; font-weight:700; color:#1a2340;
    margin:20px 0 12px; display:flex; align-items:center; gap:8px;
}

.demande-card {
    background:#fff; border-radius:14px; padding:16px;
    margin-bottom:10px; box-shadow:0 2px 10px rgba(0,0,0,.04);
    border-left:3px solid #F9A825;
}
.demande-top {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:10px; margin-bottom:8px;
}
.demande-patient { font-size:.92rem; font-weight:600; }
.demande-meta { font-size:.78rem; color:#6b7a99; margin-top:2px; }
.demande-examens { font-size:.8rem; color:#6b7a99; margin-bottom:12px; }
.demande-montant { font-size:.95rem; font-weight:700; color:#1565C0; }

.action-form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.code-input {
    flex:1; min-width:160px; padding:8px 12px;
    border:1.5px solid #dde3f0; border-radius:8px;
    font-size:.85rem; outline:none;
}
.code-input:focus { border-color:#1E88E5; }
.btn-valider {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:8px; border:none; cursor:pointer;
    background:#2E7D32; color:#fff; font-size:.82rem; font-weight:500;
}
.btn-valider:hover { background:#1B5E20; }
.btn-rejeter {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; border-radius:8px; border:none; cursor:pointer;
    background:#FFEBEE; color:#C62828; font-size:.82rem; font-weight:500;
}
.btn-rejeter:hover { background:#C62828; color:#fff; }

.empty-state { text-align:center; padding:40px 20px; color:#6b7a99; }
.empty-state i { font-size:2.5rem; color:#dde3f0; margin-bottom:12px; display:block; }

/* Historique */
.historique-table { background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.04); }
.hist-row {
    display:grid; grid-template-columns:1.5fr 1fr .8fr .8fr; gap:8px;
    padding:10px 14px; border-bottom:1px solid #f0f2f8;
    align-items:center; font-size:.8rem;
}
.hist-row:last-child { border-bottom:none; }
.hist-header { background:#fafbfc; font-size:.7rem; font-weight:600; color:#6b7a99; text-transform:uppercase; }
.hist-patient { font-weight:500; }
.hist-date { color:#6b7a99; font-size:.75rem; }
.status-tag { padding:2px 8px; border-radius:20px; font-size:.7rem; font-weight:600; }
.status-validee { background:#E8F5E9; color:#2E7D32; }
.status-rejetee { background:#FFEBEE; color:#C62828; }
.creance-tag-due { background:#FFF8E1; color:#F57F17; padding:2px 8px; border-radius:6px; font-size:.7rem; }
.creance-tag-paid { background:#E8F5E9; color:#2E7D32; padding:2px 8px; border-radius:6px; font-size:.7rem; }

.footer-note { text-align:center; font-size:.72rem; color:#b0b8cc; margin-top:24px; }

/* Import */
.import-card { background:#fff; border-radius:14px; padding:18px; box-shadow:0 2px 10px rgba(0,0,0,.04); }
.upload-zone {
    border:2px dashed #dde3f0; border-radius:12px;
    padding:28px 20px; text-align:center; cursor:pointer;
    transition:all .2s;
}
.upload-zone:hover { border-color:#8E24AA; background:#F3E5F5; }
.upload-zone i { font-size:2rem; color:#CE93D8; margin-bottom:8px; display:block; }
.upload-text { font-size:.85rem; color:#6b7a99; margin:0; }
.import-help { font-size:.75rem; color:#6b7a99; margin:10px 0 0; line-height:1.5; }

/* Sous-comptes */
.sous-comptes-list { display:flex; flex-direction:column; gap:10px; margin-bottom:14px; }
.sous-compte-card { background:#fff; border-radius:14px; padding:14px; box-shadow:0 2px 10px rgba(0,0,0,.04); }
.sc-inactive { opacity:.6; }
.sc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.sc-nom { font-size:.88rem; font-weight:600; }
.sc-permissions { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px; }
.perm-tag { background:#E3F2FD; color:#1565C0; padding:2px 8px; border-radius:6px; font-size:.7rem; }
.sc-link-box { margin-bottom:10px; }
.sc-link-input {
    width:100%; padding:7px 10px; border:1.5px solid #dde3f0; border-radius:8px;
    font-family:monospace; font-size:.7rem; color:#6b7a99; background:#fafbfd;
}
.sc-actions { display:flex; gap:8px; }
.btn-sc-action {
    display:inline-flex; align-items:center; gap:5px;
    padding:6px 12px; border-radius:7px; border:none; cursor:pointer;
    background:#F4F6F9; color:#1a2340; font-size:.75rem;
}
.btn-sc-action:hover { background:#1a2340; color:#fff; }
.btn-sc-danger { background:#FFEBEE; color:#C62828; }
.btn-sc-danger:hover { background:#C62828; color:#fff; }

.creer-sc-card { background:#fff; border-radius:14px; padding:16px; box-shadow:0 2px 10px rgba(0,0,0,.04); }
.creer-sc-title { font-size:.85rem; font-weight:600; margin:0 0 10px; }
.sc-perm-checks { display:flex; flex-direction:column; gap:8px; font-size:.82rem; color:#1a2340; }
.sc-perm-checks label { display:flex; align-items:center; gap:8px; cursor:pointer; }
.sc-perm-checks input { width:16px; height:16px; accent-color:#8E24AA; }

@media (max-width: 600px) {
    .hist-row { grid-template-columns:1fr; gap:4px; }
}
</style>
</head>
<body>
<div class="wrap">

    <div class="portal-header">
        <div class="portal-logo"><i class="fas fa-shield-heart"></i></div>
        <div>
            <p class="portal-title">Laboratoire Drouot</p>
            <p class="portal-sub">Portail assurance</p>
        </div>
        <div class="assurance-badge">
            <div class="assurance-badge-name"><?= htmlspecialchars($assurance['nom']) ?></div>
            <div class="assurance-badge-context"><?= htmlspecialchars($contexteLabel) ?></div>
        </div>
    </div>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'validated'): ?>
    <div class="alert-box alert-success">
        <i class="fas fa-circle-check"></i> Demande validée avec succès.
    </div>
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'rejected'): ?>
    <div class="alert-box alert-error">
        <i class="fas fa-circle-xmark"></i> Demande rejetée.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert-box alert-error">
        <i class="fas fa-triangle-exclamation"></i>
        <?= $_GET['error'] === 'code_requis' ? 'Le code d\'accord est obligatoire.' : 'Demande introuvable ou déjà traitée.' ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card orange">
            <div class="stat-value"><?= count($enAttente) ?></div>
            <div class="stat-label">En attente de validation</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($historique) ?></div>
            <div class="stat-label">Demandes traitées</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?= count(array_filter($historique, fn($h) => $h['assurance_statut'] === 'validee')) ?>
            </div>
            <div class="stat-label">Validées</div>
        </div>
    </div>

    <!-- Créances -->
    <?php if ($permissions['peut_voir_creances']): ?>
    <div class="creance-box">
        <div class="creance-title">
            <i class="fas fa-hand-holding-dollar"></i>
            Récapitulatif de vos créances envers le laboratoire
        </div>
        <div class="creance-grid">
            <div>
                <div class="creance-item-value">
                    <?= number_format((float)$creanceStats['total_du'], 0, ',', ' ') ?> F
                </div>
                <div class="creance-item-label">Dues</div>
            </div>
            <div>
                <div class="creance-item-value">
                    <?= number_format((float)$creanceStats['total_paye'], 0, ',', ' ') ?> F
                </div>
                <div class="creance-item-label">Déjà payées</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Demandes en attente -->
    <h6 class="section-title">
        <i class="fas fa-hourglass-half text-warning"></i>
        Demandes en attente de validation
    </h6>

    <?php if (empty($enAttente)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <p>Aucune demande en attente. Tout est traité !</p>
        </div>
    <?php else: ?>
        <?php foreach ($enAttente as $d): ?>
        <div class="demande-card">
            <div class="demande-top">
                <div>
                    <div class="demande-patient"><?= htmlspecialchars($d['patient_nom']) ?></div>
                    <div class="demande-meta">
                        N° police : <?= htmlspecialchars($d['numero_police'] ?? '—') ?>
                        · <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                    </div>
                </div>
                <div class="demande-montant">
                    <?= number_format((float)$d['montant_net'], 0, ',', ' ') ?> F
                </div>
            </div>
            <div class="demande-examens">
                <i class="fas fa-flask"></i>
                <?= htmlspecialchars($d['examens'] ?? '—') ?> (<?= $d['nb_examens'] ?> examen(s))
            </div>

            <?php if ($permissions['peut_valider']): ?>
            <form method="POST"
                  action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/valider/<?= $d['id'] ?>"
                  class="action-form">
                <input type="text" name="code_accord" class="code-input"
                       placeholder="Votre code d'accord..." required>
                <button type="submit" class="btn-valider">
                    <i class="fas fa-check"></i> Valider
                </button>
            </form>
            <form method="POST"
                  action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/rejeter/<?= $d['id'] ?>"
                  style="margin-top:8px;"
                  onsubmit="return confirm('Confirmer le rejet de cette prise en charge ?')">
                <button type="submit" class="btn-rejeter">
                    <i class="fas fa-xmark"></i> Rejeter la prise en charge
                </button>
            </form>
            <?php else: ?>
            <p style="font-size:.78rem;color:#b0b8cc;margin:0;">
                <i class="fas fa-lock"></i> Vous n'avez pas la permission de valider.
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Historique -->
    <h6 class="section-title">
        <i class="fas fa-clock-rotate-left text-primary"></i>
        Historique des demandes traitées
    </h6>

    <?php if (empty($historique)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Aucune demande traitée pour le moment.</p>
        </div>
    <?php else: ?>
    <div class="historique-table">
        <div class="hist-row hist-header">
            <span>Patient</span>
            <span>Date</span>
            <span>Statut</span>
            <span>Créance</span>
        </div>
        <?php foreach ($historique as $h): ?>
        <div class="hist-row">
            <span class="hist-patient"><?= htmlspecialchars($h['patient_nom']) ?></span>
            <span class="hist-date"><?= date('d/m/Y', strtotime($h['created_at'])) ?></span>
            <span>
                <?php if ($h['assurance_statut'] === 'validee'): ?>
                    <span class="status-tag status-validee">Validée</span>
                <?php else: ?>
                    <span class="status-tag status-rejetee">Rejetée</span>
                <?php endif; ?>
            </span>
            <span>
                <?php if ($h['montant_creance'] !== null): ?>
                    <?php if ($h['creance_statut'] === 'due'): ?>
                        <span class="creance-tag-due"><?= number_format((float)$h['montant_creance'], 0, ',', ' ') ?> F due</span>
                    <?php else: ?>
                        <span class="creance-tag-paid"><?= number_format((float)$h['montant_creance'], 0, ',', ' ') ?> F payée</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span style="color:#b0b8cc;">—</span>
                <?php endif; ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Import fichier assurés -->
    <?php if ($permissions['peut_importer']): ?>
    <h6 class="section-title">
        <i class="fas fa-file-excel text-primary"></i>
        Importer votre fichier d'assurés
    </h6>

    <?php if ($importResult): ?>
    <div class="alert-box alert-success">
        <i class="fas fa-circle-check"></i>
        Import terminé : <?= $importResult['inseres'] ?> ajouté(s),
        <?= $importResult['maj'] ?> mis à jour
        <?php if ($importResult['ignores'] > 0): ?>, <?= $importResult['ignores'] ?> ignoré(s)<?php endif; ?>.
    </div>
    <?php endif; ?>
    <?php if ($importError): ?>
    <div class="alert-box alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($importError) ?>
    </div>
    <?php endif; ?>

    <div class="import-card">
        <form method="POST"
              action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/import"
              enctype="multipart/form-data">
            <div class="upload-zone" onclick="document.getElementById('fichierInput').click()">
                <i class="fas fa-cloud-arrow-up"></i>
                <p class="upload-text">Cliquez pour sélectionner votre fichier .xlsx</p>
                <input type="file" name="fichier" id="fichierInput"
                       accept=".xlsx,.xls" required style="display:none;"
                       onchange="this.form.submit()">
            </div>
        </form>
        <p class="import-help">
            Colonnes attendues : Numéro de police, Nom de l'assuré, Statut
            (en_regle ou suspendu). Le ré-import met à jour les numéros
            déjà connus sans supprimer les autres.
        </p>
    </div>
    <?php endif; ?>

    <!-- Gestion des sous-comptes (portail principal uniquement) -->
    <?php if (!$sousCompte): ?>
    <h6 class="section-title">
        <i class="fas fa-users-gear text-primary"></i>
        Sous-comptes
    </h6>

    <?php if (!empty($sousComptes)): ?>
    <div class="sous-comptes-list">
        <?php foreach ($sousComptes as $sc):
            $scUrl = BASE_URL . 'assurance/portal/' . $sc['token_acces'];
        ?>
        <div class="sous-compte-card <?= !$sc['is_active'] ? 'sc-inactive' : '' ?>">
            <div class="sc-top">
                <div class="sc-nom"><?= htmlspecialchars($sc['nom']) ?></div>
                <?php if ($sc['is_active']): ?>
                    <span class="status-tag status-validee">Actif</span>
                <?php else: ?>
                    <span class="status-tag status-rejetee">Inactif</span>
                <?php endif; ?>
            </div>
            <div class="sc-permissions">
                <?php if ($sc['peut_importer']): ?><span class="perm-tag">Import</span><?php endif; ?>
                <?php if ($sc['peut_valider']): ?><span class="perm-tag">Validation</span><?php endif; ?>
                <?php if ($sc['peut_voir_creances']): ?><span class="perm-tag">Créances</span><?php endif; ?>
            </div>
            <div class="sc-link-box">
                <input type="text" readonly class="sc-link-input" value="<?= htmlspecialchars($scUrl) ?>"
                       onclick="this.select()">
            </div>
            <div class="sc-actions">
                <form method="POST" action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/sous-comptes/<?= $sc['id'] ?>/toggle">
                    <button type="submit" class="btn-sc-action">
                        <i class="fas <?= $sc['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                        <?= $sc['is_active'] ? 'Désactiver' : 'Activer' ?>
                    </button>
                </form>
                <form method="POST" action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/sous-comptes/<?= $sc['id'] ?>/delete"
                      onsubmit="return confirm('Supprimer ce sous-compte ?')">
                    <button type="submit" class="btn-sc-action btn-sc-danger">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <p>Aucun sous-compte créé pour le moment.</p>
    </div>
    <?php endif; ?>

    <div class="creer-sc-card">
        <p class="creer-sc-title">Créer un nouveau sous-compte</p>
        <form method="POST" action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/sous-comptes/store">
            <input type="text" name="nom" class="code-input" style="width:100%;margin-bottom:10px;"
                   placeholder="Nom du sous-compte (ex: Agence Bonanjo)" required>
            <div class="sc-perm-checks">
                <label><input type="checkbox" name="peut_importer"> Peut importer le fichier d'assurés</label>
                <label><input type="checkbox" name="peut_valider" checked> Peut valider/rejeter les demandes</label>
                <label><input type="checkbox" name="peut_voir_creances"> Peut voir les créances</label>
            </div>
            <button type="submit" class="btn-valider" style="margin-top:10px;">
                <i class="fas fa-plus"></i> Créer le sous-compte
            </button>
        </form>
    </div>
    <?php endif; ?>

    <p class="footer-note">
        Laboratoire Drouot — Douala · Lien personnel, ne pas partager
    </p>

</div>
</body>
</html>