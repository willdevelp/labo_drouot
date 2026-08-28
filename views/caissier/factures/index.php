<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Gestion des factures</h1>
        <p class="page-sub"><?= count($factures) ?> facture(s)</p>
    </div>
</div>

<!-- Stats rapides -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fas fa-file-invoice"></i></div>
            <div>
                <div class="kpi-value"><?= $stats['total'] ?></div>
                <div class="kpi-label">Total factures</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fas fa-circle-check"></i></div>
            <div>
                <div class="kpi-value"><?= $stats['payees'] ?></div>
                <div class="kpi-label">Payées</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon red"><i class="fas fa-hourglass-half"></i></div>
            <div>
                <div class="kpi-value"><?= $stats['attente'] ?></div>
                <div class="kpi-label">Non encaissées</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fas fa-money-bill-wave"></i></div>
            <div>
                <div class="kpi-value" style="font-size:1rem;">
                    <?= number_format((float)$stats['revenus_jour'], 0, ',', ' ') ?>
                </div>
                <div class="kpi-label">Revenus aujourd'hui (FCFA)</div>
            </div>
        </div>
    </div>
</div>

<!-- Filtres -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search" class="search-input"
                   placeholder="Rechercher patient..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
    <select name="statut" class="filter-select">
            <option value="">Tous</option>
            <option value="emise"
                <?= ($_GET['statut'] ?? '') === 'emise' ? 'selected' : '' ?>>
                Non encaissées
            </option>
            <option value="partiellement_payee"
                <?= ($_GET['statut'] ?? '') === 'partiellement_payee' ? 'selected' : '' ?>>
                Partiellement payées
            </option>
            <option value="payee"
                <?= ($_GET['statut'] ?? '') === 'payee' ? 'selected' : '' ?>>
                Payées
            </option>
        </select>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-filter"></i> Filtrer
        </button>
        <a href="<?= BASE_URL ?>caissier/factures"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Table -->
<div class="card-drouot">
    <?php if (empty($factures)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-file-invoice fa-3x mb-3" style="color:#dde3f0;"></i>
            <p>Aucune facture trouvée.</p>
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
                    <th>Mode</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($factures as $f):
                    // ATTENTION : factures.statut est un ENUM français
                    // ('brouillon','emise','payee','partiellement_payee',
                    // 'annulee','remboursee') — ne jamais comparer à
                    // 'paid'/'pending' (anglais), sinon tout tombe côté
                    // "non encaissée" par défaut.
                    $estPayee     = $f['statut'] === 'payee';
                    $estPartielle = $f['statut'] === 'partiellement_payee';
                    $estEncaissee = $estPayee || $estPartielle;

                    // Nombre de jours depuis l'émission, pour signaler
                    // les factures non encaissées qui traînent.
                    $joursAttente = null;
                    if (!$estPayee) {
                        $joursAttente = (int) floor(
                            (time() - strtotime($f['created_at'])) / 86400
                        );
                    }
                ?>
                <tr class="<?= !$estPayee ? 'tr-non-encaisse' : '' ?>">
                    <td>
                        <span class="ref-code">
                            FAC-<?= str_pad($f['id'], 4, '0', STR_PAD_LEFT) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:500;font-size:.88rem;">
                            <?= htmlspecialchars($f['patient_nom']) ?>
                        </div>
                        <div style="font-size:.75rem;color:#6b7a99;">
                            <?= htmlspecialchars($f['telephone']) ?>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;max-width:160px;">
                        <?= htmlspecialchars(
                            mb_strimwidth($f['examens'] ?? '—', 0, 35, '...')
                        ) ?>
                    </td>
                    <td style="font-weight:700;color:#1E88E5;">
                        <?= number_format((float)$f['montant_total'], 0, ',', ' ') ?> F
                    </td>
                    <td>
                        <?php if ($f['mode_paiement']): ?>
                        <span class="mode-badge mode-<?= $f['mode_paiement'] ?>">
                            <?php $modes = [
                                'cash'      => '💵 Espèces',
                                'momo'      => '📱 MoMo',
                                'card'      => '💳 Carte',
                                'insurance' => '🏥 Assurance',
                            ]; echo $modes[$f['mode_paiement']] ?? $f['mode_paiement']; ?>
                        </span>
                        <?php else: ?>
                            <span style="color:#b0b8cc;font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($estPayee): ?>
                            <span class="badge-normal">
                                <i class="fas fa-circle-check"></i> Payée
                            </span>
                        <?php elseif ($estPartielle): ?>
                            <span class="badge-partielle">
                                <i class="fas fa-circle-half-stroke"></i> Partiellement payée
                            </span>
                            <?php if ($joursAttente >= 2): ?>
                            <div class="attente-depuis">
                                depuis <?= $joursAttente ?> jour<?= $joursAttente > 1 ? 's' : '' ?>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge-non-encaisse">
                                <span class="pulse-dot"></span>
                                Non encaissée
                            </span>
                            <?php if ($joursAttente >= 2): ?>
                            <div class="attente-depuis">
                                depuis <?= $joursAttente ?> jour<?= $joursAttente > 1 ? 's' : '' ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= date('d/m/Y H:i', strtotime($f['created_at'])) ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>caissier/factures/<?= $f['id'] ?>"
                               class="action-btn <?= !$estPayee ? 'action-btn-pulse' : '' ?>"
                               title="<?= $estPayee ? 'Voir' : 'Encaisser' ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($estPayee): ?>
                            <a href="<?= BASE_URL ?>caissier/recus/<?= $f['id'] ?>"
                               class="action-btn action-btn-green" title="Reçu">
                                <i class="fas fa-receipt"></i>
                            </a>
                            <?php endif; ?>
                        </div>
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
.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap { position:relative; flex:1; min-width:200px; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.filter-select { padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; cursor:pointer; }
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.ref-code { font-family:monospace; font-size:.78rem; color:#1E88E5; font-weight:600; }
.mode-badge { padding:3px 8px; border-radius:6px; font-size:.75rem; font-weight:500; background:#F4F6F9; color:#1a2340; }
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; background:#E3F2FD; color:#1565C0; text-decoration:none; font-size:.78rem; transition:all .2s; }
.action-btn:hover { background:#1E88E5; color:#fff; }
.action-btn-green { background:#E8F5E9; color:#2E7D32; }
.action-btn-green:hover { background:#2E7D32; color:#fff; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }

/* ── Facture non encaissée : mise en avant ── */
.tr-non-encaisse td {
    background:#FFFBF2;
}
.tr-non-encaisse:hover td {
    background:#FFF6E5;
}
.tr-non-encaisse td:first-child {
    box-shadow: inset 3px 0 0 #F57F17;
}

.badge-non-encaisse {
    display:inline-flex; align-items:center; gap:6px;
    padding:3px 10px; border-radius:20px;
    background:#FFF3E0; color:#E65100;
    font-size:.75rem; font-weight:600;
    border:1px solid #FFCC80;
}
.pulse-dot {
    width:6px; height:6px; border-radius:50%;
    background:#F57F17; flex-shrink:0;
    animation: pulse-dot-anim 1.6s ease-in-out infinite;
}
@keyframes pulse-dot-anim {
    0%   { box-shadow: 0 0 0 0 rgba(245,127,23,.55); }
    70%  { box-shadow: 0 0 0 6px rgba(245,127,23,0); }
    100% { box-shadow: 0 0 0 0 rgba(245,127,23,0); }
}
@media (prefers-reduced-motion: reduce) {
    .pulse-dot { animation: none; }
}

.attente-depuis {
    font-size:.68rem; color:#E65100; margin-top:3px; font-weight:500;
}

.badge-partielle {
    display:inline-flex; align-items:center; gap:6px;
    padding:3px 10px; border-radius:20px;
    background:#E3F2FD; color:#1565C0;
    font-size:.75rem; font-weight:600;
    border:1px solid #90CAF9;
}

.action-btn-pulse {
    background:#FFF3E0; color:#E65100;
}
.action-btn-pulse:hover { background:#F57F17; color:#fff; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>