<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Résultats — Portail Médecin — Laboratoire Drouot</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { box-sizing:border-box; }
body {
    margin:0; font-family:'DM Sans', -apple-system, sans-serif;
    background:#f4f6fb; color:#1a2340;
}
.wrap { max-width:920px; margin:0 auto; padding:20px 16px 60px; }

.back-link {
    display:inline-flex; align-items:center; gap:6px;
    color:#1E88E5; text-decoration:none; font-size:.85rem;
    font-weight:500; margin-bottom:14px;
}

.result-header {
    background:#fff; border-radius:16px; padding:20px;
    margin-bottom:18px; box-shadow:0 2px 10px rgba(0,0,0,.04);
}
.result-header-top {
    display:flex; align-items:center; justify-content:space-between;
    gap:10px; flex-wrap:wrap;
}
.patient-nom-big { font-size:1.1rem; font-weight:700; }
.patient-meta { font-size:.82rem; color:#6b7a99; margin-top:3px; }
.code-badge {
    background:#E3F2FD; color:#1565C0; padding:3px 12px;
    border-radius:20px; font-size:.8rem; font-family:monospace;
}

.examen-block {
    background:#fff; border-radius:14px; padding:16px;
    margin-bottom:12px; box-shadow:0 2px 10px rgba(0,0,0,.04);
    border-left:3px solid #1E88E5;
}
.examen-header {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:12px; padding-bottom:10px;
    border-bottom:1.5px solid #f0f2f8; flex-wrap:wrap; gap:8px;
}
.examen-nom { font-size:.95rem; font-weight:600; }
.examen-cat { font-size:.72rem; color:#6b7a99; background:#F4F6F9; padding:1px 8px; border-radius:6px; }

.table-resultats { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-resultats th {
    padding:8px 10px; text-align:left; font-size:.72rem;
    font-weight:600; color:#6b7a99; text-transform:uppercase;
    letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8;
}
.table-resultats td { padding:9px 10px; border-bottom:1px solid #f0f2f8; }
.table-resultats tr:last-child td { border-bottom:none; }
.row-abnormal td { background:#fff5f5; }
.value-abnormal { color:#C62828; font-weight:600; }
.value-normal { color:#1a2340; font-weight:600; }
.norme-range { background:#E3F2FD; color:#1565C0; padding:2px 6px; border-radius:4px; font-size:.72rem; }
.interp-badge { display:inline-flex; padding:3px 8px; border-radius:20px; font-size:.72rem; font-weight:500; }
.interp-normal { background:#E8F5E9; color:#2E7D32; }
.interp-low { background:#E3F2FD; color:#1565C0; }
.interp-high { background:#FFEBEE; color:#C62828; }

.comment-box {
    background:#fff; border-radius:14px; padding:16px;
    margin-top:14px; box-shadow:0 2px 10px rgba(0,0,0,.04);
    border-left:3px solid #2E7D32;
}
.comment-title { font-size:.82rem; font-weight:600; color:#6b7a99; margin-bottom:6px; }
.comment-text { font-size:.88rem; color:#1a2340; line-height:1.5; }
.comment-meta { font-size:.74rem; color:#6b7a99; margin-top:8px; }

.footer-note {
    text-align:center; font-size:.72rem; color:#b0b8cc; margin-top:24px;
}
</style>
</head>
<body>
<div class="wrap">

    <a href="<?= BASE_URL ?>medecin/portal/<?= $token ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Retour au portail
    </a>

    <!-- Header résultat -->
    <div class="result-header">
        <div class="result-header-top">
            <div>
                <div class="patient-nom-big"><?= htmlspecialchars($demande['patient_nom']) ?></div>
                <div class="patient-meta">
                    <?= $demande['sexe'] === 'F' ? 'Féminin' : 'Masculin' ?>
                    <?php if ($demande['date_naissance']): ?>
                    — <?= date_diff(date_create($demande['date_naissance']), date_create('today'))->y ?> ans
                    <?php endif; ?>
                </div>
            </div>
            <span class="code-badge"><?= htmlspecialchars($demande['code_barre']) ?></span>
        </div>
    </div>

    <!-- Résultats par examen -->
    <?php foreach ($examens as $ex): ?>
    <?php $params = $resultatsData[$ex['id']] ?? []; ?>
    <div class="examen-block">
        <div class="examen-header">
            <div class="examen-nom"><?= htmlspecialchars($ex['nom']) ?></div>
            <span class="examen-cat"><?= htmlspecialchars($ex['categorie'] ?? '') ?></span>
        </div>

        <?php if (empty($params)): ?>
            <p style="font-size:.82rem;color:#6b7a99;">Aucun paramètre disponible.</p>
        <?php else: ?>
        <table class="table-resultats">
            <thead>
                <tr>
                    <th>Paramètre</th>
                    <th>Valeur</th>
                    <th>Unité</th>
                    <th>Norme</th>
                    <th>Interprétation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($params as $param): ?>
                <tr class="<?= $param['is_abnormal'] ? 'row-abnormal' : '' ?>">
                    <td><?= htmlspecialchars($param['libelle']) ?></td>
                    <td class="<?= $param['is_abnormal'] ? 'value-abnormal' : 'value-normal' ?>">
                        <?= $param['valeur'] !== null ? htmlspecialchars($param['valeur']) : '—' ?>
                    </td>
                    <td><?= htmlspecialchars($param['unite'] ?? '') ?></td>
                    <td>
                        <?php if ($param['valeur_min'] !== null || $param['valeur_max'] !== null): ?>
                        <span class="norme-range">
                            <?= $param['valeur_min'] ?? '?' ?> – <?= $param['valeur_max'] ?? '?' ?>
                        </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $interp = $param['interpretation'] ?? 'normal';
                        $cfg = [
                            'normal' => ['interp-normal', '✅ Normal'],
                            'bas'    => ['interp-low',    '⬇️ Bas'],
                            'élevé'  => ['interp-high',   '⬆️ Élevé'],
                        ];
                        [$ic, $il] = $cfg[$interp] ?? ['interp-normal', '—'];
                        ?>
                        <span class="interp-badge <?= $ic ?>"><?= $il ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Commentaire biologiste -->
    <?php if ($demande['commentaire']): ?>
    <div class="comment-box">
        <div class="comment-title">
            <i class="fas fa-comment-medical"></i> Interprétation biologique
        </div>
        <div class="comment-text"><?= nl2br(htmlspecialchars($demande['commentaire'])) ?></div>
        <div class="comment-meta">
            Validé par Dr <?= htmlspecialchars($demande['biologiste_nom'] ?? '—') ?>
            le <?= $demande['validated_at'] ? date('d/m/Y à H:i', strtotime($demande['validated_at'])) : '—' ?>
        </div>
    </div>
    <?php endif; ?>

    <p class="footer-note">
        Laboratoire Drouot — Douala · Lien personnel, ne pas partager
    </p>

</div>
</body>
</html>
