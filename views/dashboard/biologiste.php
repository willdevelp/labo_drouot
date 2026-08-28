<?php
/**
 * Dashboard Biologiste — Laboratoire Drouot
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['biologiste', 'admin']);

$db   = Database::getConnection();
$user = Auth::user();

// ── KPI ─────────────────────────────────────────────────

// À valider (transmis par technicien)
$kpi['valides_jour'] = $db->query("
    SELECT COUNT(*) FROM validations
    WHERE DATE(validated_at) = CURDATE()
")->fetchColumn();

// Validés aujourd'hui
$kpi['a_valider'] = $db->query("
    SELECT COUNT(*) FROM echantillons ech
    JOIN demandes_examens d ON ech.demande_id = d.id
    WHERE ech.status = 'analyse'
    AND d.status != 'terminee'
")->fetchColumn();

// Rejetés aujourd'hui
$kpi['rejetes_jour'] = $db->query("
    SELECT COUNT(DISTINCT record_id) FROM audit_logs
    WHERE action LIKE 'REJECT:%'
    AND DATE(created_at) = CURDATE()
")->fetchColumn();

// Commentaires ajoutés
$kpi['commentaires'] = $db->query("
    SELECT COUNT(*) FROM validations
    WHERE commentaire IS NOT NULL
    AND commentaire != ''
")->fetchColumn();

// ── RÉSULTATS À VALIDER ──────────────────────────────────
$aValider = $db->query("
    SELECT
        ech.id, ech.code_barre, ech.status,
        ech.collected_at,
        d.id AS demande_id, d.total_price,
        CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
        p.telephone, p.sexe, p.date_naissance,
        GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens,
        COUNT(DISTINCT e.id) AS nb_examens,
        SUM(r.is_abnormal) AS nb_anormaux,
        COUNT(DISTINCT r.id) AS nb_resultats
    FROM echantillons ech
    JOIN demandes_examens d ON ech.demande_id = d.id
    JOIN patients p ON d.patient_id = p.id
    LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
    LEFT JOIN examens e ON dei.examen_id = e.id
    LEFT JOIN resultats r ON r.echantillon_id = ech.id
   WHERE ech.status = 'analyse'
AND d.status != 'terminee'
GROUP BY ech.id
    ORDER BY nb_anormaux DESC, ech.collected_at ASC
    LIMIT 10
")->fetchAll();

// ── VALIDATIONS RÉCENTES ─────────────────────────────────
$validations = $db->query("
    SELECT
        v.id, v.commentaire, v.validated_at,
        CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
        ech.code_barre,
        GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens
    FROM validations v
    JOIN demandes_examens d ON v.demande_id = d.id
    JOIN patients p ON d.patient_id = p.id
    JOIN echantillons ech ON ech.demande_id = d.id
    LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
    LEFT JOIN examens e ON dei.examen_id = e.id
    GROUP BY v.id
    ORDER BY v.validated_at DESC
    LIMIT 5
")->fetchAll();

// ── RÉPARTITION VALIDATIONS ──────────────────────────────
$repartition = $db->query("
    SELECT
        SUM(CASE WHEN d.status = 'terminee' THEN 1 ELSE 0 END) AS valides,
        SUM(CASE WHEN d.status = 'en_cours'
            AND ech.status != 'analyse'     THEN 1 ELSE 0 END) AS en_attente,
        SUM(CASE WHEN ech.status = 'analyse'
            AND d.status != 'terminee'      THEN 1 ELSE 0 END) AS a_valider
    FROM demandes_examens d
    LEFT JOIN echantillons ech ON ech.demande_id = d.id
")->fetch();
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<!-- ══════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Tableau de bord Biologie</h1>
        <p class="page-sub">
            Bonjour Dr. <?= htmlspecialchars($user['name']) ?> 👋 —
            <?= date('d/m/Y') ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>biologiste/validations"
           class="btn-primary-drouot">
            <i class="fas fa-circle-check"></i>
            Valider les résultats
            <?php if ($kpi['a_valider'] > 0): ?>
            <span class="btn-badge"><?= $kpi['a_valider'] ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════
     KPI CARDS
══════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card <?= $kpi['a_valider'] > 0 ? 'kpi-urgent' : '' ?>">
            <div class="kpi-icon red">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="kpi-value" style="color:<?= $kpi['a_valider'] > 0 ? '#C62828' : 'inherit' ?>">
                    <?= $kpi['a_valider'] ?>
                </div>
                <div class="kpi-label">À valider</div>
                <div class="kpi-trend <?= $kpi['a_valider'] > 0 ? 'down' : 'up' ?>">
                    <?php if ($kpi['a_valider'] > 0): ?>
                        <i class="fas fa-exclamation"></i> En attente
                    <?php else: ?>
                        <i class="fas fa-check"></i> Tout à jour
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-circle-check"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['valides_jour'] ?></div>
                <div class="kpi-label">Validés aujourd'hui</div>
                <div class="kpi-trend up">
                    <i class="fas fa-calendar-day"></i> Ce jour
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon orange">
                <i class="fas fa-rotate-left"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['rejetes_jour'] ?></div>
                <div class="kpi-label">Rejetés</div>
                <div class="kpi-trend warn">
                    <i class="fas fa-calendar-day"></i> Aujourd'hui
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-comment-medical"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['commentaires'] ?></div>
                <div class="kpi-label">Commentaires</div>
                <div class="kpi-trend up">
                    <i class="fas fa-pen"></i> Interprétations
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════
     RÉSULTATS À VALIDER + STATS
══════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Résultats à valider -->
    <div class="col-xl-8">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-microscope text-primary me-2"></i>
                    Résultats à valider
                    <?php if (!empty($aValider)): ?>
                    <span class="count-badge"><?= count($aValider) ?></span>
                    <?php endif; ?>
                </h6>
                <a href="<?= BASE_URL ?>biologiste/validations"
                   class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($aValider)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-2x mb-2"
                       style="color:#2E7D32;"></i>
                    <p class="text-muted">
                        Aucun résultat en attente de validation. ✅
                    </p>
                </div>
            <?php else: ?>
                <div class="validation-list">
                    <?php foreach ($aValider as $item): ?>
                    <div class="validation-item
                        <?= $item['nb_anormaux'] > 0 ? 'item-urgent' : '' ?>">

                        <!-- Priorité -->
                        <div class="priority-indicator">
                            <?php if ($item['nb_anormaux'] > 0): ?>
                                <div class="priority-dot dot-red"
                                     title="Valeurs anormales"></div>
                            <?php else: ?>
                                <div class="priority-dot dot-green"
                                     title="Normal"></div>
                            <?php endif; ?>
                        </div>

                        <!-- Infos -->
                        <div class="val-info flex-1">
                            <div class="val-patient">
                                <?= htmlspecialchars($item['patient_nom']) ?>
                                <span class="val-code">
                                    <?= htmlspecialchars($item['code_barre']) ?>
                                </span>
                            </div>
                            <div class="val-examens">
                                <i class="fas fa-flask"></i>
                                <?= htmlspecialchars(
                                    mb_strimwidth($item['examens'] ?? '—', 0, 50, '...')
                                ) ?>
                            </div>
                            <div class="val-stats">
                                <span class="badge-pending">
                                    <?= $item['nb_resultats'] ?> résultats
                                </span>
                                <?php if ($item['nb_anormaux'] > 0): ?>
                                <span class="badge-critical">
                                    ⚠️ <?= $item['nb_anormaux'] ?> anormal(aux)
                                </span>
                                <?php else: ?>
                                <span class="badge-normal">✅ Tous normaux</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="val-actions">
                            <a href="<?= BASE_URL ?>biologiste/validations/<?= $item['id'] ?>"
                               class="btn-valider
                               <?= $item['nb_anormaux'] > 0
                                   ? 'btn-valider-urgent' : '' ?>">
                                <i class="fas fa-eye"></i> Valider
                            </a>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne droite -->
    <div class="col-xl-4">

        <!-- Répartition validations -->
        <div class="card-drouot mb-3">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-chart-pie text-primary me-2"></i>
                Répartition
            </h6>
            <div class="repartition-list">
                <?php
                $totalRep = max(1,
                    ($repartition['valides'] ?? 0) +
                    ($repartition['en_attente'] ?? 0) +
                    ($repartition['a_valider'] ?? 0)
                );
                $items = [
                    ['label'=>'Validés',    'val'=>$repartition['valides']    ?? 0, 'color'=>'#2E7D32'],
                    ['label'=>'En attente', 'val'=>$repartition['en_attente'] ?? 0, 'color'=>'#F9A825'],
                    ['label'=>'À valider',  'val'=>$repartition['a_valider']  ?? 0, 'color'=>'#C62828'],
                ];
                foreach ($items as $it):
                    $pct = round($it['val'] / $totalRep * 100);
                ?>
                <div class="rep-row">
                    <div class="rep-dot"
                         style="background:<?= $it['color'] ?>"></div>
                    <span class="rep-label"><?= $it['label'] ?></span>
                    <div class="rep-bar-wrap">
                        <div class="rep-bar">
                            <div class="rep-fill"
                                 style="width:<?= $pct ?>%;background:<?= $it['color'] ?>">
                            </div>
                        </div>
                    </div>
                    <span class="rep-count"><?= $it['val'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Validations récentes -->
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-clock-rotate-left text-primary me-2"></i>
                    Récemment validés
                </h6>
                <a href="<?= BASE_URL ?>biologiste/resultats-valides"
                   class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($validations)): ?>
                <div class="text-center py-3 text-muted">
                    <p style="font-size:.82rem;">Aucune validation récente.</p>
                </div>
            <?php else: ?>
                <?php foreach ($validations as $v): ?>
                <div class="recent-val-item">
                    <div class="rval-dot dot-green"></div>
                    <div class="rval-info">
                        <div class="rval-patient">
                            <?= htmlspecialchars($v['patient_nom']) ?>
                        </div>
                        <div class="rval-code">
                            <?= htmlspecialchars($v['code_barre']) ?>
                        </div>
                    </div>
                    <div class="rval-date">
                        <?= $v['validated_at']
                            ? date('d/m H:i', strtotime($v['validated_at']))
                            : '—' ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</div>

<!-- ══════════════════════════════════════
     ACTIONS RAPIDES
══════════════════════════════════════ -->
<div class="card-drouot">
    <p class="card-section-title mb-3">
        <i class="fas fa-bolt text-warning me-2"></i> Actions rapides
    </p>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>biologiste/validations"
           class="raccourci-btn raccourci-red">
            <i class="fas fa-circle-check"></i>
            <span>Valider résultats</span>
            <?php if ($kpi['a_valider'] > 0): ?>
            <span class="rc-badge"><?= $kpi['a_valider'] ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>biologiste/resultats-valides"
           class="raccourci-btn raccourci-green">
            <i class="fas fa-clipboard-check"></i>
            <span>Résultats validés</span>
        </a>
        <a href="<?= BASE_URL ?>biologiste/commentaires"
           class="raccourci-btn raccourci-blue">
            <i class="fas fa-comment-medical"></i>
            <span>Commentaires</span>
        </a>
        <a href="<?= BASE_URL ?>biologiste/rapports"
           class="raccourci-btn raccourci-gray">
            <i class="fas fa-file-waveform"></i>
            <span>Rapports</span>
        </a>
    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title {
    font-size:.88rem; font-weight:600; color:#1a2340; margin:0;
    display:flex; align-items:center; gap:8px;
}
.btn-link-drouot {
    font-size:.78rem; color:#1E88E5;
    text-decoration:none; display:flex; align-items:center; gap:4px;
}
.btn-link-drouot:hover { color:#0D47A1; }

/* KPI urgent */
.kpi-urgent {
    border:1.5px solid #FFCDD2 !important;
    background:#FFF5F5 !important;
}

/* Btn badge */
.btn-badge {
    background:#fff; color:#C62828;
    padding:1px 7px; border-radius:20px;
    font-size:.75rem; font-weight:700;
}

/* Count badge */
.count-badge {
    background:#FFEBEE; color:#C62828;
    padding:2px 8px; border-radius:20px;
    font-size:.72rem; font-weight:600;
}

/* Validation list */
.validation-list { display:flex; flex-direction:column; gap:2px; }
.validation-item {
    display:flex; align-items:center; gap:12px;
    padding:10px; border-radius:10px;
    border:1px solid #f0f2f8;
    transition:all .15s; margin-bottom:6px;
}
.validation-item:last-child { margin-bottom:0; }
.validation-item:hover { border-color:#1E88E5; background:#fafbff; }
.item-urgent {
    border-color:#FFCDD2 !important;
    background:#FFF9F9 !important;
}
.item-urgent:hover { border-color:#C62828 !important; }

.priority-indicator { flex-shrink:0; }
.priority-dot {
    width:12px; height:12px; border-radius:50%;
}
.dot-red   { background:#C62828; box-shadow:0 0 0 3px rgba(198,40,40,.15); }
.dot-green { background:#2E7D32; box-shadow:0 0 0 3px rgba(46,125,50,.15); }
.dot-orange{ background:#F9A825; }
.dot-blue  { background:#1E88E5; }

.flex-1 { flex:1; min-width:0; }
.val-patient {
    font-size:.88rem; font-weight:600; color:#1a2340;
    display:flex; align-items:center; gap:8px;
    flex-wrap:wrap;
}
.val-code {
    font-family:monospace; font-size:.72rem; color:#90CAF9;
    background:#E3F2FD; padding:1px 6px; border-radius:4px;
}
.val-examens {
    font-size:.78rem; color:#6b7a99;
    display:flex; align-items:center; gap:5px;
    margin-top:2px;
}
.val-examens i { font-size:.7rem; }
.val-stats {
    display:flex; gap:6px; flex-wrap:wrap; margin-top:4px;
}
.val-actions { flex-shrink:0; }

.btn-valider {
    display:inline-flex; align-items:center; gap:5px;
    padding:6px 12px; border-radius:8px;
    background:#1E88E5; color:#fff;
    font-size:.78rem; font-weight:500;
    text-decoration:none; transition:background .2s;
}
.btn-valider:hover { background:#0D47A1; color:#fff; }
.btn-valider-urgent {
    background:#C62828 !important;
    animation:pulse-red 2s infinite;
}
.btn-valider-urgent:hover { background:#7f0000 !important; }
@keyframes pulse-red {
    0%,100% { box-shadow:0 0 0 0 rgba(198,40,40,.3); }
    50%      { box-shadow:0 0 0 6px rgba(198,40,40,0); }
}

/* Répartition */
.repartition-list { display:flex; flex-direction:column; gap:10px; }
.rep-row { display:flex; align-items:center; gap:8px; font-size:.82rem; }
.rep-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.rep-label { min-width:75px; color:#6b7a99; }
.rep-bar-wrap { flex:1; }
.rep-bar { height:8px; background:#f0f2f8; border-radius:20px; overflow:hidden; }
.rep-fill { height:100%; border-radius:20px; transition:width .6s ease; }
.rep-count { min-width:24px; text-align:right; font-weight:600; color:#1a2340; }

/* Validations récentes */
.recent-val-item {
    display:flex; align-items:center; gap:10px;
    padding:7px 0; border-bottom:1px solid #f0f2f8;
    font-size:.82rem;
}
.recent-val-item:last-child { border-bottom:none; }
.rval-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.rval-info { flex:1; }
.rval-patient { font-weight:500; color:#1a2340; }
.rval-code {
    font-family:monospace; font-size:.72rem; color:#90CAF9;
}
.rval-date { font-size:.75rem; color:#6b7a99; white-space:nowrap; }

/* Raccourcis */
.raccourci-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:9px 16px; border-radius:10px;
    font-size:.85rem; font-weight:500;
    text-decoration:none; transition:all .2s;
    border:1.5px solid transparent; position:relative;
}
.rc-badge {
    background:#C62828; color:#fff;
    padding:1px 6px; border-radius:20px;
    font-size:.7rem; font-weight:700;
}
.raccourci-red  { background:#FFEBEE; color:#C62828; border-color:#FFCDD2; }
.raccourci-red:hover  { background:#C62828; color:#fff; border-color:#C62828; }
.raccourci-green{ background:#E8F5E9; color:#2E7D32; border-color:#C8E6C9; }
.raccourci-green:hover{ background:#2E7D32; color:#fff; border-color:#2E7D32; }
.raccourci-blue { background:#E3F2FD; color:#1565C0; border-color:#BBDEFB; }
.raccourci-blue:hover { background:#1E88E5; color:#fff; border-color:#1E88E5; }
.raccourci-gray { background:#F4F6F9; color:#1a2340; border-color:#dde3f0; }
.raccourci-gray:hover { background:#0D47A1; color:#fff; border-color:#0D47A1; }
</style>


<?php include __DIR__ . '/../layouts/footer.php'; ?>