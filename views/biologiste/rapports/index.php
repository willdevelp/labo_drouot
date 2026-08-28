<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Rapports générés</h1>
        <p class="page-sub"><?= count($rapports) ?> rapport(s)</p>
    </div>
</div>

<div class="card-drouot">
    <?php if (empty($rapports)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-file-waveform fa-3x mb-3"
               style="color:#dde3f0;"></i>
            <p>Aucun rapport généré pour le moment.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Demande</th>
                    <th>Montant</th>
                    <th>Commentaire</th>
                    <th>Généré le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rapports as $r): ?>
                <tr>
                    <td style="font-weight:500;font-size:.88rem;">
                        <?= htmlspecialchars($r['patient_nom']) ?>
                        <div style="font-size:.75rem;color:#6b7a99;">
                            <?= htmlspecialchars($r['telephone']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="ref-code">
                            DE-<?= str_pad($r['demande_id'], 4, '0', STR_PAD_LEFT) ?>
                        </span>
                    </td>
                    <td style="font-weight:600;color:#1E88E5;">
                        <?= number_format((float)$r['total_price'], 0, ',', ' ') ?> F
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;max-width:160px;">
                        <?= $r['commentaire']
                            ? htmlspecialchars(
                                mb_strimwidth($r['commentaire'], 0, 40, '...')
                              )
                            : '<span style="color:#b0b8cc;">—</span>' ?>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= $r['generated_at']
                            ? date('d/m/Y H:i', strtotime($r['generated_at']))
                            : '—' ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= BASE_URL ?>secretaire/patients/<?= $r['demande_id'] ?>"
                               class="action-btn" title="Voir dossier">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="window.print()"
                                    class="action-btn action-btn-green"
                                    title="Imprimer">
                                <i class="fas fa-print"></i>
                            </button>
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
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.ref-code { font-family:monospace; font-size:.78rem; color:#1E88E5; font-weight:600; }
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; background:#E3F2FD; color:#1565C0; text-decoration:none; font-size:.78rem; transition:all .2s; border:none; cursor:pointer; }
.action-btn:hover { background:#1E88E5; color:#fff; }
.action-btn-green { background:#E8F5E9; color:#2E7D32; }
.action-btn-green:hover { background:#2E7D32; color:#fff; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>