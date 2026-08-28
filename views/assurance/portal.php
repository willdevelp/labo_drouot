<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portail Assurance — Laboratoire Drouot</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{
    /* ── Palette — dossier d'assurance ── */
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
html { -webkit-text-size-adjust:100%; }
body {
    margin:0; font-family:'Plus Jakarta Sans', -apple-system, sans-serif;
    background:var(--paper); color:var(--ink);
    line-height:1.5;
}
.wrap { max-width:920px; margin:0 auto; padding:22px 16px 70px; }

a { color:inherit; }
:focus-visible { outline:2.5px solid var(--aubergine); outline-offset:2px; border-radius:4px; }
@media (prefers-reduced-motion: reduce) {
    * { animation-duration:.001ms !important; transition-duration:.001ms !important; }
}

.eyebrow {
    font-family:'JetBrains Mono', monospace;
    font-size:.66rem; font-weight:600; letter-spacing:.12em;
    text-transform:uppercase; color:var(--ink-faint);
}

/* ══════════════════════════════════════
   EN-TÊTE — dossier + cachet compagnie
══════════════════════════════════════ */
.portal-header {
    position:relative; overflow:hidden;
    display:flex; align-items:center; gap:16px;
    background:linear-gradient(135deg, var(--aubergine-deep), var(--aubergine) 130%);
    border-radius:var(--radius-lg); padding:22px 24px;
    margin-bottom:16px; box-shadow:var(--shadow);
    color:#fff;
}
.portal-header::before{
    content:''; position:absolute; inset:0;
    background-image:radial-gradient(circle at 85% 15%, rgba(255,255,255,.09), transparent 55%);
    pointer-events:none;
}
.portal-crest {
    width:46px; height:46px; border-radius:50%;
    border:1.5px solid rgba(255,255,255,.55);
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; flex-shrink:0; position:relative;
}
.portal-crest::after{
    content:''; position:absolute; inset:5px;
    border:1px dashed rgba(255,255,255,.4); border-radius:50%;
}
.portal-title {
    font-family:'Fraunces', serif; font-weight:600;
    font-size:1.18rem; margin:0; letter-spacing:-.01em;
}
.portal-sub { font-size:.74rem; color:rgba(255,255,255,.72); margin:3px 0 0; }

/* Cachet compagnie — motif signature, repris pour les statuts plus bas */
.cachet {
    margin-left:auto; flex-shrink:0;
    border:1.5px dashed rgba(255,255,255,.55);
    border-radius:50% / 44%;
    padding:9px 18px;
    transform:rotate(-3deg);
    text-align:center;
}
.cachet-name {
    font-family:'Fraunces', serif; font-weight:700;
    font-size:.86rem; text-transform:uppercase; letter-spacing:.04em;
    white-space:nowrap;
}
.cachet-context {
    font-family:'JetBrains Mono', monospace;
    font-size:.6rem; letter-spacing:.08em; text-transform:uppercase;
    color:rgba(255,255,255,.75); margin-top:2px;
}

/* Navigation secondaire — vers la gestion des assurés */
.portal-nav {
    display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap;
}
.portal-nav-link {
    display:inline-flex; align-items:center; gap:7px;
    padding:9px 15px; border-radius:var(--radius-sm);
    background:var(--paper-card); border:1px solid var(--line);
    color:var(--aubergine); text-decoration:none;
    font-size:.8rem; font-weight:600; box-shadow:var(--shadow);
    transition:all .15s;
}
.portal-nav-link:hover { background:var(--aubergine); color:#fff; border-color:var(--aubergine); }
.portal-nav-link .count-pill {
    background:var(--aubergine-tint); color:var(--aubergine);
    padding:1px 7px; border-radius:20px; font-size:.68rem; font-weight:700;
    font-family:'JetBrains Mono', monospace;
}
.portal-nav-link:hover .count-pill { background:rgba(255,255,255,.25); color:#fff; }

/* ══════════════════════════════════════
   ALERTES
══════════════════════════════════════ */
.alert-box {
    padding:.85rem 1rem; border-radius:var(--radius-sm); margin-bottom:16px;
    display:flex; align-items:center; gap:9px; font-size:.85rem; font-weight:500;
}
.alert-success { background:var(--sage-tint); color:var(--sage); }
.alert-error   { background:var(--clay-tint); color:var(--clay); }

/* ══════════════════════════════════════
   BANDEAU STATS
══════════════════════════════════════ */
.stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:16px; }
.stat-card {
    background:var(--paper-card); border-radius:var(--radius-md); padding:15px 12px;
    text-align:center; box-shadow:var(--shadow); border:1px solid var(--line);
}
.stat-value { font-family:'JetBrains Mono', monospace; font-size:1.5rem; font-weight:600; color:var(--ink); }
.stat-label { font-size:.68rem; color:var(--ink-soft); margin-top:3px; }
.stat-card.gold .stat-value { color:var(--gold); }
.stat-card.sage .stat-value { color:var(--sage); }

/* ══════════════════════════════════════
   RELEVÉ DE CRÉANCES
══════════════════════════════════════ */
.creance-box {
    position:relative; overflow:hidden;
    background:linear-gradient(135deg, var(--aubergine), #7B3FAE 130%);
    border-radius:var(--radius-lg); padding:22px 24px; margin-bottom:16px; color:#fff;
    box-shadow:var(--shadow);
}
.creance-title {
    font-size:.76rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em;
    opacity:.85; display:flex; align-items:center; gap:8px; margin-bottom:16px;
    font-family:'JetBrains Mono', monospace;
}
.creance-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.creance-item-value { font-family:'JetBrains Mono', monospace; font-size:1.55rem; font-weight:600; }
.creance-item-label { font-size:.74rem; opacity:.8; margin-top:3px; }

/* ══════════════════════════════════════
   TITRES DE SECTION
══════════════════════════════════════ */
.section-title {
    font-family:'Fraunces', serif; font-weight:600;
    font-size:1rem; color:var(--ink);
    margin:26px 0 12px; display:flex; align-items:center; gap:9px;
}
.section-title i { color:var(--aubergine); font-size:.85rem; }
.section-title .count-tag {
    font-family:'JetBrains Mono', monospace; font-weight:500;
    font-size:.68rem; color:var(--ink-faint); margin-left:2px;
}

/* ══════════════════════════════════════
   DOSSIERS EN ATTENTE — bordereau
══════════════════════════════════════ */
.demande-card {
    position:relative;
    background:var(--paper-card); border-radius:var(--radius-md); padding:16px 18px;
    margin-bottom:10px; box-shadow:var(--shadow);
    border-left:3px solid var(--gold);
}
.demande-top {
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:10px; margin-bottom:4px;
}
.demande-patient { font-family:'Fraunces', serif; font-size:1rem; font-weight:600; }
.demande-meta {
    font-family:'JetBrains Mono', monospace; font-size:.7rem;
    color:var(--ink-soft); margin-top:3px; letter-spacing:.01em;
}
.demande-divider {
    border:none; border-top:1px dashed var(--line);
    margin:12px 0;
}
.demande-examens { font-size:.8rem; color:var(--ink-soft); display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
.demande-montant { font-family:'JetBrains Mono', monospace; font-size:1rem; font-weight:600; color:var(--aubergine); white-space:nowrap; }

.action-form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.code-input {
    flex:1; min-width:160px; padding:9px 12px;
    border:1.5px solid var(--line); border-radius:var(--radius-sm);
    font-family:'Plus Jakarta Sans', sans-serif; font-size:.85rem; outline:none;
    background:var(--paper); color:var(--ink); transition:border-color .15s;
}
.code-input:focus { border-color:var(--aubergine); background:#fff; }
.btn-valider {
    display:inline-flex; align-items:center; gap:6px;
    padding:9px 18px; border-radius:var(--radius-sm); border:none; cursor:pointer;
    background:var(--aubergine); color:#fff; font-size:.82rem; font-weight:600;
    font-family:'Plus Jakarta Sans', sans-serif; transition:background .15s;
}
.btn-valider:hover { background:var(--aubergine-deep); }
.btn-rejeter {
    display:inline-flex; align-items:center; gap:6px;
    padding:9px 16px; border-radius:var(--radius-sm); border:1.5px solid var(--clay-tint); cursor:pointer;
    background:var(--clay-tint); color:var(--clay); font-size:.82rem; font-weight:600;
    font-family:'Plus Jakarta Sans', sans-serif; transition:all .15s;
}
.btn-rejeter:hover { background:var(--clay); color:#fff; border-color:var(--clay); }

.permission-lock {
    font-size:.78rem; color:var(--ink-faint); margin:0;
    display:flex; align-items:center; gap:6px;
}

.empty-state { text-align:center; padding:44px 20px; color:var(--ink-faint); }
.empty-state i { font-size:2.2rem; color:var(--line); margin-bottom:12px; display:block; }
.empty-state p { font-size:.85rem; margin:0; color:var(--ink-soft); }

/* ══════════════════════════════════════
   REGISTRE — historique
══════════════════════════════════════ */
.historique-table {
    background:var(--paper-card); border-radius:var(--radius-lg);
    overflow:hidden; box-shadow:var(--shadow); border:1px solid var(--line);
}
.hist-row {
    display:grid; grid-template-columns:1.5fr 1fr .9fr .9fr; gap:10px;
    padding:12px 16px; border-bottom:1px solid var(--line);
    align-items:center; font-size:.82rem;
}
.hist-row:last-child { border-bottom:none; }
.hist-row:not(.hist-header):nth-child(even) { background:var(--paper); }
.hist-header {
    background:var(--aubergine-tint); font-size:.64rem; font-weight:700;
    color:var(--aubergine); text-transform:uppercase; letter-spacing:.07em;
    font-family:'JetBrains Mono', monospace;
}
.hist-patient { font-weight:600; }
.hist-date { font-family:'JetBrains Mono', monospace; color:var(--ink-soft); font-size:.76rem; }

/* Tampons de statut — reprise du motif "cachet" */
.stamp-tag {
    display:inline-block; font-family:'Fraunces', serif; font-weight:700;
    font-size:.64rem; text-transform:uppercase; letter-spacing:.05em;
    padding:2px 10px; border-radius:20px 4px 20px 4px;
    border:1.3px solid currentColor; transform:rotate(-3deg);
}
.stamp-validee { background:var(--sage-tint); color:var(--sage); }
.stamp-rejetee { background:var(--clay-tint); color:var(--clay); }

.creance-tag-due  { font-family:'JetBrains Mono', monospace; background:var(--gold-tint); color:var(--gold); padding:2px 9px; border-radius:6px; font-size:.72rem; font-weight:600; }
.creance-tag-paid { font-family:'JetBrains Mono', monospace; background:var(--sage-tint); color:var(--sage); padding:2px 9px; border-radius:6px; font-size:.72rem; font-weight:600; }

.footer-note {
    text-align:center; font-size:.72rem; color:var(--ink-faint);
    margin-top:30px; font-family:'JetBrains Mono', monospace; letter-spacing:.02em;
}

/* ══════════════════════════════════════
   IMPORT FICHIER ASSURÉS
══════════════════════════════════════ */
.import-card { background:var(--paper-card); border-radius:var(--radius-lg); padding:20px; box-shadow:var(--shadow); border:1px solid var(--line); }
.import-header { display:flex; justify-content:flex-end; margin-bottom:12px; }
.btn-modele {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 15px; border-radius:var(--radius-sm);
    background:var(--aubergine-tint); color:var(--aubergine); text-decoration:none;
    font-size:.78rem; font-weight:600; transition:all .2s;
}
.btn-modele:hover { background:var(--aubergine); color:#fff; }
.upload-zone {
    border:1.5px dashed var(--line); border-radius:var(--radius-md);
    padding:30px 20px; text-align:center; cursor:pointer;
    transition:all .2s; background:var(--paper);
}
.upload-zone:hover { border-color:var(--aubergine); background:var(--aubergine-tint); }
.upload-zone i { font-size:1.8rem; color:var(--aubergine); opacity:.55; margin-bottom:9px; display:block; }
.upload-text { font-size:.85rem; color:var(--ink-soft); margin:0; font-weight:500; }
.import-help { font-size:.74rem; color:var(--ink-faint); margin:12px 0 0; line-height:1.6; }

/* ══════════════════════════════════════
   SOUS-COMPTES
══════════════════════════════════════ */
.sous-comptes-list { display:flex; flex-direction:column; gap:10px; margin-bottom:14px; }
.sous-compte-card { background:var(--paper-card); border-radius:var(--radius-md); padding:15px; box-shadow:var(--shadow); border:1px solid var(--line); }
.sc-inactive { opacity:.55; }
.sc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:9px; }
.sc-nom { font-family:'Fraunces', serif; font-size:.92rem; font-weight:600; }
.sc-permissions { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:11px; }
.perm-tag {
    font-family:'JetBrains Mono', monospace;
    background:var(--aubergine-tint); color:var(--aubergine);
    padding:2px 9px; border-radius:6px; font-size:.66rem; font-weight:600;
}
.sc-link-box { margin-bottom:11px; }
.sc-link-input {
    width:100%; padding:8px 10px; border:1.5px solid var(--line); border-radius:var(--radius-sm);
    font-family:'JetBrains Mono', monospace; font-size:.68rem; color:var(--ink-soft); background:var(--paper);
    cursor:text;
}
.sc-actions { display:flex; gap:8px; }
.btn-sc-action {
    display:inline-flex; align-items:center; gap:5px;
    padding:7px 13px; border-radius:var(--radius-sm); border:none; cursor:pointer;
    background:var(--paper); color:var(--ink); font-size:.75rem; font-weight:500;
    font-family:'Plus Jakarta Sans', sans-serif; transition:all .15s;
}
.btn-sc-action:hover { background:var(--ink); color:#fff; }
.btn-sc-danger { background:var(--clay-tint); color:var(--clay); }
.btn-sc-danger:hover { background:var(--clay); color:#fff; }

.creer-sc-card { background:var(--paper-card); border-radius:var(--radius-lg); padding:18px; box-shadow:var(--shadow); border:1px solid var(--line); }
.creer-sc-title { font-family:'Fraunces', serif; font-size:.92rem; font-weight:600; margin:0 0 12px; }
.sc-perm-checks { display:flex; flex-direction:column; gap:9px; font-size:.83rem; color:var(--ink); }
.sc-perm-checks label { display:flex; align-items:center; gap:9px; cursor:pointer; }
.sc-perm-checks input { width:16px; height:16px; accent-color:var(--aubergine); }

@media (max-width: 600px) {
    .portal-header { flex-wrap:wrap; }
    .cachet { margin-left:0; transform:rotate(0deg); }
    .hist-row { grid-template-columns:1fr 1fr; row-gap:6px; }
    .stats-row { grid-template-columns:1fr; }
    .creance-grid { grid-template-columns:1fr; }
}
</style>
</head>
<body>
<div class="wrap">

    <div class="portal-header">
        <div class="portal-crest"><i class="fas fa-shield-heart"></i></div>
        <div>
            <p class="portal-title">Laboratoire Drouot</p>
            <p class="portal-sub">Portail partenaire assurance</p>
        </div>
        <div class="cachet">
            <div class="cachet-name"><?= htmlspecialchars($assurance['nom']) ?></div>
            <div class="cachet-context"><?= htmlspecialchars($contexteLabel) ?></div>
        </div>
    </div>

    <div class="portal-nav">
        <?php if ($permissions['peut_importer']): ?>
        <a href="<?= BASE_URL ?>assurance/portal/<?= $token ?>/assures" class="portal-nav-link">
            <i class="fas fa-users"></i> Mes assurés
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>assurance/portal/<?= $token ?>/historique" class="portal-nav-link">
            <i class="fas fa-book"></i> Registre
            <span class="count-pill"><?= count($historique) ?></span>
        </a>
    </div>

    <?php if (isset($_GET['action']) && $_GET['action'] === 'validated'): ?>
    <div class="alert-box alert-success">
        <i class="fas fa-circle-check"></i> Dossier validé avec succès.
    </div>
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'rejected'): ?>
    <div class="alert-box alert-error">
        <i class="fas fa-circle-xmark"></i> Dossier rejeté.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert-box alert-error">
        <i class="fas fa-triangle-exclamation"></i>
        <?= $_GET['error'] === 'code_requis' ? 'Le code d\'accord est obligatoire pour valider.' : 'Dossier introuvable ou déjà traité.' ?>
    </div>
    <?php endif; ?>

    <!-- Bandeau statistiques -->
    <div class="stats-row">
        <div class="stat-card gold">
            <div class="stat-value"><?= count($enAttente) ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($historique) ?></div>
            <div class="stat-label">Dossiers traités</div>
        </div>
        <div class="stat-card sage">
            <div class="stat-value">
                <?= count(array_filter($historique, fn($h) => $h['assurance_statut'] === 'validee')) ?>
            </div>
            <div class="stat-label">Validés</div>
        </div>
    </div>

    <!-- Relevé de créances -->
    <?php if ($permissions['peut_voir_creances']): ?>
    <div class="creance-box">
        <div class="creance-title">
            <i class="fas fa-hand-holding-dollar"></i>
            Relevé de compte — créances envers le laboratoire
        </div>
        <div class="creance-grid">
            <div>
                <div class="creance-item-value">
                    <?= number_format((float)$creanceStats['total_du'], 0, ',', ' ') ?> F
                </div>
                <div class="creance-item-label">Dues</div>
            </div>
            <div>
                <div class="creance-item-value">
                    <?= number_format((float)$creanceStats['total_paye'], 0, ',', ' ') ?> F
                </div>
                <div class="creance-item-label">Déjà réglées</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dossiers en attente -->
    <h6 class="section-title">
        <i class="fas fa-hourglass-half"></i>
        Dossiers en attente de validation
        <span class="count-tag">— <?= count($enAttente) ?></span>
    </h6>

    <?php if (empty($enAttente)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <p>Aucun dossier en attente. Le registre est à jour.</p>
        </div>
    <?php else: ?>
        <?php foreach ($enAttente as $d): ?>
        <div class="demande-card">
            <div class="demande-top">
                <div>
                    <div class="demande-patient"><?= htmlspecialchars($d['patient_nom']) ?></div>
                    <div class="demande-meta">
                        N° POLICE <?= htmlspecialchars($d['numero_police'] ?? '—') ?>
                        &nbsp;·&nbsp; <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                    </div>
                </div>
            </div>
            <hr class="demande-divider">
            <div class="demande-examens">
                <span><i class="fas fa-flask"></i> <?= htmlspecialchars($d['examens'] ?? '—') ?> (<?= $d['nb_examens'] ?> examen<?= $d['nb_examens'] > 1 ? 's' : '' ?>)</span>
                <span class="demande-montant"><?= number_format((float)$d['montant_net'], 0, ',', ' ') ?> F</span>
            </div>

            <?php if ($permissions['peut_valider']): ?>
            <form method="POST"
                  action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/valider/<?= $d['id'] ?>"
                  class="action-form">
                <input type="text" name="code_accord" class="code-input"
                       placeholder="Votre code d'accord..." required>
                <button type="submit" class="btn-valider">
                    <i class="fas fa-check"></i> Valider
                </button>
            </form>
            <form method="POST"
                  action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/rejeter/<?= $d['id'] ?>"
                  style="margin-top:8px;"
                  onsubmit="return confirm('Confirmer le rejet de cette prise en charge ?')">
                <button type="submit" class="btn-rejeter">
                    <i class="fas fa-xmark"></i> Rejeter la prise en charge
                </button>
            </form>
            <?php else: ?>
            <p class="permission-lock">
                <i class="fas fa-lock"></i> Vous n'avez pas la permission de valider les dossiers.
            </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Import fichier assurés -->
    <?php if ($permissions['peut_importer']): ?>
    <h6 class="section-title">
        <i class="fas fa-file-excel"></i>
        Importer votre fichier d'assurés
    </h6>

    <?php if ($importResult): ?>
    <div class="alert-box alert-success">
        <i class="fas fa-circle-check"></i>
        Import terminé : <?= $importResult['inseres'] ?> ajouté(s),
        <?= $importResult['maj'] ?> mis à jour
        <?php if ($importResult['ignores'] > 0): ?>, <?= $importResult['ignores'] ?> ignoré(s)<?php endif; ?>.
    </div>
    <?php endif; ?>
    <?php if ($importError): ?>
    <div class="alert-box alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($importError) ?>
    </div>
    <?php endif; ?>

    <div class="import-card">
        <div class="import-header">
            <a href="<?= BASE_URL ?>assets/modele_import_assures.xlsx"
               class="btn-modele" download>
                <i class="fas fa-file-arrow-down"></i>
                Télécharger le modèle Excel
            </a>
        </div>
        <form method="POST"
              action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/import"
              enctype="multipart/form-data">
            <div class="upload-zone" onclick="document.getElementById('fichierInput').click()">
                <i class="fas fa-cloud-arrow-up"></i>
                <p class="upload-text">Cliquez pour sélectionner votre fichier .xlsx</p>
                <input type="file" name="fichier" id="fichierInput"
                       accept=".xlsx,.xls" required style="display:none;"
                       onchange="this.form.submit()">
            </div>
        </form>
        <p class="import-help">
            Colonnes attendues : Numéro de police, Nom de l'assuré, Statut
            (en_regle ou suspendu). Le ré-import met à jour les numéros
            déjà connus sans supprimer les autres.
        </p>
    </div>
    <?php endif; ?>

    <!-- Sous-comptes (portail principal uniquement) -->
    <?php if (!$sousCompte): ?>
    <h6 class="section-title">
        <i class="fas fa-users-gear"></i>
        Sous-comptes
    </h6>

    <?php if (!empty($sousComptes)): ?>
    <div class="sous-comptes-list">
        <?php foreach ($sousComptes as $sc):
            $scUrl = BASE_URL . 'assurance/portal/' . $sc['token_acces'];
        ?>
        <div class="sous-compte-card <?= !$sc['is_active'] ? 'sc-inactive' : '' ?>">
            <div class="sc-top">
                <div class="sc-nom"><?= htmlspecialchars($sc['nom']) ?></div>
                <?php if ($sc['is_active']): ?>
                    <span class="stamp-tag stamp-validee">Actif</span>
                <?php else: ?>
                    <span class="stamp-tag stamp-rejetee">Inactif</span>
                <?php endif; ?>
            </div>
            <div class="sc-permissions">
                <?php if ($sc['peut_importer']): ?><span class="perm-tag">Import</span><?php endif; ?>
                <?php if ($sc['peut_valider']): ?><span class="perm-tag">Validation</span><?php endif; ?>
                <?php if ($sc['peut_voir_creances']): ?><span class="perm-tag">Créances</span><?php endif; ?>
            </div>
            <div class="sc-link-box">
                <input type="text" readonly class="sc-link-input" value="<?= htmlspecialchars($scUrl) ?>"
                       onclick="this.select()">
            </div>
            <div class="sc-actions">
                <form method="POST" action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/sous-comptes/<?= $sc['id'] ?>/toggle">
                    <button type="submit" class="btn-sc-action">
                        <i class="fas <?= $sc['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                        <?= $sc['is_active'] ? 'Désactiver' : 'Activer' ?>
                    </button>
                </form>
                <form method="POST" action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/sous-comptes/<?= $sc['id'] ?>/delete"
                      onsubmit="return confirm('Supprimer ce sous-compte ?')">
                    <button type="submit" class="btn-sc-action btn-sc-danger">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <p>Aucun sous-compte créé pour le moment.</p>
    </div>
    <?php endif; ?>

    <div class="creer-sc-card">
        <p class="creer-sc-title">Créer un nouveau sous-compte</p>
        <form method="POST" action="<?= BASE_URL ?>assurance/portal/<?= $token ?>/sous-comptes/store">
            <input type="text" name="nom" class="code-input" style="width:100%;margin-bottom:10px;"
                   placeholder="Nom du sous-compte (ex: Agence Bonanjo)" required>
            <div class="sc-perm-checks">
                <label><input type="checkbox" name="peut_importer"> Peut importer le fichier d'assurés</label>
                <label><input type="checkbox" name="peut_valider" checked> Peut valider/rejeter les dossiers</label>
                <label><input type="checkbox" name="peut_voir_creances"> Peut voir les créances</label>
            </div>
            <button type="submit" class="btn-valider" style="margin-top:10px;">
                <i class="fas fa-plus"></i> Créer le sous-compte
            </button>
        </form>
    </div>
    <?php endif; ?>

    <p class="footer-note">
        LABORATOIRE DROUOT — DOUALA · LIEN PERSONNEL, NE PAS PARTAGER
    </p>

</div>
</body>
</html>