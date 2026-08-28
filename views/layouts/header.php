<?php
/**
 * Layout Header / Topbar — Laboratoire Drouot
 * Inclut le CSS global + topbar avec hamburger mobile
 */
 //header.php
$user = Auth::user();

// Titres des pages selon l'URL
$pageTitles = [
    'dashboard'    => 'Tableau de bord',
    'patients'     => 'Gestion des patients',
    'demandes'     => 'Demandes d\'examens',
    'factures'     => 'Facturation',
    'paiements'    => 'Paiements',
    'echantillons' => 'Échantillons',
    'resultats'    => 'Résultats',
    'validations'  => 'Validations biologiques',
    'rapports'     => 'Rapports',
    'stocks'       => 'Stocks & Réactifs',
    'users'        => 'Utilisateurs',
    'audit'        => 'Audit logs',
    'kpi'          => 'Indicateurs KPI',
    'profil'       => 'Mon profil',
];
$uri       = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments  = explode('/', trim($uri, '/'));
$lastSeg   = end($segments);
$pageTitle = $pageTitles[$lastSeg] ?? 'Tableau de bord';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Laboratoire Drouot</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1E88E5">
    <link rel="icon" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Labo Drouot">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- File d'attente hors ligne -->
    <script src="/assets/js/offline-outbox.js"></script>

    <style>
        /* ═══════════════════════════════════════════
           VARIABLES — Design System Drouot
        ═══════════════════════════════════════════ */
        :root {
            --blue-primary:  #1E88E5;
            --blue-dark:     #0D47A1;
            --green-valid:   #2E7D32;
            --orange-alert:  #F9A825;
            --red-critical:  #C62828;
            --bg-app:        #F4F6F9;
            --white:         #ffffff;
            --text-dark:     #1a2340;
            --text-muted:    #6b7a99;
            --border:        #dde3f0;
            --sidebar-width: 240px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg-app);
            color: var(--text-dark);
        }

        /* ═══════════════════════════════════════════
           LAYOUT PRINCIPAL
        ═══════════════════════════════════════════ */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════
           SIDEBAR
        ═══════════════════════════════════════════ */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--blue-dark);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 1000;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }

        /* Logo */
        .sidebar-logo {
            padding: 18px 16px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.10);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-logo-icon {
            width: 36px; height: 36px;
            background: var(--blue-primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-logo-icon i { color: #fff; font-size: 1rem; }
        .sidebar-logo-name {
            display: block;
            color: #fff;
            font-size: 0.92rem;
            font-weight: 600;
            line-height: 1.3;
        }
        .sidebar-logo-sub {
            display: block;
            color: #90CAF9;
            font-size: 0.72rem;
        }
        .sidebar-close-btn {
            display: none; /* affiché seulement mobile */
            margin-left: auto;
            background: rgba(255,255,255,0.10);
            border: none;
            border-radius: 8px;
            width: 30px; height: 30px;
            color: #fff;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        /* Badge rôle */
        .sidebar-role-badge {
            margin: 12px 14px 4px;
            padding: 5px 10px;
            background: rgba(255,255,255,0.08);
            border-radius: 20px;
            font-size: 0.72rem;
            color: #90CAF9;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .sidebar-role-badge i { font-size: 0.5rem; color: #4CAF50; }

        /* Navigation */
        .sidebar-nav {
            padding: 8px 0;
            flex: 1;
        }
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: #B3D4F5;
            text-decoration: none;
            font-size: 0.88rem;
            border-left: 3px solid transparent;
            transition: all 0.18s ease;
        }
        .sidebar-nav-item i {
            font-size: 0.95rem;
            width: 18px;
            text-align: center;
            color: #90CAF9;
        }
        .sidebar-nav-item:hover {
            background: rgba(255,255,255,0.07);
            color: #fff;
        }
        .sidebar-nav-item:hover i { color: #fff; }
        .sidebar-nav-item.active {
            background: rgba(255,255,255,0.12);
            border-left-color: var(--blue-primary);
            color: #fff;
            font-weight: 500;
        }
        .sidebar-nav-item.active i { color: #90CAF9; }

        /* Footer sidebar */
        .sidebar-footer {
            padding: 12px 0;
            border-top: 1px solid rgba(255,255,255,0.10);
        }
        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            color: #ef9a9a;
            text-decoration: none;
            font-size: 0.88rem;
            transition: all 0.18s;
        }
        .sidebar-logout:hover {
            background: rgba(198,40,40,0.15);
            color: #ff6b6b;
        }
        .sidebar-logout i { font-size: 0.95rem; }

        /* Overlay mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active { display: block; }

        /* ═══════════════════════════════════════════
           TOPBAR
        ═══════════════════════════════════════════ */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: 58px;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 12px;
            z-index: 900;
        }

        /* Bouton hamburger — caché desktop */
        .hamburger-btn {
            display: none;
            width: 36px; height: 36px;
            background: var(--blue-dark);
            border: none;
            border-radius: 9px;
            color: #fff;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
            transition: background 0.2s;
        }
        .hamburger-btn:hover { background: var(--blue-primary); }

        .topbar-title {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-dark);
            flex: 1;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .topbar-icon-btn {
            width: 36px; height: 36px;
            background: var(--bg-app);
            border: 1px solid var(--border);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 0.95rem;
            position: relative;
            transition: all 0.2s;
            text-decoration: none;
        }
        .topbar-icon-btn:hover {
            background: var(--blue-primary);
            color: #fff;
            border-color: var(--blue-primary);
        }
        .notif-dot {
            position: absolute;
            top: 5px; right: 5px;
            width: 8px; height: 8px;
            background: var(--red-critical);
            border-radius: 50%;
            border: 2px solid #fff;
        }
        .topbar-avatar {
            width: 36px; height: 36px;
            background: var(--blue-primary);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            border: 2px solid var(--border);
        }
        .topbar-user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.3;
        }
        .topbar-user-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-dark);
        }
        .topbar-user-role {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        /* ═══════════════════════════════════════════
           MENU DÉROULANT PROFIL (topbar avatar)
        ═══════════════════════════════════════════ */
        .topbar-user-dropdown { position: relative; display: flex; align-items: center; gap: 10px; }
        .topbar-user-trigger {
            display: flex; align-items: center; gap: 10px;
            background: none; border: none; cursor: pointer;
            padding: 2px; border-radius: 10px;
        }
        .topbar-dropdown-chevron { font-size: 0.68rem; color: var(--text-muted); margin-left: 2px; transition: transform 0.2s; }
        .topbar-user-trigger[aria-expanded="true"] .topbar-dropdown-chevron { transform: rotate(180deg); }

        .topbar-dropdown-menu {
            position: absolute; top: calc(100% + 10px); right: 0;
            min-width: 200px; background: #fff;
            border: 1px solid var(--border); border-radius: 12px;
            box-shadow: 0 8px 24px rgba(13,71,161,.14);
            padding: 6px; z-index: 1100; display: none;
        }
        .topbar-dropdown-menu.is-open { display: block; }
        .topbar-dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 8px;
            font-size: 0.84rem; color: var(--text-dark); text-decoration: none;
            transition: background 0.15s;
        }
        .topbar-dropdown-item i { width: 16px; color: #90CAF9; text-align: center; }
        .topbar-dropdown-item:hover { background: var(--bg-app); }
        .topbar-dropdown-item-danger { color: var(--red-critical); }
        .topbar-dropdown-item-danger i { color: #EF9A9A; }
        .topbar-dropdown-item-danger:hover { background: #FFEBEE; }
        .topbar-dropdown-divider { height: 1px; background: var(--border); margin: 6px 4px; }

        /* ═══════════════════════════════════════════
           CONTENU PRINCIPAL
        ═══════════════════════════════════════════ */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: 58px;
            padding: 24px;
            flex: 1;
            min-height: calc(100vh - 58px);
        }

        /* ═══════════════════════════════════════════
           RESPONSIVE MOBILE
        ═══════════════════════════════════════════ */
        @media (max-width: 768px) {

            /* Sidebar cachée par défaut sur mobile */
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 24px rgba(0,0,0,0.2);
            }

            /* Bouton close visible sur mobile */
            .sidebar-close-btn {
                display: flex;
            }

            /* Hamburger visible sur mobile */
            .hamburger-btn {
                display: flex;
            }

            /* Topbar pleine largeur */
            .topbar {
                left: 0;
            }

            /* Contenu pleine largeur */
            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            /* Cacher nom/rôle sur très petit écran */
            .topbar-user-info {
                display: none;
            }
        }

        /* ═══════════════════════════════════════════
           COMPOSANTS RÉUTILISABLES
        ═══════════════════════════════════════════ */

        /* Cards */
        .card-drouot {
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(13,71,161,0.05);
        }

        /* KPI Cards */
        .kpi-card {
            background: var(--white);
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .kpi-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .kpi-icon.blue   { background: #E3F2FD; color: var(--blue-primary); }
        .kpi-icon.green  { background: #E8F5E9; color: var(--green-valid); }
        .kpi-icon.orange { background: #FFF8E1; color: var(--orange-alert); }
        .kpi-icon.red    { background: #FFEBEE; color: var(--red-critical); }
        .kpi-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            line-height: 1;
        }
        .kpi-label {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .kpi-trend {
            font-size: 0.75rem;
            margin-top: 3px;
        }
        .kpi-trend.up   { color: var(--green-valid); }
        .kpi-trend.down { color: var(--red-critical); }
        .kpi-trend.warn { color: var(--orange-alert); }

        /* Badges statut */
        .badge-normal   { background: #E8F5E9; color: #2E7D32; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .badge-warn     { background: #FFF8E1; color: #F57F17; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .badge-critical { background: #FFEBEE; color: #C62828; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .badge-pending  { background: #E3F2FD; color: #1565C0; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .badge-paid     { background: #E8F5E9; color: #2E7D32; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }

        /* Boutons */
        .btn-primary-drouot {
            background: var(--blue-primary);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: 0.6rem 1.2rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
            text-decoration: none;
        }
        .btn-primary-drouot:hover { background: var(--blue-dark); color: #fff; }

        .btn-success-drouot {
            background: var(--green-valid);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: 0.6rem 1.2rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-success-drouot:hover { background: #1b5e20; }

        .btn-danger-drouot {
            background: var(--red-critical);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: 0.6rem 1.2rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-danger-drouot:hover { background: #7f0000; }

        /* Bannière de connectivité (PWA) */
        #connectivity-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            background: var(--orange-alert);
            color: #1a2340;
        }
        #connectivity-banner.show { display: flex; }
        #connectivity-banner.back-online {
            background: var(--green-valid);
            color: #fff;
        }
    </style>
</head>
<body>
<div id="connectivity-banner">
    <i class="fa-solid fa-wifi"></i>
    <span id="connectivity-banner-text">Hors ligne — certaines actions nécessitent une connexion.</span>
</div>
<div class="app-layout">

    <!-- Overlay mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Topbar -->
    <header class="topbar">
        <!-- Hamburger (mobile) -->
        <button class="hamburger-btn" id="hamburgerBtn"
                onclick="openSidebar()"
                aria-label="Ouvrir le menu">
            <i class="fas fa-bars"></i>
        </button>

        <span class="topbar-title"><?= htmlspecialchars($pageTitle) ?></span>

        <div class="topbar-actions">
            <!-- Notifications -->
            <a href="#" class="topbar-icon-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="notif-dot"></span>
            </a>
            <!-- Profil -->
            <div class="topbar-user-dropdown">
                <button type="button" class="topbar-user-trigger" id="topbarUserTrigger">
                    <div class="topbar-user-info">
                        <span class="topbar-user-name"><?= htmlspecialchars($user['name']) ?></span>
                        <span class="topbar-user-role"><?= htmlspecialchars($user['role']) ?></span>
                    </div>
                    <div class="topbar-avatar">
                        <?= Auth::initials() ?>
                    </div>
                    <i class="fas fa-chevron-down topbar-dropdown-chevron"></i>
                </button>

                <div class="topbar-dropdown-menu" id="topbarUserMenu">
                    <a href="<?= BASE_URL ?>profil" class="topbar-dropdown-item">
                        <i class="fas fa-user-pen"></i> Mon profil
                    </a>
                    <div class="topbar-dropdown-divider"></div>
                    <a href="<?= BASE_URL ?>logout" class="topbar-dropdown-item topbar-dropdown-item-danger">
                        <i class="fas fa-right-from-bracket"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </header>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var trigger = document.getElementById('topbarUserTrigger');
        var menu    = document.getElementById('topbarUserMenu');
        if (!trigger || !menu) return;

        function closeMenu() {
            menu.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
        }
        function openMenu() {
            menu.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.contains('is-open') ? closeMenu() : openMenu();
        });

        document.addEventListener('click', function (e) {
            if (!menu.contains(e.target) && e.target !== trigger) closeMenu();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeMenu();
        });
    });
    </script>

    <!-- Contenu principal -->
    <main class="main-content">