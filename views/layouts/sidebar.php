
<?php
/**
 * Layout Sidebar — Laboratoire Drouot
 * Desktop : fixe gauche | Mobile : drawer hamburger
 */
 //sidebar.php
$user        = Auth::user();
$currentRole = $user['role'];
$currentUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Navigation par rôle
$navItems = [
    'admin' => [
        ['icon' => 'fa-gauge',        'label' => 'Tableau de bord',  'url' => 'dashboard/admin'],
        ['icon' => 'fa-users-cog',    'label' => 'Utilisateurs',     'url' => 'admin/users'],
        ['icon' => 'fa-flask',        'label' => 'Examens',          'url' => 'admin/examens'],
        ['icon' => 'fa-user-doctor',  'label' => 'Médecins',         'url' => 'admin/medecins'],
        ['icon' => 'fa-shield-heart', 'label' => 'Assurances',       'url' => 'admin/assurances'],
        ['icon' => 'fa-boxes-stacked','label' => 'Stocks',           'url' => 'admin/stocks'],
        ['icon' => 'fa-chart-bar',    'label' => 'Rapports',         'url' => 'admin/rapports'],
        ['icon' => 'fa-shield-halved','label' => 'Audit logs',       'url' => 'admin/audit'],
    ],
    'directeur' => [
        ['icon' => 'fa-gauge',        'label' => 'Tableau de bord',  'url' => 'dashboard/directeur'],
        ['icon' => 'fa-chart-line',   'label' => 'Indicateurs KPI',  'url' => 'directeur/kpi'],
        ['icon' => 'fa-money-bill',   'label' => 'Revenus',          'url' => 'directeur/revenus'],
        ['icon' => 'fa-flask',        'label' => 'Examens',          'url' => 'directeur/examens'],
        ['icon' => 'fa-file-medical', 'label' => 'Rapports',         'url' => 'directeur/rapports'],
    ],
    'secretaire' => [
        ['icon' => 'fa-gauge',        'label' => 'Tableau de bord',  'url' => 'dashboard/secretaire'],
        ['icon' => 'fa-user-plus',    'label' => 'Patients',         'url' => 'secretaire/patients'],
        ['icon' => 'fa-flask',        'label' => 'Demandes examens', 'url' => 'secretaire/demandes'],
        ['icon' => 'fa-file-invoice', 'label' => 'Factures proforma','url' => 'secretaire/factures'],
        ['icon' => 'fa-bell',         'label' => 'Notifications',    'url' => 'secretaire/notifications'],
    ],
    'caissier' => [
        ['icon' => 'fa-gauge',        'label' => 'Tableau de bord',  'url' => 'dashboard/caissier'],
        ['icon' => 'fa-file-invoice-dollar', 'label' => 'Factures', 'url' => 'caissier/factures'],
        ['icon' => 'fa-cash-register','label' => 'Encaissements',    'url' => 'caissier/paiements'],
        ['icon' => 'fa-receipt',      'label' => 'Reçus',            'url' => 'caissier/recus'],
        ['icon' => 'fa-clock-rotate-left','label' => 'Historique',   'url' => 'caissier/historique'],
    ],
    'technicien' => [
        ['icon' => 'fa-gauge',        'label' => 'Tableau de bord',  'url' => 'dashboard/technicien'],
        ['icon' => 'fa-vial',         'label' => 'Échantillons',     'url' => 'technicien/echantillons'],
        ['icon' => 'fa-microscope',   'label' => 'Mes analyses',     'url' => 'technicien/analyses'],
        ['icon' => 'fa-keyboard',     'label' => 'Saisie résultats', 'url' => 'technicien/resultats'],
        ['icon' => 'fa-chart-simple', 'label' => 'Analyses en cours','url' => 'technicien/encours'],
    ],
    'biologiste' => [
        ['icon' => 'fa-gauge',        'label' => 'Tableau de bord',  'url' => 'dashboard/biologiste'],
        ['icon' => 'fa-circle-check', 'label' => 'Validations',      'url' => 'biologiste/validations'],
       ['icon' => 'fa-list-check', 'label' => 'Résultats validés', 'url' => 'biologiste/resultats-valides'],
        ['icon' => 'fa-comment-medical','label' => 'Commentaires',   'url' => 'biologiste/commentaires'],
        ['icon' => 'fa-file-waveform','label' => 'Rapports',         'url' => 'biologiste/rapports'],
    ],
    'comptable' => [
        ['icon' => 'fa-gauge',        'label' => 'Tableau de bord',  'url' => 'dashboard/comptable'],
        ['icon' => 'fa-file-invoice-dollar','label' => 'Facturation','url' => 'comptable/facturation'],
        ['icon' => 'fa-chart-pie',    'label' => 'Comptabilité',     'url' => 'comptable/comptabilite'],
        ['icon' => 'fa-hand-holding-dollar','label' => 'Impayés',    'url' => 'comptable/impayes'],
    ],
    'infirmier' => [
        ['icon' => 'fa-gauge',        'label' => 'Tableau de bord',  'url' => 'dashboard/infirmier'],
        ['icon' => 'fa-syringe',      'label' => 'Prélèvements',     'url' => 'infirmier/prelevements'],
        ['icon' => 'fa-users',        'label' => 'Patients',         'url' => 'infirmier/patients'],
    ],
];

$items = $navItems[$currentRole] ?? $navItems['admin'];

// Libellés des rôles
$roleLabels = [
    'admin'      => 'Administrateur',
    'directeur'  => 'Direction',
    'secretaire' => 'Secrétariat',
    'caissier'   => 'Caisse',
    'technicien' => 'Laboratoire',
    'biologiste' => 'Biologie',
    'comptable'  => 'Comptabilité',
    'infirmier'  => 'Infirmerie',
];
$roleLabel = $roleLabels[$currentRole] ?? 'Utilisateur';
?>

<!-- ══════════════════════════════════════════════
     OVERLAY MOBILE (fond sombre derrière le drawer)
══════════════════════════════════════════════ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ══════════════════════════════════════════════
     SIDEBAR (desktop fixe | mobile drawer)
══════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">
            <i class="fas fa-flask"></i>
        </div>
        <div class="sidebar-logo-text">
            <span class="sidebar-logo-name">Labo Drouot</span>
            <span class="sidebar-logo-sub">Douala</span>
        </div>
        <!-- Bouton fermer (mobile seulement) -->
        <button class="sidebar-close-btn" onclick="closeSidebar()" aria-label="Fermer le menu">
            <i class="fas fa-xmark"></i>
        </button>
    </div>

    <!-- Rôle actif -->
    <div class="sidebar-role-badge">
        <i class="fas fa-circle-dot"></i>
        <?= htmlspecialchars($roleLabel) ?>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <?php foreach ($items as $item): ?>
            <?php
                $url     = BASE_URL . $item['url'];
                $isActive = str_contains($currentUri, $item['url']);
            ?>
            <a href="<?= $url ?>"
               class="sidebar-nav-item <?= $isActive ? 'active' : '' ?>"
               onclick="closeSidebar()">
                <i class="fas <?= $item['icon'] ?>"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Footer sidebar -->
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>logout" class="sidebar-logout">
            <i class="fas fa-right-from-bracket"></i>
            <span>Déconnexion</span>
        </a>
    </div>

</aside>
