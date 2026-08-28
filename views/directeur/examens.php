<?php
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../config/database.php';
Auth::requireRole(['directeur', 'admin']);
$db   = Database::getConnection();
$user = Auth::user();

// Stats examens
$statsExamens = $db->query("
    SELECT
        e.id, e.nom, e.categorie, e.prix,
        COUNT(dei.id)    AS nb_realises,
        SUM(dei.price)   AS revenus_generes
    FROM examens e
    LEFT JOIN demande_examens_items dei ON dei.examen_id = e.id
    WHERE e.is_active = 1
    GROUP BY e.id
    ORDER BY nb_realises DESC
")->fetchAll();

// Stats par catégorie
$parCategorie = $db->query("
    SELECT
        e.categorie,
        COUNT(DISTINCT e.id)  AS nb_examens,
        COUNT(dei.id)         AS nb_realises,
        SUM(dei.price)        AS revenus
    FROM examens e
    LEFT JOIN demande_examens_items dei ON dei.examen_id = e.id
    WHERE e.is_active = 1
    GROUP BY e.categorie
    ORDER BY nb_realises DESC
")->fetchAll();

include __DIR__ . '/../../views/layouts/header.php';
?>

<div class="d-flex align-items-center
            justify-content-between mb-4">
    <div>
        <h1 class="page-title">Statistiques examens</h1>
        <p class="page-sub">Performance par examen et catégorie</p>
    </div>
    <a href="<?= BASE_URL ?>dashboard/directeur"
       class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<!-- Par catégorie -->
<div class="card-drouot mb-4">
    <h6 class="card-section-title mb-3">
        <i class="fas fa-folder-open text-primary me-2"></i>
        Performance par catégorie
    </h6>
    <?php if (empty($parCategorie)): ?>
        <p class="text-muted text-center py-3">
            Aucune donnée.
        </p>
    <?php else: ?>
    <div class="row g-3">
        <?php
        $catColors = [
            '#1E88E5','#2E7D32','#F9A825',
            '#C62828','#7B1FA2','#00838F'
        ];
        foreach ($parCategorie as $i => $cat):
        ?>
        <div class="col-md-4">
            <div class="cat-card"
                 style="border-top:3px solid
                 <?= $catColors[$i % 6] ?>">
                <div class="cat-nom">
                    <?= htmlspecialchars(
                        $cat['categorie'] ?? 'Autre'
                    ) ?>
                </div>
                <div class="cat-val">
                    <?= $cat['nb_realises'] ?>
                    <span>réalisé(s)</span>
                </div>
                <div class="cat-rev"
                     style="color:<?= $catColors[$i % 6] ?>">
                    <?= number_format(
                        (float)$cat['revenus'], 0, ',', ' '
                    ) ?> FCFA
                </div>
                <div class="cat-nb">
                    <?= $cat['nb_examens'] ?> type(s) d'examen
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Détail examens -->
<div class="card-drouot">
    <h6 class="card-section-title mb-3">
        <i class="fas fa-flask text-primary me-2"></i>
        Détail par examen
    </h6>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Examen</th>
                    <th>Catégorie</th>
                    <th>Prix unitaire</th>
                    <th>Réalisés</th>
                    <th>Revenus générés</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>
                <?php
             $maxNb = 1;

if (!empty($statsExamens)) {
    $maxNb = max(
        1,
        max(array_column($statsExamens, 'nb_realises'))
    );
}
                foreach ($statsExamens as $ex):
                    $pct = round($ex['nb_realises']/$maxNb*100);
                ?>
                <tr>
                    <td style="font-weight:500;font-size:.88rem;">
                        <?= htmlspecialchars($ex['nom']) ?>
                    </td>
                    <td>
                        <span class="cat-badge">
                            <?= htmlspecialchars(
                                $ex['categorie'] ?? '—'
                            ) ?>
                        </span>
                    </td>
                    <td style="color:#1E88E5;font-weight:500;">
                        <?= number_format(
                            (float)$ex['prix'], 0, ',', ' '
                        ) ?> F
                    </td>
                    <td style="font-weight:600;">
                        <?= $ex['nb_realises'] ?>
                    </td>
                    <td style="font-weight:600;color:#2E7D32;">
                        <?= number_format(
                            (float)$ex['revenus_generes'],
                            0, ',', ' '
                        ) ?> F
                    </td>
                    <td>
                        <div style="display:flex;
                            align-items:center;gap:6px;">
                            <div style="flex:1;height:6px;
                                background:#f0f2f8;
                                border-radius:10px;
                                overflow:hidden;">
                                <div style="width:<?= $pct ?>%;
                                    height:100%;background:#1E88E5;
                                    border-radius:10px;"></div>
                            </div>
                            <span style="font-size:.72rem;
                                color:#6b7a99;min-width:30px;">
                                <?= $pct ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; }
.cat-card { background:#fafbfc; border-radius:10px; padding:14px; border:1px solid #f0f2f8; }
.cat-nom { font-size:.82rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
.cat-val { font-size:1.4rem; font-weight:700; color:#1a2340; }
.cat-val span { font-size:.75rem; font-weight:400; color:#6b7a99; }
.cat-rev { font-size:.85rem; font-weight:600; margin-top:2px; }
.cat-nb  { font-size:.72rem; color:#b0b8cc; margin-top:4px; }
.cat-badge { background:#F4F6F9; color:#6b7a99; padding:2px 8px; border-radius:6px; font-size:.75rem; }
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include __DIR__ . '/../../views/layouts/footer.php'; ?>