<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            <i class="fas fa-plus text-primary me-2"></i>
            Nouvel examen
        </h1>
        <p class="page-sub">
            Créer un examen médical avec ses informations de base
        </p>
    </div>
    <a href="<?= BASE_URL ?>admin/examens"
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

<div class="row g-4">
    <div class="col-xl-8">
        <form method="POST"
              action="<?= BASE_URL ?>admin/examens/store"
              id="formExamen"
              data-offline-store="examens"
              data-redirect="<?= BASE_URL ?>admin/examens"
        >
            <div class="card-drouot mb-4">
                <h6 class="form-section-title">
                    <i class="fas fa-flask"></i>
                    Informations de l'examen
                </h6>
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label-drouot">
                            Code examen
                        </label>
                        <input type="text" name="code"
                               class="form-input-drouot"
                               placeholder="Ex: NFS, GLUC..."
                               value="<?= htmlspecialchars(
                                   $old['code'] ?? ''
                               ) ?>"
                               style="text-transform:uppercase;">
                        <div style="font-size:.7rem;color:#6b7a99;
                            margin-top:3px;">
                            Auto-généré si vide
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label-drouot">
                            Nom de l'examen
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="nom"
                               class="form-input-drouot"
                               placeholder="Ex: Numération Formule Sanguine"
                               value="<?= htmlspecialchars(
                                   $old['nom'] ?? ''
                               ) ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-drouot">
                            Catégorie
                            <span class="required">*</span>
                        </label>
                        <input type="text" name="categorie"
                               class="form-input-drouot"
                               id="catInput"
                               placeholder="Ex: Hématologie, Biochimie..."
                               value="<?= htmlspecialchars(
                                   $old['categorie'] ?? ''
                               ) ?>"
                               autocomplete="off"
                               required>
                        <!-- Suggestions catégories -->
                        <?php if (!empty($categories)): ?>
                        <div class="cat-suggestions">
                            <?php foreach ($categories as $c): ?>
                            <span class="cat-suggestion-btn"
                                  onclick="setCategorie(
                                      '<?= addslashes($c) ?>'
                                  )">
                                <?= htmlspecialchars($c) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-drouot">
                            Prix (FCFA)
                            <span class="required">*</span>
                        </label>
                        <div class="prix-input-wrap">
                            <input type="number"
                                   name="prix"
                                   class="form-input-drouot"
                                   placeholder="Ex: 5000"
                                   min="0" step="100"
                                   value="<?= htmlspecialchars(
                                       $old['prix'] ?? ''
                                   ) ?>"
                                   required>
                            <span class="prix-currency">FCFA</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label-drouot">
                            Description
                        </label>
                        <textarea name="description"
                                  class="form-input-drouot"
                                  rows="3"
                                  placeholder="Description de l'examen, indication clinique..."><?= htmlspecialchars(
                            $old['description'] ?? ''
                        ) ?></textarea>
                    </div>

                </div>
            </div>

            <div class="info-box-next">
                <i class="fas fa-circle-info"
                   style="color:#1E88E5;font-size:1.2rem;"></i>
                <div>
                    <strong>Étape suivante</strong><br>
                    <span style="font-size:.82rem;color:#6b7a99;">
                        Après création, vous serez redirigé vers
                        la configuration des paramètres d'analyse
                        (normes, unités, valeurs...).
                    </span>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>admin/examens"
                   class="btn-outline-drouot">
                    <i class="fas fa-xmark"></i> Annuler
                </a>
                <button type="submit"
                        class="btn-primary-drouot"
                        style="padding:.7rem 1.5rem;">
                    <i class="fas fa-flask"></i>
                    Créer et configurer les paramètres →
                </button>
            </div>
        </form>
    </div>

    <div class="col-xl-4">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-lightbulb text-warning me-2"></i>
                Exemples d'examens
            </h6>
            <div class="exemple-list">
                <?php
                $exemples = [
                    ['NFS',   'Numération Formule Sanguine', 'Hématologie',  5000],
                    ['GLUC',  'Glycémie à jeun',             'Biochimie',    2500],
                    ['CHOL',  'Cholestérol total',           'Biochimie',    3000],
                    ['TSH',   'TSH ultrasensible',           'Hormonologie', 8000],
                    ['VIHAB', 'Sérologie VIH Ag/Ac',        'Sérologie',    5000],
                    ['UREE',  'Urée sanguine',               'Biochimie',    2000],
                ];
                foreach ($exemples as [$code, $nom, $cat, $prix]):
                ?>
                <div class="exemple-item"
                     onclick="fillExemple(
                         '<?= $code ?>',
                         '<?= addslashes($nom) ?>',
                         '<?= addslashes($cat) ?>',
                         '<?= $prix ?>'
                     )">
                    <div>
                        <span class="ex-code"><?= $code ?></span>
                        <span class="ex-nom"><?= $nom ?></span>
                    </div>
                    <span class="ex-cat"><?= $cat ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; }
.form-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin-bottom:1rem; display:flex; align-items:center; gap:8px; padding-bottom:8px; border-bottom:1.5px solid #f0f2f8; }
.form-section-title i { color:#1E88E5; }
.form-label-drouot { display:block; font-size:.78rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
.required { color:#C62828; }
.form-input-drouot { width:100%; padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; transition:all .2s; resize:vertical; }
.form-input-drouot:focus { border-color:#1E88E5; background:#fff; box-shadow:0 0 0 3px rgba(30,136,229,.08); }
.prix-input-wrap { position:relative; }
.prix-input-wrap .form-input-drouot { padding-right:60px; }
.prix-currency { position:absolute; right:.75rem; top:50%; transform:translateY(-50%); font-size:.8rem; font-weight:600; color:#6b7a99; }

/* Suggestions catégories */
.cat-suggestions { display:flex; flex-wrap:wrap; gap:5px; margin-top:6px; }
.cat-suggestion-btn { background:#E3F2FD; color:#1565C0; border:none; border-radius:20px; padding:3px 10px; font-size:.72rem; cursor:pointer; transition:all .15s; font-family:'DM Sans',sans-serif; }
.cat-suggestion-btn:hover { background:#1E88E5; color:#fff; }

.info-box-next { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; background:#f0f7ff; border-radius:12px; margin-bottom:1.5rem; }

/* Exemples */
.exemple-list { display:flex; flex-direction:column; gap:0; }
.exemple-item { padding:8px 10px; border-radius:8px; cursor:pointer; transition:background .15s; border-bottom:1px solid #f0f2f8; display:flex; align-items:center; justify-content:space-between; gap:8px; }
.exemple-item:last-child { border-bottom:none; }
.exemple-item:hover { background:#f0f7ff; }
.ex-code { font-family:monospace; font-size:.72rem; color:#90CAF9; margin-right:6px; }
.ex-nom  { font-size:.82rem; color:#1a2340; font-weight:500; }
.ex-cat  { font-size:.72rem; color:#6b7a99; background:#F4F6F9; padding:2px 7px; border-radius:6px; white-space:nowrap; }

.form-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:1.5rem; padding-top:1.5rem; border-top:1.5px solid #f0f2f8; }
.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-danger { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<script src="/js/LocalDB.js"></script>

<script>
function setCategorie(val) {
    document.getElementById('catInput').value = val;
}

function fillExemple(code, nom, cat, prix) {
    document.querySelector('[name="code"]').value = code;
    document.querySelector('[name="nom"]').value  = nom;
    document.getElementById('catInput').value     = cat;
    document.querySelector('[name="prix"]').value = prix;
}

// ── Offline-first : hors ligne → IndexedDB (données utilisables tout de suite)
const formExamen = document.getElementById('formExamen');

formExamen.addEventListener('submit', async function (e) {
    // En ligne → POST PHP classique
    if (navigator.onLine) {
        return;
    }

    e.preventDefault();
    e.stopImmediatePropagation();

    const nom = formExamen.querySelector('[name="nom"]').value.trim();
    const categorie = (document.getElementById('catInput') || {}).value?.trim()
        || formExamen.querySelector('[name="categorie"]')?.value?.trim()
        || '';
    const prix = parseFloat(formExamen.querySelector('[name="prix"]').value) || 0;

    if (!nom || !categorie || prix <= 0) {
        alert('Nom, catégorie et prix sont obligatoires.');
        return;
    }

    const examenData = {
        code: formExamen.querySelector('[name="code"]').value.trim() || '',
        nom,
        categorie,
        prix,
        description: formExamen.querySelector('[name="description"]')?.value?.trim() || '',
        is_active: 1,
        nb_parametres: 0,
        nb_realises: 0
    };

    const fd = new FormData(formExamen);
    const fields = [];
    fd.forEach((v, k) => fields.push([k, v]));
    const setField = (k, v) => {
        const i = fields.findIndex(([key]) => key === k);
        if (i >= 0) fields[i] = [k, String(v)];
        else fields.push([k, String(v)]);
    };
    Object.entries(examenData).forEach(([k, v]) => setField(k, v));

    try {
        await LocalDB.create(
            'examens',
            examenData,
            formExamen.action || '<?= BASE_URL ?>admin/examens/store',
            {
                type: 'examens',
                fields,
                label: 'Nouvel examen — ' + examenData.nom
            }
        );

        LocalDB.toast(
            'Examen enregistré sur cet appareil. Tu peux continuer à travailler hors ligne.',
            'success'
        );

        // Retour liste : affichage immédiat depuis IndexedDB
        window.location.href = '<?= BASE_URL ?>admin/examens';
    } catch (err) {
        console.error(err);
        alert('Erreur enregistrement local : ' + (err.message || err));
    }
});
</script>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>