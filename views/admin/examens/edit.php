<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Modifier — <?= htmlspecialchars($examen['nom']) ?>
        </h1>
        <p class="page-sub">Modifier les informations de l'examen</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>/parametres"
           class="btn-primary-drouot">
            <i class="fas fa-sliders"></i> Paramètres
        </a>
        <a href="<?= BASE_URL ?>admin/examens"
           class="btn-outline-drouot">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
<div class="alert-drouot alert-danger mb-4">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="col-xl-7">
    <form method="POST"
          action="<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>/edit"
          data-offline-store="examens"
          data-entity-id="<?= $examen['id'] ?>"
          data-redirect="<?= BASE_URL ?>admin/examens">

        <div class="card-drouot mb-4">
            <h6 class="form-section-title">
                <i class="fas fa-flask"></i>
                Informations de l'examen
            </h6>
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label-drouot">Code</label>
                    <input type="text"
                           class="form-input-drouot"
                           value="<?= htmlspecialchars(
                               $examen['code'] ?? ''
                           ) ?>"
                           disabled
                           style="background:#f4f6f9;color:#6b7a99;">
                    <div style="font-size:.7rem;color:#b0b8cc;margin-top:3px;">
                        Non modifiable
                    </div>
                </div>

                <div class="col-md-9">
                    <label class="form-label-drouot">
                        Nom <span class="required">*</span>
                    </label>
                    <input type="text" name="nom"
                           class="form-input-drouot"
                           value="<?= htmlspecialchars($examen['nom']) ?>"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label-drouot">
                        Catégorie <span class="required">*</span>
                    </label>
                    <input type="text" name="categorie"
                           class="form-input-drouot"
                           id="catInput"
                           value="<?= htmlspecialchars(
                               $examen['categorie']
                           ) ?>"
                           required>
                    <?php if (!empty($categories)): ?>
                    <div class="cat-suggestions mt-1">
                        <?php foreach ($categories as $c): ?>
                        <span class="cat-suggestion-btn"
                              onclick="document.getElementById('catInput').value='<?= addslashes($c) ?>'">
                            <?= htmlspecialchars($c) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label-drouot">
                        Prix (FCFA) <span class="required">*</span>
                    </label>
                    <div class="prix-input-wrap">
                        <input type="number" name="prix"
                               class="form-input-drouot"
                               value="<?= $examen['prix'] ?>"
                               min="0" step="100" required>
                        <span class="prix-currency">FCFA</span>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label-drouot">
                        Description
                    </label>
                    <textarea name="description"
                              class="form-input-drouot"
                              rows="3"><?= htmlspecialchars(
                        $examen['description'] ?? ''
                    ) ?></textarea>
                </div>

            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>"
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
.form-input-drouot { width:100%; padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; transition:all .2s; resize:vertical; }
.form-input-drouot:focus { border-color:#1E88E5; background:#fff; box-shadow:0 0 0 3px rgba(30,136,229,.08); }
.prix-input-wrap { position:relative; }
.prix-input-wrap .form-input-drouot { padding-right:60px; }
.prix-currency { position:absolute; right:.75rem; top:50%; transform:translateY(-50%); font-size:.8rem; font-weight:600; color:#6b7a99; }
.cat-suggestions { display:flex; flex-wrap:wrap; gap:5px; }
.cat-suggestion-btn { background:#E3F2FD; color:#1565C0; border:none; border-radius:20px; padding:3px 10px; font-size:.72rem; cursor:pointer; transition:all .15s; font-family:'DM Sans',sans-serif; }
.cat-suggestion-btn:hover { background:#1E88E5; color:#fff; }
.form-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:1.5rem; padding-top:1.5rem; border-top:1.5px solid #f0f2f8; }
.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-danger { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>