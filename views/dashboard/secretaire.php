<?php
/**
 * Dashboard Secrétaire — Laboratoire Drouot
 */
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['secretaire', 'admin']);

$db   = Database::getConnection();
$user = Auth::user();

// ── KPI ─────────────────────────────────────────────────

// Patients du jour
$kpi['patients_jour'] = $db->query("
    SELECT COUNT(*) FROM patients
    WHERE DATE(created_at) = CURDATE()
")->fetchColumn();

// Demandes du jour
$kpi['demandes_jour'] = $db->query("
    SELECT COUNT(*) FROM demandes_examens
    WHERE DATE(created_at) = CURDATE()
")->fetchColumn();

// Factures proforma du jour
$kpi['factures_jour'] = $db->query("
    SELECT COUNT(*) FROM factures
    WHERE DATE(created_at) = CURDATE()
")->fetchColumn();

// En attente paiement
$kpi['attente_paiement'] = $db->query("
    SELECT COUNT(*) FROM demandes_examens
    WHERE payment_status = 'unpaid'
")->fetchColumn();

// ── DEMANDES RÉCENTES ────────────────────────────────────
$demandes = $db->query("
    SELECT
        d.id,
        CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
        d.total_price,
        d.status,
        d.payment_status,
        d.created_at,
        GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens
    FROM demandes_examens d
    JOIN patients p ON d.patient_id = p.id
    LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
    LEFT JOIN examens e ON dei.examen_id = e.id
    GROUP BY d.id
    ORDER BY d.created_at DESC
    LIMIT 8
")->fetchAll();

// ── PATIENTS RÉCENTS ─────────────────────────────────────
$patients_recents = $db->query("
    SELECT id, uuid, nom, prenom, telephone, sexe, created_at
    FROM patients
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

// ── HELPERS ─────────────────────────────────────────────
function statusLabel(string $status): string {
    return match($status) {
        'pending'    => '<span class="badge-pending">En attente</span>',
        'paid'       => '<span class="badge-paid">Payé</span>',
        'processing' => '<span class="badge-warn">En cours</span>',
        'completed'  => '<span class="badge-normal">Terminé</span>',
        default      => '<span class="badge-pending">' . $status . '</span>',
    };
}
function paymentLabel(string $status): string {
    return match($status) {
        'paid'   => '<span class="badge-normal">Payé</span>',
        'unpaid' => '<span class="badge-critical">Impayé</span>',
        default  => '<span class="badge-pending">' . $status . '</span>',
    };
}
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<!-- ══════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Tableau de bord Secrétariat</h1>
        <p class="page-sub">Bonjour, <?= htmlspecialchars($user['name']) ?> 👋</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>secretaire/patients/create" class="btn-primary-drouot">
            <i class="fas fa-user-plus"></i> Nouveau patient
        </a>
        <a href="<?= BASE_URL ?>secretaire/demandes/create" class="btn-success-drouot">
            <i class="fas fa-flask"></i> Nouvelle demande
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════
     KPI CARDS
══════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon green">
                <i class="fas fa-hospital-user"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['patients_jour'] ?></div>
                <div class="kpi-label">Patients aujourd'hui</div>
                <div class="kpi-trend up">
                    <i class="fas fa-calendar-day"></i> Ce jour
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon blue">
                <i class="fas fa-flask"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['demandes_jour'] ?></div>
                <div class="kpi-label">Demandes aujourd'hui</div>
                <div class="kpi-trend up">
                    <i class="fas fa-arrow-up"></i> +<?= $kpi['demandes_jour'] ?> ce jour
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="kpi-card">
            <div class="kpi-icon orange">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <div class="kpi-value"><?= $kpi['factures_jour'] ?></div>
                <div class="kpi-label">Factures du jour</div>
                <div class="kpi-trend warn">
                    <i class="fas fa-clock"></i> Aujourd'hui
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
                <div class="kpi-value"><?= $kpi['attente_paiement'] ?></div>
                <div class="kpi-label">En attente paiement</div>
                <div class="kpi-trend down">
                    <i class="fas fa-exclamation"></i> À encaisser
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════
     RACCOURCIS RAPIDES
══════════════════════════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card-drouot">
            <p class="card-section-title mb-3">
                <i class="fas fa-bolt text-warning me-2"></i>Actions rapides
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= BASE_URL ?>secretaire/patients/create"
                   class="raccourci-btn raccourci-blue">
                    <i class="fas fa-user-plus"></i>
                    <span>Nouveau patient</span>
                </a>
                <a href="<?= BASE_URL ?>secretaire/demandes/create"
                   class="raccourci-btn raccourci-green">
                    <i class="fas fa-flask"></i>
                    <span>Nouvelle demande</span>
                </a>
                <a href="<?= BASE_URL ?>secretaire/patients"
                   class="raccourci-btn raccourci-gray">
                    <i class="fas fa-magnifying-glass"></i>
                    <span>Rechercher patient</span>
                </a>
                <a href="<?= BASE_URL ?>secretaire/factures"
                   class="raccourci-btn raccourci-orange">
                    <i class="fas fa-file-invoice"></i>
                    <span>Générer facture</span>
                </a>
                <a href="<?= BASE_URL ?>secretaire/notifications"
                   class="raccourci-btn raccourci-gray">
                    <i class="fas fa-paper-plane"></i>
                    <span>Envoyer lien patient</span>
                </a>
                <a href="<?= BASE_URL ?>secretaire/patients?action=qr"
                   class="raccourci-btn raccourci-gray">
                    <i class="fas fa-qrcode"></i>
                    <span>Imprimer QR Code</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     DEMANDES RÉCENTES + PATIENTS RÉCENTS
══════════════════════════════════════ -->
<div class="row g-3">

    <!-- Demandes récentes -->
    <div class="col-xl-8">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-flask text-primary me-2"></i>
                    Demandes récentes
                </h6>
                <a href="<?= BASE_URL ?>secretaire/demandes"
                   class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($demandes)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-flask fa-2x mb-2"></i>
                    <p>Aucune demande pour le moment.</p>
                    <a href="<?= BASE_URL ?>secretaire/demandes/create"
                       class="btn-primary-drouot mt-2">
                        <i class="fas fa-plus"></i> Créer une demande
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-drouot">
                        <thead>
                            <tr>
                                <th>Réf.</th>
                                <th>Patient</th>
                                <th>Examens</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($demandes as $d): ?>
                            <tr>
                                <td>
                                    <span class="ref-code">
                                        DE-<?= str_pad($d['id'], 4, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-500">
                                        <?= htmlspecialchars($d['patient_nom']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="examens-cell" title="<?= htmlspecialchars($d['examens'] ?? '') ?>">
                                        <?= htmlspecialchars(
                                            mb_strimwidth($d['examens'] ?? '—', 0, 30, '...')
                                        ) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-500">
                                        <?= number_format((float)$d['total_price'], 0, ',', ' ') ?> F
                                    </span>
                                </td>
                                <td><?= statusLabel($d['status']) ?></td>
                                <td><?= paymentLabel($d['payment_status']) ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>secretaire/demandes/<?= $d['id'] ?>"
                                       class="action-btn" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Patients récents -->
    <div class="col-xl-4">
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-users text-primary me-2"></i>
                    Patients récents
                </h6>
                <a href="<?= BASE_URL ?>secretaire/patients"
                   class="btn-link-drouot">
                    Voir tout <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (empty($patients_recents)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <p>Aucun patient enregistré.</p>
                </div>
            <?php else: ?>
                <div class="patient-list">
                    <?php foreach ($patients_recents as $p): ?>
                    <div class="patient-item">
                        <div class="patient-avatar <?= $p['sexe'] === 'F' ? 'female' : 'male' ?>">
                            <?= strtoupper(substr($p['prenom'] ?? 'P', 0, 1) . substr($p['nom'] ?? '', 0, 1)) ?>
                        </div>
                        <div class="patient-info">
                            <div class="patient-name">
                                <?= htmlspecialchars(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? '')) ?>
                            </div>
                            <div class="patient-phone">
                                <i class="fas fa-phone"></i>
                                <?= htmlspecialchars($p['telephone'] ?? '—') ?>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>secretaire/patients/<?= $p['id'] ?>"
                           class="action-btn ms-auto" title="Voir dossier">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Bouton créer patient -->
            <div class="mt-3">
                <a href="<?= BASE_URL ?>secretaire/patients/create"
                   class="btn-primary-drouot w-100 justify-content-center">
                    <i class="fas fa-user-plus"></i> Nouveau patient
                </a>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════
     STYLES SPÉCIFIQUES SECRÉTAIRE
══════════════════════════════════════ -->
<style>
    .page-title {
        font-size: 1.3rem; font-weight: 600;
        color: #1a2340; margin: 0;
    }
    .page-sub {
        font-size: 0.85rem; color: #6b7a99; margin: 2px 0 0;
    }
    .card-section-title {
        font-size: 0.88rem; font-weight: 600; color: #1a2340; margin: 0;
    }
    .btn-link-drouot {
        font-size: 0.78rem; color: #1E88E5;
        text-decoration: none;
        display: flex; align-items: center; gap: 4px;
    }
    .btn-link-drouot:hover { color: #0D47A1; }

    /* Raccourcis */
    .raccourci-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 16px; border-radius: 10px;
        font-size: 0.85rem; font-weight: 500;
        text-decoration: none; transition: all 0.2s;
        border: 1.5px solid transparent;
    }
    .raccourci-btn i { font-size: 0.9rem; }
    .raccourci-blue  { background:#E3F2FD; color:#1565C0; border-color:#BBDEFB; }
    .raccourci-blue:hover  { background:#1E88E5; color:#fff; border-color:#1E88E5; }
    .raccourci-green { background:#E8F5E9; color:#2E7D32; border-color:#C8E6C9; }
    .raccourci-green:hover { background:#2E7D32; color:#fff; border-color:#2E7D32; }
    .raccourci-orange{ background:#FFF8E1; color:#F57F17; border-color:#FFE082; }
    .raccourci-orange:hover{ background:#F9A825; color:#fff; border-color:#F9A825; }
    .raccourci-gray  { background:#F4F6F9; color:#1a2340; border-color:#dde3f0; }
    .raccourci-gray:hover  { background:#0D47A1; color:#fff; border-color:#0D47A1; }

    /* Table */
    .table-drouot {
        width: 100%; border-collapse: collapse; font-size: 0.83rem;
    }
    .table-drouot th {
        padding: 8px 10px; text-align: left;
        font-size: 0.75rem; font-weight: 600;
        color: #6b7a99; text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1.5px solid #f0f2f8;
        background: #fafbfc;
    }
    .table-drouot td {
        padding: 10px 10px; color: #1a2340;
        border-bottom: 1px solid #f0f2f8;
        vertical-align: middle;
    }
    .table-drouot tr:last-child td { border-bottom: none; }
    .table-drouot tr:hover td { background: #fafbff; }
    .ref-code {
        font-family: monospace; font-size: 0.78rem;
        color: #1E88E5; font-weight: 600;
    }
    .examens-cell { color: #6b7a99; }
    .fw-500 { font-weight: 500; }
    .action-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; border-radius: 7px;
        background: #E3F2FD; color: #1565C0;
        text-decoration: none; transition: all 0.2s;
        font-size: 0.8rem;
    }
    .action-btn:hover { background: #1E88E5; color: #fff; }

    /* Patients list */
    .patient-list { display: flex; flex-direction: column; gap: 2px; }
    .patient-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f2f8;
    }
    .patient-item:last-child { border-bottom: none; }
    .patient-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.8rem; font-weight: 600; flex-shrink: 0;
    }
    .patient-avatar.male   { background: #E3F2FD; color: #1565C0; }
    .patient-avatar.female { background: #FCE4EC; color: #880E4F; }
    .patient-name {
        font-size: 0.85rem; font-weight: 500; color: #1a2340;
    }
    .patient-phone {
        font-size: 0.75rem; color: #6b7a99;
        display: flex; align-items: center; gap: 4px;
    }
    .patient-phone i { font-size: 0.65rem; }
    .w-100 { width: 100%; }
    .justify-content-center { justify-content: center; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>