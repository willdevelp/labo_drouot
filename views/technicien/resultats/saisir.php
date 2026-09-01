<?php
/**
 * Saisie des résultats — Moteur dynamique
 * Laboratoire Drouot
 */
include __DIR__ . '/../../../views/layouts/header.php';
?>

<?php if ($success): ?>
<div class="alert-drouot alert-success mb-3">
    <i class="fas fa-circle-check"></i>
    Résultats sauvegardés avec succès !
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert-drouot alert-danger mb-3">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Saisie des résultats
            <span class="ref-badge">
                <?= htmlspecialchars($echantillon['code_barre']) ?>
            </span>
        </h1>
        <p class="page-sub">
            Patient :
            <strong><?= htmlspecialchars($echantillon['patient_nom']) ?></strong>
            —
            <?= $echantillon['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?>
            <?php if ($echantillon['date_naissance']): ?>
            —
            <?= date_diff(
                date_create($echantillon['date_naissance']),
                date_create('today')
            )->y ?> ans
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>technicien/echantillons"
           class="btn-outline-drouot">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<form method="POST"
      action="<?= BASE_URL ?>technicien/resultats/<?= $echantillon['id'] ?>/save"
      id="formResultats"
      data-offline-store="examens"
      data-redirect="<?= BASE_URL ?>technicien/echantillons">

<div class="row g-4">

    <!-- Colonne gauche — Formulaires dynamiques -->
    <div class="col-xl-8">

        <?php if (empty($examens)): ?>
            <div class="card-drouot text-center py-5">
                <i class="fas fa-flask fa-3x mb-3" style="color:#dde3f0;"></i>
                <p class="text-muted">Aucun examen trouvé pour cet échantillon.</p>
            </div>
        <?php else: ?>

        <?php foreach ($examens as $ex): ?>
        <div class="card-drouot mb-4 examen-block">

            <!-- En-tête examen -->
            <div class="examen-header">
                <div>
                    <div class="examen-nom">
                        <?= htmlspecialchars($ex['nom']) ?>
                    </div>
                    <div class="examen-meta">
                        <span class="examen-code">
                            <?= htmlspecialchars($ex['code'] ?? '') ?>
                        </span>
                        <span class="examen-cat">
                            <?= htmlspecialchars($ex['categorie'] ?? '') ?>
                        </span>
                    </div>
                </div>
                <div class="examen-progress" id="prog_<?= $ex['id'] ?>">
                    <span class="prog-text">0/<?= count($parametres[$ex['id']] ?? []) ?></span>
                </div>
            </div>

            <!-- Paramètres dynamiques -->
            <?php
            $params = $parametres[$ex['id']] ?? [];
            if (empty($params)):
            ?>
                <div class="no-params">
                    <i class="fas fa-info-circle"></i>
                    Aucun paramètre configuré pour cet examen.
                    Contactez l'administrateur.
                </div>
            <?php else: ?>

            <div class="params-grid">
                <?php foreach ($params as $param):
                    $existant = $resultatsExistants[$param['id']] ?? null;
                    $valeurExistante = $existant['valeur'] ?? '';
                    $interpExistante = $existant['interpretation'] ?? '';
                    $isAbnormal = $existant['is_abnormal'] ?? 0;
                ?>
                <div class="param-row <?= $isAbnormal ? 'param-abnormal' : '' ?>"
                     id="param_<?= $param['id'] ?>">

                    <!-- Label -->
                    <div class="param-label-wrap">
                        <div class="param-libelle">
                            <?= htmlspecialchars($param['libelle']) ?>
                        </div>
                        <?php if ($param['unite']): ?>
                        <div class="param-unite">
                            <?= htmlspecialchars($param['unite']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($param['valeur_min'] !== null ||
                                  $param['valeur_max'] !== null): ?>
                        <div class="param-norme">
                            Norme :
                            <?= $param['valeur_min'] !== null
                                ? $param['valeur_min'] : '?' ?>
                            –
                            <?= $param['valeur_max'] !== null
                                ? $param['valeur_max'] : '?' ?>
                            <?= htmlspecialchars($param['unite'] ?? '') ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Saisie selon type -->
                    <div class="param-input-wrap">

                        <?php if ($param['type'] === 'numeric'): ?>
                        <div class="input-result-wrap">
                            <input
                                type="number"
                                step="0.01"
                                name="valeurs[<?= $param['id'] ?>]"
                                class="input-result"
                                value="<?= htmlspecialchars($valeurExistante) ?>"
                                placeholder="Saisir valeur"
                                data-param-id="<?= $param['id'] ?>"
                                data-min="<?= $param['valeur_min'] ?>"
                                data-max="<?= $param['valeur_max'] ?>"
                                data-examen-id="<?= $ex['id'] ?>"
                                data-total="<?= count($params) ?>"
                                oninput="evaluerValeur(this)">
                            <span class="unite-inline">
                                <?= htmlspecialchars($param['unite'] ?? '') ?>
                            </span>
                        </div>

                        <?php elseif ($param['type'] === 'select'): ?>
                        <?php
                        $options = [];
                        if ($param['valeurs_possibles']) {
                            $options = json_decode(
                                $param['valeurs_possibles'], true
                            ) ?? [];
                        }
                        ?>
                        <select
                            name="valeurs[<?= $param['id'] ?>]"
                            class="input-result input-select"
                            data-param-id="<?= $param['id'] ?>"
                            data-examen-id="<?= $ex['id'] ?>"
                            data-total="<?= count($params) ?>"
                            onchange="evaluerSelect(this)">
                            <option value="">-- Choisir --</option>
                            <?php foreach ($options as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>"
                                <?= $valeurExistante === $opt ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                        <?php else: ?>
                        <input
                            type="text"
                            name="valeurs[<?= $param['id'] ?>]"
                            class="input-result"
                            value="<?= htmlspecialchars($valeurExistante) ?>"
                            placeholder="Saisir résultat"
                            data-param-id="<?= $param['id'] ?>"
                            data-examen-id="<?= $ex['id'] ?>"
                            data-total="<?= count($params) ?>"
                            oninput="markFilled(this)">
                        <?php endif; ?>

                    </div>

                    <!-- Interprétation auto -->
                    <div class="param-interp"
                         id="interp_<?= $param['id'] ?>">
                        <?php if ($valeurExistante !== ''): ?>
                            <?php
                            $interpClass = match($interpExistante) {
                                'normal' => 'interp-normal',
                                'bas'    => 'interp-low',
                                'élevé'  => 'interp-high',
                                default  => 'interp-normal',
                            };
                            $interpIcon = match($interpExistante) {
                                'normal' => '✅',
                                'bas'    => '⬇️',
                                'élevé'  => '⬆️',
                                default  => '—',
                            };
                            ?>
                            <span class="interp-badge <?= $interpClass ?>">
                                <?= $interpIcon ?>
                                <?= ucfirst($interpExistante) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>

        <!-- Boutons d'action -->
        <?php if (!empty($examens)): ?>
        <div class="form-actions">
            <button type="submit" name="action" value="save"
                    class="btn-primary-drouot"
                    id="btnSave" style="padding:.75rem 1.5rem;">
                <i class="fas fa-floppy-disk"></i>
                Sauvegarder les résultats
            </button>

            <?php if ($echantillon['status'] !== 'analyse'): ?>
            <button type="button"
                    onclick="confirmTransmettre()"
                    class="btn-success-drouot"
                    style="padding:.75rem 1.5rem;"
                    id="btnTransmettre">
                <i class="fas fa-paper-plane"></i>
                Transmettre au biologiste
            </button>
            <?php else: ?>
            <span class="badge-normal" style="padding:10px 16px;font-size:.88rem;">
                ✅ Transmis au biologiste
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Colonne droite — Récap + alertes -->
    <div class="col-xl-4">

        <!-- Infos échantillon -->
        <div class="card-drouot mb-3">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-vial text-primary me-2"></i>
                Échantillon
            </h6>
            <div class="info-row">
                <span class="info-label">Code</span>
                <span class="info-value ref-code">
                    <?= htmlspecialchars($echantillon['code_barre']) ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut</span>
                <span class="info-value">
                    <?php
                    $sBadge = [
                        'planifie'   => ['badge-pending', 'Planifié'],
                        'collecte'   => ['badge-pending', 'Collecté'],
                        'en_transit' => ['badge-warn',     'En transit'],
                        'recu_labo'  => ['badge-warn',     'Reçu au labo'],
                        'en_analyse' => ['badge-warn',     'En analyse'],
                        'analyse'    => ['badge-normal',   'Analysé'],
                        'rejete'     => ['badge-critical', 'Rejeté'],
                    ];
                    [$sc, $sl] = $sBadge[$echantillon['status']]
                        ?? ['badge-pending', $echantillon['status']];
                    ?>
                    <span class="<?= $sc ?>"><?= $sl ?></span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Collecté</span>
                <span class="info-value">
                    <?= $echantillon['collected_at']
                        ? date('d/m/Y H:i',
                            strtotime($echantillon['collected_at']))
                        : '—' ?>
                </span>
            </div>
        </div>

        <!-- Progression globale -->
        <div class="card-drouot mb-3">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-chart-simple text-primary me-2"></i>
                Progression
            </h6>
            <div class="global-progress">
                <div class="gp-circle" id="gpCircle">
                    <span id="gpPct">0%</span>
                </div>
                <div class="gp-info">
                    <div id="gpText" style="font-size:.82rem;color:#6b7a99;">
                        0 paramètre(s) saisi(s)
                    </div>
                    <div id="gpAnormal"
                         style="font-size:.78rem;color:#C62828;margin-top:3px;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertes valeurs anormales -->
        <div class="card-drouot" id="alertesCard">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-triangle-exclamation text-warning me-2"></i>
                Valeurs anormales
            </h6>
            <div id="alertesList">
                <p style="font-size:.8rem;color:#6b7a99;text-align:center;">
                    Aucune valeur anormale détectée.
                </p>
            </div>
        </div>

    </div>

</div>

</form>

<!-- Formulaire transmission — EN DEHORS du formulaire principal -->
<form method="POST"
      action="<?= BASE_URL ?>technicien/resultats/<?= $echantillon['id'] ?>/transmettre"
      id="formTransmettre"
      data-offline-store="examens"
      data-redirect="<?= BASE_URL ?>technicien/echantillons"
      style="display:none;">
</form>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0;
    display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.ref-badge  { background:#E3F2FD; color:#1565C0; padding:3px 12px;
    border-radius:20px; font-size:.82rem; font-family:monospace; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; }

/* Examen block */
.examen-block { border-left:3px solid #1E88E5; }
.examen-header {
    display:flex; align-items:flex-start;
    justify-content:space-between; margin-bottom:1rem;
    padding-bottom:10px; border-bottom:1.5px solid #f0f2f8;
}
.examen-nom  { font-size:.95rem; font-weight:600; color:#1a2340; }
.examen-meta { display:flex; gap:8px; margin-top:3px; flex-wrap:wrap; }
.examen-code { font-family:monospace; font-size:.75rem; color:#90CAF9; }
.examen-cat  { background:#F4F6F9; color:#6b7a99;
    padding:1px 8px; border-radius:6px; font-size:.72rem; }
.examen-progress {
    background:#E3F2FD; color:#1565C0;
    padding:4px 10px; border-radius:20px;
    font-size:.75rem; font-weight:600; white-space:nowrap;
}
.no-params {
    background:#FFF8E1; border-radius:8px;
    padding:10px 14px; font-size:.82rem; color:#F57F17;
    display:flex; align-items:center; gap:8px;
}

/* Params grid */
.params-grid { display:flex; flex-direction:column; gap:0; }
.param-row {
    display:grid;
    grid-template-columns: 1fr 1fr auto;
    gap:12px; align-items:center;
    padding:10px 0;
    border-bottom:1px solid #f0f2f8;
    transition:background .15s;
}
.param-row:last-child { border-bottom:none; }
.param-row:hover { background:#fafbff; border-radius:8px; }
.param-abnormal { background:#fff9f9 !important; }

.param-libelle { font-size:.85rem; font-weight:500; color:#1a2340; }
.param-unite   { font-size:.72rem; color:#6b7a99; margin-top:1px; }
.param-norme   {
    font-size:.7rem; color:#90CAF9;
    background:#E3F2FD; padding:2px 6px;
    border-radius:4px; margin-top:3px;
    display:inline-block;
}

.input-result-wrap { display:flex; align-items:center; gap:6px; }
.input-result {
    width:100%; padding:.55rem .75rem;
    border:1.5px solid #dde3f0; border-radius:8px;
    font-family:'DM Sans',sans-serif; font-size:.9rem;
    color:#1a2340; background:#fafbfd;
    outline:none; transition:all .2s;
}
.input-result:focus { border-color:#1E88E5; background:#fff;
    box-shadow:0 0 0 3px rgba(30,136,229,.08); }
.input-result.abnormal-low  { border-color:#1565C0; background:#EFF7FF; }
.input-result.abnormal-high { border-color:#C62828; background:#FFF5F5; }
.input-select { cursor:pointer; }
.unite-inline { font-size:.75rem; color:#6b7a99; white-space:nowrap; }

/* Interprétation */
.param-interp { min-width:80px; }
.interp-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 8px; border-radius:20px;
    font-size:.72rem; font-weight:500;
}
.interp-normal { background:#E8F5E9; color:#2E7D32; }
.interp-low    { background:#E3F2FD; color:#1565C0; }
.interp-high   { background:#FFEBEE; color:#C62828; }

/* Infos sidebar */
.info-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:7px 0; border-bottom:1px solid #f0f2f8; font-size:.83rem;
}
.info-row:last-child { border-bottom:none; }
.info-label { color:#6b7a99; }
.info-value { font-weight:500; }
.ref-code   { font-family:monospace; color:#1E88E5; font-size:.8rem; }

/* Progression circulaire */
.global-progress { display:flex; align-items:center; gap:14px; }
.gp-circle {
    width:60px; height:60px; border-radius:50%;
    background:conic-gradient(#1E88E5 0%, #f0f2f8 0%);
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; transition:background .3s;
}
#gpPct { font-size:.8rem; font-weight:700; color:#1a2340; }

/* Form actions */
.form-actions {
    display:flex; gap:10px; flex-wrap:wrap;
    margin-top:1rem; padding-top:1rem;
    border-top:1.5px solid #f0f2f8;
}
.alert-drouot { padding:.85rem 1rem; border-radius:10px;
    display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px;
    background:#fff; color:#1a2340; font-size:.88rem; font-weight:500;
    text-decoration:none; transition:all .2s;
    font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<script>
let filledParams   = {};
let abnormalParams = {};
let totalParams    = 0;

// Compter total params
document.querySelectorAll('.input-result').forEach(inp => {
    totalParams++;
    // Marquer existants
    if (inp.value !== '') {
        filledParams[inp.dataset.paramId] = true;
    }
});
updateGlobalProgress();

// ── Évaluation numérique ─────────────────────────────────
function evaluerValeur(input) {
    const val     = parseFloat(input.value);
    const min     = parseFloat(input.dataset.min);
    const max     = parseFloat(input.dataset.max);
    const paramId = input.dataset.paramId;
    const interpEl= document.getElementById('interp_' + paramId);
    const rowEl   = document.getElementById('param_' + paramId);

    input.classList.remove('abnormal-low','abnormal-high');
    rowEl.classList.remove('param-abnormal');

    if (input.value === '') {
        delete filledParams[paramId];
        delete abnormalParams[paramId];
        if (interpEl) interpEl.innerHTML = '';
        updateProgress(input);
        return;
    }

    filledParams[paramId] = true;

    let interp = '✅ Normal';
    let cls    = 'interp-normal';

    if (!isNaN(min) && val < min) {
        interp = '⬇️ Bas';
        cls    = 'interp-low';
        input.classList.add('abnormal-low');
        rowEl.classList.add('param-abnormal');
        abnormalParams[paramId] = {
            libelle: input.closest('.param-row')
                         .querySelector('.param-libelle')?.textContent?.trim(),
            valeur: input.value,
            interp: 'Bas (< ' + min + ')'
        };
    } else if (!isNaN(max) && val > max) {
        interp = '⬆️ Élevé';
        cls    = 'interp-high';
        input.classList.add('abnormal-high');
        rowEl.classList.add('param-abnormal');
        abnormalParams[paramId] = {
            libelle: input.closest('.param-row')
                         .querySelector('.param-libelle')?.textContent?.trim(),
            valeur: input.value,
            interp: 'Élevé (> ' + max + ')'
        };
    } else {
        delete abnormalParams[paramId];
    }

    if (interpEl) {
        interpEl.innerHTML =
            `<span class="interp-badge ${cls}">${interp}</span>`;
    }

    updateProgress(input);
    updateAlertes();
}

function evaluerSelect(select) {
    const paramId = select.dataset.paramId;
    if (select.value !== '') {
        filledParams[paramId] = true;
    } else {
        delete filledParams[paramId];
    }
    updateProgress(select);
}

function markFilled(input) {
    const paramId = input.dataset.paramId;
    if (input.value !== '') {
        filledParams[paramId] = true;
    } else {
        delete filledParams[paramId];
    }
    updateProgress(input);
}

// ── Progression par examen ───────────────────────────────
function updateProgress(input) {
    const examenId = input.dataset.examenId;
    const total    = parseInt(input.dataset.total);
    const examen   = document.getElementById('prog_' + examenId);

    if (!examen) { updateGlobalProgress(); return; }

    // Compter remplis pour cet examen
    let filled = 0;
    document.querySelectorAll(
        `[data-examen-id="${examenId}"].input-result`
    ).forEach(inp => {
        if (inp.value !== '') filled++;
    });

    examen.innerHTML =
        `<span class="prog-text">${filled}/${total}</span>`;
    examen.style.background = filled === total ? '#E8F5E9' : '#E3F2FD';
    examen.style.color      = filled === total ? '#2E7D32' : '#1565C0';

    updateGlobalProgress();
}

function updateGlobalProgress() {
    const filled  = Object.keys(filledParams).length;
    const total   = totalParams;
    const pct     = total > 0 ? Math.round((filled / total) * 100) : 0;

    document.getElementById('gpPct').textContent  = pct + '%';
    document.getElementById('gpText').textContent =
        filled + ' / ' + total + ' paramètre(s) saisi(s)';

    // Cercle conic
    const color = pct === 100 ? '#2E7D32' : '#1E88E5';
    document.getElementById('gpCircle').style.background =
        `conic-gradient(${color} ${pct}%, #f0f2f8 ${pct}%)`;
}

function updateAlertes() {
    const list = document.getElementById('alertesList');
    const keys = Object.keys(abnormalParams);

    if (keys.length === 0) {
        list.innerHTML =
            `<p style="font-size:.8rem;color:#6b7a99;text-align:center;">
                Aucune valeur anormale détectée.
             </p>`;
        return;
    }

    list.innerHTML = keys.map(k => {
        const a = abnormalParams[k];
        const isHigh = a.interp.startsWith('Élevé');
        return `<div style="display:flex;align-items:center;gap:8px;
                    padding:6px 0;border-bottom:1px solid #f0f2f8;
                    font-size:.8rem;">
            <span style="font-size:1rem;">${isHigh ? '⬆️' : '⬇️'}</span>
            <div style="flex:1;">
                <div style="font-weight:500;color:#1a2340;">
                    ${a.libelle}
                </div>
                <div style="color:${isHigh ? '#C62828' : '#1565C0'};">
                    ${a.valeur} — ${a.interp}
                </div>
            </div>
        </div>`;
    }).join('');
}

// ── Transmission biologiste ──────────────────────────────
function confirmTransmettre() {
    const filled = Object.keys(filledParams).length;
    if (filled === 0) {
        alert('Veuillez saisir au moins un résultat avant de transmettre.');
        return;
    }
    if (confirm(
        'Transmettre les résultats au biologiste ?\n\n' +
        filled + ' paramètre(s) saisi(s).\n\n' +
        'Cette action changera le statut de l\'échantillon.'
    )) {
        document.getElementById('formTransmettre').submit();
    }
}

// Init alertes existantes
document.querySelectorAll('.param-abnormal').forEach(row => {
    const libelle = row.querySelector('.param-libelle')?.textContent?.trim();
    const input   = row.querySelector('.input-result');
    const interp  = row.querySelector('.interp-badge');
    if (input && libelle) {
        abnormalParams[input.dataset.paramId] = {
            libelle,
            valeur: input.value,
            interp: interp?.textContent?.trim() ?? 'Anormal'
        };
    }
});
updateAlertes();
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>