<?php
/**
 * Suivi de caisse — Liste des paiements avec filtres
 * Laboratoire Drouot
 *
 * Variables attendues du controller (CaissierController::paiements) :
 * $paiements, $totalRows, $totalPages, $page, $perPage,
 * $totalFiltre, $nbFiltre, $repartitionModes, $totalJour, $totalMois,
 * $caissiers, $user
 */

function formatFcfa(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function modeLabel(?string $mode): string {
    $labels = [
        'cash'      => 'Espèces',
        'momo'      => 'Mobile Money',
        'card'      => 'Carte',
        'insurance' => 'Assurance',
        'virement'  => 'Virement',
    ];
    return $labels[$mode] ?? ($mode ? ucfirst($mode) : '—');
}

$modeColors = [
    'cash'      => '#185FA5',
    'momo'      => '#534AB7',
    'card'      => '#3B6D11',
    'insurance' => '#854F0B',
    'virement'  => '#993556',
];

// Reconstruit la query string des filtres actifs (hors "page") pour
// la pagination et pour le bouton "Réinitialiser".
$filtreParams = [
    'date_debut'  => $_GET['date_debut']  ?? '',
    'date_fin'    => $_GET['date_fin']    ?? '',
    'mode'        => $_GET['mode']        ?? '',
    'search'      => $_GET['search']      ?? '',
    'caissier_id' => $_GET['caissier_id'] ?? '',
];
$filtreParams = array_filter($filtreParams, fn($v) => $v !== '');
$qsBase = http_build_query($filtreParams);
?>

<?php include __DIR__ . '/../../layouts/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Suivi de caisse</h1>
        <p class="page-sub">Historique des paiements encaissés, avec filtres pour un meilleur suivi</p>
    </div>
    <a href="<?= BASE_URL ?>caissier/factures" class="btn-primary-drouot">
        <i class="fas fa-file-invoice"></i> Factures
    </a>
</div>

<!-- ── KPI ── -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-filter"></i></div>
            <div>
                <div class="kpi-value" style="font-size:1rem;"><?= formatFcfa($totalFiltre) ?></div>
                <div class="kpi-label">Total sur la période filtrée</div>
                <div class="kpi-trend up"><?= $nbFiltre ?> transaction(s)</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div class="kpi-value" style="font-size:1rem;"><?= formatFcfa((float)$totalJour) ?></div>
                <div class="kpi-label">Encaissé aujourd'hui</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fas fa-calendar-days"></i></div>
            <div>
                <div class="kpi-value" style="font-size:1rem;"><?= formatFcfa((float)$totalMois) ?></div>
                <div class="kpi-label">Encaissé ce mois</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon red"><i class="fas fa-receipt"></i></div>
            <div>
                <div class="kpi-value"><?= $totalRows ?></div>
                <div class="kpi-label">Transactions correspondantes</div>
            </div>
        </div>
    </div>
</div>

<!-- ── FILTRES ── -->
<div class="card-drouot mb-4">
    <form method="get" action="<?= BASE_URL ?>caissier/paiements" class="filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="filter-label">Du</label>
                <input type="date" name="date_debut" class="filter-input"
                       value="<?= htmlspecialchars($_GET['date_debut'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label">Au</label>
                <input type="date" name="date_fin" class="filter-input"
                       value="<?= htmlspecialchars($_GET['date_fin'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label">Mode de paiement</label>
                <select name="mode" class="filter-input">
                    <option value="">Tous les modes</option>
                    <?php foreach (['cash','momo','card','insurance','virement'] as $m): ?>
                    <option value="<?= $m ?>" <?= ($_GET['mode'] ?? '') === $m ? 'selected' : '' ?>>
                        <?= modeLabel($m) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($caissiers)): ?>
            <div class="col-md-2">
                <label class="filter-label">Caissier</label>
                <select name="caissier_id" class="filter-input">
                    <option value="">Tous</option>
                    <?php foreach ($caissiers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($_GET['caissier_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-<?= !empty($caissiers) ? 3 : 5 ?>">
                <label class="filter-label">Recherche</label>
                <input type="text" name="search" class="filter-input"
                       placeholder="Patient, n° facture, référence..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn-primary-drouot" style="padding:8px 14px;">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($filtreParams)): ?>
                <a href="<?= BASE_URL ?>caissier/paiements" class="btn-reset-drouot" title="Réinitialiser">
                    <i class="fas fa-xmark"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">

    <!-- Tableau -->
    <div class="col-xl-8">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-list-check text-primary me-2"></i>
                    Transactions
                    <span class="text-muted" style="font-weight:400;font-size:0.78rem;">
                        (<?= $totalRows ?> résultat<?= $totalRows > 1 ? 's' : '' ?>)
                    </span>
                </h6>
            </div>

            <?php if (empty($paiements)): ?>
                <p class="text-muted text-center py-4">Aucun paiement ne correspond à ces filtres.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-drouot">
                        <thead>
                            <tr>
                                <th>Facture</th>
                                <th>Patient</th>
                                <th>Montant</th>
                                <th>Mode</th>
                                <th>Référence</th>
                                <th>Caissier</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paiements as $p): ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL ?>caissier/factures/<?= $p['facture_id'] ?>" class="facture-ref">
                                        <?= htmlspecialchars($p['facture_numero']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($p['patient_nom']) ?></td>
                                <td style="font-weight:500;"><?= formatFcfa((float)$p['montant']) ?></td>
                                <td><span class="mode-pill"><?= modeLabel($p['mode_paiement']) ?></span></td>
                                <td style="color:#6b7a99;font-size:0.78rem;">
                                    <?= $p['reference_paiement'] ? htmlspecialchars($p['reference_paiement']) : '—' ?>
                                </td>
                                <td style="font-size:0.78rem;"><?= htmlspecialchars($p['caissier_name']) ?></td>
                                <td style="white-space:nowrap;color:#6b7a99;font-size:0.77rem;">
                                    <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination-drouot mt-3">
                    <?php
                    $qs = $qsBase !== '' ? $qsBase . '&' : '';
                    ?>
                    <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
                       href="<?= BASE_URL ?>caissier/paiements?<?= $qs ?>page=<?= max(1, $page - 1) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="page-info">Page <?= $page ?> / <?= $totalPages ?></span>
                    <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
                       href="<?= BASE_URL ?>caissier/paiements?<?= $qs ?>page=<?= min($totalPages, $page + 1) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Répartition par mode -->
    <div class="col-xl-4">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-wallet text-primary me-2"></i>
                Répartition (période filtrée)
            </h6>
            <?php if (empty($repartitionModes)): ?>
                <p class="text-muted text-center py-3" style="font-size:0.85rem;">Aucune donnée</p>
            <?php else: ?>
                <?php $totalRepartition = array_sum(array_column($repartitionModes, 'total')) ?: 1; ?>
                <?php foreach ($repartitionModes as $m):
                    $pct = round(($m['total'] / $totalRepartition) * 100);
                    $color = $modeColors[$m['mode_paiement']] ?? '#888';
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:0.8rem;color:#1a2340;font-weight:500;">
                            <?= modeLabel($m['mode_paiement']) ?>
                        </span>
                        <span style="font-size:0.78rem;color:#6b7a99;">
                            <?= $m['nb'] ?> · <?= formatFcfa((float)$m['total']) ?>
                        </span>
                    </div>
                    <div class="mode-bar-wrap">
                        <div class="mode-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
    .page-title { font-size: 1.3rem; font-weight: 600; color: #1a2340; margin: 0; }
    .page-sub { font-size: 0.85rem; color: #6b7a99; margin: 2px 0 0; }
    .card-section-title { font-size: 0.88rem; font-weight: 600; color: #1a2340; margin: 0; }

    .filter-label {
        font-size: 0.72rem; font-weight: 600; color: #6b7a99;
        text-transform: uppercase; letter-spacing: 0.02em;
        margin-bottom: 4px; display: block;
    }
    .filter-input {
        width: 100%; padding: 7px 10px;
        border: 1px solid #dde3f0; border-radius: 8px;
        font-size: 0.82rem; color: #1a2340; background: #fff;
    }
    .filter-input:focus { outline: none; border-color: #1E88E5; }
    .btn-reset-drouot {
        display: inline-flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 8px;
        background: #F4F6F9; color: #6b7a99; text-decoration: none;
    }
    .btn-reset-drouot:hover { background: #FFEBEE; color: #C62828; }

    .table-drouot { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .table-drouot thead tr { border-bottom: 1px solid #f0f2f8; }
    .table-drouot th {
        padding: 8px 10px; text-align: left; color: #6b7a99;
        font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em;
    }
    .table-drouot td { padding: 9px 10px; color: #1a2340; border-bottom: 1px solid #f8f9fc; }
    .table-drouot tbody tr:last-child td { border-bottom: none; }
    .table-drouot tbody tr:hover { background: #f8f9fc; }

    .facture-ref { font-weight: 600; color: #1E88E5; font-size: 0.8rem; text-decoration: none; }
    .facture-ref:hover { text-decoration: underline; }

    .mode-pill {
        display: inline-block; font-size: 0.7rem; padding: 2px 8px;
        background: #EDE7F6; color: #4527A0; border-radius: 20px; font-weight: 500;
    }
    .mode-bar-wrap { height: 6px; background: #f0f2f8; border-radius: 4px; overflow: hidden; }
    .mode-bar { height: 100%; border-radius: 4px; transition: width 0.4s ease; }

    .pagination-drouot {
        display: flex; align-items: center; justify-content: center; gap: 12px;
    }
    .page-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px;
        background: #F4F6F9; color: #1a2340; text-decoration: none; font-size: 0.8rem;
    }
    .page-btn:hover:not(.disabled) { background: #1E88E5; color: #fff; }
    .page-btn.disabled { opacity: 0.4; pointer-events: none; }
    .page-info { font-size: 0.8rem; color: #6b7a99; }
</style>

<?php include __DIR__ . '/../../layouts/footer.php'; ?>
