<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            <i class="fas fa-plus-circle text-primary me-2"></i>
            Nouveau réactif / stock
        </h1>
        <p class="page-sub">Ajouter un article au stock du laboratoire</p>
    </div>
    <a href="<?= BASE_URL ?>admin/stocks" class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert-drouot alert-danger mb-4">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card-drouot">
            <h6 class="form-section-title">
                <i class="fas fa-flask"></i> Informations du réactif
            </h6>

            <form method="POST" action="<?= BASE_URL ?>admin/stocks/store" data-offline-store="stocks" id="formStock">

                <!-- Nom -->
                <div class="form-group mb-3">
                    <label class="form-label-drouot">
                        Nom du réactif <span class="required">*</span>
                    </label>
                    <input type="text" name="name"
                           class="form-input-drouot"
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                           placeholder="Ex: Réactif Glucose, Tubes EDTA..."
                           required>
                </div>

                <!-- Code -->
                <div class="form-group mb-3">
                    <label class="form-label-drouot">Code article</label>
                    <input type="text" name="code"
                           class="form-input-drouot"
                           value="<?= htmlspecialchars($old['code'] ?? '') ?>"
                           placeholder="Ex: REA-GLU-001">
                </div>

                <!-- Catégorie -->
                <div class="form-group mb-3">
                    <label class="form-label-drouot">Catégorie</label>
                    <select name="categorie" class="form-input-drouot">
                        <option value="">-- Sélectionner --</option>
                        <?php
                        $categories = [
                            'Réactifs chimiques',
                            'Consommables',
                            'Tubes & prélèvement',
                            'Équipements',
                            'Contrôles qualité',
                            'Autres',
                        ];
                        foreach ($categories as $cat):
                        ?>
                        <option value="<?= $cat ?>"
                            <?= ($old['categorie'] ?? '') === $cat
                                ? 'selected' : '' ?>>
                            <?= $cat ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <!-- Quantité -->
                    <div class="col-md-4">
                        <label class="form-label-drouot">
                            Quantité initiale <span class="required">*</span>
                        </label>
                        <input type="number" name="quantity" min="0"
                               class="form-input-drouot"
                               value="<?= htmlspecialchars($old['quantity'] ?? '0') ?>"
                               required>
                    </div>

                    <!-- Unité -->
                    <div class="col-md-4">
                        <label class="form-label-drouot">Unité</label>
                        <input type="text" name="unite"
                               class="form-input-drouot"
                               value="<?= htmlspecialchars($old['unite'] ?? '') ?>"
                               placeholder="Ex: flacons, boîtes, mL">
                    </div>

                    <!-- Seuil alerte -->
                    <div class="col-md-4">
                        <label class="form-label-drouot">
                            Seuil d'alerte
                            <span class="form-hint" title="Alerte si quantité ≤ ce seuil">
                                <i class="fas fa-info-circle"></i>
                            </span>
                        </label>
                        <input type="number" name="threshold" min="0"
                               class="form-input-drouot"
                               value="<?= htmlspecialchars($old['threshold'] ?? '5') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <!-- Fournisseur -->
                    <div class="col-md-6">
                        <label class="form-label-drouot">Fournisseur</label>
                        <input type="text" name="fournisseur"
                               class="form-input-drouot"
                               value="<?= htmlspecialchars($old['fournisseur'] ?? '') ?>"
                               placeholder="Nom du fournisseur">
                    </div>

                    <!-- Prix unitaire -->
                    <div class="col-md-6">
                        <label class="form-label-drouot">
                            Prix unitaire (FCFA)
                        </label>
                        <input type="number" name="prix_unitaire" min="0"
                               class="form-input-drouot"
                               value="<?= htmlspecialchars($old['prix_unitaire'] ?? '') ?>"
                               placeholder="0">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <!-- Date expiration -->
                    <div class="col-md-6">
                        <label class="form-label-drouot">
                            Date d'expiration
                        </label>
                        <input type="date" name="expiry_date"
                               class="form-input-drouot"
                               value="<?= htmlspecialchars($old['expiry_date'] ?? '') ?>">
                    </div>

                    <!-- Numéro de lot -->
                    <div class="col-md-6">
                        <label class="form-label-drouot">Numéro de lot</label>
                        <input type="text" name="lot_number"
                               class="form-input-drouot"
                               value="<?= htmlspecialchars($old['lot_number'] ?? '') ?>"
                               placeholder="Ex: LOT-2026-001">
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions mt-4">
                    <button type="submit" class="btn-primary-drouot">
                        <i class="fas fa-floppy-disk"></i>
                        Enregistrer le réactif
                    </button>
                    <a href="<?= BASE_URL ?>admin/stocks"
                       class="btn-outline-drouot">
                        <i class="fas fa-xmark"></i> Annuler
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- Aide -->
    <div class="col-xl-5">
        <div class="card-drouot help-card">
            <h6 class="form-section-title">
                <i class="fas fa-circle-info"></i> Guide de saisie
            </h6>
            <div class="help-item">
                <div class="help-icon">📦</div>
                <div>
                    <div class="help-title">Quantité initiale</div>
                    <div class="help-desc">
                        Indiquez le stock actuel disponible en laboratoire.
                    </div>
                </div>
            </div>
            <div class="help-item">
                <div class="help-icon">⚠️</div>
                <div>
                    <div class="help-title">Seuil d'alerte</div>
                    <div class="help-desc">
                        Une alerte sera affichée quand la quantité
                        atteint ou passe sous ce seuil.
                    </div>
                </div>
            </div>
            <div class="help-item">
                <div class="help-icon">📅</div>
                <div>
                    <div class="help-title">Date d'expiration</div>
                    <div class="help-desc">
                        Les réactifs expirant dans moins de 30 jours
                        seront signalés sur le tableau de bord.
                    </div>
                </div>
            </div>
            <div class="help-item">
                <div class="help-icon">🏷️</div>
                <div>
                    <div class="help-title">Numéro de lot</div>
                    <div class="help-desc">
                        Référence fabricant pour la traçabilité
                        des réactifs.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0;
    display:flex; align-items:center; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }

.form-section-title {
    font-size:.88rem; font-weight:600; color:#1a2340;
    margin-bottom:1.2rem;
    display:flex; align-items:center; gap:8px;
    padding-bottom:8px; border-bottom:1.5px solid #f0f2f8;
}
.form-section-title i { color:#1E88E5; }

.form-label-drouot {
    display:block; font-size:.78rem; font-weight:600;
    color:#6b7a99; text-transform:uppercase;
    letter-spacing:.05em; margin-bottom:5px;
}
.form-input-drouot {
    width:100%; padding:.7rem .9rem;
    border:1.5px solid #dde3f0; border-radius:10px;
    font-family:'DM Sans',sans-serif; font-size:.9rem;
    color:#1a2340; background:#fafbfd;
    outline:none; transition:all .2s;
}
.form-input-drouot:focus {
    border-color:#1E88E5; background:#fff;
    box-shadow:0 0 0 3px rgba(30,136,229,.08);
}
select.form-input-drouot { cursor:pointer; }

.required { color:#C62828; }
.form-hint { color:#90CAF9; font-size:.8rem; cursor:help; margin-left:3px; }

.form-actions {
    display:flex; gap:10px; flex-wrap:wrap;
    padding-top:1rem; border-top:1.5px solid #f0f2f8;
}

/* Help card */
.help-card { background:#f8faff; border:1.5px solid #E3F2FD; }
.help-item {
    display:flex; align-items:flex-start; gap:12px;
    padding:10px 0; border-bottom:1px solid #f0f2f8;
}
.help-item:last-child { border-bottom:none; }
.help-icon { font-size:1.3rem; flex-shrink:0; margin-top:1px; }
.help-title { font-size:.85rem; font-weight:600; color:#1a2340; }
.help-desc  { font-size:.78rem; color:#6b7a99; margin-top:2px; }

/* Alerts */
.alert-drouot { padding:.85rem 1rem; border-radius:10px;
    display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-danger { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }

.btn-outline-drouot {
    display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:1.5px solid #dde3f0;
    border-radius:9px; background:#fff; color:#1a2340;
    font-size:.88rem; font-weight:500; text-decoration:none;
    transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer;
}
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<script>
OfflineOutbox.bindForm(document.getElementById('formStock'), {
    type: 'stock',
    label: (form) => 'Nouvel article de stock — ' +
        (form.querySelector('[name="name"]').value.trim() || 'sans nom'),
    redirect: '<?= BASE_URL ?>admin/stocks',
});
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>
