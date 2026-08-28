<?php
/**
 * Liste des demandes d'examens
 */
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Demandes d'examens</h1>
        <p class="page-sub"><?= count($demandes) ?> demande(s)</p>
    </div>
    <a href="<?= BASE_URL ?>secretaire/demandes/create"
       class="btn-primary-drouot">
        <i class="fas fa-plus"></i> Nouvelle demande
    </a>
</div>

<!-- Filtres -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search" class="search-input"
                   placeholder="Rechercher par patient, téléphone..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <select name="statut" class="filter-select">
            <option value="">Tous les statuts</option>
            <option value="pending"
                <?= ($_GET['statut'] ?? '') === 'pending'    ? 'selected' : '' ?>>
                En attente
            </option>
            <option value="paid"
                <?= ($_GET['statut'] ?? '') === 'paid'       ? 'selected' : '' ?>>
                Payé
            </option>
            <option value="processing"
                <?= ($_GET['statut'] ?? '') === 'processing' ? 'selected' : '' ?>>
                En cours
            </option>
            <option value="completed"
                <?= ($_GET['statut'] ?? '') === 'completed'  ? 'selected' : '' ?>>
                Terminé
            </option>
        </select>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-filter"></i> Filtrer
        </button>
        <a href="<?= BASE_URL ?>secretaire/demandes"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Table -->
<div class="card-drouot">
    <?php if (empty($demandes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-flask fa-3x mb-3" style="color:#dde3f0;"></i>
            <p>Aucune demande trouvée.</p>
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
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demandes as $d):
                    $statusBadges = [
                        'pending'    => 'badge-pending',
                        'paid'       => 'badge-paid',
                        'processing' => 'badge-warn',
                        'completed'  => 'badge-normal',
                    ];
                    $sc = $statusBadges[$d['status']] ?? 'badge-pending';
                    $pc = $d['payment_status'] === 'paid'
                        ? 'badge-normal' : 'badge-critical';
                ?>
                <tr>
                    <td>
                        <span class="ref-code">
                            DE-<?= str_pad($d['id'], 4, '0', STR_PAD_LEFT) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:500;font-size:.88rem;">
                            <?= htmlspecialchars($d['patient_nom']) ?>
                        </div>
                        <div style="font-size:.75rem;color:#6b7a99;">
                            <?= htmlspecialchars($d['telephone']) ?>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;max-width:180px;">
                        <?= htmlspecialchars(
                            mb_strimwidth($d['examens'] ?? '—', 0, 40, '...')
                        ) ?>
                    </td>
                    <td style="font-weight:600;color:#1E88E5;">
                        <?= number_format((float)$d['total_price'], 0, ',', ' ') ?> F
                    </td>
                    <td><span class="<?= $sc ?>">
                        <?= ucfirst($d['status']) ?>
                    </span></td>
                    <td><span class="<?= $pc ?>">
                        <?= $d['payment_status'] === 'paid' ? 'Payé' : 'Impayé' ?>
                    </span></td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
                    </td>
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

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap { position:relative; flex:1; min-width:200px; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem;
    border:1.5px solid #dde3f0; border-radius:10px;
    font-family:'DM Sans',sans-serif; font-size:.9rem;
    color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.filter-select { padding:.7rem .9rem; border:1.5px solid #dde3f0;
    border-radius:10px; font-family:'DM Sans',sans-serif;
    font-size:.88rem; color:#1a2340; background:#fafbfd;
    outline:none; cursor:pointer; }
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem;
    font-weight:600; color:#6b7a99; text-transform:uppercase;
    letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340;
    border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.ref-code { font-family:monospace; font-size:.78rem; color:#1E88E5; font-weight:600; }
.action-btn { display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px; border-radius:6px;
    background:#E3F2FD; color:#1565C0;
    text-decoration:none; font-size:.78rem; transition:all .2s; }
.action-btn:hover { background:#1E88E5; color:#fff; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px;
    background:#fff; color:#1a2340; font-size:.88rem; font-weight:500;
    text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>