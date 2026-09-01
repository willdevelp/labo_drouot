<?php
/**
 * Gestion des paramètres d'examen — Moteur dynamique
 */
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<?php if ($created): ?>
<div class="alert-drouot alert-success mb-3">
    <i class="fas fa-circle-check"></i>
    Examen créé ! Configurez maintenant ses paramètres d'analyse.
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert-drouot alert-success mb-3">
    <i class="fas fa-circle-check"></i>
    Paramètre sauvegardé avec succès !
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert-drouot alert-danger mb-3">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Paramètres —
            <span style="color:#1E88E5;">
                <?= htmlspecialchars($examen['nom']) ?>
            </span>
        </h1>
        <p class="page-sub">
            Code : <?= htmlspecialchars($examen['code'] ?? '—') ?>
            · <?= htmlspecialchars($examen['categorie']) ?>
            · <?= number_format((float)$examen['prix'], 0, ',', ' ') ?> FCFA
            · <?= count($parametres) ?> paramètre(s) configuré(s)
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>/edit"
           class="btn-outline-drouot">
            <i class="fas fa-pen"></i> Modifier l'examen
        </a>
        <a href="<?= BASE_URL ?>admin/examens"
           class="btn-outline-drouot">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- Colonne gauche — Paramètres existants -->
    <div class="col-xl-7">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-sliders text-primary me-2"></i>
                Paramètres configurés
                <span class="count-badge">
                    <?= count($parametres) ?>
                </span>
            </h6>

            <?php if (empty($parametres)): ?>
            <div class="empty-params">
                <i class="fas fa-sliders fa-2x mb-2"
                   style="color:#dde3f0;"></i>
                <p>Aucun paramètre configuré.</p>
                <p style="font-size:.78rem;">
                    Ajoutez les paramètres à analyser
                    (ex: Hémoglobine, Glycémie...) avec
                    leurs valeurs de référence.
                </p>
            </div>
            <?php else: ?>

            <div class="params-table">
                <div class="params-header">
                    <span>Libellé</span>
                    <span>Type</span>
                    <span>Unité</span>
                    <span>Normes</span>
                    <span>Ordre</span>
                    <span>Actions</span>
                </div>

                <?php foreach ($parametres as $p): ?>
                <div class="param-row-item"
                     id="paramRow_<?= $p['id'] ?>">

                    <!-- Vue normale -->
                    <div class="param-view"
                         id="view_<?= $p['id'] ?>">
                        <span class="param-libelle-text">
                            <?= htmlspecialchars($p['libelle']) ?>
                            <?php if ($p['code']): ?>
                            <small class="param-code">
                                <?= htmlspecialchars($p['code']) ?>
                            </small>
                            <?php endif; ?>
                        </span>
                        <span>
                            <span class="type-badge
                                type-<?= $p['type'] ?>">
                                <?= $p['type'] ?>
                            </span>
                        </span>
                        <span style="font-size:.8rem;color:#6b7a99;">
                            <?= htmlspecialchars($p['unite'] ?? '—') ?>
                        </span>
                        <span style="font-size:.78rem;">
                            <?php if ($p['valeur_min'] !== null
                                   || $p['valeur_max'] !== null): ?>
                            <span class="norme-chip">
                                <?= $p['valeur_min'] ?? '?' ?>
                                –
                                <?= $p['valeur_max'] ?? '?' ?>
                            </span>
                            <?php elseif ($p['valeurs_possibles']): ?>
                            <span class="norme-chip">
                                <?php
                                $opts = json_decode(
                                    $p['valeurs_possibles'], true
                                ) ?? [];
                                echo htmlspecialchars(
                                    implode(', ', array_slice($opts,0,3))
                                );
                                if (count($opts) > 3) echo '...';
                                ?>
                            </span>
                            <?php else: ?>
                            <span style="color:#b0b8cc;">—</span>
                            <?php endif; ?>
                        </span>
                        <span style="font-size:.8rem;
                            color:#6b7a99;text-align:center;">
                            <?= $p['ordre'] ?>
                        </span>
                        <span>
                            <div class="d-flex gap-1">
                                <button
                                    onclick="editParam(<?= $p['id'] ?>)"
                                    class="action-btn action-btn-edit"
                                    title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST"
                                    action="<?= BASE_URL ?>admin/parametres/<?= $p['id'] ?>/delete"
                                    data-offline-store="parametres"
                                    data-offline-delete-id="<?= $p['id'] ?>"
                                    data-redirect="<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>/parametres"
                                    style="display:inline;">
                                    
                                    <input type="hidden" name="examen_id" value="<?= $examen['id'] ?>">
                                    
                                    <button type="submit"
                                            class="action-btn action-btn-danger"
                                            title="Supprimer"
                                            onclick="return confirm('Supprimer ce paramètre ?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </span>
                    </div>

                    <!-- Formulaire édition inline -->
                    <div class="param-edit-form"
                         id="edit_<?= $p['id'] ?>"
                         style="display:none;">
                        <form method="POST"
                            action="<?= BASE_URL ?>admin/parametres/<?= $p['id'] ?>/edit"
                            data-offline-store="parametres"
                            data-entity-id="<?= $p['id'] ?>"
                            data-redirect="<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>/parametres">

                            <input type="hidden" name="examen_id" value="<?= $examen['id'] ?>">

                            <div class="edit-inline-grid">
                                <div>
                                    <label class="form-label-drouot">
                                        Libellé *
                                    </label>
                                    <input type="text"
                                        name="libelle"
                                        class="form-input-drouot"
                                        value="<?= htmlspecialchars($p['libelle']) ?>"
                                        required>
                                </div>
                                <div>
                                    <label class="form-label-drouot">
                                        Type
                                    </label>
                                    <select name="type"
                                            class="form-input-drouot"
                                            onchange="toggleTypeFields(this, <?= $p['id'] ?>)">
                                        <option value="numeric" <?= $p['type']==='numeric' ? 'selected':'' ?>>
                                            Numérique
                                        </option>
                                        <option value="text" <?= $p['type']==='text' ? 'selected':'' ?>>
                                            Texte
                                        </option>
                                        <option value="select" <?= $p['type']==='select' ? 'selected':'' ?>>
                                            Liste
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label-drouot">
                                        Unité
                                    </label>
                                    <input type="text"
                                        name="unite"
                                        class="form-input-drouot"
                                        value="<?= htmlspecialchars($p['unite'] ?? '') ?>"
                                        placeholder="g/L, mmol/L...">
                                </div>
                                <div>
                                    <label class="form-label-drouot">
                                        Ordre
                                    </label>
                                    <input type="number"
                                        name="ordre"
                                        class="form-input-drouot"
                                        value="<?= $p['ordre'] ?>"
                                        min="1">
                                </div>
                                <div id="editMin_<?= $p['id'] ?>"
                                    class="<?= $p['type']==='select' ? 'hidden' : '' ?>">
                                    <label class="form-label-drouot">
                                        Min
                                    </label>
                                    <input type="number"
                                        name="valeur_min"
                                        step="0.01"
                                        class="form-input-drouot"
                                        value="<?= $p['valeur_min'] ?? '' ?>"
                                        placeholder="Normal min">
                                </div>
                                <div id="editMax_<?= $p['id'] ?>"
                                    class="<?= $p['type']==='select' ? 'hidden' : '' ?>">
                                    <label class="form-label-drouot">
                                        Max
                                    </label>
                                    <input type="number"
                                        name="valeur_max"
                                        step="0.01"
                                        class="form-input-drouot"
                                        value="<?= $p['valeur_max'] ?? '' ?>"
                                        placeholder="Normal max">
                                </div>
                                <div id="editOpts_<?= $p['id'] ?>"
                                    class="col-span-2 <?= $p['type']!=='select' ? 'hidden' : '' ?>">
                                    <label class="form-label-drouot">
                                        Options (une par ligne)
                                    </label>
                                    <textarea name="valeurs_possibles"
                                            class="form-input-drouot"
                                            rows="3"><?php
                                    if (!empty($p['valeurs_possibles'])) {
                                        $opts = is_array($p['valeurs_possibles']) 
                                            ? $p['valeurs_possibles'] 
                                            : json_decode($p['valeurs_possibles'], true) ?? [];
                                        echo htmlspecialchars(implode("\n", $opts));
                                    }
                                    ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-2">
                                <button type="submit"
                                        class="btn-success-drouot"
                                        style="padding:6px 14px; font-size:.82rem;">
                                    <i class="fas fa-floppy-disk"></i>
                                    Sauvegarder
                                </button>
                                <button type="button"
                                        onclick="cancelEdit(<?= $p['id'] ?>)"
                                        class="btn-outline-drouot"
                                        style="padding:6px 14px; font-size:.82rem;">
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne droite — Ajouter paramètre -->
    <div class="col-xl-5">
        <div class="card-drouot sticky-form">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-plus text-primary me-2"></i>
                Ajouter un paramètre
            </h6>

            <form method="POST"
                  action="<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>/parametres/store"
                  id="formParam"
                  data-offline-store="parametres"
                  data-redirect="<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>/parametres">

                <div class="row g-2">

                    <div class="col-12">
                        <label class="form-label-drouot">
                            Libellé <span class="required">*</span>
                        </label>
                        <input type="text" name="libelle"
                               class="form-input-drouot"
                               placeholder="Ex: Hémoglobine, Glycémie..."
                               required>
                    </div>

                    <div class="col-6">
                        <label class="form-label-drouot">
                            Code (optionnel)
                        </label>
                        <input type="text" name="code"
                               class="form-input-drouot"
                               placeholder="Ex: HGB, GLU">
                    </div>

                    <div class="col-6">
                        <label class="form-label-drouot">
                            Type de valeur
                        </label>
                        <select name="type"
                                class="form-input-drouot"
                                id="typeSelect"
                                onchange="toggleNewTypeFields()">
                            <option value="numeric">
                                🔢 Numérique
                            </option>
                            <option value="text">
                                📝 Texte libre
                            </option>
                            <option value="select">
                                📋 Liste choix
                            </option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label-drouot">
                            Unité de mesure
                        </label>
                        <div class="unite-suggestions">
                            <?php
                            $uniteSugg = [
                                'g/L','mg/L','mmol/L','g/dL',
                                'µg/L','%','mm/h','UI/L',
                                '10³/µL','10⁶/µL'
                            ];
                            foreach ($uniteSugg as $u):
                            ?>
                            <span class="unite-btn"
                                  onclick="setUnite('<?= $u ?>')">
                                <?= $u ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" name="unite"
                               class="form-input-drouot mt-1"
                               id="uniteInput"
                               placeholder="Ex: g/L, mmol/L, %...">
                    </div>

                    <!-- Normes numériques -->
                    <div id="numericFields">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label-drouot">
                                    Valeur min normale
                                </label>
                                <input type="number"
                                       name="valeur_min"
                                       step="0.001"
                                       class="form-input-drouot"
                                       placeholder="Ex: 12">
                            </div>
                            <div class="col-6">
                                <label class="form-label-drouot">
                                    Valeur max normale
                                </label>
                                <input type="number"
                                       name="valeur_max"
                                       step="0.001"
                                       class="form-input-drouot"
                                       placeholder="Ex: 16">
                            </div>
                        </div>
                    </div>

                    <!-- Options pour select -->
                    <div id="selectFields" class="col-12 hidden">
                        <label class="form-label-drouot">
                            Options possibles
                            <small>(une par ligne)</small>
                        </label>
                        <textarea name="valeurs_possibles"
                                  class="form-input-drouot"
                                  rows="4"
                                  placeholder="Positif&#10;Négatif&#10;Douteux">
                        </textarea>
                    </div>

                    <div class="col-6">
                        <label class="form-label-drouot">
                            Ordre d'affichage
                        </label>
                        <input type="number" name="ordre"
                               class="form-input-drouot"
                               value="<?= count($parametres) + 1 ?>"
                               min="1">
                    </div>

                </div>

                <button type="submit"
                        class="btn-primary-drouot w-100
                               justify-content-center mt-3"
                        style="padding:.8rem;">
                    <i class="fas fa-plus"></i>
                    Ajouter ce paramètre
                </button>

            </form>

            <!-- Paramètres types courants -->
            <div class="mt-4">
                <p class="form-label-drouot mb-2">
                    Paramètres courants :
                </p>
                <div class="quick-params">
                    <?php
                    $quickParams = [
                        ['Hémoglobine','HGB','numeric','g/dL',11.5,17.5],
                        ['Glycémie','GLU','numeric','g/L',0.7,1.1],
                        ['Cholestérol','CHOL','numeric','g/L',null,2.0],
                        ['Créatinine','CREA','numeric','µmol/L',62,115],
                        ['Leucocytes','WBC','numeric','10³/µL',4.0,10.0],
                        ['Plaquettes','PLT','numeric','10³/µL',150,400],
                        ['TSH','TSH','numeric','µUI/mL',0.4,4.0],
                        ['Résultat','RES','select',null,null,null],
                    ];
                    foreach ($quickParams as [$lib,$code,$type,$unit,$min,$max]):
                    ?>
                    <button type="button"
                            class="quick-param-btn"
                            onclick="fillQuickParam(
                                '<?= addslashes($lib) ?>',
                                '<?= $code ?>',
                                '<?= $type ?>',
                                '<?= $unit ?>',
                                '<?= $min ?>',
                                '<?= $max ?>'
                            )">
                        <?= $lib ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; display:flex; align-items:center; gap:8px; }
.count-badge { background:#E3F2FD; color:#1565C0; padding:2px 8px; border-radius:20px; font-size:.72rem; font-weight:600; }
.form-label-drouot { display:block; font-size:.78rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
.required { color:#C62828; }
.form-input-drouot { width:100%; padding:.65rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; transition:all .2s; resize:vertical; }
.form-input-drouot:focus { border-color:#1E88E5; background:#fff; box-shadow:0 0 0 3px rgba(30,136,229,.08); }
.hidden { display:none !important; }
.col-span-2 { grid-column:span 2; }

/* Unités */
.unite-suggestions { display:flex; flex-wrap:wrap; gap:4px; margin-bottom:4px; }
.unite-btn { background:#F4F6F9; color:#1a2340; border:1px solid #dde3f0; border-radius:6px; padding:2px 8px; font-size:.72rem; cursor:pointer; transition:all .15s; font-family:'DM Sans',sans-serif; }
.unite-btn:hover { background:#1E88E5; color:#fff; border-color:#1E88E5; }

/* Table params */
.params-table { display:flex; flex-direction:column; gap:0; }
.params-header { display:grid; grid-template-columns:2fr 1fr 1fr 1.5fr .5fr 1fr; gap:8px; padding:6px 10px; background:#fafbfc; border-radius:8px; margin-bottom:4px; font-size:.72rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; }
.param-row-item { border-bottom:1px solid #f0f2f8; }
.param-row-item:last-child { border-bottom:none; }
.param-view { display:grid; grid-template-columns:2fr 1fr 1fr 1.5fr .5fr 1fr; gap:8px; align-items:center; padding:10px; font-size:.83rem; }
.param-view:hover { background:#fafbff; }
.param-libelle-text { font-weight:500; color:#1a2340; }
.param-code { font-family:monospace; font-size:.7rem; color:#90CAF9; background:#E3F2FD; padding:1px 5px; border-radius:4px; margin-left:4px; }

/* Type badges */
.type-badge { padding:2px 8px; border-radius:6px; font-size:.7rem; font-weight:600; }
.type-numeric { background:#E8F5E9; color:#2E7D32; }
.type-text    { background:#E3F2FD; color:#1565C0; }
.type-select  { background:#FFF8E1; color:#F57F17; }

.norme-chip { background:#E3F2FD; color:#1565C0; padding:2px 6px; border-radius:4px; font-size:.7rem; }

/* Edit inline */
.edit-inline-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.param-edit-form { padding:12px; background:#f8fafc; border-radius:8px; margin:4px 0 8px; border:1px solid #E3F2FD; }

/* Empty */
.empty-params { text-align:center; padding:2rem; color:#6b7a99; }

/* Quick params */
.quick-params { display:flex; flex-wrap:wrap; gap:5px; }
.quick-param-btn { background:#F4F6F9; color:#1a2340; border:1px solid #dde3f0; border-radius:20px; padding:3px 10px; font-size:.72rem; cursor:pointer; transition:all .15s; font-family:'DM Sans',sans-serif; }
.quick-param-btn:hover { background:#0D47A1; color:#fff; border-color:#0D47A1; }

.sticky-form { position:sticky; top:80px; }
.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; font-size:.78rem; transition:all .2s; border:none; cursor:pointer; }
.action-btn-edit  { background:#FFF8E1; color:#F57F17; }
.action-btn-edit:hover { background:#F9A825; color:#fff; }
.action-btn-danger { background:#FFEBEE; color:#C62828; }
.action-btn-danger:hover { background:#C62828; color:#fff; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
.w-100 { width:100%; } .justify-content-center { justify-content:center; }
</style>

<script>
// Toggle type fields — nouveau param
function toggleNewTypeFields() {
    const type = document.getElementById('typeSelect').value;
    const num  = document.getElementById('numericFields');
    const sel  = document.getElementById('selectFields');
    num.style.display = type === 'select' ? 'none' : 'block';
    sel.style.display = type === 'select' ? 'block' : 'none';
}

// Toggle type fields — édition inline
function toggleTypeFields(select, pid) {
    const type = select.value;
    document.getElementById('editMin_'+pid).classList
        .toggle('hidden', type === 'select');
    document.getElementById('editMax_'+pid).classList
        .toggle('hidden', type === 'select');
    document.getElementById('editOpts_'+pid).classList
        .toggle('hidden', type !== 'select');
}

// Edit inline
function editParam(pid) {
    document.getElementById('view_'+pid).style.display='none';
    document.getElementById('edit_'+pid).style.display='block';
}
function cancelEdit(pid) {
    document.getElementById('view_'+pid).style.display='';
    document.getElementById('edit_'+pid).style.display='none';
}

// Unité suggérée
function setUnite(u) {
    document.getElementById('uniteInput').value = u;
}

// Quick param
function fillQuickParam(lib, code, type, unit, min, max) {
    document.querySelector('[name="libelle"]').value = lib;
    document.querySelector('[name="code"]').value    = code;
    const sel = document.getElementById('typeSelect');
    sel.value = type;
    toggleNewTypeFields();
    document.getElementById('uniteInput').value = unit || '';
    if (type === 'numeric') {
        document.querySelector('[name="valeur_min"]').value
            = min || '';
        document.querySelector('[name="valeur_max"]').value
            = max || '';
    }
    document.querySelector('[name="libelle"]').focus();
}

// ── Hors ligne : création d'un paramètre ─────────────────
OfflineOutbox.bindForm(document.getElementById('formParam'), {
    type: 'examen',
    label: (form) => 'Nouveau paramètre — ' +
        (form.querySelector('[name="libelle"]').value.trim() || 'sans nom'),
    redirect: '<?= BASE_URL ?>admin/examens/<?= $examen['id'] ?>/parametres',
});
</script>

<div id="pendingParametresBox" style="display:none;"></div>
<script>
OfflineOutbox.renderPendingWidget('pendingParametresBox', ['examen']);
</script>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>