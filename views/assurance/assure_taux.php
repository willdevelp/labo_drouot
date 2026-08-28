<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Taux — <?= htmlspecialchars($assure['nom_assure']) ?> — Laboratoire Drouot</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{
    --paper:        #F7F4FB;
    --paper-card:   #FFFFFF;
    --ink:          #211B36;
    --ink-soft:     #6B6480;
    --ink-faint:    #9A93AD;
    --line:         #E4DEF0;
    --aubergine:        #5B2A86;
    --aubergine-deep:   #331A52;
    --aubergine-tint:   #F1E9FA;
    --gold:         #B8863D;
    --gold-tint:    #FBF2E2;
    --sage:         #3F7859;
    --sage-tint:    #E7F2EC;
    --clay:         #A83F3F;
    --clay-tint:    #FBEAEA;
    --radius-lg: 18px;
    --radius-md: 12px;
    --radius-sm: 8px;
    --shadow: 0 2px 14px rgba(51,26,82,.06);
}
* { box-sizing:border-box; }
body {
    margin:0; font-family:'Plus Jakarta Sans', -apple-system, sans-serif;
    background:var(--paper); color:var(--ink); line-height:1.5;
}
.wrap { max-width:820px; margin:0 auto; padding:22px 16px 90px; }
a { color:inherit; }
:focus-visible { outline:2.5px solid var(--aubergine); outline-offset:2px; border-radius:4px; }
@media (prefers-reduced-motion: reduce) { * { animation-duration:.001ms !important; transition-duration:.001ms !important; } }

.back-link {
    display:inline-flex; align-items:center; gap:7px;
    font-size:.8rem; font-weight:600; color:var(--aubergine);
    text-decoration:none; margin-bottom:14px;
}
.back-link:hover { color:var(--aubergine-deep); }

.assure-header {
    background:linear-gradient(135deg, var(--aubergine-deep), var(--aubergine) 130%);
    border-radius:var(--radius-lg); padding:20px 22px; margin-bottom:16px;
    color:#fff; box-shadow:var(--shadow);
    display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
}
.assure-header-nom { font-family:'Fraunces', serif; font-weight:600; font-size:1.1rem; }
.assure-header-police { font-family:'JetBrains Mono', monospace; font-size:.76rem; color:rgba(255,255,255,.75); margin-top:3px; }

.stamp-tag {
    display:inline-block; font-family:'Fraunces', serif; font-weight:700;
    font-size:.64rem; text-transform:uppercase; letter-spacing:.05em;
    padding:3px 11px; border-radius:20px 4px 20px 4px;
    border:1.3px solid #fff; color:#fff; background:rgba(255,255,255,.12);
    transform:rotate(-3deg);
}

.alert-box {
    padding:.8rem 1rem; border-radius:var(--radius-sm); margin-bottom:16px;
    display:flex; align-items:center; gap:9px; font-size:.85rem; font-weight:500;
    background:var(--sage-tint); color:var(--sage);
}

.info-note {
    background:var(--aubergine-tint); color:var(--aubergine-deep);
    padding:11px 14px; border-radius:var(--radius-sm); font-size:.8rem;
    display:flex; align-items:flex-start; gap:9px; margin-bottom:18px; line-height:1.55;
}
.info-note i { margin-top:2px; }

.cat-title {
    font-family:'Fraunces', serif; font-weight:600; font-size:.9rem;
    color:var(--ink); margin:20px 0 8px; padding-bottom:6px;
    border-bottom:1.5px solid var(--line);
}
.cat-title:first-of-type { margin-top:0; }

.examen-row {
    background:var(--paper-card); border:1px solid var(--line); border-radius:var(--radius-sm);
    padding:10px 14px; margin-bottom:7px;
    display:grid; grid-template-columns:1fr auto auto; gap:14px; align-items:center;
}
.examen-nom { font-size:.85rem; font-weight:600; color:var(--ink); }
.examen-code { font-family:'JetBrains Mono', monospace; font-size:.66rem; color:var(--ink-faint); margin-top:1px; }
.examen-prix { font-family:'JetBrains Mono', monospace; font-size:.72rem; color:var(--ink-soft); white-space:nowrap; }

.taux-input-wrap { display:flex; flex-direction:column; align-items:flex-end; gap:3px; }
.taux-input {
    width:82px; padding:7px 9px; text-align:right;
    border:1.5px solid var(--line); border-radius:6px;
    font-family:'JetBrains Mono', monospace; font-size:.85rem; color:var(--ink);
    background:var(--paper); outline:none; transition:all .15s;
}
.taux-input:focus { border-color:var(--aubergine); background:#fff; }
.taux-input.overridden { border-color:var(--gold); background:var(--gold-tint); color:var(--gold); font-weight:600; }
.taux-origin { font-size:.62rem; color:var(--ink-faint); white-space:nowrap; }
.taux-origin.overridden { color:var(--gold); font-weight:600; }

.submit-bar {
    position:sticky; bottom:0; margin-top:22px;
    background:var(--paper-card); border:1px solid var(--line); border-radius:var(--radius-md);
    padding:14px 16px; box-shadow:0 -4px 18px rgba(51,26,82,.08);
    display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
}
.submit-hint { font-size:.75rem; color:var(--ink-soft); }
.btn-save {
    display:inline-flex; align-items:center; gap:7px;
    padding:11px 22px; border-radius:var(--radius-sm); border:none; cursor:pointer;
    background:var(--aubergine); color:#fff; font-size:.86rem; font-weight:600;
    font-family:'Plus Jakarta Sans', sans-serif; transition:background .15s;
}
.btn-save:hover { background:var(--aubergine-deep); }

@media (max-width:560px){
    .examen-row { grid-template-columns:1fr; row-gap:8px; }
    .taux-input-wrap { align-items:flex-start; }
}
</style>
</head>
<body>
<div class="wrap">

    <a href="<?= BASE_URL ?>assurance/portal/<?= $token ?>/assures" class="back-link">
        <i class="fas fa-arrow-left"></i> Retour aux assurés
    </a>

    <div class="assure-header">
        <div>
            <div class="assure-header-nom"><?= htmlspecialchars($assure['nom_assure']) ?></div>
            <div class="assure-header-police">N° <?= htmlspecialchars($assure['numero_police']) ?></div>
        </div>
        <span class="stamp-tag"><?= $assure['statut'] === 'en_regle' ? 'En règle' : 'Suspendu' ?></span>
    </div>

    <?php if ($saved): ?>
    <div class="alert-box">
        <i class="fas fa-circle-check"></i> Taux enregistrés avec succès.
    </div>
    <?php endif; ?>

    <div class="info-note">
        <i class="fas fa-circle-info"></i>
        <span>
            Laissez un champ vide pour appliquer le taux standard de votre compagnie
            (taux spécifique à l'examen s'il existe, sinon votre taux par défaut).
            Un champ rempli devient prioritaire pour cet assuré, sur cet examen précis.
        </span>
    </div>

    <form method="POST" action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/assures/<?= $assure['id'] ?>/taux">
        <?php foreach ($examensGroupes as $categorie => $examens): ?>
        <h6 class="cat-title"><?= htmlspecialchars($categorie) ?></h6>

        <?php foreach ($examens as $ex):
            $isOverridden = $ex['taux_assure'] !== null;
            $origine = $isOverridden
                ? 'Taux personnalisé'
                : ($ex['taux_compagnie'] !== null ? 'Taux compagnie/examen' : 'Taux par défaut');
        ?>
        <div class="examen-row">
            <div>
                <div class="examen-nom"><?= htmlspecialchars($ex['nom']) ?></div>
                <div class="examen-code"><?= htmlspecialchars($ex['code'] ?? '') ?></div>
            </div>
            <div class="examen-prix"><?= number_format((float)$ex['prix'], 0, ',', ' ') ?> F</div>
            <div class="taux-input-wrap">
                <input type="number" step="0.1" min="0" max="100"
                       name="taux[<?= $ex['id'] ?>]"
                       class="taux-input <?= $isOverridden ? 'overridden' : '' ?>"
                       value="<?= $isOverridden ? $ex['taux_assure'] : '' ?>"
                       placeholder="<?= number_format((float)$ex['taux_effectif'], 1) ?>"
                       oninput="markOverride(this)">
                <span class="taux-origin <?= $isOverridden ? 'overridden' : '' ?>" id="origin_<?= $ex['id'] ?>">
                    <?= $origine ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>

        <div class="submit-bar">
            <span class="submit-hint">
                <i class="fas fa-shield-heart"></i> Ces taux s'appliqueront automatiquement dès la prochaine demande de cet assuré.
            </span>
            <button type="submit" class="btn-save">
                <i class="fas fa-floppy-disk"></i> Enregistrer les taux
            </button>
        </div>
    </form>

</div>
<script>
function markOverride(input) {
    const id = input.name.match(/\[(\d+)\]/)[1];
    const origin = document.getElementById('origin_' + id);
    if (input.value.trim() !== '') {
        input.classList.add('overridden');
        origin.textContent = 'Taux personnalisé';
        origin.classList.add('overridden');
    } else {
        input.classList.remove('overridden');
        origin.classList.remove('overridden');
        origin.textContent = 'Taux par défaut';
    }
}
</script>
</body>
</html>
