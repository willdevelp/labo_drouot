<?php
/**
 * Portail Patient — Page principale
 * Accès sans login via token
 */
$age = $patient['date_naissance']
    ? date_diff(
        date_create($patient['date_naissance']),
        date_create('today')
    )->y
    : null;
$initials = strtoupper(
    substr($patient['prenom'], 0, 1) .
    substr($patient['nom'], 0, 1)
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes résultats — Laboratoire Drouot</title>
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

        /* ── TOPBAR ── */
        .portal-topbar {
            background:var(--dark);
            padding:14px 20px;
            display:flex; align-items:center;
            justify-content:space-between;
            position:sticky; top:0; z-index:100;
        }
        .portal-logo {
            display:flex; align-items:center; gap:10px;
        }
        .portal-logo-icon {
            width:34px; height:34px; background:var(--blue);
            border-radius:9px;
            display:flex; align-items:center; justify-content:center;
        }
        .portal-logo-icon i { color:#fff; font-size:1rem; }
        .portal-logo-text {
            color:#fff; font-size:.92rem; font-weight:600;
        }
        .portal-logo-sub {
            color:#90CAF9; font-size:.72rem; display:block;
        }
        .portal-secure {
            display:flex; align-items:center; gap:6px;
            color:#90CAF9; font-size:.75rem;
        }

        /* ── HERO PATIENT ── */
        .portal-hero {
            background:linear-gradient(135deg, var(--dark), var(--blue));
            padding:28px 20px;
            color:#fff;
        }
        .hero-inner {
            max-width:700px; margin:0 auto;
            display:flex; align-items:center; gap:16px;
        }
        .hero-avatar {
            width:60px; height:60px; border-radius:50%;
            background:rgba(255,255,255,.2);
            border:3px solid rgba(255,255,255,.4);
            display:flex; align-items:center; justify-content:center;
            font-size:1.3rem; font-weight:600; flex-shrink:0;
        }
        .hero-greeting {
            font-size:1.2rem; font-weight:600; margin-bottom:3px;
        }
        .hero-sub { font-size:.85rem; color:rgba(255,255,255,.75); }

        /* Stats rapides */
        .hero-stats {
            max-width:700px; margin:0 auto;
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:10px; margin-top:20px;
        }
        .hero-stat {
            background:rgba(255,255,255,.12);
            border-radius:10px; padding:12px;
            text-align:center;
        }
        .hero-stat-val {
            font-size:1.5rem; font-weight:700;
        }
        .hero-stat-label {
            font-size:.72rem; color:rgba(255,255,255,.75);
            margin-top:2px;
        }

        /* ── CONTENU ── */
        .portal-content {
            max-width:700px; margin:0 auto;
            padding:24px 16px;
        }

        .section-title {
            font-size:.88rem; font-weight:600; color:var(--text);
            margin-bottom:14px;
            display:flex; align-items:center; gap:8px;
        }
        .section-title i { color:var(--blue); }

        /* ── CARTES ANALYSES ── */
        .analyse-card {
            background:var(--white);
            border-radius:14px;
            border:1px solid var(--border);
            padding:16px;
            margin-bottom:12px;
            text-decoration:none;
            display:block;
            transition:all .2s;
            box-shadow:0 2px 8px rgba(13,71,161,.05);
        }
        .analyse-card:hover {
            border-color:var(--blue);
            transform:translateY(-1px);
            box-shadow:0 6px 20px rgba(13,71,161,.12);
        }
        .analyse-card.locked {
            opacity:.7; cursor:default;
        }
        .analyse-card.locked:hover {
            transform:none; border-color:var(--border);
        }

        .card-top {
            display:flex; align-items:flex-start;
            justify-content:space-between; gap:12px;
            margin-bottom:10px;
        }
        .card-date {
            font-size:.75rem; color:var(--muted);
            margin-bottom:4px;
        }
        .card-examens {
            font-size:.88rem; font-weight:500; color:var(--text);
            line-height:1.4;
        }

        /* Statuts */
        .status-pill {
            display:inline-flex; align-items:center; gap:5px;
            padding:4px 12px; border-radius:20px;
            font-size:.75rem; font-weight:600;
            white-space:nowrap; flex-shrink:0;
        }
        .status-disponible {
            background:#E8F5E9; color:#2E7D32;
        }
        .status-encours {
            background:#FFF8E1; color:#F57F17;
        }
        .status-attente {
            background:#F4F6F9; color:#6b7a99;
        }

        .card-bottom {
            display:flex; align-items:center;
            justify-content:space-between; gap:8px;
            flex-wrap:wrap;
        }
        .card-meta {
            display:flex; gap:8px; flex-wrap:wrap;
        }
        .meta-chip {
            background:var(--bg);
            border:1px solid var(--border);
            border-radius:6px; padding:3px 8px;
            font-size:.72rem; color:var(--muted);
            display:flex; align-items:center; gap:4px;
        }
        .meta-chip.abnormal {
            background:#FFEBEE; border-color:#FFCDD2;
            color:var(--red);
        }

        .card-action {
            display:inline-flex; align-items:center; gap:5px;
            padding:6px 14px; border-radius:8px;
            background:var(--blue); color:#fff;
            font-size:.78rem; font-weight:500;
            transition:background .2s; flex-shrink:0;
        }
        .analyse-card:hover .card-action { background:var(--dark); }

        /* Commentaire biologiste */
        .bio-comment {
            background:#f0f7ff;
            border-left:3px solid var(--blue);
            border-radius:0 8px 8px 0;
            padding:8px 12px; margin-top:10px;
            font-size:.78rem; color:var(--muted);
            font-style:italic;
            display:flex; gap:8px;
        }
        .bio-comment i { color:#90CAF9; flex-shrink:0; margin-top:2px; }

        /* Vide */
        .empty-state {
            text-align:center; padding:3rem 1rem;
            color:var(--muted);
        }
        .empty-state i {
            font-size:3rem; color:#dde3f0;
            display:block; margin-bottom:1rem;
        }

        /* ── FOOTER ── */
        .portal-footer {
            max-width:700px; margin:0 auto;
            padding:0 16px 32px;
            text-align:center;
        }
        .footer-secure {
            font-size:.72rem; color:var(--muted);
            display:flex; align-items:center;
            justify-content:center; gap:6px;
            padding:12px;
            background:var(--white);
            border-radius:10px;
            border:1px solid var(--border);
        }
        .footer-secure i { color:#90CAF9; }

        /* ── RESPONSIVE ── */
        @media (max-width:480px) {
            .hero-stats {
                grid-template-columns:repeat(2,1fr);
            }
            .hero-inner { flex-direction:column; text-align:center; }
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="portal-topbar">
    <div class="portal-logo">
        <div class="portal-logo-icon">
            <i class="fas fa-flask"></i>
        </div>
        <div>
            <span class="portal-logo-text">Laboratoire Drouot</span>
            <span class="portal-logo-sub">Portail Patient</span>
        </div>
    </div>
    <div class="portal-secure">
        <i class="fas fa-shield-halved"></i>
        Accès sécurisé
    </div>
</div>

<!-- Hero -->
<div class="portal-hero">
    <div class="hero-inner">
        <div class="hero-avatar"><?= $initials ?></div>
        <div>
            <div class="hero-greeting">
                Bonjour <?= htmlspecialchars($patient['prenom']) ?> 👋
            </div>
            <div class="hero-sub">
                <?= htmlspecialchars(
                    strtoupper($patient['nom'])
                ) ?>
                <?= $age ? "· $age ans" : '' ?>
                <?= $patient['sexe'] === 'F' ? '· Féminin' : '· Masculin' ?>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="hero-stats">
        <div class="hero-stat">
            <div class="hero-stat-val"><?= $stats['total'] ?></div>
            <div class="hero-stat-label">Analyses totales</div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-val"
                 style="color:#81C784;">
                <?= $stats['disponible'] ?>
            </div>
            <div class="hero-stat-label">Disponibles</div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-val"
                 style="color:#FFD54F;">
                <?= $stats['en_cours'] ?>
            </div>
            <div class="hero-stat-label">En cours</div>
        </div>
        <div class="hero-stat">
            <div class="hero-stat-val"
                 style="color:rgba(255,255,255,.6);">
                <?= $stats['archive'] ?>
            </div>
            <div class="hero-stat-label">Archives</div>
        </div>
    </div>
</div>

<!-- Contenu -->
<div class="portal-content">

    <div class="section-title">
        <i class="fas fa-flask"></i>
        Mes analyses
    </div>

    <?php if (empty($analyses)): ?>
        <div class="empty-state">
            <i class="fas fa-flask"></i>
            <p>Aucune analyse pour le moment.</p>
            <p style="font-size:.82rem;margin-top:6px;">
                Vos résultats apparaîtront ici dès qu'ils seront disponibles.
            </p>
        </div>
    <?php else: ?>

        <?php foreach ($analyses as $a):
          $isDisponible = $a['status'] === 'terminee' && $a['validated_at'];
            $isEnCours    = !$isDisponible
                            && $a['payment_status'] === 'paid';
            $isAttente    = $a['payment_status'] !== 'paid';
        ?>

        <?php if ($isDisponible): ?>
        <!-- Résultat disponible — cliquable -->
        <a href="<?= BASE_URL ?>patient/resultat/<?= urlencode($tokenData['token']) ?>/<?= $a['id'] ?>"
           class="analyse-card">
        <?php else: ?>
        <!-- Non disponible — non cliquable -->
        <div class="analyse-card locked">
        <?php endif; ?>

            <div class="card-top">
                <div>
                    <div class="card-date">
                        <i class="fas fa-calendar"></i>
                        <?= date('d/m/Y', strtotime($a['created_at'])) ?>
                    </div>
                    <div class="card-examens">
                        <?= htmlspecialchars(
                            mb_strimwidth($a['examens'] ?? 'Analyses', 0, 60, '...')
                        ) ?>
                    </div>
                </div>
                <?php if ($isDisponible): ?>
                    <span class="status-pill status-disponible">
                        ✅ Disponible
                    </span>
                <?php elseif ($isEnCours): ?>
                    <span class="status-pill status-encours">
                        ⏳ En cours
                    </span>
                <?php else: ?>
                    <span class="status-pill status-attente">
                        ⌛ En attente
                    </span>
                <?php endif; ?>
            </div>

            <div class="card-bottom">
                <div class="card-meta">
                    <span class="meta-chip">
                        <i class="fas fa-flask"></i>
                        <?= $a['nb_examens'] ?> examen(s)
                    </span>
                    <?php if ($isDisponible): ?>
                        <span class="meta-chip">
                            <i class="fas fa-chart-simple"></i>
                            <?= $a['nb_resultats'] ?> résultats
                        </span>
                        <?php if ($a['nb_anormaux'] > 0): ?>
                        <span class="meta-chip abnormal">
                            ⚠️ <?= $a['nb_anormaux'] ?> anormal(aux)
                        </span>
                        <?php endif; ?>
                        <?php if ($a['validated_at']): ?>
                        <span class="meta-chip">
                            <i class="fas fa-user-doctor"></i>
                            Validé le
                            <?= date('d/m/Y', strtotime($a['validated_at'])) ?>
                        </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if ($isDisponible): ?>
                <span class="card-action">
                    <i class="fas fa-eye"></i> Voir résultats
                </span>
                <?php endif; ?>
            </div>

            <!-- Commentaire biologiste -->
            <?php if ($isDisponible && $a['commentaire']): ?>
            <div class="bio-comment">
                <i class="fas fa-user-doctor"></i>
                <span>
                    <strong>Dr. Biologiste :</strong>
                    <?= htmlspecialchars(
                        mb_strimwidth($a['commentaire'], 0, 120, '...')
                    ) ?>
                </span>
            </div>
            <?php endif; ?>

        <?php if ($isDisponible): ?>
        </a>
        <?php else: ?>
        </div>
        <?php endif; ?>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

<!-- Footer -->
<div class="portal-footer">
    <div class="footer-secure">
        <i class="fas fa-lock"></i>
        Vos résultats sont confidentiels et protégés.
        Ne partagez jamais ce lien.
    </div>
</div>

</body>
</html>