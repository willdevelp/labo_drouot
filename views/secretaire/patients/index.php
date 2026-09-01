<?php
/**
 * Liste patients — Secrétaire
 *
 * Variables attendues du controller :
 * $patients, $totalPatients, $page, $perPage, $totalPages
 */
include __DIR__ . '/../../../views/layouts/header.php';

// Query string des filtres actifs (hors page/per_page), pour construire
// les liens de pagination sans perdre la recherche en cours.
$qsParams = array_filter([
    'search' => $_GET['search'] ?? '',
], fn($v) => $v !== '');
$qsBase = http_build_query($qsParams);
$qs     = $qsBase !== '' ? $qsBase . '&' : '';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Patients</h1>
        <p class="page-sub">
            <?= $totalPatients ?> patient(s) au total
            <?php if (!empty($_GET['search'])): ?>
                · résultats pour « <?= htmlspecialchars($_GET['search']) ?> »
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>secretaire/patients/create" class="btn-primary-drouot">
        <i class="fas fa-user-plus"></i> Nouveau patient
    </a>
</div>

<!-- Patients créés hors ligne, en attente de synchronisation -->
<div class="card-drouot mb-4" id="pendingPatientsBox" style="display:none; border-left:3px solid #F9A825;">
    <h6 class="card-section-title mb-3" style="display:flex;align-items:center;gap:8px;">
        <i class="fas fa-cloud-arrow-up" style="color:#F9A825;"></i>
        En attente de synchronisation
    </h6>
    <div id="pendingPatientsList"></div>
</div>

<script>
(async function () {
    const box  = document.getElementById('pendingPatientsBox');
    const list = document.getElementById('pendingPatientsList');
    if (!box || typeof OfflineOutbox === 'undefined') return;

    async function render() {
        const items = (await OfflineOutbox.getAll()).filter(i => i.type === 'patient');
        if (items.length === 0) {
            box.style.display = 'none';
            return;
        }
        box.style.display = 'block';
        list.innerHTML = items.map(i => `
            <div style="display:flex;align-items:center;justify-content:between;
                        gap:10px;padding:8px 0;border-bottom:1px solid #f0f2f8;
                        font-size:.85rem;">
                <i class="fas fa-user-clock" style="color:#F9A825;"></i>
                <span style="flex:1;">${i.label}</span>
                <span style="color:#6b7a99;font-size:.75rem;">
                    ${new Date(i.createdAt).toLocaleString('fr-FR')}
                </span>
            </div>
        `).join('');
    }

    window.addEventListener('outbox:updated', render);
    render();
})();
</script>

<!-- Recherche + taille de page -->
<div class="card-drouot mb-4">
    <form method="GET" action="" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search"
                   class="search-input"
                   placeholder="Rechercher par nom, prénom, téléphone, ID..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   autofocus>
        </div>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-search"></i> Rechercher
        </button>
        <?php if (!empty($_GET['search'])): ?>
        <a href="<?= BASE_URL ?>secretaire/patients" class="btn-outline-drouot">
            <i class="fas fa-xmark"></i> Effacer
        </a>
        <?php endif; ?>

        <div class="per-page-wrap">
            <label for="perPageSelect" class="per-page-label">Par page</label>
            <select id="perPageSelect" name="per_page" class="per-page-select" onchange="this.form.submit()">
                <option value="25" <?= $perPage == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100</option>
            </select>
        </div>
    </form>
</div>

<!-- Liste -->
<div class="card-drouot">
    <?php if (empty($patients)): ?>
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x mb-3" style="color:#dde3f0;"></i>
            <p class="text-muted">Aucun patient trouvé.</p>
            <a href="<?= BASE_URL ?>secretaire/patients/create"
               class="btn-primary-drouot mt-2">
                <i class="fas fa-user-plus"></i> Créer le premier patient
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-drouot">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>ID</th>
                        <th>Téléphone</th>
                        <th>Sexe</th>
                        <th>Date naiss.</th>
                        <th>Enregistré le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $p): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="patient-avatar-sm <?= $p['sexe'] === 'F' ? 'female' : 'male' ?>">
                                    <?= strtoupper(
                                        substr($p['prenom'] ?? 'P', 0, 1) .
                                        substr($p['nom']    ?? '',  0, 1)
                                    ) ?>
                                </div>
                                <div>
                                    <div style="font-weight:500;font-size:.88rem;">
                                        <?= htmlspecialchars(strtoupper($p['nom']) . ' ' . $p['prenom']) ?>
                                    </div>
                                    <div style="font-size:.75rem;color:#6b7a99;">
                                        <?= htmlspecialchars($p['email'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-family:monospace;font-size:.75rem;color:#1E88E5;">
                                <?= htmlspecialchars(substr($p['uuid'], 0, 8)) ?>...
                            </span>
                        </td>
                        <td><?= htmlspecialchars($p['telephone'] ?? '—') ?></td>
                        <td>
                            <span class="<?= $p['sexe'] === 'F' ? 'badge-female-badge' : 'badge-male-badge' ?>">
                                <?= $p['sexe'] === 'F' ? 'F' : 'M' ?>
                            </span>
                        </td>
                        <td style="font-size:.82rem;color:#6b7a99;">
                            <?= $p['date_naissance']
                                ? date('d/m/Y', strtotime($p['date_naissance']))
                                : '—' ?>
                        </td>
                        <td style="font-size:.82rem;color:#6b7a99;">
                            <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= BASE_URL ?>secretaire/patients/<?= $p['id'] ?>"
                                   class="action-btn" title="Voir dossier">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>secretaire/patients/<?= $p['id'] ?>/edit"
                                   class="action-btn action-btn-edit" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <a href="<?= BASE_URL ?>secretaire/demandes/create?patient_id=<?= $p['id'] ?>"
                                   class="action-btn action-btn-green" title="Nouvelle demande">
                                    <i class="fas fa-flask"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-drouot mt-3">
            <span class="pagination-info">
                Page <?= $page ?> / <?= $totalPages ?> — <?= $totalPatients ?> patient(s)
            </span>
            <div class="pagination-controls">
                <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
                   href="<?= BASE_URL ?>secretaire/patients?<?= $qs ?>per_page=<?= $perPage ?>&page=1"
                   title="Première page">
                    <i class="fas fa-angles-left"></i>
                </a>
                <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
                   href="<?= BASE_URL ?>secretaire/patients?<?= $qs ?>per_page=<?= $perPage ?>&page=<?= max(1, $page - 1) ?>"
                   title="Page précédente">
                    <i class="fas fa-chevron-left"></i>
                </a>

                <?php
                // Fenêtre glissante de numéros de page autour de la page actuelle
                $range = 2;
                $start = max(1, $page - $range);
                $end   = min($totalPages, $page + $range);
                if ($start > 1): ?>
                    <a class="page-num" href="<?= BASE_URL ?>secretaire/patients?<?= $qs ?>per_page=<?= $perPage ?>&page=1">1</a>
                    <?php if ($start > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a class="page-num <?= $i === $page ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>secretaire/patients?<?= $qs ?>per_page=<?= $perPage ?>&page=<?= $i ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
                    <a class="page-num" href="<?= BASE_URL ?>secretaire/patients?<?= $qs ?>per_page=<?= $perPage ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
                <?php endif; ?>

                <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
                   href="<?= BASE_URL ?>secretaire/patients?<?= $qs ?>per_page=<?= $perPage ?>&page=<?= min($totalPages, $page + 1) ?>"
                   title="Page suivante">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
                   href="<?= BASE_URL ?>secretaire/patients?<?= $qs ?>per_page=<?= $perPage ?>&page=<?= $totalPages ?>"
                   title="Dernière page">
                    <i class="fas fa-angles-right"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }

.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap {
    position:relative; flex:1; min-width:200px;
}
.search-input-wrap i {
    position:absolute; left:.9rem; top:50%;
    transform:translateY(-50%); color:#b0b8cc;
}
.search-input {
    width:100%; padding:.7rem .9rem .7rem 2.4rem;
    border:1.5px solid #dde3f0; border-radius:10px;
    font-family:'DM Sans',sans-serif; font-size:.9rem;
    color:#1a2340; background:#fafbfd; outline:none;
    transition:border .2s;
}
.search-input:focus { border-color:#1E88E5; }

.per-page-wrap {
    display:flex; align-items:center; gap:8px;
    margin-left:auto;
}
.per-page-label { font-size:.78rem; color:#6b7a99; white-space:nowrap; }
.per-page-select {
    padding:.55rem .7rem; border:1.5px solid #dde3f0; border-radius:9px;
    font-family:'DM Sans',sans-serif; font-size:.85rem; color:#1a2340;
    background:#fafbfd; outline:none; cursor:pointer;
}
.per-page-select:focus { border-color:#1E88E5; }

.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340; border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }

.patient-avatar-sm {
    width:32px; height:32px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.75rem; font-weight:600; flex-shrink:0;
}
.patient-avatar-sm.male   { background:#E3F2FD; color:#1565C0; }
.patient-avatar-sm.female { background:#FCE4EC; color:#880E4F; }
.badge-male-badge   { background:#E3F2FD; color:#1565C0; padding:2px 8px; border-radius:20px; font-size:.75rem; }
.badge-female-badge { background:#FCE4EC; color:#880E4F; padding:2px 8px; border-radius:20px; font-size:.75rem; }

.action-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px; border-radius:6px;
    background:#E3F2FD; color:#1565C0;
    text-decoration:none; font-size:.78rem;
    transition:all .2s;
}
.action-btn:hover       { background:#1E88E5; color:#fff; }
.action-btn-edit        { background:#FFF8E1; color:#F57F17; }
.action-btn-edit:hover  { background:#F9A825; color:#fff; }
.action-btn-green       { background:#E8F5E9; color:#2E7D32; }
.action-btn-green:hover { background:#2E7D32; color:#fff; }

.btn-outline-drouot {
    display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:1.5px solid #dde3f0;
    border-radius:9px; background:#fff; color:#1a2340;
    font-size:.88rem; font-weight:500; text-decoration:none;
    transition:all .2s; font-family:'DM Sans',sans-serif;
}
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }

/* ── Pagination ── */
.pagination-drouot {
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:10px;
}
.pagination-info { font-size:.78rem; color:#6b7a99; }
.pagination-controls { display:flex; align-items:center; gap:4px; flex-wrap:wrap; }
.page-btn, .page-num {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:32px; height:32px; padding:0 8px;
    border-radius:8px; background:#F4F6F9; color:#1a2340;
    text-decoration:none; font-size:.8rem; transition:all .15s;
}
.page-btn:hover:not(.disabled), .page-num:hover:not(.active) { background:#1E88E5; color:#fff; }
.page-btn.disabled { opacity:.4; pointer-events:none; }
.page-num.active { background:#1E88E5; color:#fff; font-weight:600; }
.page-ellipsis { padding:0 4px; color:#9aa5bd; font-size:.82rem; }

@media (max-width:768px) {
    .per-page-wrap { margin-left:0; }
    .pagination-drouot { flex-direction:column; align-items:flex-start; }
}
</style>


<div id="offlinePatientsRoot"></div>
<script>
(function () {
    const SERVER = <?= json_encode(array_map(function ($p) {
        return [
            'id' => (string)$p['id'],
            'nom' => $p['nom'] ?? '',
            'prenom' => $p['prenom'] ?? '',
            'sexe' => $p['sexe'] ?? '',
            'telephone' => $p['telephone'] ?? '',
            'date_naissance' => $p['date_naissance'] ?? '',
            'synced' => 1,
        ];
    }, $patients ?? []), JSON_UNESCAPED_UNICODE) ?>;

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function isLocal(id) { return String(id).startsWith('local_'); }

    async function refresh() {
        if (typeof LocalDB === 'undefined') return;
        try { if (SERVER.length) await LocalDB.seedStore('patients', SERVER); } catch(e){}
        const all = (await LocalDB.getAll('patients')).filter(x => !x._deleted);
        const pending = all.filter(x => isLocal(x.id) || x.synced === 0);
        const root = document.getElementById('offlinePatientsRoot');
        if (!root) return;

        if (!navigator.onLine) {
            // masquer tableau serveur
            document.querySelectorAll('.table-drouot').forEach(t => { t.style.display = 'none'; });
            root.innerHTML = `
              <div class="card-drouot mb-3" style="border-color:#FFE082;background:#FFFDE7;">
                <strong style="color:#F57F17;"><i class="fas fa-database me-1"></i> Mode hors ligne</strong>
                — ${all.length} patient(s) sur cet appareil
                ${pending.length ? ' · ' + pending.length + ' non sync' : ''}
              </div>
              <div class="card-drouot"><div class="table-responsive"><table class="table-drouot" style="width:100%">
                <thead><tr>
                  <th>Nom</th><th>Prénom</th><th>Sexe</th><th>Téléphone</th><th>Statut</th>
                </tr></thead>
                <tbody>
                  ${all.length ? all.map(p => `
                    <tr>
                      <td>${esc(p.nom)}</td>
                      <td>${esc(p.prenom)}</td>
                      <td>${esc(p.sexe)}</td>
                      <td>${esc(p.telephone)}</td>
                      <td>${isLocal(p.id)||p.synced===0 ? '<span class="badge-pending">Local</span>' : '<span class="badge-normal">Sync</span>'}</td>
                    </tr>`).join('') : '<tr><td colspan="5" class="text-muted">Aucun patient local</td></tr>'}
                </tbody>
              </table></div></div>`;
        } else if (pending.length) {
            root.innerHTML = `
              <div class="card-drouot mb-3" style="border-color:#FFE082;background:#FFFDE7;">
                <strong style="color:#F57F17;">${pending.length} patient(s) créé(s) hors ligne</strong> — sync auto
                <ul style="margin:8px 0 0;padding-left:18px;font-size:.85rem;">
                  ${pending.map(p => `<li>${esc(p.nom)} ${esc(p.prenom)} — ${esc(p.telephone)}</li>`).join('')}
                </ul>
              </div>`;
        } else {
            root.innerHTML = '';
        }
    }
    document.addEventListener('DOMContentLoaded', refresh);
    window.addEventListener('localdb:patients-updated', refresh);
    window.addEventListener('outbox:updated', refresh);
    window.addEventListener('online', async () => {
        if (typeof LocalDB !== 'undefined') {
            await LocalDB.syncAll(true);
            setTimeout(() => location.reload(), 500);
        }
    });
    window.addEventListener('offline', refresh);
})();
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>