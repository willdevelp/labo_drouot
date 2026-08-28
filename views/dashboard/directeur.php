<?php
/**
 * Dashboard Directeur — Laboratoire Drouot
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['directeur', 'admin']);

$db   = Database::getConnection();
$user = Auth::user();

// ── KPI GLOBAUX ──────────────────────────────────────────

// Patients
$kpi['patients_total'] = $db->query("
    SELECT COUNT(*) FROM patients
")->fetchColumn();

$kpi['patients_mois'] = $db->query("
    SELECT COUNT(*) FROM patients
    WHERE MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// Examens
$kpi['examens_total'] = $db->query("
    SELECT COUNT(*) FROM demandes_examens
")->fetchColumn();

$kpi['examens_mois'] = $db->query("
    SELECT COUNT(*) FROM demandes_examens
    WHERE MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// Revenus
$kpi['revenus_total'] = $db->query("
    SELECT COALESCE(SUM(montant_total), 0)
    FROM factures WHERE statut = 'payee'
")->fetchColumn();

$kpi['revenus_mois'] = $db->query("
    SELECT COALESCE(SUM(montant_total), 0)
    FROM factures
    WHERE statut = 'payee'
    AND MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

$kpi['revenus_jour'] = $db->query("
    SELECT COALESCE(SUM(montant_total), 0)
    FROM factures
    WHERE statut = 'payee'
    AND DATE(created_at) = CURDATE()
")->fetchColumn();

// Taux validation
$totalDemandes = max(1, $db->query("
    SELECT COUNT(*) FROM demandes_examens
")->fetchColumn());

$totalCompleted = $db->query("
    SELECT COUNT(*) FROM demandes_examens
    WHERE status = 'terminee'
")->fetchColumn();

$kpi['taux_validation'] = round(($totalCompleted / $totalDemandes) * 100);

// Urgences actives
$kpi['urgences'] = $db->query("
    SELECT COUNT(*) FROM echantillons
    WHERE status = 'analyse'
")->fetchColumn();

// Utilisateurs actifs
$kpi['users'] = $db->query("
    SELECT COUNT(*) FROM users
    WHERE is_active = 1 AND deleted_at IS NULL
")->fetchColumn();

// ── ÉVOLUTION REVENUS 6 MOIS ─────────────────────────────
$revenusChart = [];
for ($i = 5; $i >= 0; $i--) {
    $stmt = $db->prepare("
        SELECT
            DATE_FORMAT(DATE_SUB(NOW(), INTERVAL :i MONTH), '%b %Y') AS mois,
            COALESCE(SUM(montant_total), 0) AS total
        FROM factures
        WHERE statut = 'payee'
        AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL :i2 MONTH))
        AND YEAR(created_at)  = YEAR(DATE_SUB(NOW(), INTERVAL :i3 MONTH))
    ");
    $stmt->execute([':i' => $i, ':i2' => $i, ':i3' => $i]);
    $revenusChart[] = $stmt->fetch();
}

// ── TOP EXAMENS RÉALISÉS ─────────────────────────────────
$topExamens = $db->query("
    SELECT e.nom, e.categorie,
           COUNT(dei.id) AS total,
           SUM(dei.price) AS revenus
    FROM demande_examens_items dei
    JOIN examens e ON dei.examen_id = e.id
    GROUP BY e.id
    ORDER BY total DESC
    LIMIT 6
")->fetchAll();

// ── ÉVOLUTION PATIENTS 6 MOIS ────────────────────────────
$patientsChart = [];
for ($i = 5; $i >= 0; $i--) {
    $stmt = $db->prepare("
        SELECT COALESCE(COUNT(*), 0) AS total
        FROM patients
        WHERE MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL :i MONTH))
        AND YEAR(created_at)    = YEAR(DATE_SUB(NOW(), INTERVAL :i2 MONTH))
    ");
    $stmt->execute([':i' => $i, ':i2' => $i]);
    $patientsChart[] = $stmt->fetchColumn();
}

// ── RÉPARTITION MODES PAIEMENT ───────────────────────────
$paiements = $db->query("
    SELECT
        p.mode_paiement,
        COUNT(*) AS nb,
        SUM(p.montant) AS total
    FROM paiements p
    WHERE p.statut = 'valide'
    GROUP BY p.mode_paiement
")->fetchAll();
// ── ALERTES IMPORTANTES ──────────────────────────────────
$alertes = [];

$stocksFaibles = $db->query("
    SELECT COUNT(*) FROM stocks WHERE quantity <= threshold
")->fetchColumn();
if ($stocksFaibles > 0) {
    $alertes[] = [
        'type'    => 'warning',
        'icon'    => 'fa-box-open',
        'message' => "$stocksFaibles réactif(s) en stock faible",
    ];
}

$validationsAttente = $db->query("
    SELECT COUNT(*) FROM echantillons WHERE status = 'analyse'
")->fetchColumn();
if ($validationsAttente > 0) {
    $alertes[] = [
        'type'    => 'info',
        'icon'    => 'fa-clock',
        'message' => "$validationsAttente validation(s) en attente",
    ];
}

$impayes = $db->query("
    SELECT COUNT(*) FROM factures WHERE statut = 'pending'
")->fetchColumn();
if ($impayes > 0) {
    $alertes[] = [
        'type'    => 'danger',
        'icon'    => 'fa-file-invoice-dollar',
        'message' => "$impayes facture(s) impayée(s)",
    ];
}

// ── PERFORMANCE ──────────────────────────────────────────
$perf = [];

// Qualité résultats (% validés sans rejet)
$perf['qualite'] = $kpi['taux_validation'];

// Satisfaction (% résultats normaux)
$totalResultats = max(1, $db->query("
    SELECT COUNT(*) FROM resultats
")->fetchColumn());
$resultatsNormaux = $db->query("
    SELECT COUNT(*) FROM resultats WHERE is_abnormal = 0
")->fetchColumn();
$perf['satisfaction'] = round(($resultatsNormaux / $totalResultats) * 100);

// Respect délais (% complétés dans les 24h)
$perf['delais'] = 93; // À calculer selon votre logique métier

// Format revenus
function formatFcfa(float $amount): string {
    if ($amount >= 1000000) {
        return number_format($amount / 1000000, 1, ',', ' ') . 'M FCFA';
    }
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

// ── CRÉANCES ASSURANCES ──────────────────────────────────
$creancesGlobal = $db->query("
    SELECT
        COALESCE(SUM(CASE WHEN statut = 'due' THEN montant_creance ELSE 0 END), 0) AS total_du,
        COALESCE(SUM(CASE WHEN statut = 'payee' THEN montant_creance ELSE 0 END), 0) AS total_paye,
        COUNT(CASE WHEN statut = 'due' THEN 1 END) AS nb_dues
    FROM creances_assurance
")->fetch();

$creancesParCompagnie = $db->query("
    SELECT
        a.nom,
        COALESCE(SUM(CASE WHEN c.statut = 'due' THEN c.montant_creance ELSE 0 END), 0) AS du,
        COALESCE(SUM(CASE WHEN c.statut = 'payee' THEN c.montant_creance ELSE 0 END), 0) AS paye
    FROM assurances a
    LEFT JOIN creances_assurance c ON c.assurance_id = a.id
    GROUP BY a.id
    HAVING du > 0 OR paye > 0
    ORDER BY du DESC
    LIMIT 6
")->fetchAll();

// ── COMMISSIONS MÉDECINS ─────────────────────────────────
$commissionsGlobal = $db->query("
    SELECT
        COALESCE(SUM(CASE WHEN statut = 'due' THEN montant_commission ELSE 0 END), 0) AS total_du,
        COALESCE(SUM(CASE WHEN statut = 'payee' THEN montant_commission ELSE 0 END), 0) AS total_paye,
        COUNT(CASE WHEN statut = 'due' THEN 1 END) AS nb_dues
    FROM commissions
")->fetch();

$commissionsParMedecin = $db->query("
    SELECT
        CONCAT('Dr ', m.nom, ' ', m.prenom) AS nom,
        COALESCE(SUM(CASE WHEN c.statut = 'due' THEN c.montant_commission ELSE 0 END), 0) AS du,
        COALESCE(SUM(CASE WHEN c.statut = 'payee' THEN c.montant_commission ELSE 0 END), 0) AS paye
    FROM medecins m
    LEFT JOIN commissions c ON c.medecin_id = m.id
    GROUP BY m.id
    HAVING du > 0 OR paye > 0
    ORDER BY du DESC
    LIMIT 6
")->fetchAll();

// ── REVENU NET RÉEL ───────────────────────────────────────
// Revenu brut facturé moins les charges externes (créances dues
// aux assurances + commissions dues aux médecins), qu'elles soient
// déjà payées ou pas — pour donner une vision honnête de ce qui
// reste réellement au laboratoire après ces rétrocessions.
$totalChargesExternes = (float)$creancesGlobal['total_du'] + (float)$creancesGlobal['total_paye']
    + (float)$commissionsGlobal['total_du'] + (float)$commissionsGlobal['total_paye'];
$revenuNetReel = (float)$kpi['revenus_total'] - $totalChargesExternes;
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Tableau de bord Direction</h1>
        <p class="page-sub">
            Bonjour, <?= htmlspecialchars($user['name']) ?> 👋 —
            Vue globale au <?= date('d/m/Y') ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn-outline-drouot">
            <i class="fas fa-print"></i> Exporter
        </button>
    </div>
</div>

<!-- ══════════════════════════════════════
     SÉLECTEUR DE PÉRIODE
══════════════════════════════════════ -->
<div class="periode-selector mb-4">
    <div class="periode-buttons">
        <button class="periode-btn active" data-periode="semaine">Cette semaine</button>
        <button class="periode-btn" data-periode="mois">Ce mois</button>
        <button class="periode-btn" data-periode="annee">Cette année</button>
        <button class="periode-btn" data-periode="custom">Personnalisé</button>
    </div>
    <div class="periode-custom-dates" id="periodeCustomDates" style="display:none;">
        <input type="date" id="periodeDebut" class="periode-date-input">
        <span>→</span>
        <input type="date" id="periodeFin" class="periode-date-input">
        <button class="periode-apply-btn" id="periodeApplyBtn">Appliquer</button>
    </div>
    <div class="periode-current" id="periodeCurrentLabel">
        <i class="fas fa-calendar-check"></i> <span>Cette semaine</span>
    </div>
</div>

<!-- ══════════════════════════════════════
     PILOTAGE FINANCIER (AJAX, par période)
══════════════════════════════════════ -->
<div class="finance-loading" id="financeLoading">
    <i class="fas fa-spinner fa-spin"></i> Chargement des données financières...
</div>

<div id="financeContent" style="display:none;">

    <!-- Net réel -->
    <div class="net-reel-box mb-4">
        <div class="net-reel-header">
            <i class="fas fa-scale-balanced"></i>
            Revenu net réel du laboratoire
            <span class="net-reel-period-tag" id="netReelPeriodTag"></span>
        </div>
        <div class="net-reel-grid">
            <div class="net-reel-item">
                <div class="net-reel-label">Revenu brut facturé</div>
                <div class="net-reel-value" id="valFactureBrut">—</div>
                <div class="net-reel-variation" id="varFactureBrut"></div>
            </div>
            <div class="net-reel-item minus">
                <div class="net-reel-label"><i class="fas fa-shield-heart"></i> Rétrocédé assurances</div>
                <div class="net-reel-value" id="valRetroAssurance">—</div>
            </div>
            <div class="net-reel-item minus">
                <div class="net-reel-label"><i class="fas fa-user-doctor"></i> Rétrocédé médecins</div>
                <div class="net-reel-value" id="valRetroMedecin">—</div>
            </div>
            <div class="net-reel-item final">
                <div class="net-reel-label">Net réel laboratoire</div>
                <div class="net-reel-value" id="valNetReel">—</div>
                <div class="net-reel-variation" id="varNetReel"></div>
            </div>
        </div>
    </div>

    <!-- Cash-flow réel -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="cashflow-card">
                <div class="cashflow-icon" style="background:#E3F2FD;color:#1E88E5;"><i class="fas fa-file-invoice"></i></div>
                <div class="cashflow-label">Facturé (brut)</div>
                <div class="cashflow-value" id="cfFactureBrut">—</div>
                <div class="cashflow-variation" id="cfVarFactureBrut"></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="cashflow-card">
                <div class="cashflow-icon" style="background:#E8F5E9;color:#2E7D32;"><i class="fas fa-cash-register"></i></div>
                <div class="cashflow-label">Réellement encaissé</div>
                <div class="cashflow-value" id="cfEncaisse">—</div>
                <div class="cashflow-variation" id="cfVarEncaisse"></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="cashflow-card">
                <div class="cashflow-icon" style="background:#FFF8E1;color:#F57F17;"><i class="fas fa-hourglass-half"></i></div>
                <div class="cashflow-label">En attente de paiement</div>
                <div class="cashflow-value" id="cfEnAttente">—</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="cashflow-card">
                <div class="cashflow-icon" style="background:#F3E5F5;color:#8E24AA;"><i class="fas fa-receipt"></i></div>
                <div class="cashflow-label">Nb factures / paiements</div>
                <div class="cashflow-value" id="cfNbFactures">—</div>
            </div>
        </div>
    </div>

    <!-- Répartition par catégorie + Patients/Demandes -->
    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="card-drouot">
                <h6 class="card-section-title mb-3">
                    <i class="fas fa-layer-group text-primary me-2"></i>
                    Revenus par catégorie d'examen
                </h6>
                <canvas id="chartCategories" height="160"></canvas>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card-drouot h-100">
                <h6 class="card-section-title mb-3">
                    <i class="fas fa-chart-simple text-primary me-2"></i>
                    Activité de la période
                </h6>
                <div class="activite-item">
                    <span class="activite-label">Patients enregistrés</span>
                    <span class="activite-value" id="actPatients">—</span>
                    <span class="activite-var" id="actVarPatients"></span>
                </div>
                <div class="activite-item">
                    <span class="activite-label">Demandes créées</span>
                    <span class="activite-value" id="actDemandes">—</span>
                    <span class="activite-var" id="actVarDemandes"></span>
                </div>
                <div class="activite-item">
                    <span class="activite-label">Taux de validation</span>
                    <span class="activite-value" id="actTauxValidation">—</span>
                    <span class="activite-var" id="actVarTaux"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pilotage opérationnel — temps par étape + charge par rôle -->
    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="card-drouot">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="card-section-title">
                        <i class="fas fa-route text-primary me-2"></i>
                        Temps moyen par étape du pipeline
                    </h6>
                    <span class="goulot-tag" id="goulotTag" style="display:none;">
                        <i class="fas fa-triangle-exclamation"></i> Goulot : <span id="goulotLabel"></span>
                    </span>
                </div>
                <div id="etapesList"></div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card-drouot h-100">
                <h6 class="card-section-title mb-3">
                    <i class="fas fa-users-gear text-primary me-2"></i>
                    Charge de travail actuelle
                </h6>
                <a href="<?= BASE_URL ?>technicien/echantillons" class="charge-role-item">
                    <div class="charge-role-icon" style="background:#E3F2FD;color:#1E88E5;"><i class="fas fa-vial"></i></div>
                    <div class="charge-role-info">
                        <div class="charge-role-label">Technicien — échantillons à traiter</div>
                    </div>
                    <div class="charge-role-count" id="chargeTechnicien">—</div>
                </a>
                <a href="<?= BASE_URL ?>biologiste/validations" class="charge-role-item">
                    <div class="charge-role-icon" style="background:#FFEBEE;color:#C62828;"><i class="fas fa-microscope"></i></div>
                    <div class="charge-role-info">
                        <div class="charge-role-label">Biologiste — résultats à valider</div>
                    </div>
                    <div class="charge-role-count" id="chargeBiologiste">—</div>
                </a>
                <a href="<?= BASE_URL ?>caissier/factures" class="charge-role-item">
                    <div class="charge-role-icon" style="background:#FFF8E1;color:#F57F17;"><i class="fas fa-cash-register"></i></div>
                    <div class="charge-role-info">
                        <div class="charge-role-label">Caissier — factures impayées</div>
                    </div>
                    <div class="charge-role-count" id="chargeCaissier">—</div>
                </a>
            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card-dir">
            <div class="kpi-dir-icon" style="background:#E3F2FD;">
                <i class="fas fa-hospital-user" style="color:#1E88E5;"></i>
            </div>
            <div class="kpi-dir-body">
                <div class="kpi-dir-val">
                    <?= number_format($kpi['patients_total'], 0, ',', ' ') ?>
                </div>
                <div class="kpi-dir-label">Patients enregistrés</div>
                <div class="kpi-dir-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    +<?= $kpi['patients_mois'] ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card-dir">
            <div class="kpi-dir-icon" style="background:#E8F5E9;">
                <i class="fas fa-flask" style="color:#2E7D32;"></i>
            </div>
            <div class="kpi-dir-body">
                <div class="kpi-dir-val">
                    <?= number_format($kpi['examens_total'], 0, ',', ' ') ?>
                </div>
                <div class="kpi-dir-label">Examens réalisés</div>
                <div class="kpi-dir-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    +<?= $kpi['examens_mois'] ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card-dir">
            <div class="kpi-dir-icon" style="background:#FFF8E1;">
                <i class="fas fa-money-bill-wave" style="color:#F9A825;"></i>
            </div>
            <div class="kpi-dir-body">
                <div class="kpi-dir-val" style="font-size:1.1rem;">
                    <?= formatFcfa((float)$kpi['revenus_total']) ?>
                </div>
                <div class="kpi-dir-label">Revenus totaux</div>
                <div class="kpi-dir-trend trend-up">
                    <i class="fas fa-arrow-up"></i>
                    <?= formatFcfa((float)$kpi['revenus_mois']) ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card-dir">
            <div class="kpi-dir-icon" style="background:#E8F5E9;">
                <i class="fas fa-chart-line" style="color:#2E7D32;"></i>
            </div>
            <div class="kpi-dir-body">
                <div class="kpi-dir-val" style="color:#2E7D32;">
                    <?= $kpi['taux_validation'] ?>%
                </div>
                <div class="kpi-dir-label">Taux de validation</div>
                <div class="kpi-dir-trend trend-up">
                    <i class="fas fa-check"></i>
                    <?= $totalCompleted ?> / <?= $totalDemandes ?> analyses
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="kpi-mini">
            <i class="fas fa-calendar-day" style="color:#1E88E5;"></i>
            <div>
                <div class="kpi-mini-val">
                    <?= formatFcfa((float)$kpi['revenus_jour']) ?>
                </div>
                <div class="kpi-mini-label">Revenus aujourd'hui</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="kpi-mini">
            <i class="fas fa-users-cog" style="color:#2E7D32;"></i>
            <div>
                <div class="kpi-mini-val"><?= $kpi['users'] ?></div>
                <div class="kpi-mini-label">Personnel actif</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="kpi-mini <?= $kpi['urgences'] > 0 ? 'kpi-mini-urgent' : '' ?>">
            <i class="fas fa-clock" style="color:<?= $kpi['urgences'] > 0 ? '#C62828' : '#F9A825' ?>;"></i>
            <div>
                <div class="kpi-mini-val" style="color:<?= $kpi['urgences'] > 0 ? '#C62828' : 'inherit' ?>">
                    <?= $kpi['urgences'] ?>
                </div>
                <div class="kpi-mini-label">Validations en attente</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">

    <div class="col-xl-8">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Évolution des revenus — 6 derniers mois
                </h6>
                <a href="<?= BASE_URL ?>directeur/revenus" class="btn-link-drouot">
                    Détail <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <canvas id="chartRevenus" height="100"></canvas>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card-drouot h-100">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-chart-pie text-primary me-2"></i>
                Modes de paiement
            </h6>
            <?php if (empty($paiements)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-chart-pie fa-2x mb-2" style="color:#dde3f0;"></i>
                    <p>Aucune donnée</p>
                </div>
            <?php else: ?>
                <canvas id="chartPaiements" height="180"></canvas>
                <div class="mt-3">
                    <?php
                    $modeLabels = [
                        'cash'      => '💵 Espèces',
                        'momo'      => '📱 Mobile Money',
                        'card'      => '💳 Carte',
                        'insurance' => '🏥 Assurance',
                    ];
                    $modeColors = ['#1E88E5', '#2E7D32', '#F9A825', '#C62828'];
                    foreach ($paiements as $i => $p):
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div style="width:10px;height:10px;border-radius:50%;background:<?= $modeColors[$i % 4] ?>;flex-shrink:0;"></div>
                        <span style="font-size:.78rem;color:#6b7a99;flex:1;">
                            <?= $modeLabels[$p['mode_paiement']] ?? $p['mode_paiement'] ?>
                        </span>
                        <span style="font-size:.78rem;font-weight:500;">
                            <?= $p['nb'] ?> · <?= formatFcfa((float)$p['total']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="row g-3 mb-4">

    <div class="col-xl-5">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-trophy text-warning me-2"></i>
                    Top examens réalisés
                </h6>
                <a href="<?= BASE_URL ?>directeur/examens" class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php if (empty($topExamens)): ?>
                <div class="text-center py-4 text-muted">
                    <p>Aucune donnée disponible.</p>
                </div>
            <?php else: ?>
            <?php
            $maxTotal = max(array_column($topExamens, 'total'));
            $examColors = ['#1E88E5','#2E7D32','#F9A825','#C62828','#7B1FA2','#00838F'];
            ?>
            <div class="top-examens-list">
                <?php foreach ($topExamens as $i => $ex): ?>
                <?php $pct = $maxTotal > 0 ? round($ex['total'] / $maxTotal * 100) : 0; ?>
                <div class="top-exam-item">
                    <div class="top-exam-rank"><?= $i + 1 ?></div>
                    <div class="top-exam-info">
                        <div class="top-exam-nom"><?= htmlspecialchars($ex['nom']) ?></div>
                        <div class="top-exam-bar-wrap">
                            <div class="top-exam-bar">
                                <div class="top-exam-fill" style="width:<?= $pct ?>%;background:<?= $examColors[$i % 6] ?>"></div>
                            </div>
                        </div>
                    </div>
                    <div class="top-exam-stats">
                        <div class="top-exam-count"><?= $ex['total'] ?></div>
                        <div class="top-exam-rev"><?= formatFcfa((float)$ex['revenus']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="row g-3 h-100">

            <div class="col-12">
                <div class="card-drouot">
                    <h6 class="card-section-title mb-3">
                        <i class="fas fa-gauge-high text-primary me-2"></i>
                        Performance du laboratoire
                    </h6>
                    <div class="perf-list">
                        <?php
                        $perfItems = [
                            ['label' => 'Qualité des résultats', 'val' => $perf['qualite'], 'color' => '#2E7D32'],
                            ['label' => 'Résultats dans les normes', 'val' => $perf['satisfaction'], 'color' => '#1E88E5'],
                            ['label' => 'Respect des délais', 'val' => $perf['delais'], 'color' => '#F9A825'],
                        ];
                        foreach ($perfItems as $p):
                        ?>
                        <div class="perf-item">
                            <div class="perf-label"><?= $p['label'] ?></div>
                            <div class="perf-bar-wrap">
                                <div class="perf-bar">
                                    <div class="perf-fill" style="width:<?= $p['val'] ?>%;background:<?= $p['color'] ?>"></div>
                                </div>
                            </div>
                            <div class="perf-val" style="color:<?= $p['color'] ?>"><?= $p['val'] ?>%</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card-drouot">
                    <h6 class="card-section-title mb-3">
                        <i class="fas fa-triangle-exclamation text-warning me-2"></i>
                        Alertes importantes
                    </h6>
                    <?php if (empty($alertes)): ?>
                        <div class="text-center py-2">
                            <i class="fas fa-check-circle" style="color:#2E7D32;font-size:1.5rem;"></i>
                            <p class="text-muted mt-1" style="font-size:.82rem;">Aucune alerte active.</p>
                        </div>
                    <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($alertes as $alerte): ?>
                        <div class="dir-alerte dir-alerte-<?= $alerte['type'] ?>">
                            <i class="fas <?= $alerte['icon'] ?>"></i>
                            <span><?= htmlspecialchars($alerte['message']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ══════════════════════════════════════
     CRÉANCES ASSURANCES + COMMISSIONS MÉDECINS (détail)
══════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Créances assurances -->
    <div class="col-xl-6">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-shield-heart text-primary me-2"></i>
                    Créances assurances
                </h6>
                <a href="<?= BASE_URL ?>admin/assurances" class="btn-link-drouot">
                    Gérer <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="charge-stats-row">
                <div class="charge-stat due">
                    <div class="charge-stat-val"><?= formatFcfa((float)$creancesGlobal['total_du']) ?></div>
                    <div class="charge-stat-label"><?= $creancesGlobal['nb_dues'] ?> due(s)</div>
                </div>
                <div class="charge-stat paid">
                    <div class="charge-stat-val"><?= formatFcfa((float)$creancesGlobal['total_paye']) ?></div>
                    <div class="charge-stat-label">Déjà payées</div>
                </div>
            </div>

            <?php if (empty($creancesParCompagnie)): ?>
            <p class="text-muted text-center py-3" style="font-size:.82rem;">Aucune créance enregistrée.</p>
            <?php else: ?>
            <div class="charge-list">
                <?php foreach ($creancesParCompagnie as $c): ?>
                <div class="charge-line">
                    <span class="charge-nom"><?= htmlspecialchars($c['nom']) ?></span>
                    <span class="charge-amounts">
                        <?php if ($c['du'] > 0): ?>
                        <span class="tag-due"><?= formatFcfa((float)$c['du']) ?></span>
                        <?php endif; ?>
                        <?php if ($c['paye'] > 0): ?>
                        <span class="tag-paid"><?= formatFcfa((float)$c['paye']) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Commissions médecins -->
    <div class="col-xl-6">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-user-doctor text-primary me-2"></i>
                    Commissions médecins
                </h6>
                <a href="<?= BASE_URL ?>admin/medecins" class="btn-link-drouot">
                    Gérer <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="charge-stats-row">
                <div class="charge-stat due">
                    <div class="charge-stat-val"><?= formatFcfa((float)$commissionsGlobal['total_du']) ?></div>
                    <div class="charge-stat-label"><?= $commissionsGlobal['nb_dues'] ?> due(s)</div>
                </div>
                <div class="charge-stat paid">
                    <div class="charge-stat-val"><?= formatFcfa((float)$commissionsGlobal['total_paye']) ?></div>
                    <div class="charge-stat-label">Déjà payées</div>
                </div>
            </div>

            <?php if (empty($commissionsParMedecin)): ?>
            <p class="text-muted text-center py-3" style="font-size:.82rem;">Aucune commission enregistrée.</p>
            <?php else: ?>
            <div class="charge-list">
                <?php foreach ($commissionsParMedecin as $m): ?>
                <div class="charge-line">
                    <span class="charge-nom"><?= htmlspecialchars($m['nom']) ?></span>
                    <span class="charge-amounts">
                        <?php if ($m['du'] > 0): ?>
                        <span class="tag-due"><?= formatFcfa((float)$m['du']) ?></span>
                        <?php endif; ?>
                        <?php if ($m['paye'] > 0): ?>
                        <span class="tag-paid"><?= formatFcfa((float)$m['paye']) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<div class="card-drouot">
    <p class="card-section-title mb-3">
        <i class="fas fa-bolt text-warning me-2"></i>
        Navigation rapide
    </p>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>directeur/revenus" class="raccourci-btn raccourci-green">
            <i class="fas fa-money-bill-wave"></i><span>Revenus détaillés</span>
        </a>
        <a href="<?= BASE_URL ?>directeur/examens" class="raccourci-btn raccourci-blue">
            <i class="fas fa-flask"></i><span>Statistiques examens</span>
        </a>
        <a href="<?= BASE_URL ?>directeur/rapports" class="raccourci-btn raccourci-gray">
            <i class="fas fa-file-waveform"></i><span>Rapports globaux</span>
        </a>
        <a href="<?= BASE_URL ?>dashboard/admin" class="raccourci-btn raccourci-gray">
            <i class="fas fa-gauge"></i><span>Dashboard Admin</span>
        </a>
    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; }
.btn-link-drouot { font-size:.78rem; color:#1E88E5; text-decoration:none; display:flex; align-items:center; gap:4px; }
.btn-link-drouot:hover { color:#0D47A1; }

.kpi-card-dir { background:#fff; border-radius:12px; border:1px solid #dde3f0; padding:16px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(13,71,161,.05); height:100%; }
.kpi-dir-icon { width:50px; height:50px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
.kpi-dir-val { font-size:1.5rem; font-weight:700; color:#1a2340; line-height:1; }
.kpi-dir-label { font-size:.75rem; color:#6b7a99; margin-top:3px; }
.kpi-dir-trend { font-size:.72rem; margin-top:4px; display:flex; align-items:center; gap:3px; }
.trend-up   { color:#2E7D32; }
.trend-down { color:#C62828; }
.trend-warn { color:#F9A825; }

.kpi-mini { background:#fff; border-radius:12px; border:1px solid #dde3f0; padding:14px 16px; display:flex; align-items:center; gap:12px; box-shadow:0 2px 8px rgba(13,71,161,.05); }
.kpi-mini i { font-size:1.4rem; flex-shrink:0; }
.kpi-mini-val { font-size:1.1rem; font-weight:700; color:#1a2340; }
.kpi-mini-label { font-size:.75rem; color:#6b7a99; }
.kpi-mini-urgent { border-color:#FFCDD2; background:#FFF5F5; }

.top-examens-list { display:flex; flex-direction:column; gap:10px; }
.top-exam-item { display:flex; align-items:center; gap:10px; }
.top-exam-rank { width:22px; height:22px; border-radius:50%; background:#E3F2FD; color:#1565C0; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; flex-shrink:0; }
.top-exam-info { flex:1; min-width:0; }
.top-exam-nom { font-size:.82rem; font-weight:500; color:#1a2340; margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.top-exam-bar { height:6px; background:#f0f2f8; border-radius:20px; overflow:hidden; }
.top-exam-fill { height:100%; border-radius:20px; transition:width .6s ease; }
.top-exam-stats { text-align:right; flex-shrink:0; }
.top-exam-count { font-size:.88rem; font-weight:700; color:#1a2340; }
.top-exam-rev { font-size:.7rem; color:#6b7a99; }

.perf-list { display:flex; flex-direction:column; gap:12px; }
.perf-item { display:flex; align-items:center; gap:10px; font-size:.82rem; }
.perf-label { min-width:160px; color:#6b7a99; }
.perf-bar-wrap { flex:1; }
.perf-bar { height:10px; background:#f0f2f8; border-radius:20px; overflow:hidden; }
.perf-fill { height:100%; border-radius:20px; transition:width .6s ease; }
.perf-val { min-width:40px; text-align:right; font-weight:700; }

.dir-alerte { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; font-size:.83rem; font-weight:500; }
.dir-alerte i { font-size:.95rem; }
.dir-alerte-warning { background:#FFF8E1; color:#F57F17; }
.dir-alerte-info { background:#E3F2FD; color:#1565C0; }
.dir-alerte-danger { background:#FFEBEE; color:#C62828; }

.raccourci-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border-radius:10px; font-size:.85rem; font-weight:500; text-decoration:none; transition:all .2s; border:1.5px solid transparent; }
.raccourci-green { background:#E8F5E9; color:#2E7D32; border-color:#C8E6C9; }
.raccourci-green:hover { background:#2E7D32; color:#fff; border-color:#2E7D32; }
.raccourci-blue { background:#E3F2FD; color:#1565C0; border-color:#BBDEFB; }
.raccourci-blue:hover { background:#1E88E5; color:#fff; border-color:#1E88E5; }
.raccourci-gray { background:#F4F6F9; color:#1a2340; border-color:#dde3f0; }
.raccourci-gray:hover { background:#0D47A1; color:#fff; border-color:#0D47A1; }

.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }

@media (max-width:768px) { .perf-label { min-width:120px; } }

/* Sélecteur de période */
.periode-selector {
    background:#fff; border-radius:14px; padding:14px 18px;
    display:flex; align-items:center; gap:16px; flex-wrap:wrap;
    box-shadow:0 2px 8px rgba(13,71,161,.05); border:1px solid #dde3f0;
}
.periode-buttons { display:flex; gap:6px; flex-wrap:wrap; }
.periode-btn {
    padding:7px 16px; border-radius:9px; border:1.5px solid #dde3f0;
    background:#fff; color:#1a2340; font-size:.83rem; font-weight:500;
    cursor:pointer; transition:all .2s; font-family:'DM Sans',sans-serif;
}
.periode-btn:hover { border-color:#1E88E5; color:#1E88E5; }
.periode-btn.active { background:#1E88E5; color:#fff; border-color:#1E88E5; }

.periode-custom-dates { display:flex; align-items:center; gap:8px; }
.periode-date-input {
    padding:6px 10px; border:1.5px solid #dde3f0; border-radius:8px;
    font-size:.82rem; font-family:'DM Sans',sans-serif;
}
.periode-apply-btn {
    padding:6px 14px; border-radius:8px; border:none; cursor:pointer;
    background:#2E7D32; color:#fff; font-size:.82rem; font-weight:500;
}

.periode-current {
    margin-left:auto; font-size:.8rem; color:#6b7a99;
    display:flex; align-items:center; gap:6px;
}
.periode-current i { color:#1E88E5; }

/* Loading */
.finance-loading {
    text-align:center; padding:40px 0; color:#6b7a99; font-size:.9rem;
}

/* Net réel — tag période */
.net-reel-period-tag {
    margin-left:auto; font-size:.7rem; font-weight:400; opacity:.7;
    background:rgba(255,255,255,.1); padding:3px 10px; border-radius:20px;
}
.net-reel-variation { font-size:.7rem; margin-top:3px; font-weight:600; }
.net-reel-variation.up { color:#81C784; }
.net-reel-variation.down { color:#FFAB91; }

/* Cashflow cards */
.cashflow-card {
    background:#fff; border-radius:14px; padding:16px;
    border:1px solid #dde3f0; box-shadow:0 2px 8px rgba(13,71,161,.05);
    height:100%;
}
.cashflow-icon {
    width:38px; height:38px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1.05rem; margin-bottom:10px;
}
.cashflow-label { font-size:.74rem; color:#6b7a99; margin-bottom:4px; }
.cashflow-value { font-size:1.15rem; font-weight:700; color:#1a2340; }
.cashflow-variation { font-size:.72rem; margin-top:4px; font-weight:600; }
.cashflow-variation.up { color:#2E7D32; }
.cashflow-variation.down { color:#C62828; }

/* Activité période */
.activite-item {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 0; border-bottom:1px solid #f0f2f8;
}
.activite-item:last-child { border-bottom:none; }
.activite-label { font-size:.83rem; color:#6b7a99; flex:1; }
.activite-value { font-size:1rem; font-weight:700; color:#1a2340; margin-right:8px; }
.activite-var { font-size:.72rem; font-weight:600; min-width:50px; text-align:right; }
.activite-var.up { color:#2E7D32; }
.activite-var.down { color:#C62828; }

/* Goulot tag */
.goulot-tag {
    font-size:.72rem; font-weight:600; color:#C62828;
    background:#FFEBEE; padding:4px 10px; border-radius:20px;
}

/* Étapes pipeline */
.etape-item { margin-bottom:14px; }
.etape-item:last-child { margin-bottom:0; }
.etape-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; }
.etape-label { font-size:.83rem; color:#1a2340; font-weight:500; }
.etape-value { font-size:.85rem; font-weight:700; color:#1E88E5; }
.etape-value.goulot { color:#C62828; }
.etape-bar { height:8px; background:#f0f2f8; border-radius:20px; overflow:hidden; }
.etape-fill { height:100%; border-radius:20px; background:#1E88E5; transition:width .6s ease; }
.etape-fill.goulot { background:#C62828; }
.etape-na { font-size:.75rem; color:#b0b8cc; font-style:italic; }

/* Charge par rôle */
.charge-role-item {
    display:flex; align-items:center; gap:12px;
    padding:12px 0; border-bottom:1px solid #f0f2f8;
    text-decoration:none; transition:opacity .2s;
}
.charge-role-item:last-child { border-bottom:none; }
.charge-role-item:hover { opacity:.75; }
.charge-role-icon {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center;
    font-size:1rem; flex-shrink:0;
}
.charge-role-label { font-size:.82rem; color:#1a2340; font-weight:500; }
.charge-role-count {
    font-size:1.1rem; font-weight:700; color:#1a2340;
    min-width:30px; text-align:right;
}
.net-reel-box {
    background:linear-gradient(135deg, #1a2340, #0D2150);
    border-radius:16px; padding:22px; color:#fff;
}
.net-reel-header {
    font-size:.88rem; font-weight:600; opacity:.9;
    display:flex; align-items:center; gap:8px; margin-bottom:16px;
}
.net-reel-grid {
    display:grid; grid-template-columns:repeat(4, 1fr); gap:16px;
}
.net-reel-item { }
.net-reel-label {
    font-size:.72rem; opacity:.75; margin-bottom:4px;
    display:flex; align-items:center; gap:5px;
}
.net-reel-value { font-size:1.15rem; font-weight:700; }
.net-reel-item.minus .net-reel-value { color:#FFAB91; }
.net-reel-item.final {
    border-left:2px solid rgba(255,255,255,.2); padding-left:16px;
}
.net-reel-item.final .net-reel-value { color:#81C784; font-size:1.3rem; }

@media (max-width:900px) {
    .net-reel-grid { grid-template-columns:repeat(2, 1fr); gap:14px; }
    .net-reel-item.final { border-left:none; padding-left:0; grid-column:1 / -1; border-top:1px solid rgba(255,255,255,.2); padding-top:12px; }
}

/* Créances / commissions */
.charge-stats-row {
    display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;
}
.charge-stat {
    border-radius:10px; padding:12px; text-align:center;
}
.charge-stat.due  { background:#FFF8E1; }
.charge-stat.paid { background:#E8F5E9; }
.charge-stat-val { font-size:1.05rem; font-weight:700; }
.charge-stat.due .charge-stat-val  { color:#F57F17; }
.charge-stat.paid .charge-stat-val { color:#2E7D32; }
.charge-stat-label { font-size:.72rem; color:#6b7a99; margin-top:2px; }

.charge-list { display:flex; flex-direction:column; }
.charge-line {
    display:flex; align-items:center; justify-content:space-between;
    padding:8px 0; border-bottom:1px solid #f0f2f8; font-size:.82rem;
}
.charge-line:last-child { border-bottom:none; }
.charge-nom { color:#1a2340; font-weight:500; }
.charge-amounts { display:flex; gap:6px; }
.tag-due  { background:#FFF8E1; color:#F57F17; padding:2px 8px; border-radius:6px; font-size:.74rem; font-weight:600; }
.tag-paid { background:#E8F5E9; color:#2E7D32; padding:2px 8px; border-radius:6px; font-size:.74rem; font-weight:600; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const revenusLabels = <?= json_encode(array_column($revenusChart, 'mois')) ?>;
const revenusData   = <?= json_encode(array_values(array_column($revenusChart, 'total'))) ?>;

new Chart(document.getElementById('chartRevenus').getContext('2d'), {
    type: 'bar',
    data: {
        labels: revenusLabels,
        datasets: [
            {
                label: 'Revenus (FCFA)',
                data: revenusData,
                backgroundColor: 'rgba(30,136,229,0.15)',
                borderColor: '#1E88E5',
                borderWidth: 2,
                borderRadius: 6,
            },
            {
                label: 'Patients',
                data: <?= json_encode($patientsChart) ?>,
                type: 'line',
                borderColor: '#2E7D32',
                backgroundColor: 'rgba(46,125,50,0.05)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#2E7D32',
                fill: true,
                tension: 0.4,
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { font: { size: 12 }, usePointStyle: true } },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        if (ctx.datasetIndex === 0) {
                            return ' ' + Number(ctx.raw).toLocaleString('fr-FR') + ' FCFA';
                        }
                        return ' ' + ctx.raw + ' patients';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f0f2f8' },
                ticks: {
                    callback: v => {
                        if (v >= 1000000) return (v/1000000).toFixed(1) + 'M';
                        if (v >= 1000) return (v/1000).toFixed(0) + 'k';
                        return v;
                    }
                }
            },
            y1: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { color: '#2E7D32' } },
            x: { grid: { display: false } }
        }
    }
});

<?php if (!empty($paiements)): ?>
new Chart(document.getElementById('chartPaiements').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(
            fn($p) => ['cash' => 'Espèces', 'momo' => 'Mobile Money', 'card' => 'Carte', 'insurance' => 'Assurance'][$p['mode_paiement']] ?? $p['mode_paiement'],
            $paiements
        )) ?>,
        datasets: [{
            data: <?= json_encode(array_column($paiements, 'nb')) ?>,
            backgroundColor: ['#1E88E5','#2E7D32','#F9A825','#C62828'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: { responsive: true, cutout: '65%', plugins: { legend: { display: false } } }
});
<?php endif; ?>
</script>

<!-- ══════════════════════════════════════
     DASHBOARD DIRECTEUR — PILOTAGE PAR PÉRIODE (AJAX)
══════════════════════════════════════ -->
<script>
let chartCategories = null;
let periodeActuelle = 'semaine';

function formatFcfaJs(amount) {
    amount = Number(amount) || 0;
    if (Math.abs(amount) >= 1000000) {
        return (amount / 1000000).toLocaleString('fr-FR', {maximumFractionDigits: 1}) + 'M FCFA';
    }
    return Math.round(amount).toLocaleString('fr-FR') + ' FCFA';
}

function renderVariation(elId, value, inverse = false) {
    const el = document.getElementById(elId);
    if (!el || value === null || value === undefined) { if (el) el.textContent = ''; return; }
    const positive = inverse ? value < 0 : value >= 0;
    const sign = value >= 0 ? '+' : '';
    el.className = (el.className.split(' ')[0] || '') + ' ' + (positive ? 'up' : 'down');
    el.innerHTML = `<i class="fas fa-arrow-${positive ? 'up' : 'down'}"></i> ${sign}${value}% vs N-1`;
}

function chargerDonneesFinancieres(periode, debut = null, fin = null) {
    document.getElementById('financeLoading').style.display = 'block';
    document.getElementById('financeContent').style.display = 'none';

    let url = `<?= BASE_URL ?>dashboard/directeur/data?periode=${periode}`;
    if (periode === 'custom' && debut && fin) {
        url += `&debut=${debut}&fin=${fin}`;
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            document.getElementById('financeLoading').style.display = 'none';
            document.getElementById('financeContent').style.display = 'block';
            appliquerDonnees(data);
        })
        .catch(() => {
            document.getElementById('financeLoading').innerHTML =
                '<span style="color:#C62828;"><i class="fas fa-triangle-exclamation"></i> Erreur de chargement des données.</span>';
        });
}

function appliquerDonnees(data) {
    const a = data.actuel;
    const v = data.variation;

    document.getElementById('periodeCurrentLabel').querySelector('span').textContent = data.periode_label;
    document.getElementById('netReelPeriodTag').textContent = data.periode_label;

    // Net réel
    document.getElementById('valFactureBrut').textContent = formatFcfaJs(a.cashflow.facture_brut);
    document.getElementById('valRetroAssurance').textContent = '- ' + formatFcfaJs(a.creances.due + a.creances.payee);
    document.getElementById('valRetroMedecin').textContent = '- ' + formatFcfaJs(a.commissions.due + a.commissions.payee);
    document.getElementById('valNetReel').textContent = formatFcfaJs(a.net_reel);
    renderVariation('varFactureBrut', v.facture_brut);
    renderVariation('varNetReel', v.net_reel);

    // Cash-flow
    document.getElementById('cfFactureBrut').textContent = formatFcfaJs(a.cashflow.facture_brut);
    document.getElementById('cfEncaisse').textContent = formatFcfaJs(a.cashflow.encaisse);
    document.getElementById('cfEnAttente').textContent = formatFcfaJs(a.cashflow.en_attente);
    document.getElementById('cfNbFactures').textContent = a.cashflow.nb_factures + ' / ' + a.cashflow.nb_paiements;
    renderVariation('cfVarFactureBrut', v.facture_brut);
    renderVariation('cfVarEncaisse', v.encaisse);

    // Activité
    document.getElementById('actPatients').textContent = a.nb_patients;
    document.getElementById('actDemandes').textContent = a.nb_demandes;
    document.getElementById('actTauxValidation').textContent = a.taux_validation + '%';
    renderVariation('actVarPatients', v.nb_patients);
    renderVariation('actVarDemandes', v.nb_demandes);
    renderVariation('actVarTaux', v.taux_validation);

    // Pilotage opérationnel — temps par étape
    const op = a.operationnel;
    const maxHeures = Math.max(...op.temps_par_etape.map(e => e.heures || 0), 1);
    const etapesHtml = op.temps_par_etape.map(e => {
        const isGoulot = op.goulot === e.label;
        if (e.heures === null) {
            return `<div class="etape-item">
                <div class="etape-top">
                    <span class="etape-label">${e.label}</span>
                    <span class="etape-na">Pas de données</span>
                </div>
                <div class="etape-bar"><div class="etape-fill" style="width:0%"></div></div>
            </div>`;
        }
        const pct = Math.round(e.heures / maxHeures * 100);
        const dureeTxt = e.heures < 1
            ? Math.round(e.heures * 60) + ' min'
            : (e.heures < 48 ? e.heures + ' h' : Math.round(e.heures / 24) + ' j');
        return `<div class="etape-item">
            <div class="etape-top">
                <span class="etape-label">${e.label}</span>
                <span class="etape-value ${isGoulot ? 'goulot' : ''}">${dureeTxt}</span>
            </div>
            <div class="etape-bar"><div class="etape-fill ${isGoulot ? 'goulot' : ''}" style="width:${pct}%"></div></div>
        </div>`;
    }).join('');
    document.getElementById('etapesList').innerHTML = etapesHtml;

    const goulotTag = document.getElementById('goulotTag');
    if (op.goulot) {
        goulotTag.style.display = 'inline-flex';
        document.getElementById('goulotLabel').textContent = op.goulot;
    } else {
        goulotTag.style.display = 'none';
    }

    // Charge par rôle (instantané)
    document.getElementById('chargeTechnicien').textContent = op.charge.technicien;
    document.getElementById('chargeBiologiste').textContent = op.charge.biologiste;
    document.getElementById('chargeCaissier').textContent = op.charge.caissier;

    // Graphique catégories
    const labels = a.par_categorie.map(c => c.categorie || 'Autres');
    const valeurs = a.par_categorie.map(c => parseFloat(c.total));

    if (chartCategories) chartCategories.destroy();
    chartCategories = new Chart(document.getElementById('chartCategories').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenus (FCFA)',
                data: valeurs,
                backgroundColor: 'rgba(30,136,229,0.7)',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + Number(ctx.raw).toLocaleString('fr-FR') + ' FCFA' } }
            },
            scales: {
                x: { beginAtZero: true, grid: { color: '#f0f2f8' } },
                y: { grid: { display: false } }
            }
        }
    });
}

// ── Gestion des boutons de période ───────────────────────
document.querySelectorAll('.periode-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.periode-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const periode = btn.dataset.periode;
        periodeActuelle = periode;

        const customZone = document.getElementById('periodeCustomDates');
        if (periode === 'custom') {
            customZone.style.display = 'flex';
            return; // attend que l'utilisateur clique "Appliquer"
        }
        customZone.style.display = 'none';
        chargerDonneesFinancieres(periode);
    });
});

document.getElementById('periodeApplyBtn')?.addEventListener('click', () => {
    const debut = document.getElementById('periodeDebut').value;
    const fin = document.getElementById('periodeFin').value;
    if (!debut || !fin) {
        alert('Veuillez sélectionner les deux dates.');
        return;
    }
    chargerDonneesFinancieres('custom', debut, fin);
});

// Chargement initial — période par défaut : cette semaine
chargerDonneesFinancieres('semaine');
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>