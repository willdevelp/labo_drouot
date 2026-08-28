<?php
/**
 * Dashboard Caissier — Laboratoire Drouot
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['caissier']);

$db   = Database::getConnection();
$user = Auth::user();

// ── KPI ─────────────────────────────────────────────────
$kpi = [];

// Total factures émises (non supprimées)
$kpi['factures'] = $db->query("
    SELECT COUNT(*) FROM factures
    WHERE deleted_at IS NULL
")->fetchColumn();

// Total encaissements (paiements validés)
$kpi['encaissements'] = $db->query("
    SELECT COALESCE(SUM(montant), 0) FROM paiements
    WHERE statut = 'valide'
")->fetchColumn();

// Factures impayées (statut emise = en attente paiement)
$kpi['impayes'] = $db->query("
    SELECT COUNT(*) FROM factures
    WHERE statut = 'emise' AND deleted_at IS NULL
")->fetchColumn();

// Paiements validés aujourd'hui
$kpi['valides_jour'] = $db->query("
    SELECT COUNT(*) FROM paiements
    WHERE statut = 'valide'
    AND DATE(created_at) = CURDATE()
")->fetchColumn();

// Nouvelles factures ce mois
$kpi['factures_mois'] = $db->query("
    SELECT COUNT(*) FROM factures
    WHERE MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
    AND deleted_at IS NULL
")->fetchColumn();

// Encaissements ce mois
$kpi['encaissements_mois'] = $db->query("
    SELECT COALESCE(SUM(montant), 0) FROM paiements
    WHERE statut = 'valide'
    AND MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// Nouvelles factures impayées ce mois
$kpi['impayes_mois'] = $db->query("
    SELECT COUNT(*) FROM factures
    WHERE statut = 'emise'
    AND MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
    AND deleted_at IS NULL
")->fetchColumn();

// Paiements validés ce mois
$kpi['valides_mois'] = $db->query("
    SELECT COUNT(*) FROM paiements
    WHERE statut = 'valide'
    AND MONTH(created_at) = MONTH(NOW())
    AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

// ── GRAPHIQUE ENCAISSEMENTS (6 derniers mois) ────────────
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $stmt = $db->prepare("
        SELECT
            DATE_FORMAT(DATE_SUB(NOW(), INTERVAL :i MONTH), '%b') AS mois,
            COALESCE(SUM(CASE WHEN p.statut = 'valide' THEN p.montant ELSE 0 END), 0) AS encaisse,
            COUNT(DISTINCT CASE WHEN f.statut = 'emise' THEN f.id END) AS impayes
        FROM factures f
        LEFT JOIN paiements p
            ON p.facture_id = f.id
            AND MONTH(p.created_at) = MONTH(DATE_SUB(NOW(), INTERVAL :i2 MONTH))
            AND YEAR(p.created_at)  = YEAR(DATE_SUB(NOW(), INTERVAL :i3 MONTH))
        WHERE MONTH(f.created_at) = MONTH(DATE_SUB(NOW(), INTERVAL :i4 MONTH))
        AND YEAR(f.created_at)   = YEAR(DATE_SUB(NOW(), INTERVAL :i5 MONTH))
        AND f.deleted_at IS NULL
    ");
    $stmt->execute([':i'=>$i,':i2'=>$i,':i3'=>$i,':i4'=>$i,':i5'=>$i]);
    $chartData[] = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ── RÉPARTITION MODES DE PAIEMENT ───────────────────────
$modesData = $db->query("
    SELECT mode_paiement, COUNT(*) AS total, COALESCE(SUM(montant), 0) AS montant
    FROM paiements
    WHERE statut = 'valide'
    GROUP BY mode_paiement
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Total pour calcul pourcentages
$totalPaiements = array_sum(array_column($modesData, 'total')) ?: 1;

// ── FACTURES RÉCENTES ────────────────────────────────────
$facturesRecentes = $db->query("
    SELECT
        f.numero,
        f.statut,
        f.montant_net,
        f.created_at,
        CONCAT(p.prenom, ' ', p.nom) AS patient,
        (
            SELECT mode_paiement FROM paiements
            WHERE facture_id = f.id AND statut = 'valide'
            ORDER BY created_at DESC LIMIT 1
        ) AS mode_paiement
    FROM factures f
    JOIN patients p ON f.patient_id = p.id
    WHERE f.deleted_at IS NULL
    ORDER BY f.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// ── ALERTES CAISSE ───────────────────────────────────────
$alertes = [];

// Factures impayées
if ($kpi['impayes'] > 0) {
    $alertes[] = [
        'type'    => 'danger',
        'icon'    => 'fa-file-invoice-dollar',
        'message' => "{$kpi['impayes']} facture(s) en attente de paiement",
        'url'     => 'caissier/factures',
    ];
}

// Paiements partiels
$partiels = $db->query("
    SELECT COUNT(*) FROM factures
    WHERE statut = 'partiellement_payee' AND deleted_at IS NULL
")->fetchColumn();
if ($partiels > 0) {
    $alertes[] = [
        'type'    => 'warning',
        'icon'    => 'fa-money-bill-transfer',
        'message' => "$partiels paiement(s) partiel(s) à finaliser",
        'url'     => 'caissier/factures',
    ];
}

// Reçus non imprimés aujourd'hui (paiements validés sans reçu joint)
$reçusManquants = $db->query("
    SELECT COUNT(*) FROM paiements
    WHERE statut = 'valide'
    AND DATE(created_at) = CURDATE()
    AND reference_paiement IS NULL
")->fetchColumn();
if ($reçusManquants > 0) {
    $alertes[] = [
        'type'    => 'info',
        'icon'    => 'fa-print',
        'message' => "$reçusManquants reçu(s) à imprimer aujourd'hui",
        'url'     => 'caissier/recus',
    ];
}

// ── HELPERS ─────────────────────────────────────────────
function formatFcfa(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function statutBadge(string $statut): string {
    $map = [
        'payee'               => ['class' => 'success', 'label' => 'Payée'],
        'partiellement_payee' => ['class' => 'warning', 'label' => 'Partielle'],
        'emise'               => ['class' => 'danger',  'label' => 'Impayée'],
        'annulee'             => ['class' => 'secondary','label' => 'Annulée'],
        'brouillon'           => ['class' => 'secondary','label' => 'Brouillon'],
        'remboursee'          => ['class' => 'info',    'label' => 'Remboursée'],
    ];
    $s = $map[$statut] ?? ['class' => 'secondary', 'label' => ucfirst($statut)];
    return '<span class="badge-drouot badge-' . $s['class'] . '">' . $s['label'] . '</span>';
}

function modeBadge(?string $mode): string {
    if (!$mode) return '<span class="text-muted">—</span>';
    $labels = [
        'cash'      => 'Espèces',
        'momo'      => 'Mobile Money',
        'card'      => 'Carte',
        'insurance' => 'Assurance',
        'virement'  => 'Virement',
    ];
    return '<span class="mode-pill">' . ($labels[$mode] ?? ucfirst($mode)) . '</span>';
}

$modeColors = [
    'cash'      => '#185FA5',
    'momo'      => '#534AB7',
    'card'      => '#3B6D11',
    'insurance' => '#854F0B',
    'virement'  => '#993556',
];
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<!-- ══════════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Tableau de bord</h1>
        <p class="page-sub">Bonjour, <?= htmlspecialchars($user['name']) ?> 👋 — Gestion des encaissements</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>caissier/factures" class="btn-primary-drouot">
            <i class="fas fa-file-invoice"></i> Voir les factures
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     KPI CARDS — 4 colonnes
══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Factures émises -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <div class="kpi-value"><?= number_format($kpi['factures'], 0, ',', ' ') ?></div>
                <div class="kpi-label">Factures émises</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-up"></i> +<?= $kpi['factures_mois'] ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <!-- Encaissements -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="kpi-value" style="font-size:1rem;">
                    <?= formatFcfa((float)$kpi['encaissements']) ?>
                </div>
                <div class="kpi-label">Encaissements totaux</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-up"></i>
                    <?= formatFcfa((float)$kpi['encaissements_mois']) ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <!-- Factures impayées -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon orange">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['impayes'] ?></div>
                <div class="kpi-label">Factures impayées</div>
                <div class="kpi-trend <?= $kpi['impayes_mois'] > 0 ? 'down' : 'up' ?>">
                    <i class="fas fa-arrow-<?= $kpi['impayes_mois'] > 0 ? 'up' : 'down' ?>"></i>
                    +<?= $kpi['impayes_mois'] ?> ce mois
                </div>
            </div>
        </div>
    </div>

    <!-- Paiements validés -->
    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon red">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['valides_jour'] ?></div>
                <div class="kpi-label">Paiements validés aujourd'hui</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-up"></i> +<?= $kpi['valides_mois'] ?> ce mois
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     GRAPHIQUE + MODES DE PAIEMENT
══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Graphique encaissements -->
    <div class="col-xl-8">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-chart-bar text-primary me-2"></i>
                    Encaissements vs Impayés — 6 derniers mois
                </h6>
            </div>
            <canvas id="chartEncaisse" height="110"></canvas>
        </div>
    </div>

    <!-- Répartition modes de paiement -->
    <div class="col-xl-4">
        <div class="card-drouot h-100">
            <div class="mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-wallet text-primary me-2"></i>
                    Modes de paiement
                </h6>
            </div>

            <?php if (empty($modesData)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-wallet fa-2x mb-2"></i>
                    <p>Aucune donnée</p>
                </div>
            <?php else: ?>
                <!-- Barres horizontales -->
                <div class="mb-3">
                    <?php
                    $modeLabels = [
                        'cash'      => 'Espèces',
                        'momo'      => 'Mobile Money',
                        'card'      => 'Carte',
                        'insurance' => 'Assurance',
                        'virement'  => 'Virement',
                    ];
                    foreach ($modesData as $m):
                        $pct = round(($m['total'] / $totalPaiements) * 100);
                        $color = $modeColors[$m['mode_paiement']] ?? '#888';
                        $label = $modeLabels[$m['mode_paiement']] ?? ucfirst($m['mode_paiement']);
                    ?>
                    <div class="mode-row mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span style="font-size:0.77rem;color:#6b7a99;"><?= htmlspecialchars($label) ?></span>
                            <span style="font-size:0.77rem;font-weight:500;"><?= $pct ?>%</span>
                        </div>
                        <div class="mode-bar-wrap">
                            <div class="mode-bar" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Donut -->
                <canvas id="chartDonut" height="130"></canvas>
                <div class="mt-2">
                    <?php foreach ($modesData as $m):
                        $color = $modeColors[$m['mode_paiement']] ?? '#888';
                        $label = $modeLabels[$m['mode_paiement']] ?? ucfirst($m['mode_paiement']);
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;flex-shrink:0;"></div>
                        <span style="font-size:0.75rem;color:#6b7a99;"><?= htmlspecialchars($label) ?></span>
                        <span style="margin-left:auto;font-size:0.75rem;font-weight:500;">
                            <?= formatFcfa((float)$m['montant']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════
     FACTURES RÉCENTES + ALERTES & ACCÈS RAPIDES
══════════════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Tableau factures récentes -->
    <div class="col-xl-8">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-list-check text-primary me-2"></i>
                    Factures récentes
                </h6>
                <a href="<?= BASE_URL ?>caissier/factures" class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($facturesRecentes)): ?>
                <p class="text-muted text-center py-3">Aucune facture enregistrée.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-drouot">
                        <thead>
                            <tr>
                                <th>N° Facture</th>
                                <th>Patient</th>
                                <th>Montant</th>
                                <th>Mode</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facturesRecentes as $f): ?>
                            <tr>
                                <td>
                                    <span class="facture-ref">
                                        <?= htmlspecialchars($f['numero']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($f['patient']) ?></td>
                                <td style="font-weight:500;">
                                    <?= formatFcfa((float)$f['montant_net']) ?>
                                </td>
                                <td><?= modeBadge($f['mode_paiement']) ?></td>
                                <td><?= statutBadge($f['statut']) ?></td>
                                <td style="white-space:nowrap;color:#6b7a99;font-size:0.77rem;">
                                    <?= $f['created_at']
                                        ? date('d/m H:i', strtotime($f['created_at']))
                                        : '' ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?= BASE_URL ?>caissier/factures/<?= $f['numero'] ?>"
                                           class="action-btn" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (in_array($f['statut'], ['emise', 'partiellement_payee'])): ?>
                                        <a href="<?= BASE_URL ?>caissier/paiements/create?facture=<?= $f['numero'] ?>"
                                           class="action-btn action-btn-green" title="Encaisser">
                                            <i class="fas fa-cash-register"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>caissier/recus/<?= $f['numero'] ?>"
                                           class="action-btn" title="Imprimer reçu">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alertes + Accès rapides -->
    <div class="col-xl-4">

        <!-- Alertes caisse -->
        <div class="card-drouot mb-3">
            <div class="mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-triangle-exclamation text-warning me-2"></i>
                    Alertes caisse
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
        </div>

        <!-- Accès rapides caissier -->
        <div class="card-drouot">
            <div class="mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-bolt text-warning me-1"></i> Accès rapides
                </h6>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>caissier/factures" class="quick-btn">
                    <i class="fas fa-file-invoice"></i> Factures
                </a>
                <a href="<?= BASE_URL ?>caissier/paiements" class="quick-btn">
                    <i class="fas fa-cash-register"></i> Paiements
                </a>
                <a href="<?= BASE_URL ?>caissier/recus" class="quick-btn">
                    <i class="fas fa-print"></i> Reçus
                </a>
                <a href="<?= BASE_URL ?>caissier/historique" class="quick-btn">
                    <i class="fas fa-clock-rotate-left"></i> Historique
                </a>
            </div>

            <!-- Résumé du jour -->
            <div class="mt-4 resumé-jour">
                <p class="card-section-title mb-2" style="font-size:0.8rem;">
                    <i class="fas fa-calendar-day text-primary me-1"></i> Résumé du jour
                </p>
                <?php
                $resumeJour = $db->query("
                    SELECT
                        COUNT(DISTINCT f.id) AS nb_factures,
                        COALESCE(SUM(p.montant), 0) AS total_encaisse,
                        COUNT(DISTINCT CASE WHEN f.statut = 'emise' THEN f.id END) AS nb_impayes
                    FROM factures f
                    LEFT JOIN paiements p ON p.facture_id = f.id
                        AND p.statut = 'valide'
                        AND DATE(p.created_at) = CURDATE()
                    WHERE DATE(f.created_at) = CURDATE()
                    AND f.deleted_at IS NULL
                ")->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="resume-grid">
                    <div class="resume-item">
                        <span class="resume-val"><?= $resumeJour['nb_factures'] ?? 0 ?></span>
                        <span class="resume-lbl">Factures créées</span>
                    </div>
                    <div class="resume-item">
                        <span class="resume-val" style="font-size:0.9rem;">
                            <?= formatFcfa((float)($resumeJour['total_encaisse'] ?? 0)) ?>
                        </span>
                        <span class="resume-lbl">Encaissé</span>
                    </div>
                    <div class="resume-item">
                        <span class="resume-val" style="color:#C62828;">
                            <?= $resumeJour['nb_impayes'] ?? 0 ?>
                        </span>
                        <span class="resume-lbl">Impayées</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- ══════════════════════════════════════════════
     STYLES SPÉCIFIQUES PAGE CAISSIER
══════════════════════════════════════════════ -->
<style>
    /* ── Titres page ── */
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

    /* ── Barres modes ── */
    .mode-bar-wrap {
        height: 5px;
        background: #f0f2f8;
        border-radius: 4px;
        overflow: hidden;
    }
    .mode-bar {
        height: 100%;
        border-radius: 4px;
        transition: width 0.4s ease;
    }

    /* ── Tableau factures ── */
    .table-drouot {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }
    .table-drouot thead tr {
        border-bottom: 1px solid #f0f2f8;
    }
    .table-drouot th {
        padding: 8px 10px;
        text-align: left;
        color: #6b7a99;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .table-drouot td {
        padding: 9px 10px;
        color: #1a2340;
        border-bottom: 1px solid #f8f9fc;
    }
    .table-drouot tbody tr:last-child td { border-bottom: none; }
    .table-drouot tbody tr:hover { background: #f8f9fc; }

    /* Référence facture */
    .facture-ref {
        font-weight: 600;
        color: #1E88E5;
        font-size: 0.8rem;
    }

    /* ── Badges statut ── */
    .badge-drouot {
        display: inline-block;
        font-size: 0.68rem;
        padding: 3px 9px;
        border-radius: 20px;
        font-weight: 600;
    }
    .badge-success   { background: #E8F5E9; color: #2E7D32; }
    .badge-warning   { background: #FFF8E1; color: #F57F17; }
    .badge-danger    { background: #FFEBEE; color: #C62828; }
    .badge-info      { background: #E3F2FD; color: #1565C0; }
    .badge-secondary { background: #F4F6F9; color: #6b7a99; }

    /* ── Mode pill ── */
    .mode-pill {
        display: inline-block;
        font-size: 0.7rem;
        padding: 2px 8px;
        background: #EDE7F6;
        color: #4527A0;
        border-radius: 20px;
        font-weight: 500;
    }

    /* ── Boutons action tableau ── */
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px; height: 26px;
        border-radius: 6px;
        background: #F4F6F9;
        color: #6b7a99;
        text-decoration: none;
        font-size: 0.75rem;
        transition: all 0.2s;
    }
    .action-btn:hover {
        background: #1E88E5;
        color: #fff;
    }
    .action-btn-green:hover {
        background: #2E7D32 !important;
        color: #fff !important;
    }

    /* ── Alertes ── */
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

    /* ── Accès rapides ── */
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

    /* ── Résumé du jour ── */
    .resume-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-top: 4px;
    }
    .resume-item {
        background: #F4F6F9;
        border-radius: 8px;
        padding: 8px;
        text-align: center;
    }
    .resume-val {
        display: block;
        font-size: 1rem;
        font-weight: 600;
        color: #1a2340;
    }
    .resume-lbl {
        display: block;
        font-size: 0.68rem;
        color: #6b7a99;
        margin-top: 2px;
    }
</style>

<!-- ══════════════════════════════════════════════
     CHART.JS
══════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ── Données PHP → JS ──
const chartLabels      = <?= json_encode(array_column($chartData, 'mois')) ?>;
const chartEncaisse    = <?= json_encode(array_values(array_column($chartData, 'encaisse'))) ?>;
const chartImpayes     = <?= json_encode(array_values(array_column($chartData, 'impayes'))) ?>;
const donutLabels      = <?= json_encode(array_column($modesData, 'mode_paiement')) ?>;
const donutData        = <?= json_encode(array_values(array_column($modesData, 'total'))) ?>;
const donutColors      = <?= json_encode(array_values($modeColors)) ?>;

// ── Graphique barres encaissements ──
const ctxBar = document.getElementById('chartEncaisse').getContext('2d');
new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Encaissements (FCFA)',
                data: chartEncaisse,
                backgroundColor: 'rgba(30,136,229,0.80)',
                borderRadius: 5,
                borderSkipped: false,
            },
            {
                label: 'Impayés (nb)',
                data: chartImpayes,
                backgroundColor: 'rgba(198,40,40,0.55)',
                borderRadius: 5,
                borderSkipped: false,
                yAxisID: 'y2',
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
                labels: { font: { size: 12 }, usePointStyle: true }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f0f2f8' },
                title: { display: true, text: 'FCFA', font: { size: 11 }, color: '#6b7a99' }
            },
            y2: {
                beginAtZero: true,
                position: 'right',
                grid: { display: false },
                title: { display: true, text: 'Nb impayés', font: { size: 11 }, color: '#6b7a99' }
            },
            x: { grid: { display: false } }
        }
    }
});

<?php if (!empty($modesData)): ?>
// ── Donut modes de paiement ──
const ctxDonut = document.getElementById('chartDonut').getContext('2d');
const modeLabelsMap = {
    cash: 'Espèces', momo: 'Mobile Money',
    card: 'Carte', insurance: 'Assurance', virement: 'Virement'
};
new Chart(ctxDonut, {
    type: 'doughnut',
    data: {
        labels: donutLabels.map(k => modeLabelsMap[k] || k),
        datasets: [{
            data: donutData,
            backgroundColor: donutColors.slice(0, donutData.length),
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>