<?php
/**
 * Import et gestion du fichier d'assurés — par compagnie
 * Laboratoire Drouot
 */
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<?php if ($success && $importResult): ?>
<div class="alert-drouot alert-success mb-3">
    <i class="fas fa-circle-check"></i>
    Import terminé : <strong><?= $importResult['inseres'] ?></strong> nouvel(les) assuré(s) ajouté(s),
    <strong><?= $importResult['maj'] ?></strong> mis à jour
    <?php if ($importResult['ignores'] > 0): ?>
    , <strong><?= $importResult['ignores'] ?></strong> ligne(s) ignorée(s) (incomplètes)
    <?php endif; ?>
    .
</div>
<?php endif; ?>

<?php if ($importError): ?>
<div class="alert-drouot alert-danger mb-3">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($importError) ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Assurés —
            <span style="color:#1E88E5;"><?= htmlspecialchars($assurance['nom']) ?></span>
        </h1>
        <p class="page-sub">
            <?= $statsAssures['total'] ?? 0 ?> assuré(s) importé(s) ·
            <?= $statsAssures['en_regle'] ?? 0 ?> en règle ·
            <?= $statsAssures['suspendus'] ?? 0 ?> suspendu(s)
        </p>
    </div>
    <a href="<?= BASE_URL ?>admin/assurances" class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<div class="row g-4">

    <!-- Colonne gauche — Liste assurés -->
    <div class="col-xl-7">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-users text-primary me-2"></i>
                    Assurés importés
                </h6>
            </div>

            <form method="GET" class="search-form mb-3">
                <div class="search-input-wrap">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" name="search" class="search-input"
                           placeholder="Numéro de police ou nom..."
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
                <button type="submit" class="btn-primary-drouot">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <?php if (empty($assures)): ?>
            <div class="empty-params">
                <i class="fas fa-file-excel fa-2x mb-2" style="color:#dde3f0;"></i>
                <p>Aucun assuré importé pour cette compagnie.</p>
                <p style="font-size:.78rem;">
                    Importez le fichier Excel fourni par l'assureur via le formulaire à droite.
                </p>
            </div>
            <?php else: ?>
            <div class="assures-table">
                <div class="assures-header">
                    <span>Numéro de police</span>
                    <span>Nom de l'assuré</span>
                    <span>Statut</span>
                    <span></span>
                </div>
                <?php foreach ($assures as $as): ?>
                <div class="assures-row">
                    <span class="assure-police"><?= htmlspecialchars($as['numero_police']) ?></span>
                    <span class="assure-nom"><?= htmlspecialchars($as['nom_assure']) ?></span>
                    <span>
                        <?php if ($as['statut'] === 'en_regle'): ?>
                            <span class="badge-normal">En règle</span>
                        <?php else: ?>
                            <span class="badge-critical">Suspendu</span>
                        <?php endif; ?>
                    </span>
                    <span>
                        <form method="POST"
                              action="<?= BASE_URL ?>admin/assures/<?= $as['id'] ?>/delete"
                              class="js-offline-form"
                              data-offline-type="assurance"
                              data-offline-label="<?= htmlspecialchars('Retrait assuré — ' . ($as['nom_assure'] ?? ('#' . $as['id']))) ?>"
                              data-offline-redirect="<?= BASE_URL ?>admin/assurances/<?= $assurance['id'] ?>/assures">
                            <input type="hidden" name="assurance_id" value="<?= $assurance['id'] ?>">
                            <button type="submit" class="action-btn action-btn-danger"
                                    title="Retirer"
                                    onclick="return confirm('Retirer cet assuré ?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($assures) >= 300): ?>
            <p style="font-size:.75rem;color:#6b7a99;text-align:center;margin-top:10px;">
                Affichage limité à 300 résultats. Affinez la recherche pour en voir plus.
            </p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne droite — Import -->
    <div class="col-xl-5">
        <div class="card-drouot sticky-form">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title" style="margin:0;">
                    <i class="fas fa-file-excel text-primary me-2"></i>
                    Importer un fichier Excel
                </h6>
                <a href="<?= BASE_URL ?>assets/modele_import_assures.xlsx"
                   class="btn-modele-admin" download>
                    <i class="fas fa-file-arrow-down"></i> Modèle
                </a>
            </div>

            <form method="POST"
                  action="<?= BASE_URL ?>admin/assurances/<?= $assurance['id'] ?>/assures/import"
                  enctype="multipart/form-data">

                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-arrow-up"></i>
                    <p class="upload-text">Cliquez ou déposez le fichier .xlsx ici</p>
                    <input type="file" name="fichier" id="fichierInput"
                           accept=".xlsx,.xls" required
                           onchange="updateFileName(this)">
                    <p class="upload-filename" id="fileName"></p>
                </div>

                <button type="submit" class="btn-primary-drouot w-100 justify-content-center mt-3"
                        style="padding:.8rem;">
                    <i class="fas fa-upload"></i>
                    Importer le fichier
                </button>
            </form>

            <div class="format-help mt-4">
                <p class="format-help-title">
                    <i class="fas fa-circle-info"></i> Format attendu
                </p>
                <p class="format-help-text">
                    Le fichier doit contenir 3 colonnes, dans cet ordre,
                    avec une ligne d'en-tête :
                </p>
                <table class="format-example">
                    <thead>
                        <tr>
                            <th>Numéro de police</th>
                            <th>Nom de l'assuré</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>NSIA-2026-00123</td>
                            <td>KAMGA Jean</td>
                            <td>en_regle</td>
                        </tr>
                        <tr>
                            <td>NSIA-2026-00456</td>
                            <td>TCHOUMI Marie</td>
                            <td>suspendu</td>
                        </tr>
                    </tbody>
                </table>
                <p class="format-help-text" style="margin-top:8px;">
                    Toute valeur de statut différente de "suspendu" sera
                    considérée comme "en règle". Un ré-import met à jour
                    les numéros existants sans supprimer les autres.
                </p>
            </div>
        </div>
    </div>

</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; display:flex; align-items:center; gap:8px; }

.search-form { display:flex; gap:8px; }
.search-input-wrap { position:relative; flex:1; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.65rem .9rem .65rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.85rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }

/* Table assurés */
.assures-table { display:flex; flex-direction:column; }
.assures-header {
    display:grid; grid-template-columns:1.3fr 1.5fr 1fr .4fr; gap:8px;
    padding:6px 10px; background:#fafbfc; border-radius:8px;
    margin-bottom:4px; font-size:.7rem; font-weight:600; color:#6b7a99;
    text-transform:uppercase; letter-spacing:.05em;
}
.assures-row {
    display:grid; grid-template-columns:1.3fr 1.5fr 1fr .4fr; gap:8px;
    align-items:center; padding:9px 10px; border-bottom:1px solid #f0f2f8;
    font-size:.83rem;
}
.assures-row:last-child { border-bottom:none; }
.assures-row:hover { background:#fafbff; }
.assure-police { font-family:monospace; font-size:.78rem; color:#1565C0; }
.assure-nom { color:#1a2340; font-weight:500; }

.action-btn { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:6px; font-size:.74rem; transition:all .2s; border:none; cursor:pointer; }
.action-btn-danger { background:#FFEBEE; color:#C62828; }
.action-btn-danger:hover { background:#C62828; color:#fff; }

/* Upload zone */
.upload-zone {
    border:2px dashed #dde3f0; border-radius:12px;
    padding:30px 20px; text-align:center; cursor:pointer;
    transition:all .2s; position:relative;
}
.upload-zone:hover { border-color:#1E88E5; background:#f0f7ff; }
.upload-zone i { font-size:2rem; color:#90CAF9; margin-bottom:10px; display:block; }
.upload-text { font-size:.85rem; color:#6b7a99; margin:0; }
.upload-zone input[type="file"] {
    position:absolute; inset:0; opacity:0; cursor:pointer;
}
.upload-filename { font-size:.8rem; font-weight:600; color:#1E88E5; margin:8px 0 0; }

/* Format help */
.format-help { background:#fafbfc; border-radius:10px; padding:14px; border:1px solid #f0f2f8; }
.format-help-title { font-size:.8rem; font-weight:600; color:#1a2340; margin:0 0 6px; display:flex; align-items:center; gap:6px; }
.format-help-text { font-size:.76rem; color:#6b7a99; margin:0; line-height:1.5; }
.format-example { width:100%; border-collapse:collapse; margin-top:8px; font-size:.72rem; }
.format-example th {
    background:#E3F2FD; color:#1565C0; padding:5px 8px; text-align:left;
    font-weight:600;
}
.format-example td { padding:5px 8px; border-bottom:1px solid #f0f2f8; color:#1a2340; }

.empty-params { text-align:center; padding:2rem; color:#6b7a99; }
.sticky-form { position:sticky; top:80px; }
.btn-modele-admin {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 12px; border-radius:7px;
    background:#E3F2FD; color:#1565C0; text-decoration:none;
    font-size:.74rem; font-weight:500; transition:all .2s; flex-shrink:0;
}
.btn-modele-admin:hover { background:#1E88E5; color:#fff; }

.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
.w-100 { width:100%; } .justify-content-center { justify-content:center; }
</style>

<script>
function updateFileName(input) {
    const name = input.files[0]?.name || '';
    document.getElementById('fileName').textContent = name;
}
</script>

<?php include dirname(__DIR__) . '/../../views/layouts/footer.php'; ?>