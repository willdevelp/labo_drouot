<?php
include __DIR__ . '/../../../views/layouts/header.php';
?>

<?php if (isset($_GET['transmitted'])): ?>
<div class="alert-drouot alert-success mb-4">
    <i class="fas fa-circle-check"></i>
    Résultats transmis au biologiste avec succès !
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Échantillons</h1>
        <p class="page-sub"><?= count($echantillons) ?> échantillon(s)</p>
    </div>
</div>

<!-- Filtres -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search" class="search-input"
                   placeholder="Code barre, patient..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <select name="statut" class="filter-select">
            <option value="">À traiter</option>
            <option value="collected"
                <?= ($_GET['statut']??'') === 'collected' ? 'selected':'' ?>>
                Collectés
            </option>
            <option value="in_analysis"
                <?= ($_GET['statut']??'') === 'in_analysis' ? 'selected':'' ?>>
                En analyse
            </option>
            <option value="analyzed"
                <?= ($_GET['statut']??'') === 'analyzed' ? 'selected':'' ?>>
                Terminés
            </option>
        </select>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-filter"></i> Filtrer
        </button>
        <a href="<?= BASE_URL ?>technicien/echantillons"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Liste -->
<div class="card-drouot">
    <?php if (empty($echantillons)): ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-3x mb-3"
               style="color:#2E7D32;"></i>
            <p class="text-muted">
                Aucun échantillon à traiter pour le moment.
            </p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Code barre</th>
                    <th>Patient</th>
                    <th>Examens</th>
                    <th>Statut</th>
                    <th>Progression</th>
                    <th>Collecté</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($echantillons as $ech): ?>
                <tr>
                    <td>
                        <span class="ref-code">
                            <?= htmlspecialchars($ech['code_barre']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:500;font-size:.88rem;">
                            <?= htmlspecialchars($ech['patient_nom']) ?>
                        </div>
                        <div style="font-size:.75rem;color:#6b7a99;">
                            <?= htmlspecialchars($ech['telephone']) ?>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;max-width:180px;">
                        <?= htmlspecialchars(
                            mb_strimwidth($ech['examens'] ?? '—', 0, 40, '...')
                        ) ?>
                        <div style="font-size:.72rem;color:#90CAF9;">
                            <?= $ech['nb_examens'] ?> examen(s)
                        </div>
                    </td>
                    <td>
                        <?php
                        $badges = [
                            'collected'   => ['badge-pending','Collecté'],
                            'in_analysis' => ['badge-warn','En analyse'],
                            'analyzed'    => ['badge-normal','Terminé'],
                        ];
                        [$cls,$lbl] = $badges[$ech['status']]
                            ?? ['badge-pending',$ech['status']];
                        ?>
                        <span class="<?= $cls ?>"><?= $lbl ?></span>
                    </td>
                    <td>
                        <?php
                        $pct = $ech['nb_examens'] > 0
                            ? min(100, round(
                                ($ech['nb_resultats'] / $ech['nb_examens']) * 100
                              ))
                            : 0;
                        $barColor = $pct === 100 ? '#2E7D32'
                            : ($pct > 0 ? '#F9A825' : '#dde3f0');
                        ?>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="flex:1;height:6px;background:#f0f2f8;
                                border-radius:10px;overflow:hidden;">
                                <div style="width:<?= $pct ?>%;height:100%;
                                    background:<?= $barColor ?>;border-radius:10px;">
                                </div>
                            </div>
                            <span style="font-size:.72rem;color:#6b7a99;
                                white-space:nowrap;">
                                <?= $ech['nb_resultats'] ?>/<?= $ech['nb_examens'] ?>
                            </span>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= $ech['collected_at']
                            ? date('d/m H:i', strtotime($ech['collected_at']))
                            : '—' ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>technicien/resultats/<?= $ech['id'] ?>"
                           class="btn-saisir">
                            <i class="fas fa-keyboard"></i> Saisir
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
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.filter-select { padding:.7rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; cursor:pointer; }
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.ref-code { font-family:monospace; font-size:.78rem; color:#1E88E5; font-weight:600; }
.btn-saisir { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:8px; background:#1E88E5; color:#fff; font-size:.75rem; font-weight:500; text-decoration:none; transition:background .2s; }
.btn-saisir:hover { background:#0D47A1; color:#fff; }
.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>