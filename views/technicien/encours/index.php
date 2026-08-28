<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Analyses en cours</h1>
        <p class="page-sub"><?= count($encours) ?> en cours</p>
    </div>
</div>

<div class="card-drouot">
    <?php if (empty($encours)): ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-3x mb-3" style="color:#2E7D32;"></i>
            <p class="text-muted">Aucune analyse en cours. Tout est traité !</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Code barre</th>
                    <th>Patient</th>
                    <th>Examens</th>
                    <th>Collecté</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($encours as $e): ?>
                <tr>
                    <td>
                        <span class="ref-code">
                            <?= htmlspecialchars($e['code_barre']) ?>
                        </span>
                    </td>
                    <td style="font-weight:500;font-size:.88rem;">
                        <?= htmlspecialchars($e['patient_nom']) ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;max-width:200px;">
                        <?= htmlspecialchars(
                            mb_strimwidth($e['examens'] ?? '—', 0, 50, '...')
                        ) ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= $e['collected_at']
                            ? date('d/m/Y H:i', strtotime($e['collected_at']))
                            : '—' ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>technicien/resultats/<?= $e['id'] ?>"
                           class="btn-saisir">
                            <i class="fas fa-keyboard"></i> Continuer
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
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.ref-code { font-family:monospace; font-size:.78rem; color:#1E88E5; font-weight:600; }
.btn-saisir { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:8px; background:#F9A825; color:#fff; font-size:.75rem; font-weight:500; text-decoration:none; transition:background .2s; }
.btn-saisir:hover { background:#F57F17; color:#fff; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>