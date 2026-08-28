<?php
/**
 * Dashboard Administrateur — Laboratoire Drouot
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['admin']);

$db   = Database::getConnection();
$user = Auth::user();

// ── KPI ─────────────────────────────────────────────────
$kpi = [];

// Total utilisateurs actifs
$kpi['users'] = $db->query("
    SELECT COUNT(*) FROM users WHERE is_active = 1 AND deleted_at IS NULL
")->fetchColumn();

// Total patients
$kpi['patients'] = $db->query("
    SELECT COUNT(*) FROM patients
")->fetchColumn();

// Total examens
$kpi['examens'] = $db->query("
    SELECT COUNT(*) FROM demandes_examens
")->fetchColumn();

// Revenus total (factures payées)
$kpi['revenus'] = $db->query("
    SELECT COALESCE(SUM(montant_total), 0) FROM factures WHERE statut = 'paid'
")->fetchColumn();

// Nouveaux utilisateurs ce mois
$kpi['users_mois'] = $db->query("
    SELECT COUNT(*) FROM users
    WHERE MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// Nouveaux patients ce mois
$kpi['patients_mois'] = $db->query("
    SELECT COUNT(*) FROM patients
    WHERE MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// Nouveaux examens ce mois
$kpi['examens_mois'] = $db->query("
    SELECT COUNT(*) FROM demandes_examens
    WHERE MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// Revenus ce mois
$kpi['revenus_mois'] = $db->query("
    SELECT COALESCE(SUM(montant_total), 0) FROM factures
    WHERE statut = 'paid'
    AND MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// Urgences actives
$kpi['urgences'] = $db->query("
    SELECT COUNT(*) FROM demandes_examens
    WHERE status = 'processing'
")->fetchColumn();

// ── GRAPHIQUE ACTIVITÉ (6 derniers mois) ────────────────
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $stmt = $db->prepare("
        SELECT
            DATE_FORMAT(DATE_SUB(NOW(), INTERVAL :i MONTH), '%b') AS mois,
            COUNT(DISTINCT p.id) AS patients,
            COUNT(DISTINCT d.id) AS examens
        FROM patients p
        LEFT JOIN demandes_examens d
            ON MONTH(d.created_at) = MONTH(DATE_SUB(NOW(), INTERVAL :i2 MONTH))
            AND YEAR(d.created_at)  = YEAR(DATE_SUB(NOW(), INTERVAL :i3 MONTH))
        WHERE MONTH(p.created_at) = MONTH(DATE_SUB(NOW(), INTERVAL :i4 MONTH))
        AND YEAR(p.created_at)   = YEAR(DATE_SUB(NOW(), INTERVAL :i5 MONTH))
    ");
    $stmt->execute([':i'=>$i,':i2'=>$i,':i3'=>$i,':i4'=>$i,':i5'=>$i]);
    $chartData[] = $stmt->fetch();
}

// ── RÉPARTITION EXAMENS PAR CATÉGORIE ───────────────────
$repartition = $db->query("
    SELECT e.categorie, COUNT(dei.id) AS total
    FROM demande_examens_items dei
    JOIN examens e ON dei.examen_id = e.id
    GROUP BY e.categorie
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// ── ACTIVITÉS RÉCENTES (audit_logs) ─────────────────────
$activites = $db->query("
    SELECT a.action, a.table_name, a.created_at, u.name
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 6
")->fetchAll();

// ── ALERTES ─────────────────────────────────────────────
$alertes = [];

// Stock faible
$stocksFaibles = $db->query("
    SELECT COUNT(*) FROM stocks WHERE quantity <= threshold
")->fetchColumn();
if ($stocksFaibles > 0) {
    $alertes[] = [
        'type'    => 'warning',
        'icon'    => 'fa-box-open',
        'message' => "$stocksFaibles réactif(s) en stock faible",
        'url'     => 'admin/stocks',
    ];
}

// Validations en attente
$validationsAttente = $db->query("
    SELECT COUNT(*) FROM demandes_examens
    WHERE status = 'processing'
")->fetchColumn();
if ($validationsAttente > 0) {
    $alertes[] = [
        'type'    => 'info',
        'icon'    => 'fa-clock',
        'message' => "$validationsAttente analyse(s) en attente de validation",
        'url'     => 'admin/rapports',
    ];
}

// Factures impayées
$impayes = $db->query("
    SELECT COUNT(*) FROM factures WHERE statut = 'pending'
")->fetchColumn();
if ($impayes > 0) {
    $alertes[] = [
        'type'    => 'danger',
        'icon'    => 'fa-file-invoice-dollar',
        'message' => "$impayes facture(s) impayée(s)",
        'url'     => 'admin/rapports',
    ];
}

// ── FORMAT REVENUS ───────────────────────────────────────
function formatFcfa(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<!-- ══════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Tableau de bord</h1>
        <p class="page-sub">Bonjour, <?= htmlspecialchars($user['name']) ?> 👋 — Vue d'ensemble du système</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>admin/users" class="btn-primary-drouot">
            <i class="fas fa-user-plus"></i> Ajouter utilisateur
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     KPI CARDS — 4 colonnes
══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Utilisateurs -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-users-cog"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['users'] ?></div>
                <div class="kpi-label">Utilisateurs actifs</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-up"></i> +<?= $kpi['users_mois'] ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <!-- Patients -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-hospital-user"></i>
            </div>
            <div>
                <div class="kpi-value"><?= number_format($kpi['patients'], 0, ',', ' ') ?></div>
                <div class="kpi-label">Patients enregistrés</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-up"></i> +<?= $kpi['patients_mois'] ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <!-- Examens -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon orange">
                <i class="fas fa-flask"></i>
            </div>
            <div>
                <div class="kpi-value"><?= number_format($kpi['examens'], 0, ',', ' ') ?></div>
                <div class="kpi-label">Demandes d'examens</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-up"></i> +<?= $kpi['examens_mois'] ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <!-- Revenus -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon red">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="kpi-value" style="font-size:1.1rem;">
                    <?= formatFcfa((float)$kpi['revenus']) ?>
                </div>
                <div class="kpi-label">Revenus totaux</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-up"></i>
                    <?= formatFcfa((float)$kpi['revenus_mois']) ?> ce mois
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     GRAPHIQUE + RÉPARTITION
══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Graphique activité -->
    <div class="col-xl-8">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Activité globale — 6 derniers mois
                </h6>
            </div>
            <canvas id="chartActivite" height="100"></canvas>
        </div>
    </div>

    <!-- Répartition examens -->
    <div class="col-xl-4">
        <div class="card-drouot h-100">
            <div class="mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-chart-pie text-primary me-2"></i>
                    Répartition des examens
                </h6>
            </div>
            <?php if (empty($repartition)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-flask fa-2x mb-2"></i>
                    <p>Aucune donnée</p>
                </div>
            <?php else: ?>
                <canvas id="chartRepartition" height="200"></canvas>
                <div class="mt-3">
                    <?php
                    $colors = ['#1E88E5','#2E7D32','#F9A825','#C62828','#7B1FA2'];
                    foreach ($repartition as $i => $r):
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div style="width:10px;height:10px;border-radius:50%;background:<?= $colors[$i % 5] ?>;flex-shrink:0;"></div>
                        <span style="font-size:0.78rem;color:#6b7a99;">
                            <?= htmlspecialchars($r['categorie'] ?? 'Autre') ?>
                        </span>
                        <span style="margin-left:auto;font-size:0.78rem;font-weight:500;">
                            <?= $r['total'] ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     ACTIVITÉS RÉCENTES + ALERTES
══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Activités récentes -->
    <div class="col-xl-7">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-clock-rotate-left text-primary me-2"></i>
                    Activités récentes
                </h6>
                <a href="<?= BASE_URL ?>admin/audit" class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php if (empty($activites)): ?>
                <p class="text-muted text-center py-3">Aucune activité récente.</p>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($activites as $act): ?>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-content">
                            <span class="activity-text">
                                <?= htmlspecialchars($act['name'] ?? 'Système') ?>
                                — <?= htmlspecialchars($act['action'] ?? '') ?>
                                <?php if ($act['table_name']): ?>
                                    <span class="activity-table"><?= htmlspecialchars($act['table_name']) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="activity-time">
                                <?= $act['created_at']
                                    ? date('d/m H:i', strtotime($act['created_at']))
                                    : '' ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertes système -->
    <div class="col-xl-5">
        <div class="card-drouot">
            <div class="mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-triangle-exclamation text-warning me-2"></i>
                    Alertes système
                </h6>
            </div>
            <?php if (empty($alertes)): ?>
                <div class="text-center py-3">
                    <i class="fas fa-circle-check fa-2x text-success mb-2"></i>
                    <p class="text-muted" style="font-size:0.85rem;">Aucune alerte active</p>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($alertes as $alerte): ?>
                    <a href="<?= BASE_URL . $alerte['url'] ?>" class="alerte-item alerte-<?= $alerte['type'] ?>">
                        <i class="fas <?= $alerte['icon'] ?>"></i>
                        <span><?= htmlspecialchars($alerte['message']) ?></span>
                        <i class="fas fa-chevron-right ms-auto"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Accès rapides admin -->
            <div class="mt-4">
                <p class="card-section-title mb-2">
                    <i class="fas fa-bolt text-warning me-1"></i> Accès rapides
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>admin/users" class="quick-btn">
                        <i class="fas fa-users-cog"></i> Utilisateurs
                    </a>
                    <a href="<?= BASE_URL ?>admin/examens" class="quick-btn">
                        <i class="fas fa-flask"></i> Examens
                    </a>
                    <a href="<?= BASE_URL ?>admin/stocks" class="quick-btn">
                        <i class="fas fa-boxes-stacked"></i> Stocks
                    </a>
                    <a href="<?= BASE_URL ?>admin/audit" class="quick-btn">
                        <i class="fas fa-shield-halved"></i> Audit
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     STYLES SPÉCIFIQUES PAGE ADMIN
══════════════════════════════════════════════ -->
<style>
    .page-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #1a2340;
        margin: 0;
    }
    .page-sub {
        font-size: 0.85rem;
        color: #6b7a99;
        margin: 2px 0 0;
    }
    .card-section-title {
        font-size: 0.88rem;
        font-weight: 600;
        color: #1a2340;
        margin: 0;
    }
    .btn-link-drouot {
        font-size: 0.78rem;
        color: #1E88E5;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .btn-link-drouot:hover { color: #0D47A1; }

    /* Activités */
    .activity-list { display: flex; flex-direction: column; gap: 0; }
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f2f8;
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #1E88E5;
        flex-shrink: 0;
        margin-top: 5px;
    }
    .activity-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex: 1;
        gap: 8px;
    }
    .activity-text { font-size: 0.82rem; color: #1a2340; }
    .activity-table {
        background: #E3F2FD;
        color: #1565C0;
        padding: 1px 6px;
        border-radius: 4px;
        font-size: 0.72rem;
        margin-left: 4px;
    }
    .activity-time { font-size: 0.75rem; color: #6b7a99; white-space: nowrap; }

    /* Alertes */
    .alerte-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 0.83rem;
        font-weight: 500;
        text-decoration: none;
        transition: opacity 0.2s;
    }
    .alerte-item:hover { opacity: 0.85; }
    .alerte-item i:first-child { font-size: 0.95rem; }
    .alerte-warning { background: #FFF8E1; color: #F57F17; }
    .alerte-info    { background: #E3F2FD; color: #1565C0; }
    .alerte-danger  { background: #FFEBEE; color: #C62828; }

    /* Accès rapides */
    .quick-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        background: #F4F6F9;
        border: 1px solid #dde3f0;
        border-radius: 8px;
        font-size: 0.78rem;
        color: #1a2340;
        text-decoration: none;
        transition: all 0.2s;
    }
    .quick-btn:hover {
        background: #1E88E5;
        color: #fff;
        border-color: #1E88E5;
    }
    .quick-btn i { font-size: 0.8rem; }
</style>

<!-- ══════════════════════════════════════════════
     CHART.JS
══════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// Données PHP → JS
const chartLabels  = <?= json_encode(array_column($chartData, 'mois')) ?>;
const chartPatients = <?= json_encode(array_values(array_column($chartData, 'patients'))) ?>;
const chartExamens  = <?= json_encode(array_values(array_column($chartData, 'examens'))) ?>;

// Graphique Activité
const ctxLine = document.getElementById('chartActivite').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Patients',
                data: chartPatients,
                borderColor: '#1E88E5',
                backgroundColor: 'rgba(30,136,229,0.08)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#1E88E5',
                fill: true,
                tension: 0.4,
            },
            {
                label: 'Examens',
                data: chartExamens,
                borderColor: '#2E7D32',
                backgroundColor: 'rgba(46,125,50,0.06)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#2E7D32',
                fill: true,
                tension: 0.4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { font: { size: 12 }, usePointStyle: true } }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f0f2f8' } },
            x: { grid: { display: false } }
        }
    }
});

<?php if (!empty($repartition)): ?>
// Graphique Répartition
const ctxPie = document.getElementById('chartRepartition').getContext('2d');
new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($repartition, 'categorie')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($repartition, 'total')) ?>,
            backgroundColor: ['#1E88E5','#2E7D32','#F9A825','#C62828','#7B1FA2'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { display: false }
        }
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>