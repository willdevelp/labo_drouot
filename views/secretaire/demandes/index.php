<?php
/**
 * Liste des demandes d'examens
 */
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Demandes d'examens</h1>
        <p class="page-sub"><?= count($demandes) ?> demande(s)</p>
    </div>
    <a href="<?= BASE_URL ?>secretaire/demandes/create"
       class="btn-primary-drouot">
        <i class="fas fa-plus"></i> Nouvelle demande
    </a>
</div>

<!-- Filtres -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search" class="search-input"
                   placeholder="Rechercher par patient, téléphone..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <select name="statut" class="filter-select">
            <option value="">Tous les statuts</option>
            <option value="pending"
                <?= ($_GET['statut'] ?? '') === 'pending'    ? 'selected' : '' ?>>
                En attente
            </option>
            <option value="paid"
                <?= ($_GET['statut'] ?? '') === 'paid'       ? 'selected' : '' ?>>
                Payé
            </option>
            <option value="processing"
                <?= ($_GET['statut'] ?? '') === 'processing' ? 'selected' : '' ?>>
                En cours
            </option>
            <option value="completed"
                <?= ($_GET['statut'] ?? '') === 'completed'  ? 'selected' : '' ?>>
                Terminé
            </option>
        </select>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-filter"></i> Filtrer
        </button>
        <a href="<?= BASE_URL ?>secretaire/demandes"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<!-- Table -->
<div class="card-drouot">
    <?php if (empty($demandes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-flask fa-3x mb-3" style="color:#dde3f0;"></i>
            <p>Aucune demande trouvée.</p>
            <a href="<?= BASE_URL ?>secretaire/demandes/create"
               class="btn-primary-drouot mt-2">
                <i class="fas fa-plus"></i> Créer une demande
            </a>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table-drouot">
            <thead>
                <tr>
                    <th>Réf.</th>
                    <th>Patient</th>
                    <th>Examens</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Paiement</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demandes as $d):
                    $statusBadges = [
                        'pending'    => 'badge-pending',
                        'paid'       => 'badge-paid',
                        'processing' => 'badge-warn',
                        'completed'  => 'badge-normal',
                    ];
                    $sc = $statusBadges[$d['status']] ?? 'badge-pending';
                    $pc = $d['payment_status'] === 'paid'
                        ? 'badge-normal' : 'badge-critical';
                ?>
                <tr>
                    <td>
                        <span class="ref-code">
                            DE-<?= str_pad($d['id'], 4, '0', STR_PAD_LEFT) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:500;font-size:.88rem;">
                            <?= htmlspecialchars($d['patient_nom']) ?>
                        </div>
                        <div style="font-size:.75rem;color:#6b7a99;">
                            <?= htmlspecialchars($d['telephone']) ?>
                        </div>
                    </td>
                    <td style="font-size:.8rem;color:#6b7a99;max-width:180px;">
                        <?= htmlspecialchars(
                            mb_strimwidth($d['examens'] ?? '—', 0, 40, '...')
                        ) ?>
                    </td>
                    <td style="font-weight:600;color:#1E88E5;">
                        <?= number_format((float)$d['total_price'], 0, ',', ' ') ?> F
                    </td>
                    <td><span class="<?= $sc ?>">
                        <?= ucfirst($d['status']) ?>
                    </span></td>
                    <td><span class="<?= $pc ?>">
                        <?= $d['payment_status'] === 'paid' ? 'Payé' : 'Impayé' ?>
                    </span></td>
                    <td style="font-size:.8rem;color:#6b7a99;">
                        <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
                    </td>
                    <td>
                        <a href="<?= BASE_URL ?>secretaire/demandes/<?= $d['id'] ?>"
                           class="action-btn" title="Voir">
                            <i class="fas fa-eye"></i>
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
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem;
    border:1.5px solid #dde3f0; border-radius:10px;
    font-family:'DM Sans',sans-serif; font-size:.9rem;
    color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }
.filter-select { padding:.7rem .9rem; border:1.5px solid #dde3f0;
    border-radius:10px; font-family:'DM Sans',sans-serif;
    font-size:.88rem; color:#1a2340; background:#fafbfd;
    outline:none; cursor:pointer; }
.table-drouot { width:100%; border-collapse:collapse; font-size:.83rem; }
.table-drouot th { padding:8px 10px; text-align:left; font-size:.73rem;
    font-weight:600; color:#6b7a99; text-transform:uppercase;
    letter-spacing:.05em; border-bottom:1.5px solid #f0f2f8; background:#fafbfc; }
.table-drouot td { padding:10px; color:#1a2340;
    border-bottom:1px solid #f0f2f8; vertical-align:middle; }
.table-drouot tr:last-child td { border-bottom:none; }
.table-drouot tr:hover td { background:#fafbff; }
.ref-code { font-family:monospace; font-size:.78rem; color:#1E88E5; font-weight:600; }
.action-btn { display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px; border-radius:6px;
    background:#E3F2FD; color:#1565C0;
    text-decoration:none; font-size:.78rem; transition:all .2s; }
.action-btn:hover { background:#1E88E5; color:#fff; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px;
    padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px;
    background:#fff; color:#1a2340; font-size:.88rem; font-weight:500;
    text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
</style>


<div id="offlineDemandesRoot"></div>
<script>
(function () {
    const SERVER = <?= json_encode(array_map(function ($d) {
        return [
            'id' => (string)$d['id'],
            'reference' => $d['reference'] ?? $d['ref'] ?? '',
            'patient_nom' => trim(($d['patient_nom'] ?? '') . ' ' . ($d['patient_prenom'] ?? '')),
            'statut' => $d['statut'] ?? '',
            'created_at' => $d['created_at'] ?? '',
            'synced' => 1,
        ];
    }, $demandes ?? []), JSON_UNESCAPED_UNICODE) ?>;

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function isLocal(id) { return String(id).startsWith('local_'); }

    async function refresh() {
        if (typeof LocalDB === 'undefined') return;
        try { if (SERVER.length) await LocalDB.seedStore('demandes', SERVER); } catch(e){}
        const all = (await LocalDB.getAll('demandes')).filter(x => !x._deleted);
        const pending = all.filter(x => isLocal(x.id) || x.synced === 0);
        const root = document.getElementById('offlineDemandesRoot');
        if (!root) return;

        if (!navigator.onLine) {
            document.querySelectorAll('.table-drouot').forEach(t => t.style.display = 'none');
            root.innerHTML = `
              <div class="card-drouot mb-3" style="border-color:#FFE082;background:#FFFDE7;">
                <strong style="color:#F57F17;"><i class="fas fa-database me-1"></i> Mode hors ligne</strong>
                — ${all.length} demande(s)
              </div>
              <div class="card-drouot"><div class="table-responsive"><table class="table-drouot" style="width:100%">
                <thead><tr><th>Réf.</th><th>Patient</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                ${all.length ? all.map(d => `
                  <tr>
                    <td class="ref-code">${esc(d.reference || d.id)}</td>
                    <td>${esc(d.patient_nom || ('Patient #' + (d.patient_id || '')))}</td>
                    <td>${esc(d.statut || 'local')}</td>
                    <td>${isLocal(d.id)||d.synced===0 ? '<span class="badge-pending">Local</span>' : ''}</td>
                  </tr>`).join('') : '<tr><td colspan="4">Aucune demande locale</td></tr>'}
                </tbody>
              </table></div></div>`;
        } else if (pending.length) {
            root.innerHTML = `
              <div class="card-drouot mb-3" style="border-color:#FFE082;background:#FFFDE7;">
                <strong style="color:#F57F17;">${pending.length} demande(s) créée(s) hors ligne</strong>
                <ul style="margin:8px 0 0;padding-left:18px;font-size:.85rem;">
                  ${pending.map(d => `<li>${esc(d.reference || d.id)} — ${esc(d.patient_nom || d.label || '')}</li>`).join('')}
                </ul>
              </div>`;
        } else root.innerHTML = '';
    }
    document.addEventListener('DOMContentLoaded', refresh);
    window.addEventListener('localdb:demandes-updated', refresh);
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