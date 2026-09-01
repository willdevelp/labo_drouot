<?php
/**
 * Gestion des compagnies d'assurance — Admin
 * Laboratoire Drouot
 */
include dirname(__DIR__) . '/../../views/layouts/header.php';
?>

<?php if ($success): ?>
<div class="alert-drouot alert-success mb-3">
    <i class="fas fa-circle-check"></i>
    Opération effectuée avec succès !
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert-drouot alert-danger mb-3">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">Compagnies d'assurance</h1>
        <p class="page-sub">
            <?= $stats['total'] ?> compagnie(s) ·
            <?= $stats['actifs'] ?> active(s) ·
            <?= number_format((float)$stats['total_du'], 0, ',', ' ') ?> FCFA de créances dues
        </p>
    </div>
</div>

<!-- Recherche -->
<div class="card-drouot mb-4">
    <form method="GET" class="search-form">
        <div class="search-input-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="search" class="search-input"
                   placeholder="Nom de la compagnie, contact..."
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-primary-drouot">
            <i class="fas fa-search"></i> Rechercher
        </button>
        <a href="<?= BASE_URL ?>admin/assurances" class="btn-outline-drouot">
            <i class="fas fa-xmark"></i>
        </a>
    </form>
</div>

<div class="row g-4">

    <!-- Colonne gauche — Liste assurances -->
    <div class="col-xl-7">
        <div class="card-drouot">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-shield-heart text-primary me-2"></i>
                Compagnies enregistrées
                <span class="count-badge"><?= count($assurances) ?></span>
            </h6>

            <?php if (empty($assurances)): ?>
            <div class="empty-params">
                <i class="fas fa-shield-heart fa-2x mb-2" style="color:#dde3f0;"></i>
                <p>Aucune compagnie enregistrée.</p>
                <p style="font-size:.78rem;">
                    Ajoutez une compagnie d'assurance via le formulaire à droite.
                </p>
            </div>
            <?php else: ?>

            <div class="assurances-list">
                <?php foreach ($assurances as $a):
                    $portalUrl = BASE_URL . 'assurance/portal/' . $a['token_acces'];
                    $waMessage = rawurlencode(
                        "Bonjour, voici le lien d'accès à votre portail Laboratoire Drouot pour valider les prises en charge de vos assurés : " . $portalUrl
                    );
                    $creancesDeAssurance = $creancesParAssurance[$a['id']] ?? [];
                    $nbDues = count(array_filter($creancesDeAssurance, fn($c) => $c['statut'] === 'due'));
                ?>
                <div class="assurance-card <?= !$a['is_active'] ? 'assurance-inactive' : '' ?>"
                     id="assCard_<?= $a['id'] ?>">

                    <!-- Vue normale -->
                    <div class="assurance-view" id="assView_<?= $a['id'] ?>">
                        <div class="assurance-top">
                            <div>
                                <div class="assurance-nom">
                                    <?= htmlspecialchars($a['nom']) ?>
                                </div>
                                <div class="assurance-meta">
                                    <?php if ($a['contact_nom']): ?>
                                    <span class="assurance-contact">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($a['contact_nom']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <span class="assurance-taux">
                                        <?= number_format((float)$a['taux_defaut'], 1) ?>% par défaut
                                    </span>
                                    <?php if ($a['nb_accords'] > 0): ?>
                                    <span class="assurance-accords">
                                        <?= $a['nb_accords'] ?> accord(s) spécifique(s)
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($a['is_active']): ?>
                                <span class="badge-normal">Active</span>
                            <?php else: ?>
                                <span class="badge-critical">Inactive</span>
                            <?php endif; ?>
                        </div>

                        <div class="assurance-stats">
                            <span class="assurance-stat">
                                <i class="fas fa-file-medical"></i>
                                <?= $a['nb_demandes'] ?> demande(s) liée(s)
                            </span>
                            <span class="assurance-stat stat-due">
                                <i class="fas fa-hourglass-half"></i>
                                <?= number_format((float)$a['creances_dues'], 0, ',', ' ') ?> F dues
                            </span>
                            <span class="assurance-stat stat-paid">
                                <i class="fas fa-circle-check"></i>
                                <?= number_format((float)$a['creances_payees'], 0, ',', ' ') ?> F payées
                            </span>
                        </div>

                        <!-- Lien portail -->
                        <div class="portal-link-box">
                            <input type="text" readonly
                                   class="portal-link-input"
                                   id="portalUrl_<?= $a['id'] ?>"
                                   value="<?= htmlspecialchars($portalUrl) ?>">
                            <button type="button"
                                    class="portal-link-btn"
                                    onclick="copyPortalLink(<?= $a['id'] ?>)"
                                    title="Copier le lien">
                                <i class="fas fa-copy"></i>
                            </button>
                            <a href="https://wa.me/?text=<?= $waMessage ?>"
                               target="_blank" rel="noopener"
                               class="portal-link-btn portal-link-wa"
                               title="Envoyer par WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>

                        <div class="assurance-actions">
                            <a href="<?= BASE_URL ?>admin/assurances/<?= $a['id'] ?>/assures"
                               class="action-btn action-btn-import"
                               title="Importer / gérer les assurés">
                                <i class="fas fa-file-excel"></i>
                            </a>
                            <a href="<?= BASE_URL ?>admin/assurances/<?= $a['id'] ?>/taux"
                               class="action-btn action-btn-taux"
                               title="Configurer les taux par examen">
                                <i class="fas fa-percent"></i>
                            </a>
                            <button onclick="editAssurance(<?= $a['id'] ?>)"
                                    class="action-btn action-btn-edit"
                                    title="Modifier">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form method="POST"
                                  action="<?= BASE_URL ?>admin/assurances/<?= $a['id'] ?>/toggle"
                                  class="js-offline-form"
                                  data-offline-type="assurance"
                                  data-offline-label="<?= htmlspecialchars(($a['is_active'] ? 'Désactivation' : 'Activation') . ' — ' . $a['nom']) ?>"
                                  data-offline-redirect="<?= BASE_URL ?>admin/assurances"
                                  style="display:inline;">
                                <button type="submit"
                                        class="action-btn <?= $a['is_active'] ? 'action-btn-warn' : 'action-btn-green' ?>"
                                        title="<?= $a['is_active'] ? 'Désactiver' : 'Activer' ?>">
                                    <i class="fas <?= $a['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                </button>
                            </form>
                            <form method="POST"
                                  action="<?= BASE_URL ?>admin/assurances/<?= $a['id'] ?>/regenerer-token"
                                  class="js-offline-form"
                                  data-offline-type="assurance"
                                  data-offline-label="<?= htmlspecialchars('Régénération lien — ' . $a['nom']) ?>"
                                  data-offline-redirect="<?= BASE_URL ?>admin/assurances"
                                  style="display:inline;">
                                <button type="submit"
                                        class="action-btn action-btn-regen"
                                        title="Régénérer le lien"
                                        onclick="return confirm('Régénérer le lien rendra l\'ancien inutilisable. Continuer ?')">
                                    <i class="fas fa-rotate"></i>
                                </button>
                            </form>
                            <form method="POST"
                                  action="<?= BASE_URL ?>admin/assurances/<?= $a['id'] ?>/delete"
                                  class="js-offline-form"
                                  data-offline-type="assurance"
                                  data-offline-label="<?= htmlspecialchars('Suppression — ' . $a['nom']) ?>"
                                  data-offline-redirect="<?= BASE_URL ?>admin/assurances"
                                  style="display:inline;">
                                <button type="submit"
                                        class="action-btn action-btn-danger"
                                        title="Supprimer"
                                        onclick="return confirm('Supprimer cette compagnie ?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Créances — section dépliable -->
                        <?php if (!empty($creancesDeAssurance)): ?>
                        <div class="creances-toggle" onclick="toggleCreances(<?= $a['id'] ?>)">
                            <i class="fas fa-chevron-down" id="creChevron_<?= $a['id'] ?>"></i>
                            <span>
                                <?= count($creancesDeAssurance) ?> créance(s)
                                <?php if ($nbDues > 0): ?>
                                · <span class="due-count"><?= $nbDues ?> due(s)</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="creances-panel" id="crePanel_<?= $a['id'] ?>" style="display:none;">

                            <?php if ($nbDues > 0): ?>
                            <form method="POST"
                                  action="<?= BASE_URL ?>admin/assurances/<?= $a['id'] ?>/payer-toutes-creances"
                                  class="mb-2">
                                <button type="submit" class="btn-pay-all"
                                        onclick="return confirm('Marquer les <?= $nbDues ?> créance(s) due(s) comme payées ?')">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Tout payer (<?= $nbDues ?>)
                                </button>
                            </form>
                            <?php endif; ?>

                            <div class="creance-lines">
                                <?php foreach ($creancesDeAssurance as $c): ?>
                                <div class="creance-line">
                                    <div class="cre-line-info">
                                        <div class="cre-line-patient"><?= htmlspecialchars($c['patient_nom']) ?></div>
                                        <div class="cre-line-meta">
                                            <?= htmlspecialchars($c['reference']) ?> ·
                                            <?= number_format((float)$c['montant_base'], 0, ',', ' ') ?> F ×
                                            <?= number_format((float)$c['taux_applique'], 1) ?>%
                                        </div>
                                    </div>
                                    <div class="cre-line-amount">
                                        <?= number_format((float)$c['montant_creance'], 0, ',', ' ') ?> F
                                    </div>
                                    <?php if ($c['statut'] === 'due'): ?>
                                    <form method="POST"
                                          action="<?= BASE_URL ?>admin/assurances/creances/<?= $c['id'] ?>/payer">
                                        <button type="submit" class="btn-pay-one" title="Marquer comme payée">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="cre-paid-tag" title="Payée le <?= date('d/m/Y', strtotime($c['paid_at'])) ?>">
                                        <i class="fas fa-circle-check"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>

                    <!-- Formulaire édition inline -->
                    <div class="assurance-edit-form" id="assEdit_<?= $a['id'] ?>" style="display:none;">
                        <form method="POST" action="<?= BASE_URL ?>admin/assurances/<?= $a['id'] ?>/edit"
                              class="js-offline-form"
                              data-offline-type="assurance"
                              data-offline-label="<?= htmlspecialchars('Modification — ' . $a['nom']) ?>"
                              data-offline-redirect="<?= BASE_URL ?>admin/assurances">
                            <div class="edit-inline-grid">
                                <div>
                                    <label class="form-label-drouot">Nom *</label>
                                    <input type="text" name="nom" class="form-input-drouot"
                                           value="<?= htmlspecialchars($a['nom']) ?>" required>
                                </div>
                                <div>
                                    <label class="form-label-drouot">Contact</label>
                                    <input type="text" name="contact_nom" class="form-input-drouot"
                                           value="<?= htmlspecialchars($a['contact_nom'] ?? '') ?>">
                                </div>
                                <div>
                                    <label class="form-label-drouot">Taux défaut (%)</label>
                                    <input type="number" name="taux_defaut" step="0.1" min="0" max="100"
                                           class="form-input-drouot" value="<?= $a['taux_defaut'] ?>">
                                </div>
                                <div>
                                    <label class="form-label-drouot">Téléphone</label>
                                    <input type="text" name="telephone" class="form-input-drouot"
                                           value="<?= htmlspecialchars($a['telephone'] ?? '') ?>">
                                </div>
                                <div class="col-span-2">
                                    <label class="form-label-drouot">Email</label>
                                    <input type="email" name="email" class="form-input-drouot"
                                           value="<?= htmlspecialchars($a['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="submit" class="btn-success-drouot"
                                        style="padding:6px 14px;font-size:.82rem;">
                                    <i class="fas fa-floppy-disk"></i> Sauvegarder
                                </button>
                                <button type="button" onclick="cancelEditAssurance(<?= $a['id'] ?>)"
                                        class="btn-outline-drouot"
                                        style="padding:6px 14px;font-size:.82rem;">
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colonne droite — Ajouter assurance -->
    <div class="col-xl-5">
        <div class="card-drouot sticky-form">
            <h6 class="card-section-title mb-3">
                <i class="fas fa-plus text-primary me-2"></i>
                Ajouter une compagnie
            </h6>

            <form method="POST" action="<?= BASE_URL ?>admin/assurances/store" id="formCreateAssurance" data-offline-store="assurances">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label-drouot">
                            Nom de la compagnie <span class="required">*</span>
                        </label>
                        <input type="text" name="nom" class="form-input-drouot"
                               placeholder="Ex: NSIA Assurances" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label-drouot">Nom du contact / gestionnaire</label>
                        <input type="text" name="contact_nom" class="form-input-drouot"
                               placeholder="Ex: M. NJOYA Paul">
                    </div>
                    <div class="col-6">
                        <label class="form-label-drouot">Téléphone</label>
                        <input type="text" name="telephone" class="form-input-drouot"
                               placeholder="Ex: 6XX XXX XXX">
                    </div>
                    <div class="col-6">
                        <label class="form-label-drouot">Email</label>
                        <input type="email" name="email" class="form-input-drouot"
                               placeholder="contact@nsia.cm">
                    </div>
                    <div class="col-12">
                        <label class="form-label-drouot">
                            Taux de prise en charge par défaut (%)
                            <span class="required">*</span>
                        </label>
                        <input type="number" name="taux_defaut" step="0.1" min="0" max="100"
                               class="form-input-drouot" placeholder="Ex: 80" value="80" required>
                        <div style="font-size:.7rem;color:#6b7a99;margin-top:3px;">
                            S'applique à tous les examens sauf accord spécifique
                            configuré ensuite par examen.
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="btn-primary-drouot w-100 justify-content-center mt-3"
                        style="padding:.8rem;">
                    <i class="fas fa-plus"></i>
                    Créer la compagnie et générer son lien
                </button>
            </form>
        </div>
    </div>

</div>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:.85rem; color:#6b7a99; margin:2px 0 0; }
.card-section-title { font-size:.88rem; font-weight:600; color:#1a2340; margin:0; display:flex; align-items:center; gap:8px; }
.count-badge { background:#E3F2FD; color:#1565C0; padding:2px 8px; border-radius:20px; font-size:.72rem; font-weight:600; }
.form-label-drouot { display:block; font-size:.78rem; font-weight:600; color:#6b7a99; text-transform:uppercase; letter-spacing:.05em; margin-bottom:5px; }
.required { color:#C62828; }
.form-input-drouot { width:100%; padding:.65rem .9rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.88rem; color:#1a2340; background:#fafbfd; outline:none; transition:all .2s; }
.form-input-drouot:focus { border-color:#1E88E5; background:#fff; box-shadow:0 0 0 3px rgba(30,136,229,.08); }
.col-span-2 { grid-column:span 2; }

.search-form { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.search-input-wrap { position:relative; flex:1; min-width:200px; }
.search-input-wrap i { position:absolute; left:.9rem; top:50%; transform:translateY(-50%); color:#b0b8cc; }
.search-input { width:100%; padding:.7rem .9rem .7rem 2.4rem; border:1.5px solid #dde3f0; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:.9rem; color:#1a2340; background:#fafbfd; outline:none; }
.search-input:focus { border-color:#1E88E5; }

/* Liste assurances */
.assurances-list { display:flex; flex-direction:column; gap:12px; }
.assurance-card { border:1.5px solid #dde3f0; border-radius:12px; padding:14px; transition:all .15s; }
.assurance-card:hover { border-color:#1E88E5; }
.assurance-inactive { opacity:.6; border-style:dashed; }

.assurance-top { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:8px; }
.assurance-nom { font-size:.92rem; font-weight:600; color:#1a2340; }
.assurance-meta { display:flex; gap:8px; margin-top:3px; flex-wrap:wrap; }
.assurance-contact { font-size:.72rem; color:#6b7a99; }
.assurance-taux { font-size:.72rem; color:#2E7D32; background:#E8F5E9; padding:1px 8px; border-radius:6px; font-weight:600; }
.assurance-accords { font-size:.72rem; color:#1565C0; background:#E3F2FD; padding:1px 8px; border-radius:6px; }

.assurance-stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; font-size:.74rem; }
.assurance-stat { display:flex; align-items:center; gap:4px; color:#6b7a99; }
.assurance-stat i { font-size:.7rem; }
.stat-due  { color:#F57F17; }
.stat-paid { color:#2E7D32; }

/* Lien portail */
.portal-link-box { display:flex; gap:6px; margin-bottom:10px; }
.portal-link-input {
    flex:1; padding:7px 10px; border:1.5px solid #dde3f0; border-radius:8px;
    font-family:monospace; font-size:.72rem; color:#1565C0;
    background:#fafbfd; outline:none;
}
.portal-link-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:34px; height:34px; border-radius:8px; flex-shrink:0;
    background:#E3F2FD; color:#1565C0; border:none; cursor:pointer;
    text-decoration:none; transition:all .2s;
}
.portal-link-btn:hover { background:#1E88E5; color:#fff; }
.portal-link-wa { background:#E8F5E9; color:#2E7D32; }
.portal-link-wa:hover { background:#25D366; color:#fff; }

.assurance-actions { display:flex; gap:5px; padding-top:8px; border-top:1px solid #f0f2f8; }
.action-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; font-size:.78rem; transition:all .2s; border:none; cursor:pointer; text-decoration:none; }
.action-btn-taux   { background:#F4F6F9; color:#1a2340; }
.action-btn-taux:hover   { background:#1a2340; color:#fff; }
.action-btn-import { background:#E8F5E9; color:#2E7D32; }
.action-btn-import:hover { background:#2E7D32; color:#fff; }
.action-btn-edit   { background:#FFF8E1; color:#F57F17; }
.action-btn-edit:hover   { background:#F9A825; color:#fff; }
.action-btn-warn   { background:#FFF8E1; color:#F57F17; }
.action-btn-warn:hover   { background:#F9A825; color:#fff; }
.action-btn-green  { background:#E8F5E9; color:#2E7D32; }
.action-btn-green:hover  { background:#2E7D32; color:#fff; }
.action-btn-regen  { background:#F4F6F9; color:#6b7a99; }
.action-btn-regen:hover  { background:#6b7a99; color:#fff; }
.action-btn-danger { background:#FFEBEE; color:#C62828; }
.action-btn-danger:hover { background:#C62828; color:#fff; }

/* Créances dépliables */
.creances-toggle {
    display:flex; align-items:center; gap:8px;
    margin-top:10px; padding-top:10px; border-top:1px solid #f0f2f8;
    font-size:.78rem; color:#1565C0; cursor:pointer;
    user-select:none; transition:color .15s;
}
.creances-toggle:hover { color:#0D47A1; }
.creances-toggle i { font-size:.7rem; transition:transform .2s; }
.creances-toggle.open i { transform:rotate(-180deg); }
.due-count { color:#F57F17; font-weight:600; }

.creances-panel {
    margin-top:10px; padding:10px; background:#fafbfc;
    border-radius:10px; border:1px solid #f0f2f8;
}
.btn-pay-all {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 14px; border-radius:8px; border:none; cursor:pointer;
    background:#2E7D32; color:#fff; font-size:.78rem; font-weight:500;
    transition:background .2s;
}
.btn-pay-all:hover { background:#1B5E20; }

.creance-lines { display:flex; flex-direction:column; gap:0; }
.creance-line {
    display:flex; align-items:center; gap:10px;
    padding:8px 0; border-bottom:1px solid #f0f2f8;
}
.creance-line:last-child { border-bottom:none; }
.cre-line-info { flex:1; min-width:0; }
.cre-line-patient { font-size:.82rem; font-weight:500; color:#1a2340; }
.cre-line-meta { font-size:.7rem; color:#6b7a99; margin-top:1px; }
.cre-line-amount { font-size:.85rem; font-weight:600; color:#1E88E5; white-space:nowrap; }
.btn-pay-one {
    display:inline-flex; align-items:center; justify-content:center;
    width:26px; height:26px; border-radius:6px; border:none; cursor:pointer;
    background:#E8F5E9; color:#2E7D32; font-size:.72rem; transition:all .2s;
}
.btn-pay-one:hover { background:#2E7D32; color:#fff; }
.cre-paid-tag { color:#2E7D32; font-size:.85rem; }

/* Edit inline */
.edit-inline-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.assurance-edit-form { padding:12px; background:#f8fafc; border-radius:8px; margin-top:4px; border:1px solid #E3F2FD; }

.empty-params { text-align:center; padding:2rem; color:#6b7a99; }
.sticky-form { position:sticky; top:80px; }

.alert-drouot { padding:.85rem 1rem; border-radius:10px; display:flex; align-items:center; gap:8px; font-size:.88rem; }
.alert-success { background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; }
.alert-danger  { background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; }
.btn-outline-drouot { display:inline-flex; align-items:center; gap:6px; padding:.6rem 1.2rem; border:1.5px solid #dde3f0; border-radius:9px; background:#fff; color:#1a2340; font-size:.88rem; font-weight:500; text-decoration:none; transition:all .2s; font-family:'DM Sans',sans-serif; cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5; color:#1E88E5; }
.w-100 { width:100%; } .justify-content-center { justify-content:center; }
</style>

<script>
function editAssurance(id) {
    document.getElementById('assView_' + id).style.display = 'none';
    document.getElementById('assEdit_' + id).style.display = 'block';
}
function cancelEditAssurance(id) {
    document.getElementById('assView_' + id).style.display = '';
    document.getElementById('assEdit_' + id).style.display = 'none';
}

function copyPortalLink(id) {
    const input = document.getElementById('portalUrl_' + id);
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = input.nextElementSibling;
        const icon = btn.querySelector('i');
        const original = icon.className;
        icon.className = 'fas fa-check';
        setTimeout(() => { icon.className = original; }, 1500);
    }).catch(() => {
        document.execCommand('copy');
    });
}

function toggleCreances(id) {
    const panel = document.getElementById('crePanel_' + id);
    const toggle = panel.previousElementSibling;
    const isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    toggle.classList.toggle('open', !isOpen);
}

// ── Hors ligne : création d'une compagnie ────────────────
/* offline-first via LocalDB */
</script>




<div id="offlineAssurancesRoot"></div>
<script>
(function () {
    const SERVER = <?= json_encode(array_map(function ($a) {
        return [
            'id' => (string)$a['id'],
            'nom' => $a['nom'] ?? '',
            'code' => $a['code'] ?? '',
            'telephone' => $a['telephone'] ?? '',
            'email' => $a['email'] ?? '',
            'adresse' => $a['adresse'] ?? '',
            'is_active' => (int)($a['is_active'] ?? 1),
            'synced' => 1,
        ];
    }, $assurances ?? []), JSON_UNESCAPED_UNICODE) ?>;

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function isLocal(id) { return String(id).startsWith('local_'); }

    async function refresh() {
        if (typeof LocalDB === 'undefined') return;
        try { if (SERVER.length) await LocalDB.seedStore('assurances', SERVER); } catch(e){}
        const all = (await LocalDB.getAll('assurances')).filter(x => !x._deleted);
        const pending = all.filter(x => isLocal(x.id) || x.synced === 0);
        const root = document.getElementById('offlineAssurancesRoot');
        if (!root) return;

        if (!navigator.onLine) {
            root.innerHTML = `
              <div class="card-drouot mb-3" style="border-color:#FFE082;background:#FFFDE7;">
                <strong style="color:#F57F17;"><i class="fas fa-database me-1"></i> Mode hors ligne</strong>
                — ${all.length} compagnie(s)
              </div>
              <div class="card-drouot">
                ${all.length ? all.map(a => `
                  <div style="padding:10px 0;border-bottom:1px solid #f0f2f8;display:flex;justify-content:space-between;gap:8px;">
                    <div>
                      <strong>${esc(a.nom)}</strong>
                      <div style="font-size:.8rem;color:#6b7a99;">${esc(a.code)} · ${esc(a.telephone)}</div>
                    </div>
                    ${isLocal(a.id)||a.synced===0 ? '<span class="badge-pending">Local</span>' : '<span class="badge-normal">Sync</span>'}
                  </div>`).join('') : '<p class="text-muted mb-0">Aucune assurance locale</p>'}
              </div>`;
        } else if (pending.length) {
            root.innerHTML = `
              <div class="card-drouot mb-3" style="border-color:#FFE082;background:#FFFDE7;">
                <strong style="color:#F57F17;">${pending.length} compagnie(s) créée(s) hors ligne</strong>
                <ul style="margin:8px 0 0;padding-left:18px;font-size:.85rem;">
                  ${pending.map(a => `<li>${esc(a.nom)}</li>`).join('')}
                </ul>
              </div>`;
        } else root.innerHTML = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formCreateAssurance');
        if (form) {
            form.addEventListener('submit', async function (e) {
                if (navigator.onLine) return;
                e.preventDefault();
                e.stopImmediatePropagation();
                const fd = new FormData(form);
                const fields = []; fd.forEach((v,k) => fields.push([k,v]));
                const payload = Object.fromEntries(Array.from(fd.entries()).filter(([,v]) => typeof v === 'string'));
                if (!payload.nom) { alert('Nom obligatoire'); return; }
                await LocalDB.create('assurances', { ...payload, is_active: 1 }, form.action, {
                    type: 'assurance', fields, label: 'Nouvelle compagnie — ' + payload.nom
                });
                LocalDB.toast('Assurance enregistrée localement', 'success');
                form.reset();
                refresh();
            }, true);
        }
        refresh();
    });
    window.addEventListener('localdb:assurances-updated', refresh);
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

<?php include dirname(__DIR__) . '/../../views/layouts/footer.php'; ?>