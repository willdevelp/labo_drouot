<?php
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['directeur', 'admin']);
$db   = Database::getConnection();
$user = Auth::user();

// Rapports globaux
$rapports = $db->query("
    SELECT
        r.id, r.generated_at,
        d.id AS demande_id, d.total_price, d.status,
        CONCAT(p.prenom,' ',p.nom) AS patient_nom,
        p.telephone,
        v.validated_at, v.commentaire,
        u.name AS biologiste_nom,
        GROUP_CONCAT(DISTINCT e.nom SEPARATOR ', ') AS examens
    FROM rapports r
    JOIN demandes_examens d ON r.demande_id = d.id
    JOIN patients p ON d.patient_id = p.id
    LEFT JOIN validations v ON v.demande_id = d.id
    LEFT JOIN users u ON v.biologiste_id = u.id
    LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
    LEFT JOIN examens e ON dei.examen_id = e.id
    GROUP BY r.id
    ORDER BY r.generated_at DESC
    LIMIT 100
")->fetchAll();

// Stats globales
$statsGlobal = $db->query("
    SELECT
        COUNT(DISTINCT r.id)   AS total_rapports,
        COUNT(DISTINCT p.id)   AS total_patients,
        SUM(d.total_price)     AS revenus_totaux,
        COUNT(DISTINCT v.id)   AS total_validations
    FROM rapports r
    JOIN demandes_examens d ON r.demande_id = d.id
    JOIN patients p ON d.patient_id = p.id
    LEFT JOIN validations v ON v.demande_id = d.id
")->fetch();

function formatFcfa(float $a): string {
    if ($a >= 1000000)
        return number_format($a/1000000, 1, ',', ' ') . 'M FCFA';
    return number_format($a, 0, ',', ' ') . ' FCFA';
}

include __DIR__ . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Rapports globaux</h1>
        <p class="page-sub">
            <?= count($rapports) ?> rapport(s) généré(s)
        </p>
    </div>
    <a href="<?= BASE_URL ?>dashboard/directeur"
       class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-file-waveform"></i>
            </div>
            <div>
                <div class="kpi-value">
                    <?= $statsGlobal['total_rapports'] ?>
                </div>
                <div class="kpi-label">Rapports générés</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-hospital-user"></i>
            </div>
            <div>
                <div class="kpi-value">
                    <?= $statsGlobal['total_patients'] ?>
                </div>
                <div class="kpi-label">Patients uniques</div>
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
                    <?= formatFcfa(
                        (float)($statsGlobal['revenus_totaux'] ?? 0)
                    ) ?>
                </div>
                <div class="kpi-label">Revenus totaux</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-user-doctor"></i>
            </div>
            <div>
                <div class="kpi-value">
                    <?= $statsGlobal['total_validations'] ?>
                </div>
                <div class="kpi-label">Validations biologistes</div>
            </div>
        </div>
    </div>
</div>

<!-- Table rapports -->
<div class="card-drouot">
    <h6 class="card-section-title mb-3">
        <i class="fas fa-list text-primary me-2"></i>
        Liste des rapports
    </h6>
    <?php if (empty($rapports)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-file-waveform fa-3x mb-3"
               style="color:#dde3f0;"></i>
            <p>Aucun rapport généré.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Examens</th>
                    <th>Montant</th>
                    <th>Biologiste</th>
                    <th>Validé le</th>
                    <th>Généré le</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rapports as $r): ?>
                <tr>
                    <td>
                        <div style="font-weight:500;font-size:.88rem;">
                            <?= htmlspecialchars($r['patient_nom']) ?>
                        </div>
                        <div style="font-size:.75rem;color:#6b7a99;">
                            <?= htmlspecialchars($r['telephone']) ?>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;
                        max-width:200px;">
                        <?= htmlspecialchars(
                            mb_strimwidth($r['examens'] ?? '—', 0, 50, '...')
                        ) ?>
                    </td>
                    <td style="font-weight:600;color:#1E88E5;">
                        <?= number_format(
                            (float)$r['total_price'], 0, ',', ' '
                        ) ?> F
                    </td>
                    <td style="font-size:.82rem;">
                        <?= htmlspecialchars(
                            $r['biologiste_nom'] ?? '—'
                        ) ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= $r['validated_at']
                            ? date('d/m/Y',
                                strtotime($r['validated_at']))
                            : '—' ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
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

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>