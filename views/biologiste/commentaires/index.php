<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Commentaires biologiques</h1>
        <p class="page-sub"><?= count($commentaires) ?> commentaire(s)</p>
    </div>
</div>

<div class="row g-3">
    <?php if (empty($commentaires)): ?>
        <div class="col-12">
            <div class="card-drouot text-center py-5 text-muted">
                <i class="fas fa-comment-medical fa-3x mb-3"
                   style="color:#dde3f0;"></i>
                <p>Aucun commentaire pour le moment.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($commentaires as $c): ?>
        <div class="col-xl-6">
            <div class="card-drouot comment-card">
                <div class="comment-header">
                    <div class="comment-patient">
                        <?= htmlspecialchars($c['patient_nom']) ?>
                    </div>
                    <div class="comment-code">
                        <?= htmlspecialchars($c['code_barre']) ?>
                    </div>
                </div>
                <div class="comment-text">
                    <i class="fas fa-quote-left"></i>
                    <?= htmlspecialchars($c['commentaire']) ?>
                </div>
                <div class="comment-footer">
                    <span>
                        Dr. <?= htmlspecialchars($c['biologiste_nom'] ?? '—') ?>
                    </span>
                    <span>
                        <?= $c['validated_at']
                            ? date('d/m/Y à H:i',
                                strtotime($c['validated_at']))
                            : '—' ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.comment-card { border-left:3px solid #1E88E5; }
.comment-header { display:flex; justify-content:space-between;
    align-items:center; margin-bottom:10px; }
.comment-patient { font-size:.9rem; font-weight:600; color:#1a2340; }
.comment-code { font-family:monospace; font-size:.72rem; color:#90CAF9;
    background:#E3F2FD; padding:2px 6px; border-radius:4px; }
.comment-text { font-size:.85rem; color:#1a2340; line-height:1.6;
    padding:10px 12px; background:#f0f7ff; border-radius:8px;
    margin-bottom:10px; font-style:italic; }
.comment-text i { color:#90CAF9; margin-right:6px; font-size:.75rem; }
.comment-footer { display:flex; justify-content:space-between;
    font-size:.75rem; color:#6b7a99; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>