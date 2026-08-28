<?php
/**
 * Résultats détaillés — Vue patient simplifiée
 */
$age = $patient['date_naissance']
    ? date_diff(
        date_create($patient['date_naissance']),
        date_create('today')
    )->y
    : null;

// Compter anormaux globaux
$totalAnormaux = 0;
foreach ($resultatsData as $params) {
    foreach ($params as $p) {
        if ($p['is_abnormal']) $totalAnormaux++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats — Laboratoire Drouot</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --blue:   #1E88E5;
            --dark:   #0D47A1;
            --green:  #2E7D32;
            --orange: #F9A825;
            --red:    #C62828;
            --bg:     #F4F6F9;
            --white:  #ffffff;
            --text:   #1a2340;
            --muted:  #6b7a99;
            --border: #dde3f0;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'DM Sans',sans-serif;
            background:var(--bg); color:var(--text);
            min-height:100vh;
        }

        /* Topbar */
        .topbar {
            background:var(--dark); padding:12px 16px;
            display:flex; align-items:center; gap:12px;
            position:sticky; top:0; z-index:100;
        }
        .back-btn {
            display:flex; align-items:center; gap:6px;
            color:#90CAF9; text-decoration:none;
            font-size:.85rem; transition:color .2s;
        }
        .back-btn:hover { color:#fff; }
        .topbar-title {
            color:#fff; font-size:.92rem; font-weight:600; flex:1;
        }
        .print-btn {
            background:rgba(255,255,255,.15); color:#fff;
            border:none; border-radius:8px;
            padding:6px 12px; font-size:.78rem;
            cursor:pointer; display:flex; align-items:center; gap:5px;
            font-family:'DM Sans',sans-serif; transition:background .2s;
        }
        .print-btn:hover { background:rgba(255,255,255,.25); }

        /* Hero résultat */
        .result-hero {
            background:linear-gradient(135deg, var(--dark), var(--blue));
            padding:20px 16px; color:#fff;
        }
        .result-hero-inner {
            max-width:700px; margin:0 auto;
        }
        .result-hero-title {
            font-size:1.1rem; font-weight:600; margin-bottom:4px;
        }
        .result-hero-sub {
            font-size:.8rem; color:rgba(255,255,255,.75);
        }
        .result-hero-badges {
            display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;
        }
        .hero-chip {
            background:rgba(255,255,255,.15);
            border-radius:20px; padding:4px 12px;
            font-size:.75rem;
            display:flex; align-items:center; gap:5px;
        }
        .hero-chip.abnormal {
            background:rgba(198,40,40,.4);
        }

        /* Contenu */
        .result-content {
            max-width:700px; margin:0 auto;
            padding:20px 16px;
        }

        /* Alerte globale anormaux */
        .global-alert {
            background:#FFF3E0;
            border:1.5px solid #FFB74D;
            border-radius:12px; padding:14px 16px;
            display:flex; gap:12px; align-items:flex-start;
            margin-bottom:20px; font-size:.85rem; color:#E65100;
        }
        .global-alert i { font-size:1.2rem; flex-shrink:0; }

        /* Bloc commentaire biologiste */
        .bio-block {
            background:var(--white);
            border-radius:12px; padding:16px;
            border-left:4px solid var(--blue);
            margin-bottom:20px;
            box-shadow:0 2px 8px rgba(13,71,161,.05);
        }
        .bio-block-title {
            font-size:.78rem; font-weight:600; color:var(--muted);
            text-transform:uppercase; letter-spacing:.06em;
            margin-bottom:8px;
            display:flex; align-items:center; gap:6px;
        }
        .bio-block-text {
            font-size:.9rem; color:var(--text); line-height:1.6;
            font-style:italic;
        }
        .bio-block-sign {
            font-size:.75rem; color:var(--muted); margin-top:8px;
            display:flex; align-items:center; gap:5px;
        }

        /* Section examen */
        .examen-section {
            background:var(--white); border-radius:12px;
            border:1px solid var(--border); margin-bottom:14px;
            overflow:hidden;
            box-shadow:0 2px 8px rgba(13,71,161,.05);
        }
        .examen-section-header {
            padding:14px 16px;
            border-bottom:1px solid var(--border);
            display:flex; align-items:center;
            justify-content:space-between;
            background:#fafbfc;
        }
        .examen-section-nom {
            font-size:.92rem; font-weight:600; color:var(--text);
        }
        .examen-section-cat {
            font-size:.72rem; color:var(--muted);
            background:var(--bg); padding:2px 8px;
            border-radius:6px; margin-top:2px;
        }

        /* Paramètres patient simplifié */
        .param-patient {
            padding:12px 16px;
            border-bottom:1px solid #f5f7fa;
            display:flex; align-items:center; gap:12px;
        }
        .param-patient:last-child { border-bottom:none; }
        .param-patient.abnormal { background:#fff9f9; }

        .param-left { flex:1; }
        .param-nom  {
            font-size:.85rem; font-weight:500; color:var(--text);
            margin-bottom:2px;
        }
        .param-norme {
            font-size:.72rem; color:var(--muted);
        }

        .param-right { text-align:right; }
        .param-val {
            font-size:1rem; font-weight:700;
        }
        .param-val.normal  { color:var(--green); }
        .param-val.low     { color:var(--blue); }
        .param-val.high    { color:var(--red); }
        .param-val.missing { color:#b0b8cc; font-size:.85rem;
            font-weight:400; }

        .param-interp {
            font-size:.72rem; margin-top:2px;
            display:flex; align-items:center;
            justify-content:flex-end; gap:3px;
        }
        .interp-normal  { color:var(--green); }
        .interp-low     { color:var(--blue); }
        .interp-high    { color:var(--red); }

        /* Indicateur barre */
        .param-indicator {
            width:6px; height:40px; border-radius:3px;
            flex-shrink:0;
        }
        .ind-normal  { background:#E8F5E9; }
        .ind-low     { background:#E3F2FD; }
        .ind-high    { background:#FFEBEE; }
        .ind-missing { background:#f0f2f8; }

        /* Footer */
        .result-footer {
            max-width:700px; margin:0 auto;
            padding:0 16px 32px;
        }
        .footer-card {
            background:var(--white); border-radius:12px;
            border:1px solid var(--border); padding:16px;
            text-align:center;
        }
        .footer-card p {
            font-size:.78rem; color:var(--muted);
            line-height:1.6;
        }
        .footer-card strong { color:var(--text); }

        /* Print */
        @media print {
            .topbar, .print-btn, .back-btn { display:none !important; }
            body { background:#fff; }
            .examen-section { box-shadow:none; break-inside:avoid; }
        }
        @media (max-width:480px) {
            .param-patient { flex-wrap:wrap; }
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <a href="<?= BASE_URL ?>patient/portal/<?= urlencode($token) ?>"
       class="back-btn">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
    <span class="topbar-title">Mes résultats</span>
    <button onclick="window.print()" class="print-btn">
        <i class="fas fa-print"></i> Imprimer
    </button>
</div>

<!-- Hero -->
<div class="result-hero">
    <div class="result-hero-inner">
        <div class="result-hero-title">
            <?= htmlspecialchars(
                $patient['prenom'] . ' ' . strtoupper($patient['nom'])
            ) ?>
        </div>
        <div class="result-hero-sub">
            Résultats du
            <?= date('d/m/Y', strtotime($demande['created_at'])) ?>
            <?= $age ? "· $age ans" : '' ?>
        </div>
        <div class="result-hero-badges">
            <span class="hero-chip">
                <i class="fas fa-flask"></i>
                <?= count($examens) ?> examen(s)
            </span>
            <?php if ($demande['validated_at']): ?>
            <span class="hero-chip">
                <i class="fas fa-user-doctor"></i>
                Validé le
                <?= date('d/m/Y', strtotime($demande['validated_at'])) ?>
            </span>
            <?php endif; ?>
            <?php if ($totalAnormaux > 0): ?>
            <span class="hero-chip abnormal">
                ⚠️ <?= $totalAnormaux ?> valeur(s) à surveiller
            </span>
            <?php else: ?>
            <span class="hero-chip">
                ✅ Tous dans les normes
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contenu -->
<div class="result-content">

    <!-- Alerte anormaux -->
    <?php if ($totalAnormaux > 0): ?>
    <div class="global-alert">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong><?= $totalAnormaux ?> valeur(s) en dehors des normes.</strong>
            Consultez votre médecin pour une interprétation complète.
        </div>
    </div>
    <?php endif; ?>

    <!-- Commentaire biologiste -->
    <?php if ($demande['commentaire']): ?>
    <div class="bio-block">
        <div class="bio-block-title">
            <i class="fas fa-user-doctor" style="color:var(--blue);"></i>
            Interprétation du biologiste
        </div>
        <div class="bio-block-text">
            "<?= htmlspecialchars($demande['commentaire']) ?>"
        </div>
        <div class="bio-block-sign">
            <i class="fas fa-signature"></i>
            Dr. <?= htmlspecialchars($demande['biologiste_nom'] ?? 'Biologiste') ?>
            — Laboratoire Drouot
            <?php if ($demande['validated_at']): ?>
            · <?= date('d/m/Y', strtotime($demande['validated_at'])) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Résultats par examen -->
    <?php foreach ($examens as $ex): ?>
    <?php $params = $resultatsData[$ex['id']] ?? []; ?>

    <div class="examen-section">
        <div class="examen-section-header">
            <div>
                <div class="examen-section-nom">
                    <?= htmlspecialchars($ex['nom']) ?>
                </div>
                <div class="examen-section-cat">
                    <?= htmlspecialchars($ex['categorie'] ?? '') ?>
                </div>
            </div>
            <?php
            $abnCount = count(array_filter(
                $params, fn($p) => $p['is_abnormal']
            ));
            ?>
            <?php if ($abnCount > 0): ?>
                <span style="background:#FFEBEE;color:#C62828;
                    padding:3px 10px;border-radius:20px;font-size:.72rem;
                    font-weight:600;">
                    ⚠️ <?= $abnCount ?> anormal(aux)
                </span>
            <?php else: ?>
                <span style="background:#E8F5E9;color:#2E7D32;
                    padding:3px 10px;border-radius:20px;font-size:.72rem;
                    font-weight:600;">
                    ✅ Normal
                </span>
            <?php endif; ?>
        </div>

        <?php foreach ($params as $p): ?>
        <?php
        $interp  = $p['interpretation'] ?? 'normal';
        $valeur  = $p['valeur'];
        $indCls  = 'ind-missing';
        $valCls  = 'missing';
        $interpCls = '';
        $interpIcon = '';

        if ($valeur !== null) {
            if ($p['is_abnormal']) {
                if ($interp === 'bas') {
                    $indCls = 'ind-low'; $valCls = 'low';
                    $interpCls = 'interp-low';
                    $interpIcon = '⬇️ Bas';
                } else {
                    $indCls = 'ind-high'; $valCls = 'high';
                    $interpCls = 'interp-high';
                    $interpIcon = '⬆️ Élevé';
                }
            } else {
                $indCls = 'ind-normal'; $valCls = 'normal';
                $interpCls = 'interp-normal';
                $interpIcon = '✅ Normal';
            }
        }
        ?>
        <div class="param-patient <?= $p['is_abnormal'] ? 'abnormal' : '' ?>">

            <div class="param-indicator <?= $indCls ?>"></div>

            <div class="param-left">
                <div class="param-nom">
                    <?= htmlspecialchars($p['libelle']) ?>
                </div>
                <?php if ($p['valeur_min'] !== null ||
                          $p['valeur_max'] !== null): ?>
                <div class="param-norme">
                    Norme :
                    <?= $p['valeur_min'] ?? '?' ?>
                    –
                    <?= $p['valeur_max'] ?? '?' ?>
                    <?= htmlspecialchars($p['unite'] ?? '') ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="param-right">
                <?php if ($valeur !== null): ?>
                <div class="param-val <?= $valCls ?>">
                    <?= htmlspecialchars($valeur) ?>
                    <?php if ($p['unite']): ?>
                    <span style="font-size:.75rem;font-weight:400;
                        color:var(--muted);">
                        <?= htmlspecialchars($p['unite']) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="param-interp <?= $interpCls ?>">
                    <?= $interpIcon ?>
                </div>
                <?php else: ?>
                <div class="param-val missing">— Non saisi</div>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>

    </div>
    <?php endforeach; ?>

</div>

<!-- Footer -->
<div class="result-footer">
    <div class="footer-card">
        <p>
            <strong>🧪 Laboratoire Drouot · Douala</strong><br>
            Ces résultats ont été validés par un biologiste qualifié.<br>
            Pour toute question, contactez votre médecin traitant.<br>
            <br>
            <span style="font-size:.7rem;">
                🔒 Résultats confidentiels — Ne partagez pas ce lien
            </span>
        </p>
    </div>
</div>

</body>
</html>