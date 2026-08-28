<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes assurés — Laboratoire Drouot</title>
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
.wrap { max-width:820px; margin:0 auto; padding:22px 16px 70px; }
a { color:inherit; }
:focus-visible { outline:2.5px solid var(--aubergine); outline-offset:2px; border-radius:4px; }
@media (prefers-reduced-motion: reduce) { * { animation-duration:.001ms !important; transition-duration:.001ms !important; } }

.back-link {
    display:inline-flex; align-items:center; gap:7px;
    font-size:.8rem; font-weight:600; color:var(--aubergine);
    text-decoration:none; margin-bottom:14px;
}
.back-link:hover { color:var(--aubergine-deep); }

.page-header {
    background:linear-gradient(135deg, var(--aubergine-deep), var(--aubergine) 130%);
    border-radius:var(--radius-lg); padding:20px 22px; margin-bottom:16px;
    color:#fff; box-shadow:var(--shadow);
}
.page-title { font-family:'Fraunces', serif; font-weight:600; font-size:1.15rem; margin:0; }
.page-sub { font-size:.78rem; color:rgba(255,255,255,.75); margin:4px 0 0; }

.search-card {
    background:var(--paper-card); border-radius:var(--radius-md); padding:12px;
    box-shadow:var(--shadow); border:1px solid var(--line); margin-bottom:16px;
}
.search-form { display:flex; gap:8px; }
.search-input-wrap { position:relative; flex:1; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:var(--ink-faint); }
.search-input {
    width:100%; padding:.65rem .9rem .65rem 2.3rem;
    border:1.5px solid var(--line); border-radius:var(--radius-sm);
    font-family:'Plus Jakarta Sans', sans-serif; font-size:.86rem;
    color:var(--ink); background:var(--paper); outline:none;
}
.search-input:focus { border-color:var(--aubergine); background:#fff; }
.btn-search {
    padding:.65rem 1.1rem; border-radius:var(--radius-sm); border:none; cursor:pointer;
    background:var(--aubergine); color:#fff; font-size:.85rem; font-weight:600;
}
.btn-search:hover { background:var(--aubergine-deep); }

.assure-card {
    background:var(--paper-card); border-radius:var(--radius-md); padding:14px 16px;
    box-shadow:var(--shadow); border:1px solid var(--line); margin-bottom:10px;
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
}
.assure-info { flex:1; min-width:180px; }
.assure-nom { font-family:'Fraunces', serif; font-weight:600; font-size:.94rem; }
.assure-police { font-family:'JetBrains Mono', monospace; font-size:.72rem; color:var(--ink-soft); margin-top:2px; }

.stamp-tag {
    display:inline-block; font-family:'Fraunces', serif; font-weight:700;
    font-size:.62rem; text-transform:uppercase; letter-spacing:.05em;
    padding:2px 10px; border-radius:20px 4px 20px 4px;
    border:1.3px solid currentColor; transform:rotate(-3deg);
}
.stamp-en_regle { background:var(--sage-tint); color:var(--sage); }
.stamp-suspendu { background:var(--clay-tint); color:var(--clay); }

.taux-tag {
    font-family:'JetBrains Mono', monospace; font-size:.68rem; font-weight:600;
    background:var(--gold-tint); color:var(--gold); padding:3px 9px; border-radius:6px;
    white-space:nowrap;
}
.taux-tag.none { background:var(--paper); color:var(--ink-faint); }

.btn-config {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; border-radius:var(--radius-sm); border:none; cursor:pointer;
    background:var(--aubergine-tint); color:var(--aubergine); text-decoration:none;
    font-size:.78rem; font-weight:600; transition:all .15s; white-space:nowrap;
}
.btn-config:hover { background:var(--aubergine); color:#fff; }

.empty-state { text-align:center; padding:44px 20px; color:var(--ink-faint); }
.empty-state i { font-size:2.2rem; color:var(--line); margin-bottom:12px; display:block; }
.empty-state p { font-size:.85rem; margin:0; color:var(--ink-soft); }

@media (max-width:560px){
    .assure-card { flex-direction:column; align-items:stretch; }
    .btn-config { justify-content:center; }
}
</style>
</head>
<body>
<div class="wrap">

    <a href="<?= BASE_URL ?>assurance/portal/<?= $token ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Retour au portail
    </a>

    <div class="page-header">
        <p class="page-title">Mes assurés</p>
        <p class="page-sub"><?= count($assures) ?> assuré(s) enregistré(s) — configurez des taux de prise en charge propres à chacun.</p>
    </div>

    <div class="search-card">
        <form method="GET" class="search-form">
            <div class="search-input-wrap">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" name="search" class="search-input"
                       placeholder="Nom de l'assuré, numéro de police..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <?php if (empty($assures)): ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <p>Aucun assuré trouvé. Importez votre fichier depuis le portail pour commencer.</p>
    </div>
    <?php else: ?>
        <?php foreach ($assures as $a): ?>
        <div class="assure-card">
            <div class="assure-info">
                <div class="assure-nom"><?= htmlspecialchars($a['nom_assure']) ?></div>
                <div class="assure-police">N° <?= htmlspecialchars($a['numero_police']) ?></div>
            </div>
            <span class="stamp-tag stamp-<?= $a['statut'] ?>">
                <?= $a['statut'] === 'en_regle' ? 'En règle' : 'Suspendu' ?>
            </span>
            <?php if ($a['nb_taux_specifiques'] > 0): ?>
            <span class="taux-tag">
                <i class="fas fa-percent"></i> <?= $a['nb_taux_specifiques'] ?> taux personnalisé(s)
            </span>
            <?php else: ?>
            <span class="taux-tag none">Taux standard</span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>assurance/portal/<?= $token ?>/assures/<?= $a['id'] ?>/taux" class="btn-config">
                <i class="fas fa-sliders"></i> Configurer les taux
            </a>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>
