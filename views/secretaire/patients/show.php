<?php
/**
 * Fiche patient — avec QR Code + lien WhatsApp
 */
include __DIR__ . '/../../../views/layouts/header.php';

// Lien portail patient
$portalLink = BASE_URL . 'patient/portal/' . ($tokenData['token'] ?? '');

// Lien WhatsApp
$message    = urlencode(
    "Bonjour " . $patient['prenom'] . " " . $patient['nom'] . ",\n\n" .
    "Vos résultats d'analyses du Laboratoire Drouot sont disponibles.\n" .
    "Consultez-les ici : " . $portalLink . "\n\n" .
    "Labo Drouot — Douala"
);
$whatsappLink = "https://wa.me/237" .
    preg_replace('/[^0-9]/', '', $patient['telephone']) .
    "?text=" . $message;
?>

<!-- Alerte succès création -->
<?php if ($created): ?>
<div class="alert-drouot alert-success mb-4">
    <i class="fas fa-circle-check"></i>
    Patient créé avec succès ! QR Code et lien portail générés.
</div>
<?php endif; ?>

<!-- Header page -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            Dossier patient
        </h1>
        <p class="page-sub">
            ID : <strong><?= htmlspecialchars($patient['uuid']) ?></strong>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>secretaire/patients/<?= $patient['id'] ?>/edit"
           class="btn-outline-drouot">
            <i class="fas fa-pen"></i> Modifier
        </a>
        <a href="<?= BASE_URL ?>secretaire/demandes/create?patient_id=<?= $patient['id'] ?>"
           class="btn-primary-drouot">
            <i class="fas fa-flask"></i> Nouvelle demande
        </a>
    </div>
</div>

<div class="row g-4">

    <!-- Colonne gauche — Infos patient -->
    <div class="col-xl-8">

        <!-- Infos personnelles -->
        <div class="card-drouot mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <!-- Avatar -->
                <div class="patient-big-avatar <?= $patient['sexe'] === 'F' ? 'female' : 'male' ?>">
                    <?= strtoupper(
                        substr($patient['prenom'] ?? 'P', 0, 1) .
                        substr($patient['nom']    ?? '',  0, 1)
                    ) ?>
                </div>
                <div>
                    <h2 class="patient-full-name">
                        <?= htmlspecialchars(strtoupper($patient['nom']) . ' ' . $patient['prenom']) ?>
                    </h2>
                    <div class="d-flex gap-2 mt-1">
                        <span class="badge-<?= $patient['sexe'] === 'F' ? 'female' : 'male' ?>-badge">
                            <?= $patient['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?>
                        </span>
                        <?php if ($patient['date_naissance']): ?>
                        <span class="age-badge">
                            <?= date_diff(
                                date_create($patient['date_naissance']),
                                date_create('today')
                            )->y ?> ans
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-phone"></i> Téléphone
                    </span>
                    <span class="info-value">
                        <?= htmlspecialchars($patient['telephone'] ?? '—') ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-envelope"></i> Email
                    </span>
                    <span class="info-value">
                        <?= htmlspecialchars($patient['email'] ?? '—') ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-calendar"></i> Date naissance
                    </span>
                    <span class="info-value">
                        <?= $patient['date_naissance']
                            ? date('d/m/Y', strtotime($patient['date_naissance']))
                            : '—' ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-location-dot"></i> Adresse
                    </span>
                    <span class="info-value">
                        <?= htmlspecialchars($patient['adresse'] ?? '—') ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">
                        <i class="fas fa-clock"></i> Enregistré le
                    </span>
                    <span class="info-value">
                        <?= date('d/m/Y à H:i', strtotime($patient['created_at'])) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Historique demandes -->
        <div class="card-drouot">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="card-section-title">
                    <i class="fas fa-flask text-primary me-2"></i>
                    Historique des demandes
                </h6>
                <a href="<?= BASE_URL ?>secretaire/demandes/create?patient_id=<?= $patient['id'] ?>"
                   class="btn-primary-drouot" style="font-size:0.78rem;padding:5px 12px;">
                    <i class="fas fa-plus"></i> Nouvelle demande
                </a>
            </div>

            <?php if (empty($historique)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-flask fa-2x mb-2 d-block" style="color:#dde3f0;"></i>
                    Aucune demande pour ce patient.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-drouot">
                        <thead>
                            <tr>
                                <th>Réf.</th>
                                <th>Examens</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique as $d): ?>
                            <tr>
                                <td>
                                    <span class="ref-code">
                                        DE-<?= str_pad($d['id'], 4, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>
                                <td style="font-size:0.82rem;color:#6b7a99;">
                                    <?= htmlspecialchars(
                                        mb_strimwidth($d['examens'] ?? '—', 0, 40, '...')
                                    ) ?>
                                </td>
                                <td>
                                    <?= number_format((float)$d['total_price'], 0, ',', ' ') ?> F
                                </td>
                                <td>
                                    <?php
                                    $badges = [
                                        'pending'    => 'badge-pending',
                                        'paid'       => 'badge-paid',
                                        'processing' => 'badge-warn',
                                        'completed'  => 'badge-normal',
                                    ];
                                    $cls = $badges[$d['status']] ?? 'badge-pending';
                                    ?>
                                    <span class="<?= $cls ?>">
                                        <?= ucfirst($d['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size:0.8rem;color:#6b7a99;">
                                    <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Colonne droite — QR Code + WhatsApp -->
    <div class="col-xl-4">

        <!-- QR Code -->
        <div class="card-drouot mb-4 text-center">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-qrcode text-primary me-2"></i>
                QR Code patient
            </h6>
            <div class="qr-container" id="qrcode"></div>
            <p class="qr-uuid"><?= htmlspecialchars($patient['uuid']) ?></p>
            <button onclick="window.print()" class="btn-outline-drouot w-100 mt-2">
                <i class="fas fa-print"></i> Imprimer QR Code
            </button>
        </div>

        <!-- Lien portail -->
        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-link text-primary me-2"></i>
                Lien portail patient
            </h6>
            <div class="portal-link-box">
                <span id="portalLinkText">
                    <?= htmlspecialchars($portalLink) ?>
                </span>
                <button onclick="copyLink()" class="copy-btn" title="Copier">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <p class="link-note">
                <i class="fas fa-shield-halved"></i>
                Lien sécurisé — valable 1 an
            </p>
        </div>

        <!-- Envoyer WhatsApp -->
        <div class="card-drouot mb-4">
            <h6 class="card-section-title mb-3">
                <i class="fab fa-whatsapp" style="color:#25D366;"></i>
                Envoyer au patient
            </h6>
            <a href="<?= $whatsappLink ?>"
               target="_blank"
               class="whatsapp-btn">
                <i class="fab fa-whatsapp"></i>
                Envoyer via WhatsApp
            </a>
            <p class="link-note mt-2">
                <i class="fas fa-phone"></i>
                <?= htmlspecialchars($patient['telephone']) ?>
            </p>
        </div>

        <!-- Actions rapides -->
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-bolt text-warning me-2"></i>
                Actions
            </h6>
            <div class="d-flex flex-column gap-2">
                <a href="<?= BASE_URL ?>secretaire/demandes/create?patient_id=<?= $patient['id'] ?>"
                   class="btn-primary-drouot justify-content-center">
                    <i class="fas fa-flask"></i> Créer une demande
                </a>
                <a href="<?= BASE_URL ?>secretaire/patients/<?= $patient['id'] ?>/edit"
                   class="btn-outline-drouot justify-content-center">
                    <i class="fas fa-pen"></i> Modifier le dossier
                </a>
                <a href="<?= BASE_URL ?>secretaire/patients"
                   class="btn-outline-drouot justify-content-center">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        </div>

    </div>
</div>

<style>
.page-title  { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub    { font-size:0.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:0.88rem; font-weight:600; color:#1a2340; margin:0; }

.alert-drouot { padding:0.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:0.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }

/* Avatar grand */
.patient-big-avatar {
    width:60px; height:60px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; font-weight:600; flex-shrink:0;
}
.patient-big-avatar.male   { background:#E3F2FD; color:#1565C0; }
.patient-big-avatar.female { background:#FCE4EC; color:#880E4F; }
.patient-full-name { font-size:1.15rem; font-weight:600; color:#1a2340; margin:0; }

/* Badges */
.badge-male-badge   { background:#E3F2FD; color:#1565C0; padding:2px 10px; border-radius:20px; font-size:0.75rem; }
.badge-female-badge { background:#FCE4EC; color:#880E4F; padding:2px 10px; border-radius:20px; font-size:0.75rem; }
.age-badge { background:#F4F6F9; color:#6b7a99; padding:2px 10px; border-radius:20px; font-size:0.75rem; }

/* Info grid */
.info-grid { display:flex; flex-direction:column; gap:0; }
.info-row {
    display:flex; align-items:center; justify-content:space-between;
    padding:9px 0; border-bottom:1px solid #f0f2f8; font-size:0.85rem;
}
.info-row:last-child { border-bottom:none; }
.info-label { color:#6b7a99; display:flex; align-items:center; gap:8px; }
.info-label i { width:14px; text-align:center; color:#90CAF9; }
.info-value { font-weight:500; color:#1a2340; }

/* Table */
.table-drouot { width:100%; border-collapse:collapse; font-size:0.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:0.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.ref-code { font-family:monospace; font-size:0.78rem; color:#1E88E5; font-weight:600; }

/* QR Code */
.qr-container { display:flex; justify-content:center; padding:10px 0; }
.qr-uuid { font-size:0.7rem; color:#6b7a99; font-family:monospace; margin-top:6px; word-break:break-all; }

/* Portal link */
.portal-link-box {
    display:flex; align-items:center; gap:8px;
    background:#f4f6f9; border:1px solid #dde3f0;
    border-radius:8px; padding:8px 10px; font-size:0.72rem;
    color:#6b7a99; word-break:break-all;
}
.copy-btn {
    background:#1E88E5; color:#fff; border:none;
    border-radius:6px; width:28px; height:28px;
    cursor:pointer; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:0.8rem; transition:background .2s;
}
.copy-btn:hover { background:#0D47A1; }
.link-note { font-size:0.72rem; color:#6b7a99; margin:6px 0 0; display:flex; align-items:center; gap:5px; }
.link-note i { color:#90CAF9; }

/* WhatsApp */
.whatsapp-btn {
    display:flex; align-items:center; justify-content:center; gap:8px;
    width:100%; padding:10px; border-radius:10px;
    background:#25D366; color:#fff;
    font-size:0.95rem; font-weight:600;
    text-decoration:none; transition:background .2s;
}
.whatsapp-btn:hover { background:#128C7E; color:#fff; }
.whatsapp-btn i { font-size:1.1rem; }

/* Boutons */
.btn-outline-drouot {
    display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:1.5px solid #dde3f0;
    border-radius:9px; background:#fff; color:#1a2340;
    font-size:.88rem; font-weight:500; cursor:pointer;
    text-decoration:none; transition:all .2s;
    font-family:'DM Sans',sans-serif;
}
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
.justify-content-center { justify-content:center; }
.w-100 { width:100%; }
</style>

<!-- QR Code library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
// Générer QR Code
new QRCode(document.getElementById('qrcode'), {
    text: "<?= addslashes($portalLink) ?>",
    width: 160, height: 160,
    colorDark: "#0D47A1",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});

// Copier lien
function copyLink() {
    navigator.clipboard.writeText("<?= addslashes($portalLink) ?>")
        .then(() => {
            const btn = document.querySelector('.copy-btn');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.style.background = '#2E7D32';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i>';
                btn.style.background = '#1E88E5';
            }, 2000);
        });
}
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>