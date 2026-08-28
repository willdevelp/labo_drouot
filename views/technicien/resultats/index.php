<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Résultats saisis</h1>
        <p class="page-sub"><?= count($resultats) ?> fiche(s)</p>
    </div>
</div>

<div class="card-drouot">
    <?php if (empty($resultats)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-clipboard fa-3x mb-3" style="color:#dde3f0;"></i>
            <p>Aucun résultat saisi pour le moment.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Code barre</th>
                    <th>Patient</th>
                    <th>Paramètres</th>
                    <th>Anormaux</th>
                    <th>Statut</th>
                    <th>Dernière MAJ</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultats as $r): ?>
                <tr>
                    <td>
                        <span class="ref-code">
                            <?= htmlspecialchars($r['code_barre']) ?>
                        </span>
                    </td>
                    <td style="font-weight:500;font-size:.88rem;">
                        <?= htmlspecialchars($r['patient_nom']) ?>
                    </td>
                    <td>
                        <span class="badge-pending">
                            <?= $r['nb_resultats'] ?> param.
                        </span>
                    </td>
                    <td>
                        <?php if ($r['nb_anormaux'] > 0): ?>
                            <span class="badge-critical">
                                ⚠️ <?= $r['nb_anormaux'] ?> anormal(aux)
                            </span>
                        <?php else: ?>
                            <span class="badge-normal">✅ Normal</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $badges = [
                            'collected'   => ['badge-pending','Collecté'],
                            'in_analysis' => ['badge-warn','En analyse'],
                            'analyzed'    => ['badge-normal','Transmis'],
                        ];
                        [$cls,$lbl] = $badges[$r['status']]
                            ?? ['badge-pending',$r['status']];
                        ?>
                        <span class="<?= $cls ?>"><?= $lbl ?></span>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= $r['last_update']
                            ? date('d/m/Y H:i', strtotime($r['last_update']))
                            : '—' ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>technicien/resultats/<?= $r['id'] ?>"
                           class="btn-saisir">
                            <i class="fas fa-pen"></i> Éditer
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
.btn-saisir { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:8px; background:#1E88E5; color:#fff; font-size:.75rem; font-weight:500; text-decoration:none; transition:background .2s; }
.btn-saisir:hover { background:#0D47A1; color:#fff; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>