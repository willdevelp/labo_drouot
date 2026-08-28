<?php
/**
 * Liste des reçus — Factures payées
 * Laboratoire Drouot
 *
 * Variables attendues (CaissierController::recus) :
 * $factures, $totalRows, $totalPages, $page
 */

function formatFcfa(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function modeLabelRecu(?string $mode): string {
    $labels = [
        'cash'      => 'Espèces',
        'momo'      => 'Mobile Money',
        'card'      => 'Carte',
        'insurance' => 'Assurance',
        'virement'  => 'Virement',
    ];
    return $labels[$mode] ?? ($mode ? ucfirst($mode) : '—');
}

$filtreParams = [
    'date_debut' => $_GET['date_debut'] ?? '',
    'date_fin'   => $_GET['date_fin']   ?? '',
    'search'     => $_GET['search']     ?? '',
];
$filtreParams = array_filter($filtreParams, fn($v) => $v !== '');
$qsBase = http_build_query($filtreParams);
?>

<?php include __DIR__ . '/../../layouts/header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Reçus</h1>
        <p class="page-sub">Factures payées — <?= $totalRows ?> reçu<?= $totalRows > 1 ? 's' : '' ?> disponible<?= $totalRows > 1 ? 's' : '' ?></p>
    </div>
    <a href="<?= BASE_URL ?>caissier/factures" class="btn-primary-drouot">
        <i class="fas fa-file-invoice"></i> Factures
    </a>
</div>

<!-- ── FILTRES ── -->
<div class="card-drouot mb-4">
    <form method="get" action="<?= BASE_URL ?>caissier/recus" class="filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="filter-label">Payée du</label>
                <input type="date" name="date_debut" class="filter-input"
                       value="<?= htmlspecialchars($_GET['date_debut'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="filter-label">Payée au</label>
                <input type="date" name="date_fin" class="filter-input"
                       value="<?= htmlspecialchars($_GET['date_fin'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="filter-label">Recherche</label>
                <input type="text" name="search" class="filter-input"
                       placeholder="Nom du patient ou n° facture..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn-primary-drouot" style="padding:8px 14px;">
                    <i class="fas fa-search"></i>
                </button>
                <?php if (!empty($filtreParams)): ?>
                <a href="<?= BASE_URL ?>caissier/recus" class="btn-reset-drouot" title="Réinitialiser">
                    <i class="fas fa-xmark"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="card-drouot">
    <?php if (empty($factures)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-receipt fa-2x mb-2"></i>
            <p>Aucune facture payée ne correspond à ces filtres.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-drouot">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Patient</th>
                        <th>Téléphone</th>
                        <th>Montant</th>
                        <th>Mode</th>
                        <th>Payée le</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($factures as $f): ?>
                    <tr>
                        <td><span class="facture-ref"><?= htmlspecialchars($f['numero']) ?></span></td>
                        <td><?= htmlspecialchars($f['patient_nom']) ?></td>
                        <td style="color:#6b7a99;font-size:0.78rem;"><?= htmlspecialchars($f['telephone'] ?? '—') ?></td>
                        <td style="font-weight:500;"><?= formatFcfa((float)$f['montant_net']) ?></td>
                        <td><span class="mode-pill"><?= modeLabelRecu($f['mode_paiement']) ?></span></td>
                        <td style="white-space:nowrap;color:#6b7a99;font-size:0.77rem;">
                            <?= date('d/m/Y H:i', strtotime($f['updated_at'])) ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= BASE_URL ?>caissier/factures/<?= $f['id'] ?>" class="action-btn" title="Voir la facture">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>caissier/recus/<?= $f['id'] ?>" class="action-btn action-btn-green" title="Imprimer le reçu">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-drouot mt-3">
            <?php $qs = $qsBase !== '' ? $qsBase . '&' : ''; ?>
            <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
               href="<?= BASE_URL ?>caissier/recus?<?= $qs ?>page=<?= max(1, $page - 1) ?>">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="page-info">Page <?= $page ?> / <?= $totalPages ?></span>
            <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
               href="<?= BASE_URL ?>caissier/recus?<?= $qs ?>page=<?= min($totalPages, $page + 1) ?>">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .page-title { font-size: 1.3rem; font-weight: 600; color: #1a2340; margin: 0; }
    .page-sub { font-size: 0.85rem; color: #6b7a99; margin: 2px 0 0; }

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

    .facture-ref { font-weight: 600; color: #1E88E5; font-size: 0.8rem; }

    .mode-pill {
        display: inline-block; font-size: 0.7rem; padding: 2px 8px;
        background: #EDE7F6; color: #4527A0; border-radius: 20px; font-weight: 500;
    }

    .action-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 26px; height: 26px; border-radius: 6px;
        background: #F4F6F9; color: #6b7a99; text-decoration: none;
        font-size: 0.75rem; transition: all 0.2s;
    }
    .action-btn:hover { background: #1E88E5; color: #fff; }
    .action-btn-green:hover { background: #2E7D32 !important; color: #fff !important; }

    .pagination-drouot { display: flex; align-items: center; justify-content: center; gap: 12px; }
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
