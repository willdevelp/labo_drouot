<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['directeur', 'admin']);
$db   = Database::getConnection();
$user = Auth::user();

// Revenus par mois (12 derniers mois)
$revenus12 = [];
for ($i = 11; $i >= 0; $i--) {
    $stmt = $db->prepare("
        SELECT
            DATE_FORMAT(
                DATE_SUB(NOW(), INTERVAL :i MONTH),
                '%b %Y'
            ) AS mois,
            COALESCE(SUM(montant_total), 0) AS total,
            COUNT(*) AS nb_transactions
        FROM factures
        WHERE statut = 'payee'
        AND MONTH(created_at) =
            MONTH(DATE_SUB(NOW(), INTERVAL :i2 MONTH))
        AND YEAR(created_at) =
            YEAR(DATE_SUB(NOW(), INTERVAL :i3 MONTH))
    ");
    $stmt->execute([':i'=>$i,':i2'=>$i,':i3'=>$i]);
    $revenus12[] = $stmt->fetch();
}

// Revenus par mode paiement
$parMode = $db->query("
    SELECT
        mode_paiement,
        COUNT(*) AS nb,
        SUM(montant) AS total
    FROM paiements
    WHERE statut = 'valide'
    GROUP BY mode_paiement
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);
// Total général
$totalGeneral = $db->query("
    SELECT COALESCE(SUM(montant_total),0)
    FROM factures WHERE statut='payee'
")->fetchColumn();

$totalMois = $db->query("
    SELECT COALESCE(SUM(montant_total),0)
    FROM factures
    WHERE statut='payee'
    AND MONTH(created_at)=MONTH(NOW())
    AND YEAR(created_at)=YEAR(NOW())
")->fetchColumn();

function formatFcfa(float $a): string {
    if ($a >= 1000000)
        return number_format($a/1000000,1,',',' ').'M FCFA';
    return number_format($a,0,',',' ').' FCFA';
}

include __DIR__ . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Revenus détaillés</h1>
        <p class="page-sub">
            Analyse financière — 12 derniers mois
        </p>
    </div>
    <a href="<?= BASE_URL ?>dashboard/directeur"
       class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<!-- KPI revenus -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="kpi-value" style="font-size:1.1rem;">
                    <?= formatFcfa((float)$totalGeneral) ?>
                </div>
                <div class="kpi-label">Revenus totaux</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-calendar-month"></i>
            </div>
            <div>
                <div class="kpi-value" style="font-size:1.1rem;">
                    <?= formatFcfa((float)$totalMois) ?>
                </div>
                <div class="kpi-label">Revenus ce mois</div>
            </div>
        </div>
    </div>
</div>

<!-- Graphique 12 mois -->
<div class="card-drouot mb-4">
    <h6 class="card-section-title mb-3">
        <i class="fas fa-chart-bar text-primary me-2"></i>
        Évolution mensuelle des revenus
    </h6>
    <canvas id="chartRevenus12" height="80"></canvas>
</div>

<!-- Répartition par mode -->
<div class="card-drouot mb-4">
    <h6 class="card-section-title mb-3">
        <i class="fas fa-wallet text-primary me-2"></i>
        Répartition par mode de paiement
    </h6>
    <?php if (empty($parMode)): ?>
        <p class="text-muted text-center py-3">
            Aucune donnée.
        </p>
    <?php else: ?>
    <table class="table-drouot">
        <thead>
            <tr>
                <th>Mode</th>
                <th>Transactions</th>
                <th>Total</th>
                <th>Part</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $modeLabels = [
                'cash'      => '💵 Espèces',
                'momo'      => '📱 Mobile Money',
                'card'      => '💳 Carte',
                'insurance' => '🏥 Assurance',
            ];
            $total = max(1,
                array_sum(array_column($parMode, 'total'))
            );
            foreach ($parMode as $m):
                $part = round($m['total'] / $total * 100);
            ?>
            <tr>
                <td style="font-weight:500;">
                    <?= $modeLabels[$m['mode_paiement']]
                        ?? $m['mode_paiement'] ?>
                </td>
                <td><?= $m['nb'] ?></td>
                <td style="font-weight:600;color:#1E88E5;">
                    <?= formatFcfa((float)$m['total']) ?>
                </td>
                <td>
                    <div style="display:flex;
                        align-items:center;gap:8px;">
                        <div style="flex:1;height:8px;
                            background:#f0f2f8;
                            border-radius:20px;overflow:hidden;">
                            <div style="width:<?= $part ?>%;
                                height:100%;background:#1E88E5;
                                border-radius:20px;"></div>
                        </div>
                        <span style="font-size:.78rem;
                            font-weight:600;min-width:32px;">
                            <?= $part ?>%
                        </span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Table mensuelle -->
<div class="card-drouot">
    <h6 class="card-section-title mb-3">
        <i class="fas fa-table text-primary me-2"></i>
        Détail mensuel
    </h6>
    <table class="table-drouot">
        <thead>
            <tr>
                <th>Mois</th>
                <th>Transactions</th>
                <th>Revenus</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_reverse($revenus12) as $r): ?>
            <tr>
                <td style="font-weight:500;">
                    <?= htmlspecialchars($r['mois']) ?>
                </td>
                <td><?= $r['nb_transactions'] ?></td>
                <td style="font-weight:600;color:#1E88E5;">
                    <?= formatFcfa((float)$r['total']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; }
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(
    document.getElementById('chartRevenus12').getContext('2d'),
    {
        type: 'bar',
        data: {
            labels: <?= json_encode(
                array_column($revenus12, 'mois')
            ) ?>,
            datasets: [{
                label: 'Revenus FCFA',
                data: <?= json_encode(
                    array_values(array_column($revenus12, 'total'))
                ) ?>,
                backgroundColor: 'rgba(30,136,229,0.15)',
                borderColor: '#1E88E5',
                borderWidth: 2,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f2f8' },
                    ticks: {
                        callback: v => {
                            if (v >= 1000000)
                                return (v/1000000).toFixed(1)+'M';
                            if (v >= 1000)
                                return (v/1000).toFixed(0)+'k';
                            return v;
                        }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    }
);
</script>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>