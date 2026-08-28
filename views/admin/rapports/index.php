<?php
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Rapports & Statistiques</h1>
        <p class="page-sub">Vue globale de l'activité</p>
    </div>
    <!-- Filtre période -->
    <form method="GET" class="d-flex gap-2 align-items-center">
        <select name="periode" class="filter-select"
                onchange="this.form.submit()">
            <option value="7"
                <?= ($_GET['periode']??'30')==='7'
                    ?'selected':'' ?>>
                7 derniers jours
            </option>
            <option value="30"
                <?= ($_GET['periode']??'30')==='30'
                    ?'selected':'' ?>>
                30 derniers jours
            </option>
            <option value="90"
                <?= ($_GET['periode']??'30')==='90'
                    ?'selected':'' ?>>
                3 derniers mois
            </option>
            <option value="365"
                <?= ($_GET['periode']??'30')==='365'
                    ?'selected':'' ?>>
                1 an
            </option>
        </select>
    </form>
</div>

<!-- Stats globales -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-hospital-user"></i>
            </div>
            <div>
                <div class="kpi-value">
                    <?= $stats['total_patients'] ?>
                </div>
                <div class="kpi-label">Patients</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-flask"></i>
            </div>
            <div>
                <div class="kpi-value">
                    <?= $stats['total_demandes'] ?>
                </div>
                <div class="kpi-label">Demandes</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-circle-check"></i>
            </div>
            <div>
                <div class="kpi-value">
                    <?= $stats['total_validations'] ?>
                </div>
                <div class="kpi-label">Validations</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon orange">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="kpi-value" style="font-size:1rem;">
                    <?= formatFcfa((float)$stats['revenus']) ?>
                </div>
                <div class="kpi-label">Revenus</div>
            </div>
        </div>
    </div>
</div>

<!-- Graphique revenus + Top examens -->
<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-chart-bar text-primary me-2"></i>
                Revenus quotidiens
            </h6>
            <canvas id="chartRevenus" height="120"></canvas>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-trophy text-warning me-2"></i>
                Top examens
            </h6>
            <?php if (empty($topExamens)): ?>
                <p class="text-muted text-center py-3">
                    Aucune donnée.
                </p>
            <?php else: ?>
            <?php
            $max = max(array_column($topExamens,'total'));
            $colors = ['#1E88E5','#2E7D32','#F9A825',
                       '#C62828','#7B1FA2','#00838F',
                       '#E65100','#AD1457'];
            foreach ($topExamens as $i => $ex):
                $pct = $max > 0
                    ? round($ex['total'] / $max * 100) : 0;
            ?>
            <div class="top-item">
                <div class="top-rank"
                     style="background:<?= $colors[$i%8] ?>20;
                     color:<?= $colors[$i%8] ?>;">
                    <?= $i+1 ?>
                </div>
                <div class="top-info">
                    <div class="top-nom">
                        <?= htmlspecialchars($ex['nom']) ?>
                    </div>
                    <div class="top-bar">
                        <div class="top-fill"
                             style="width:<?= $pct ?>%;
                             background:<?= $colors[$i%8] ?>">
                        </div>
                    </div>
                </div>
                <div class="top-stats">
                    <div style="font-weight:700;">
                        <?= $ex['total'] ?>
                    </div>
                    <div style="font-size:.7rem;color:#6b7a99;">
                        <?= formatFcfa((float)$ex['revenus']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Activité par rôle + Rapports -->
<div class="row g-3 mb-4">
    <div class="col-xl-4">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-users-cog text-primary me-2"></i>
                Activité par rôle
            </h6>
            <?php foreach ($activiteRole as $ar): ?>
            <?php
            $maxAct = max(array_column($activiteRole,'nb_actions'));
            $pct = $maxAct > 0
                ? round($ar['nb_actions']/$maxAct*100) : 0;
            $roleLabels = [
                'admin'      => 'Admin',
                'directeur'  => 'Directeur',
                'biologiste' => 'Biologiste',
                'technicien' => 'Technicien',
                'secretaire' => 'Secrétaire',
                'caissier'   => 'Caissier',
                'comptable'  => 'Comptable',
                'infirmier'  => 'Infirmier',
            ];
            ?>
            <div class="role-act-item">
                <span class="role-act-label">
                    <?= $roleLabels[$ar['role']] ?? $ar['role'] ?>
                </span>
                <div class="role-act-bar-wrap">
                    <div class="role-act-bar">
                        <div class="role-act-fill"
                             style="width:<?= $pct ?>%">
                        </div>
                    </div>
                </div>
                <span class="role-act-count">
                    <?= $ar['nb_actions'] ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card-drouot">
            <div class="d-flex align-items-center
                        justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-file-waveform
                               text-primary me-2"></i>
                    Rapports générés
                    <span class="count-badge">
                        <?= count($rapports) ?>
                    </span>
                </h6>
            </div>

            <?php if (empty($rapports)): ?>
                <p class="text-muted text-center py-3">
                    Aucun rapport sur cette période.
                </p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table-drouot">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Examens</th>
                            <th>Montant</th>
                            <th>Biologiste</th>
                            <th>Généré le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rapports as $r): ?>
                        <tr>
                            <td style="font-weight:500;
                                font-size:.85rem;">
                                <?= htmlspecialchars(
                                    $r['patient_nom']
                                ) ?>
                            </td>
                            <td style="font-size:.78rem;
                                color:#6b7a99;max-width:160px;">
                                <?= htmlspecialchars(
                                    mb_strimwidth(
                                        $r['examens'] ?? '—',
                                        0, 40, '...'
                                    )
                                ) ?>
                            </td>
                            <td style="font-weight:600;
                                color:#1E88E5;font-size:.82rem;">
                                <?= number_format(
                                    (float)$r['total_price'],
                                    0, ',', ' '
                                ) ?> F
                            </td>
                            <td style="font-size:.8rem;">
                                <?= htmlspecialchars(
                                    $r['biologiste_nom'] ?? '—'
                                ) ?>
                            </td>
                            <td style="font-size:.78rem;
                                color:#6b7a99;">
                                <?= $r['generated_at']
                                    ? date('d/m/Y H:i',
                                        strtotime($r['generated_at']))
                                    : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; display:flex; align-items:center; gap:8px; }
.filter-select { padding:.6rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; cursor:pointer; }
.count-badge { background:#E3F2FD; color:#1565C0; padding:2px 8px; border-radius:20px; font-size:.72rem; font-weight:600; }

/* Top examens */
.top-item { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
.top-rank { width:24px; height:24px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; flex-shrink:0; }
.top-info { flex:1; }
.top-nom  { font-size:.82rem; font-weight:500; color:#1a2340; margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.top-bar  { height:6px; background:#f0f2f8; border-radius:20px; overflow:hidden; }
.top-fill { height:100%; border-radius:20px; }
.top-stats { text-align:right; flex-shrink:0; font-size:.82rem; font-weight:700; color:#1a2340; }

/* Activité rôle */
.role-act-item { display:flex; align-items:center; gap:8px; margin-bottom:8px; font-size:.82rem; }
.role-act-label { min-width:80px; color:#6b7a99; }
.role-act-bar-wrap { flex:1; }
.role-act-bar { height:8px; background:#f0f2f8; border-radius:20px; overflow:hidden; }
.role-act-fill { height:100%; border-radius:20px; background:#1E88E5; }
.role-act-count { min-width:30px; text-align:right; font-weight:600; color:#1a2340; }

.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:9px 10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
new Chart(
    document.getElementById('chartRevenus').getContext('2d'),
    {
        type: 'bar',
        data: {
            labels: <?= json_encode(
                array_column($revenusJour, 'jour')
            ) ?>,
            datasets: [{
                label: 'Revenus FCFA',
                data: <?= json_encode(
                    array_values(
                        array_column($revenusJour, 'total')
                    )
                ) ?>,
                backgroundColor: 'rgba(30,136,229,0.15)',
                borderColor: '#1E88E5',
                borderWidth: 2,
                borderRadius: 4,
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
                x: {
                    grid: { display: false },
                    ticks: {
                        callback: function(val, idx) {
                            const d = this.getLabelForValue(val);
                            return d ? d.slice(5) : '';
                        }
                    }
                }
            }
        }
    }
);
</script>

<?php include dirname(__DIR__) .
    '/../../views/layouts/footer.php'; ?>