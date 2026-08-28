<?php
/**
 * Dashboard Technicien — Laboratoire Drouot
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['technicien', 'admin']);

$db   = Database::getConnection();
$user = Auth::user();

// ── KPI ─────────────────────────────────────────────────

// Échantillons reçus aujourd'hui
$kpi['recus'] = $db->query("
    SELECT COUNT(*) FROM echantillons
    WHERE DATE(collected_at) = CURDATE()
")->fetchColumn();

// En cours d'analyse
$kpi['en_cours'] = $db->query("
    SELECT COUNT(*) FROM echantillons
    WHERE status = 'en_analyse'
")->fetchColumn();

// Analyses du jour (résultats saisis)
$kpi['analyses'] = $db->query("
    SELECT COUNT(DISTINCT e.id)
    FROM echantillons e
    JOIN resultats r ON r.echantillon_id = e.id
    WHERE DATE(r.created_at) = CURDATE()
")->fetchColumn();

// En attente saisie
$kpi['attente'] = $db->query("
    SELECT COUNT(*) FROM echantillons ech
    JOIN demandes_examens d ON ech.demande_id = d.id
    WHERE ech.status = 'collecte'
    AND d.payment_status = 'paid'
")->fetchColumn();

// ── ÉCHANTILLONS À TRAITER ───────────────────────────────
$echantillons = $db->query("
    SELECT
        ech.id, ech.code_barre, ech.status, ech.collected_at,
        d.id AS demande_id, d.total_price,
        CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
        p.telephone,
        GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens,
        COUNT(e.id) AS nb_examens
    FROM echantillons ech
    JOIN demandes_examens d ON ech.demande_id = d.id
    JOIN patients p ON d.patient_id = p.id
    LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
    LEFT JOIN examens e ON dei.examen_id = e.id
    WHERE d.payment_status = 'paid'
    AND ech.status IN ('collecte', 'en_analyse')
    GROUP BY ech.id
    ORDER BY
        CASE ech.status
            WHEN 'collecte'   THEN 1
            WHEN 'en_analyse' THEN 2
        END,
        ech.collected_at ASC
    LIMIT 10
")->fetchAll();

// ── RÉSULTATS SAISIS AUJOURD'HUI ────────────────────────
$resultatsJour = $db->query("
    SELECT
        ech.code_barre,
        CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
        COUNT(r.id) AS nb_resultats,
        MAX(r.created_at) AS last_update,
        ech.status
    FROM resultats r
    JOIN echantillons ech ON r.echantillon_id = ech.id
    JOIN demandes_examens d ON ech.demande_id = d.id
    JOIN patients p ON d.patient_id = p.id
    WHERE DATE(r.created_at) = CURDATE()
    GROUP BY ech.id
    ORDER BY last_update DESC
    LIMIT 6
")->fetchAll();

// ── STATUT ANALYSES ──────────────────────────────────────
$statutAnalyses = $db->query("
    SELECT
        SUM(CASE WHEN status = 'collecte'   THEN 1 ELSE 0 END) AS collectes,
        SUM(CASE WHEN status = 'en_analyse' THEN 1 ELSE 0 END) AS en_cours,
        SUM(CASE WHEN status = 'analyse'    THEN 1 ELSE 0 END) AS termines
    FROM echantillons
")->fetch();
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<!-- ══════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Tableau de bord Laboratoire</h1>
        <p class="page-sub">
            Bonjour, <?= htmlspecialchars($user['name']) ?> 👋 —
            <?= date('d/m/Y') ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>technicien/echantillons"
           class="btn-primary-drouot">
            <i class="fas fa-vial"></i> Voir échantillons
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════
     KPI CARDS
══════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-vial"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['recus'] ?></div>
                <div class="kpi-label">Échantillons reçus</div>
                <div class="kpi-trend up">
                    <i class="fas fa-calendar-day"></i> Aujourd'hui
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon orange">
                <i class="fas fa-spinner"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['en_cours'] ?></div>
                <div class="kpi-label">En cours d'analyse</div>
                <div class="kpi-trend warn">
                    <i class="fas fa-clock"></i> En traitement
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-microscope"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['analyses'] ?></div>
                <div class="kpi-label">Analyses du jour</div>
                <div class="kpi-trend up">
                    <i class="fas fa-check"></i> Résultats saisis
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon red">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['attente'] ?></div>
                <div class="kpi-label">En attente saisie</div>
                <div class="kpi-trend down">
                    <i class="fas fa-exclamation"></i> À traiter
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════
     ÉCHANTILLONS À TRAITER + RÉSULTATS
══════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Échantillons à traiter -->
    <div class="col-xl-7">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-vial text-primary me-2"></i>
                    Échantillons à traiter
                </h6>
                <a href="<?= BASE_URL ?>technicien/echantillons"
                   class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($echantillons)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-2x mb-2"
                       style="color:#2E7D32;"></i>
                    <p class="text-muted">
                        Aucun échantillon en attente. Tout est traité ! ✅
                    </p>
                </div>
            <?php else: ?>
                <div class="echantillon-list">
                    <?php foreach ($echantillons as $ech): ?>
                    <div class="echantillon-item">

                        <!-- Statut indicateur -->
                        <div class="ech-status-dot
                            <?= $ech['status'] === 'en_analyse'
                                ? 'dot-orange' : 'dot-blue' ?>">
                        </div>

                        <!-- Infos -->
                        <div class="ech-info flex-1">
                            <div class="ech-code">
                                <?= htmlspecialchars($ech['code_barre']) ?>
                            </div>
                            <div class="ech-patient">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($ech['patient_nom']) ?>
                            </div>
                            <div class="ech-examens">
                                <i class="fas fa-flask"></i>
                                <?= htmlspecialchars(
                                    mb_strimwidth($ech['examens'] ?? '—', 0, 45, '...')
                                ) ?>
                                <span class="nb-badge">
                                    <?= $ech['nb_examens'] ?> exam.
                                </span>
                            </div>
                        </div>

                        <!-- Statut badge + action -->
                        <div class="ech-actions">
                            <?php if ($ech['status'] === 'en_analyse'): ?>
                                <span class="badge-warn mb-1 d-block text-center">
                                    En cours
                                </span>
                            <?php else: ?>
                                <span class="badge-pending mb-1 d-block text-center">
                                    À traiter
                                </span>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>technicien/resultats/<?= $ech['id'] ?>"
                               class="btn-saisir">
                                <i class="fas fa-keyboard"></i> Saisir
                            </a>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne droite -->
    <div class="col-xl-5">

        <!-- Statut global analyses -->
        <div class="card-drouot mb-3">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-chart-pie text-primary me-2"></i>
                Statut analyses
            </h6>
            <div class="statut-list">
                <div class="statut-row">
                    <div class="statut-dot dot-blue"></div>
                    <span class="statut-label">Collectés</span>
                    <div class="statut-bar-wrap">
                        <?php
                        $total = max(1, ($statutAnalyses['collectes'] +
                                        $statutAnalyses['en_cours'] +
                                        $statutAnalyses['termines']));
                        $pct = round($statutAnalyses['collectes'] / $total * 100);
                        ?>
                        <div class="statut-bar">
                            <div class="statut-fill fill-blue"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <span class="statut-count">
                        <?= $statutAnalyses['collectes'] ?>
                    </span>
                </div>
                <div class="statut-row">
                    <div class="statut-dot dot-orange"></div>
                    <span class="statut-label">En cours</span>
                    <div class="statut-bar-wrap">
                        <?php
                        $pct2 = round($statutAnalyses['en_cours'] / $total * 100);
                        ?>
                        <div class="statut-bar">
                            <div class="statut-fill fill-orange"
                                 style="width:<?= $pct2 ?>%"></div>
                        </div>
                    </div>
                    <span class="statut-count">
                        <?= $statutAnalyses['en_cours'] ?>
                    </span>
                </div>
                <div class="statut-row">
                    <div class="statut-dot dot-green"></div>
                    <span class="statut-label">Terminés</span>
                    <div class="statut-bar-wrap">
                        <?php
                        $pct3 = round($statutAnalyses['termines'] / $total * 100);
                        ?>
                        <div class="statut-bar">
                            <div class="statut-fill fill-green"
                                 style="width:<?= $pct3 ?>%"></div>
                        </div>
                    </div>
                    <span class="statut-count">
                        <?= $statutAnalyses['termines'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Résultats saisis aujourd'hui -->
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-clipboard-check text-primary me-2"></i>
                    Saisis aujourd'hui
                </h6>
                <a href="<?= BASE_URL ?>technicien/resultats"
                   class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($resultatsJour)): ?>
                <div class="text-center py-3 text-muted">
                    <i class="fas fa-keyboard fa-2x mb-2"
                       style="color:#dde3f0;"></i>
                    <p style="font-size:.82rem;">
                        Aucun résultat saisi aujourd'hui.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($resultatsJour as $r): ?>
                <div class="result-mini-item">
                    <div class="result-mini-code">
                        <?= htmlspecialchars($r['code_barre']) ?>
                    </div>
                    <div class="result-mini-info">
                        <div class="result-mini-patient">
                            <?= htmlspecialchars($r['patient_nom']) ?>
                        </div>
                        <div class="result-mini-nb">
                            <?= $r['nb_resultats'] ?> paramètre(s) saisi(s)
                        </div>
                    </div>
                    <?php if ($r['status'] === 'analyse'): ?>
                        <span class="badge-normal">Transmis</span>
                    <?php else: ?>
                        <span class="badge-warn">En cours</span>
                    <?php endif; ?>
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
        <a href="<?= BASE_URL ?>technicien/echantillons"
           class="raccourci-btn raccourci-blue">
            <i class="fas fa-vial"></i>
            <span>Échantillons reçus</span>
        </a>
        <a href="<?= BASE_URL ?>technicien/resultats"
           class="raccourci-btn raccourci-green">
            <i class="fas fa-keyboard"></i>
            <span>Saisir résultats</span>
        </a>
        <a href="<?= BASE_URL ?>technicien/encours"
           class="raccourci-btn raccourci-orange">
            <i class="fas fa-spinner"></i>
            <span>Analyses en cours</span>
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════
     STYLES
══════════════════════════════════════ -->
<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title {
    font-size:.88rem; font-weight:600; color:#1a2340; margin:0;
}
.btn-link-drouot {
    font-size:.78rem; color:#1E88E5; text-decoration:none;
    display:flex; align-items:center; gap:4px;
}
.btn-link-drouot:hover { color:#0D47A1; }

/* Échantillons list */
.echantillon-list { display:flex; flex-direction:column; gap:2px; }
.echantillon-item {
    display:flex; align-items:center; gap:12px;
    padding:10px 0; border-bottom:1px solid #f0f2f8;
}
.echantillon-item:last-child { border-bottom:none; }
.flex-1 { flex:1; min-width:0; }

.ech-status-dot {
    width:10px; height:10px; border-radius:50%; flex-shrink:0;
}
.dot-blue   { background:#1E88E5; }
.dot-orange { background:#F9A825; }
.dot-green  { background:#2E7D32; }

.ech-code {
    font-family:monospace; font-size:.82rem;
    font-weight:600; color:#1E88E5; margin-bottom:2px;
}
.ech-patient {
    font-size:.82rem; font-weight:500; color:#1a2340;
    display:flex; align-items:center; gap:5px; margin-bottom:2px;
}
.ech-patient i { color:#b0b8cc; font-size:.75rem; }
.ech-examens {
    font-size:.75rem; color:#6b7a99;
    display:flex; align-items:center; gap:5px; flex-wrap:wrap;
}
.ech-examens i { font-size:.7rem; }
.nb-badge {
    background:#E3F2FD; color:#1565C0;
    padding:1px 6px; border-radius:10px;
    font-size:.68rem; font-weight:500;
}

.ech-actions {
    display:flex; flex-direction:column;
    align-items:center; gap:4px; flex-shrink:0;
}
.btn-saisir {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 10px; border-radius:8px;
    background:#1E88E5; color:#fff;
    font-size:.75rem; font-weight:500;
    text-decoration:none; transition:background .2s;
}
.btn-saisir:hover { background:#0D47A1; color:#fff; }

/* Statut bars */
.statut-list { display:flex; flex-direction:column; gap:10px; }
.statut-row  {
    display:flex; align-items:center; gap:8px; font-size:.82rem;
}
.statut-dot  {
    width:8px; height:8px; border-radius:50%; flex-shrink:0;
}
.statut-label { min-width:70px; color:#6b7a99; }
.statut-bar-wrap { flex:1; }
.statut-bar {
    height:8px; background:#f0f2f8; border-radius:20px; overflow:hidden;
}
.statut-fill { height:100%; border-radius:20px; transition:width .6s ease; }
.fill-blue   { background:#1E88E5; }
.fill-orange { background:#F9A825; }
.fill-green  { background:#2E7D32; }
.statut-count {
    min-width:24px; text-align:right;
    font-weight:600; color:#1a2340;
}

/* Résultats mini */
.result-mini-item {
    display:flex; align-items:center; gap:10px;
    padding:7px 0; border-bottom:1px solid #f0f2f8; font-size:.82rem;
}
.result-mini-item:last-child { border-bottom:none; }
.result-mini-code {
    font-family:monospace; font-size:.72rem;
    color:#1E88E5; min-width:90px;
}
.result-mini-info { flex:1; }
.result-mini-patient { font-weight:500; color:#1a2340; }
.result-mini-nb { font-size:.72rem; color:#6b7a99; }

/* Raccourcis */
.raccourci-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:9px 16px; border-radius:10px;
    font-size:.85rem; font-weight:500;
    text-decoration:none; transition:all .2s;
    border:1.5px solid transparent;
}
.raccourci-blue   { background:#E3F2FD; color:#1565C0; border-color:#BBDEFB; }
.raccourci-blue:hover   { background:#1E88E5; color:#fff; border-color:#1E88E5; }
.raccourci-green  { background:#E8F5E9; color:#2E7D32; border-color:#C8E6C9; }
.raccourci-green:hover  { background:#2E7D32; color:#fff; border-color:#2E7D32; }
.raccourci-orange { background:#FFF8E1; color:#F57F17; border-color:#FFE082; }
.raccourci-orange:hover { background:#F9A825; color:#fff; border-color:#F9A825; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>