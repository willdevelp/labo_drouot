<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Résultats validés</h1>
        <p class="page-sub"><?= count($resultatsValides) ?> validation(s)</p>
    </div>
</div>

<div class="card-drouot">
    <?php if (empty($resultatsValides)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-clipboard-check fa-3x mb-3"
               style="color:#dde3f0;"></i>
            <p>Aucun résultat validé pour le moment.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Code barre</th>
                    <th>Patient</th>
                    <th>Examens</th>
                    <th>Résultats</th>
                    <th>Anormaux</th>
                    <th>Validé par</th>
                    <th>Date validation</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultatsValides as $r): ?>
                <tr>
                    <td>
                        <span class="ref-code">
                            <?= htmlspecialchars($r['code_barre']) ?>
                        </span>
                    </td>
                    <td style="font-weight:500;font-size:.88rem;">
                        <?= htmlspecialchars($r['patient_nom']) ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;max-width:160px;">
                        <?= htmlspecialchars(
                            mb_strimwidth($r['examens'] ?? '—', 0, 40, '...')
                        ) ?>
                    </td>
                    <td>
                        <span class="badge-pending">
                            <?= $r['nb_resultats'] ?> param.
                        </span>
                    </td>
                    <td>
                        <?php if ($r['nb_anormaux'] > 0): ?>
                            <span class="badge-critical">
                                ⚠️ <?= $r['nb_anormaux'] ?>
                            </span>
                        <?php else: ?>
                            <span class="badge-normal">✅</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;">
                        <?= htmlspecialchars($r['biologiste_nom'] ?? '—') ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= $r['validated_at']
                            ? date('d/m/Y H:i', strtotime($r['validated_at']))
                            : '—' ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>biologiste/validations/<?= $r['val_id'] ?>"
                           class="action-btn">
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
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.ref-code { font-family:monospace; font-size:.78rem; color:#1E88E5; font-weight:600; }
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; background:#E3F2FD; color:#1565C0; text-decoration:none; font-size:.78rem; transition:all .2s; }
.action-btn:hover { background:#1E88E5; color:#fff; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>