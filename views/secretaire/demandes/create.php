<?php
/**
 * Formulaire création demande d'examens
 * views/secretaire/demandes/create.php
 */
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            <i class="fas fa-flask text-primary me-2"></i>
            Nouvelle demande d'examens
        </h1>
        <p class="page-sub">Sélectionnez le patient et les examens</p>
    </div>
    <a href="<?= BASE_URL ?>secretaire/demandes" class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert-drouot alert-danger mb-4">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>secretaire/demandes/store" id="formDemande" data-offline-store="demandes">
<div class="row g-4">

    <!-- ══════════════════════════════════════
         COLONNE GAUCHE
    ══════════════════════════════════════ -->
    <div class="col-xl-8">

        <!-- Patient -->
        <div class="card-drouot mb-4">
            <h6 class="form-section-title">
                <i class="fas fa-user-injured"></i> Patient
            </h6>
            <?php if ($patientPreload): ?>
            <div class="patient-preloaded">
                <div class="patient-preloaded-avatar">
                    <?= strtoupper(substr($patientPreload['prenom'],0,1).substr($patientPreload['nom'],0,1)) ?>
                </div>
                <div>
                    <div class="patient-preloaded-name">
                        <?= htmlspecialchars(strtoupper($patientPreload['nom']).' '.$patientPreload['prenom']) ?>
                    </div>
                    <div class="patient-preloaded-phone">
                        <i class="fas fa-phone"></i> <?= htmlspecialchars($patientPreload['telephone']) ?>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>secretaire/demandes/create" class="change-patient-btn ms-auto">
                    <i class="fas fa-rotate"></i> Changer
                </a>
            </div>
            <input type="hidden" name="patient_id" value="<?= $patientPreload['id'] ?>">
            <script>
            // Le patient est déjà pré-chargé côté serveur : on synchronise
            // directement la section Assurance dès que le DOM est prêt,
            // sans attendre une sélection manuelle dans l'autocomplete.
            document.addEventListener('DOMContentLoaded', function () {
                initPatientAssurance(
                    <?= $patientPreload['assurance_id'] ? (int)$patientPreload['assurance_id'] : 'null' ?>,
                    <?= json_encode($patientPreload['assurance_nom']) ?>,
                    <?= json_encode($patientPreload['assurance_taux_defaut']) ?>,
                    <?= json_encode($patientPreload['num_assurance']) ?>
                );
            });
            </script>
            <?php else: ?>
            <div class="patient-search-wrap">
                <div class="search-input-wrap mb-2">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="patientSearch" class="search-input"
                           placeholder="Taper le nom, prénom ou téléphone..." autocomplete="off">
                </div>
                <div id="patientResults" class="patient-results"></div>
                <input type="hidden" name="patient_id" id="patientIdInput">
                <div id="patientSelected" class="patient-preloaded" style="display:none;margin-top:10px;">
                    <div class="patient-preloaded-avatar" id="selAvatar"></div>
                    <div>
                        <div class="patient-preloaded-name" id="selName"></div>
                        <div class="patient-preloaded-phone" id="selPhone"></div>
                    </div>
                    <button type="button" onclick="clearPatient()" class="change-patient-btn ms-auto">
                        <i class="fas fa-xmark"></i> Changer
                    </button>
                </div>
            </div>
            <script>
            const allPatients = <?= json_encode(array_map(fn($p) => [
                'id'            => $p['id'],
                'nom'           => strtoupper($p['nom']).' '.$p['prenom'],
                'initials'      => strtoupper(substr($p['prenom'],0,1).substr($p['nom'],0,1)),
                'phone'         => $p['telephone'],
                'assuranceId'   => $p['assurance_id'],
                'assuranceNom'  => $p['assurance_nom'],
                'assuranceTaux' => $p['assurance_taux_defaut'],
                'numAssurance'  => $p['num_assurance'],
            ], $patients)) ?>;
            </script>
            <?php endif; ?>
        </div>

        <!-- Médecin -->
        <div class="card-drouot mb-4">
            <h6 class="form-section-title">
                <i class="fas fa-user-doctor"></i> Médecin prescripteur
                <span class="optional-tag">optionnel</span>
            </h6>
            <div class="medecin-search-wrap">
                <div class="search-input-wrap mb-2">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="medecinSearch" class="search-input"
                           placeholder="Taper le nom du médecin..." autocomplete="off">
                </div>
                <div id="medecinResults" class="patient-results"></div>
                <input type="hidden" name="medecin_id" id="medecinIdInput">
                <div id="medecinSelected" class="patient-preloaded medecin-preloaded" style="display:none;margin-top:10px;">
                    <div class="patient-preloaded-avatar medecin-avatar" id="medSelAvatar"></div>
                    <div>
                        <div class="patient-preloaded-name" id="medSelName"></div>
                        <div class="patient-preloaded-phone" id="medSelSpecialite"></div>
                    </div>
                    <button type="button" onclick="clearMedecin()" class="change-patient-btn ms-auto">
                        <i class="fas fa-xmark"></i> Retirer
                    </button>
                </div>
            </div>
            <script>
            const allMedecins = <?= json_encode(array_map(fn($m) => [
                'id'         => $m['id'],
                'nom'        => 'Dr '.strtoupper($m['nom']).' '.$m['prenom'],
                'initials'   => strtoupper(substr($m['prenom'],0,1).substr($m['nom'],0,1)),
                'specialite' => $m['specialite'] ?? '—',
            ], $medecins)) ?>;
            </script>
        </div>

        <!-- Assurance -->
        <div class="card-drouot mb-4">
            <h6 class="form-section-title">
                <i class="fas fa-shield-heart"></i> Assurance
                <span class="optional-tag">optionnel</span>
            </h6>
            <div class="assurance-search-wrap">
                <div class="search-input-wrap mb-2" id="assuranceSearchWrap">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="assuranceSearch" class="search-input"
                           placeholder="Taper le nom de la compagnie..." autocomplete="off">
                </div>
                <div id="assuranceResults" class="patient-results"></div>
                <div id="noAssuranceMsg" class="no-assurance-note" style="display:none;">
                    <i class="fas fa-circle-info"></i> Ce patient n'a pas d'assurance enregistrée.
                </div>
                <input type="hidden" name="assurance_id" id="assuranceIdInput">
                <div id="assuranceSelected" class="patient-preloaded assurance-preloaded" style="display:none;margin-top:10px;">
                    <div class="patient-preloaded-avatar assurance-avatar" id="assSelAvatar">
                        <i class="fas fa-shield-heart"></i>
                    </div>
                    <div>
                        <div class="patient-preloaded-name" id="assSelName"></div>
                        <div class="patient-preloaded-phone" id="assSelTaux"></div>
                    </div>
                    <button type="button" onclick="clearAssurance()" class="change-patient-btn ms-auto">
                        <i class="fas fa-xmark"></i> Retirer
                    </button>
                </div>
            </div>
            <div id="assuranceDetails" style="display:none;margin-top:14px;">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label-drouot">Numéro de police / carte d'assuré</label>
                        <input type="text" name="numero_police" id="numeroPoliceInput"
                               class="form-input-drouot" placeholder="Scanner ou saisir le numéro..." autocomplete="off">
                        <input type="hidden" name="verif_fichier_statut" id="verifFichierStatutInput">
                    </div>
                    <div class="col-12" id="verifStatusZone" style="display:none;"></div>
                    <div class="col-12" id="manualFallbackZone" style="display:none;">
                        <label class="checkbox-label">
                            <input type="checkbox" id="hasCodeCheckbox" onchange="toggleCodeAccord()">
                            Le patient a déjà un code d'accord de l'assureur
                        </label>
                    </div>
                    <div class="col-12" id="codeAccordWrap" style="display:none;">
                        <label class="form-label-drouot">Code d'accord</label>
                        <input type="text" name="code_accord" id="codeAccordInput"
                               class="form-input-drouot" placeholder="Code donné par l'assureur" oninput="recalculerRecap()">
                        <div class="info-note"><i class="fas fa-circle-check"></i> Prise en charge validée immédiatement.</div>
                    </div>
                    <div class="col-12" id="enAttenteNote" style="display:none;">
                        <div class="info-note warn"><i class="fas fa-clock"></i> Sans code, le patient règle le total en attendant la validation assureur.</div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        const tauxParExamen  = <?= json_encode($tauxParExamen, JSON_FORCE_OBJECT) ?>;
        const allAssurances  = <?= json_encode(array_map(fn($a) => [
            'id'   => $a['id'],
            'nom'  => $a['nom'],
            'taux' => $a['taux_defaut'],
        ], $assurances)) ?>;
        </script>

        <!-- ══════════════════════════════════════
             SÉLECTION EXAMENS — VERSION ERGONOMIQUE
             Barre de recherche globale + catégories
             repliables + compteurs + sélection rapide
        ══════════════════════════════════════ -->
        <div class="card-drouot">
            <h6 class="form-section-title">
                <i class="fas fa-microscope"></i>
                Examens demandés
                <span class="examen-count-badge" id="examenCount">0 sélectionné(s)</span>
            </h6>

            <!-- Barre de recherche + stats -->
            <div class="examen-toolbar">
                <div class="search-input-wrap" style="flex:1;">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" id="examenSearch" class="search-input"
                           placeholder="Rechercher un examen par nom ou code...">
                </div>
                <div class="examen-toolbar-right">
                    <button type="button" class="cat-toggle-all" onclick="toggleAllCategories(true)"
                            title="Tout déplier">
                        <i class="fas fa-chevron-down"></i> Tout déplier
                    </button>
                    <button type="button" class="cat-toggle-all" onclick="toggleAllCategories(false)"
                            title="Tout replier">
                        <i class="fas fa-chevron-up"></i> Tout replier
                    </button>
                </div>
            </div>

            <!-- Zone de résultats de recherche (masquée par défaut) -->
            <div id="searchResults" class="search-results-zone" style="display:none;">
                <div class="search-results-header">
                    <span id="searchResultsCount"></span>
                    <button type="button" class="search-close-btn" onclick="clearSearch()">
                        <i class="fas fa-xmark"></i> Effacer
                    </button>
                </div>
                <div id="searchResultsList" class="examen-grid"></div>
            </div>

            <!-- Catégories repliables -->
            <div id="categoriesZone">
                <?php
                $totalExamens = array_sum(array_map('count', $examensGroupes));
                $catIndex = 0;
                foreach ($examensGroupes as $categorie => $examens):
                    $catId = 'cat_' . $catIndex++;
                    $openByDefault = count($examensGroupes) <= 5; // ouvre tout si peu de catégories
                ?>
                <div class="cat-group" data-cat="<?= htmlspecialchars(strtolower($categorie)) ?>">

                    <!-- En-tête catégorie cliquable -->
                    <div class="cat-header" onclick="toggleCat('<?= $catId ?>')"
                         id="<?= $catId ?>_header">
                        <div class="cat-header-left">
                            <i class="fas fa-chevron-<?= $openByDefault ? 'down' : 'right' ?> cat-chevron"
                               id="<?= $catId ?>_icon"></i>
                            <span class="cat-name"><?= htmlspecialchars($categorie) ?></span>
                            <span class="cat-total-badge"><?= count($examens) ?></span>
                        </div>
                        <div class="cat-header-right">
                            <span class="cat-selected-badge" id="<?= $catId ?>_sel"
                                  style="display:none;">0 sélectionné(s)</span>
                            <button type="button" class="cat-action-btn"
                                    onclick="event.stopPropagation(); selectAllInCat('<?= $catId ?>')"
                                    title="Tout sélectionner dans cette catégorie">
                                <i class="fas fa-check-double"></i>
                            </button>
                            <button type="button" class="cat-action-btn cat-action-clear"
                                    onclick="event.stopPropagation(); clearAllInCat('<?= $catId ?>')"
                                    title="Tout désélectionner">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Corps de la catégorie (repliable) -->
                    <div class="cat-body" id="<?= $catId ?>_body"
                         style="<?= $openByDefault ? '' : 'display:none;' ?>">
                        <div class="examen-grid">
                            <?php foreach ($examens as $ex): ?>
                            <label class="examen-card"
                                   data-nom="<?= strtolower(htmlspecialchars($ex['nom'])) ?>"
                                   data-code="<?= strtolower(htmlspecialchars($ex['code'] ?? '')) ?>"
                                   data-cat="<?= $catId ?>"
                                   data-prix="<?= $ex['prix'] ?>">
                                <input type="checkbox"
                                       name="examen_ids[]"
                                       value="<?= $ex['id'] ?>"
                                       class="examen-checkbox"
                                       data-nom="<?= htmlspecialchars($ex['nom']) ?>"
                                       data-prix="<?= $ex['prix'] ?>"
                                       data-cat="<?= $catId ?>"
                                       onchange="updateSelection(this)">
                                <div class="examen-card-content">
                                    <div class="examen-nom"><?= htmlspecialchars($ex['nom']) ?></div>
                                    <div class="examen-code"><?= htmlspecialchars($ex['code'] ?? '') ?></div>
                                    <div class="examen-prix"><?= number_format((float)$ex['prix'], 0, ',', ' ') ?> FCFA</div>
                                </div>
                                <div class="examen-check-icon"><i class="fas fa-check"></i></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Message aucun résultat de recherche -->
            <div id="noSearchResult" class="no-result-msg" style="display:none;">
                <i class="fas fa-magnifying-glass"></i>
                <p>Aucun examen ne correspond à votre recherche.</p>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════
         COLONNE DROITE — Récapitulatif sticky
    ══════════════════════════════════════ -->
    <div class="col-xl-4">
        <div class="card-drouot recap-sticky">
            <h6 class="form-section-title mb-3">
                <i class="fas fa-receipt"></i> Récapitulatif
            </h6>
            <div id="recapList">
                <div class="recap-empty">
                    <i class="fas fa-flask"></i>
                    <p>Aucun examen sélectionné</p>
                </div>
            </div>
            <div class="recap-divider"></div>
            <div class="recap-total">
                <span>Total</span>
                <span id="recapTotal">0 FCFA</span>
            </div>
            <div class="recap-divider mb-3"></div>
            <button type="submit" class="btn-primary-drouot w-100 justify-content-center"
                    id="submitBtn" style="padding:.85rem;">
                <i class="fas fa-paper-plane"></i> Créer la demande
            </button>
            <p class="recap-note">
                <i class="fas fa-info-circle"></i>
                Une facture proforma sera générée automatiquement.
            </p>
        </div>
    </div>

</div>
</form>

<!-- ══════════════════════════════════════
     STYLES
══════════════════════════════════════ -->
<style>
/* ── Général ── */
.page-title { font-size:1.3rem;font-weight:600;color:#1a2340;margin:0; }
.page-sub   { font-size:.85rem;color:#6b7a99;margin:2px 0 0; }
.form-section-title {
    font-size:.88rem;font-weight:600;color:#1a2340;
    margin-bottom:1rem;display:flex;align-items:center;gap:8px;
    padding-bottom:8px;border-bottom:1.5px solid #f0f2f8;
}
.form-section-title i { color:#1E88E5; }
.optional-tag { margin-left:auto;font-size:.68rem;font-weight:500;
    color:#90CAF9;background:#F4F6F9;padding:2px 8px;border-radius:10px; }

/* ── Patient / médecin / assurance ── */
.patient-preloaded { display:flex;align-items:center;gap:12px;
    background:#f0f7ff;border:1.5px solid #BBDEFB;border-radius:12px;padding:12px 14px; }
.patient-preloaded-avatar { width:42px;height:42px;border-radius:50%;
    background:#1E88E5;color:#fff;display:flex;align-items:center;
    justify-content:center;font-size:.9rem;font-weight:600;flex-shrink:0; }
.patient-preloaded-name { font-size:.9rem;font-weight:600;color:#1a2340; }
.patient-preloaded-phone { font-size:.78rem;color:#6b7a99;display:flex;align-items:center;gap:5px; }
.change-patient-btn { display:inline-flex;align-items:center;gap:5px;
    padding:5px 10px;border-radius:8px;background:#fff;border:1px solid #dde3f0;
    color:#6b7a99;font-size:.78rem;text-decoration:none;cursor:pointer;transition:all .2s; }
.change-patient-btn:hover { border-color:#1E88E5;color:#1E88E5; }
.medecin-preloaded { background:#FFF8E1;border-color:#FFE082; }
.medecin-avatar { background:#F9A825 !important; }
.assurance-preloaded { background:#F3E5F5;border-color:#E1BEE7; }
.assurance-avatar { background:#8E24AA !important; }
.search-input-wrap { position:relative; }
.search-input-wrap i { position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:#b0b8cc; }
.search-input { width:100%;padding:.7rem .9rem .7rem 2.4rem;
    border:1.5px solid #dde3f0;border-radius:10px;
    font-family:'DM Sans',sans-serif;font-size:.9rem;
    color:#1a2340;background:#fafbfd;outline:none;transition:border .2s; }
.search-input:focus { border-color:#1E88E5; }
.patient-results { border:1px solid #dde3f0;border-radius:10px;
    overflow:hidden;display:none;background:#fff;
    box-shadow:0 4px 16px rgba(0,0,0,.08);max-height:200px;overflow-y:auto; }
.patient-result-item { display:flex;align-items:center;gap:10px;
    padding:10px 14px;cursor:pointer;border-bottom:1px solid #f0f2f8;
    transition:background .15s;font-size:.85rem; }
.patient-result-item:last-child { border-bottom:none; }
.patient-result-item:hover { background:#f0f7ff; }
.pr-avatar { width:32px;height:32px;border-radius:50%;
    background:#E3F2FD;color:#1565C0;
    display:flex;align-items:center;justify-content:center;
    font-size:.75rem;font-weight:600;flex-shrink:0; }
.pr-avatar.med { background:#FFF8E1;color:#F57F17; }
.pr-nom { font-weight:500;color:#1a2340; }
.pr-tel { font-size:.75rem;color:#6b7a99; }
.checkbox-label { display:flex;align-items:center;gap:8px;font-size:.85rem;color:#1a2340;cursor:pointer;padding:8px 0; }
.checkbox-label input[type="checkbox"] { width:16px;height:16px;cursor:pointer;accent-color:#1E88E5; }
.info-note { display:flex;align-items:flex-start;gap:8px;font-size:.78rem;
    color:#2E7D32;background:#E8F5E9;padding:8px 12px;border-radius:8px;margin-top:6px; }
.info-note.warn { color:#F57F17;background:#FFF8E1; }
.verif-status { display:flex;align-items:center;gap:8px;font-size:.8rem;padding:9px 12px;border-radius:8px; }
.verif-loading { background:#F4F6F9;color:#6b7a99; }
.verif-ok      { background:#E8F5E9;color:#2E7D32; }
.verif-blocked { background:#FFEBEE;color:#C62828; }
.verif-unknown { background:#FFF8E1;color:#F57F17; }
.no-assurance-note {
    display:flex; align-items:center; gap:8px;
    font-size:.8rem; color:#6b7a99; background:#F4F6F9;
    padding:9px 12px; border-radius:8px;
}

/* ══════════════════════════════════════
   TOOLBAR EXAMENS
══════════════════════════════════════ */
.examen-toolbar {
    display:flex;align-items:center;gap:10px;
    margin-bottom:16px;flex-wrap:wrap;
}
.examen-toolbar-right {
    display:flex;gap:6px;flex-shrink:0;
}
.cat-toggle-all {
    display:inline-flex;align-items:center;gap:5px;
    padding:6px 12px;border:1px solid #dde3f0;
    border-radius:8px;background:#F4F6F9;
    color:#6b7a99;font-size:.75rem;cursor:pointer;
    transition:all .15s;white-space:nowrap;
}
.cat-toggle-all:hover { background:#E3F2FD;color:#1565C0;border-color:#1E88E5; }

/* Zone résultats recherche */
.search-results-zone {
    background:#FFFDE7;border:1.5px solid #FFF176;
    border-radius:10px;padding:12px;margin-bottom:16px;
}
.search-results-header {
    display:flex;align-items:center;justify-content:space-between;
    margin-bottom:10px;font-size:.78rem;color:#6b7a99;
}
.search-close-btn {
    display:inline-flex;align-items:center;gap:4px;
    padding:3px 10px;border-radius:6px;
    background:#fff;border:1px solid #dde3f0;
    color:#6b7a99;font-size:.72rem;cursor:pointer;transition:all .15s;
}
.search-close-btn:hover { border-color:#C62828;color:#C62828; }

/* ══════════════════════════════════════
   CATÉGORIES REPLIABLES
══════════════════════════════════════ */
.cat-group {
    border:1px solid #f0f2f8;border-radius:10px;
    margin-bottom:8px;overflow:hidden;
    transition:border-color .2s;
}
.cat-group:has(.examen-checkbox:checked) {
    border-color:#BBDEFB;
}

.cat-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:10px 14px;cursor:pointer;
    background:#FAFBFD;user-select:none;
    transition:background .15s;
}
.cat-header:hover { background:#F0F7FF; }

.cat-header-left {
    display:flex;align-items:center;gap:8px;flex:1;min-width:0;
}
.cat-chevron {
    font-size:.72rem;color:#b0b8cc;
    transition:transform .2s;flex-shrink:0;
    width:12px;
}
.cat-name {
    font-size:.83rem;font-weight:600;color:#1a2340;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.cat-total-badge {
    background:#F4F6F9;color:#6b7a99;
    padding:1px 8px;border-radius:20px;
    font-size:.68rem;font-weight:500;flex-shrink:0;
}
.cat-header-right {
    display:flex;align-items:center;gap:6px;flex-shrink:0;
    margin-left:10px;
}
.cat-selected-badge {
    background:#E3F2FD;color:#1565C0;
    padding:2px 10px;border-radius:20px;
    font-size:.68rem;font-weight:600;
}
.cat-action-btn {
    width:26px;height:26px;border-radius:6px;
    border:1px solid #dde3f0;background:#fff;
    color:#6b7a99;font-size:.7rem;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    transition:all .15s;
}
.cat-action-btn:hover { background:#E3F2FD;color:#1565C0;border-color:#1E88E5; }
.cat-action-clear:hover { background:#FFEBEE;color:#C62828;border-color:#FFCDD2; }

.cat-body { padding:10px 12px 12px; }

/* ══════════════════════════════════════
   GRILLE EXAMENS
══════════════════════════════════════ */
.examen-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(170px, 1fr));
    gap:7px;
}
.examen-card {
    position:relative;border:1.5px solid #dde3f0;
    border-radius:9px;padding:9px 11px;
    cursor:pointer;transition:all .15s;
    background:#fff;display:flex;align-items:flex-start;gap:7px;
}
.examen-card:hover { border-color:#90CAF9;background:#f0f7ff; }
.examen-card.selected { border-color:#1E88E5;background:#E3F2FD; }
.examen-checkbox { display:none; }
.examen-card-content { flex:1;min-width:0; }
.examen-nom { font-size:.8rem;font-weight:500;color:#1a2340;line-height:1.3; }
.examen-code { font-size:.68rem;color:#90CAF9;font-family:monospace;margin-top:2px; }
.examen-prix { font-size:.78rem;font-weight:600;color:#1E88E5;margin-top:4px; }
.examen-taux-badge {
    font-size:.65rem; color:#8E24AA; background:#F3E5F5;
    padding:1px 6px; border-radius:8px; margin-top:4px; display:inline-block;
}
.examen-taux-badge.assure-specifique {
    color:#B8863D; background:#FBF2E2;
}
.examen-check-icon {
    width:18px;height:18px;border-radius:50%;
    background:#dde3f0;color:#fff;
    display:flex;align-items:center;justify-content:center;
    font-size:.6rem;flex-shrink:0;transition:background .15s;margin-top:1px;
}
.examen-card.selected .examen-check-icon { background:#1E88E5; }
.examen-count-badge {
    margin-left:auto;background:#E3F2FD;color:#1565C0;
    padding:2px 10px;border-radius:20px;font-size:.72rem;font-weight:600;
}
.no-result-msg {
    text-align:center;padding:2rem 0;color:#b0b8cc;
}
.no-result-msg i { font-size:2rem;display:block;margin-bottom:8px; }
.no-result-msg p { font-size:.82rem; }

/* ══════════════════════════════════════
   RÉCAPITULATIF
══════════════════════════════════════ */
.recap-sticky { position:sticky;top:80px; }
.recap-empty { text-align:center;padding:1.5rem 0;color:#b0b8cc; }
.recap-empty i { font-size:2rem;display:block;margin-bottom:6px; }
.recap-empty p { font-size:.82rem;margin:0; }
.recap-item { display:flex;align-items:center;justify-content:space-between;
    padding:6px 0;font-size:.82rem; }
.recap-item-nom { color:#1a2340;font-weight:500;flex:1;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-right:8px; }
.recap-item-prix { color:#1E88E5;font-weight:600;white-space:nowrap; }
.recap-item-del { width:20px;height:20px;border-radius:5px;
    background:#FFEBEE;color:#C62828;border:none;cursor:pointer;
    font-size:.7rem;display:flex;align-items:center;justify-content:center;
    margin-left:5px;transition:background .15s;flex-shrink:0; }
.recap-item-del:hover { background:#C62828;color:#fff; }
.recap-divider { border-top:1.5px solid #f0f2f8;margin:10px 0; }
.recap-total { display:flex;justify-content:space-between;align-items:center;
    font-size:1rem;font-weight:700;color:#1a2340;padding:4px 0; }
#recapTotal { color:#1E88E5; }
.recap-note { font-size:.72rem;color:#6b7a99;text-align:center;
    margin:8px 0 0;display:flex;align-items:center;justify-content:center;gap:5px; }
.recap-taux-tag { font-size:.65rem;color:#2E7D32;background:#E8F5E9;
    padding:1px 5px;border-radius:8px;margin-left:4px; }
.recap-taux-tag.assure-specifique { color:#B8863D;background:#FBF2E2; }
.recap-prix-original { display:block;font-size:.65rem;color:#b0b8cc;text-decoration:line-through; }
.recap-assurance-box { background:#F3E5F5;border-radius:10px;padding:10px 12px;margin:8px 0;font-size:.8rem; }
.recap-assurance-row { display:flex;justify-content:space-between;align-items:center;padding:3px 0;color:#1a2340; }
.recap-assurance-row.assurance-color { color:#8E24AA;font-weight:600; }

/* Alertes */
.alert-drouot { padding:.85rem 1rem;border-radius:10px;display:flex;align-items:center;gap:8px;font-size:.88rem; }
.alert-danger  { background:#FFEBEE;color:#C62828;border:1px solid #FFCDD2; }
.btn-outline-drouot { display:inline-flex;align-items:center;gap:6px;
    padding:.6rem 1.2rem;border:1.5px solid #dde3f0;border-radius:9px;
    background:#fff;color:#1a2340;font-size:.88rem;font-weight:500;
    text-decoration:none;transition:all .2s;cursor:pointer; }
.btn-outline-drouot:hover { border-color:#1E88E5;color:#1E88E5; }
.w-100 { width:100%; } .justify-content-center { justify-content:center; }
.ms-auto { margin-left:auto; }
</style>

<!-- ══════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════ -->
<script>
// ════════════════════════════════════════
// État global
// ════════════════════════════════════════
let selectedExamens  = {};
let assuranceState   = {
    id:null, nom:null, tauxDefaut:0, statut:null, codeManuel:false,
    assureId:null,   // id de l'assuré résolu depuis le numéro de police
    tauxAssure:{}    // taux propres à CET assuré (priorité maximale), {examenId: taux}
};

// ════════════════════════════════════════
// Catégories repliables
// ════════════════════════════════════════
function toggleCat(catId) {
    const body  = document.getElementById(catId + '_body');
    const icon  = document.getElementById(catId + '_icon');
    const open  = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    icon.className = 'fas fa-chevron-' + (open ? 'right' : 'down') + ' cat-chevron';
}

function toggleAllCategories(open) {
    document.querySelectorAll('.cat-group').forEach(group => {
        const body = group.querySelector('[id$="_body"]');
        const icon = group.querySelector('[id$="_icon"]');
        if (body && icon) {
            body.style.display = open ? '' : 'none';
            icon.className = 'fas fa-chevron-' + (open ? 'down' : 'right') + ' cat-chevron';
        }
    });
}

function selectAllInCat(catId) {
    document.querySelectorAll(`.examen-checkbox[data-cat="${catId}"]`).forEach(cb => {
        if (!cb.checked) { cb.checked = true; updateSelection(cb); }
    });
}

function clearAllInCat(catId) {
    document.querySelectorAll(`.examen-checkbox[data-cat="${catId}"]`).forEach(cb => {
        if (cb.checked) { cb.checked = false; updateSelection(cb); }
    });
}

function updateCatBadge(catId) {
    const count = document.querySelectorAll(
        `.examen-checkbox[data-cat="${catId}"]:checked`
    ).length;
    const badge = document.getElementById(catId + '_sel');
    if (badge) {
        badge.style.display = count > 0 ? 'inline-block' : 'none';
        badge.textContent   = count + ' sélectionné(s)';
    }
}

// ════════════════════════════════════════
// Sélection examens
// ════════════════════════════════════════
function updateSelection(checkbox) {
    const card = checkbox.closest('.examen-card');
    const id   = checkbox.value;
    const nom  = checkbox.dataset.nom;
    const prix = parseFloat(checkbox.dataset.prix);
    const cat  = checkbox.dataset.cat;

    if (checkbox.checked) {
        card.classList.add('selected');
        selectedExamens[id] = { nom, prix, cat };
    } else {
        card.classList.remove('selected');
        delete selectedExamens[id];
    }

    // Synchroniser le clone dans les résultats de recherche
    const twin = document.querySelector(`#searchResultsList input.examen-checkbox[value="${id}"]`);
    if (twin && twin !== checkbox) twin.checked = checkbox.checked;
    const main = document.querySelector(`#categoriesZone input.examen-checkbox[value="${id}"]`);
    if (main && main !== checkbox) {
        main.checked = checkbox.checked;
        main.closest('.examen-card').classList.toggle('selected', checkbox.checked);
    }

    if (cat) updateCatBadge(cat);
    renderRecap();
}

// ════════════════════════════════════════
// Recherche globale avec résultats inline
// ════════════════════════════════════════
document.getElementById('examenSearch').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    const searchZone = document.getElementById('searchResults');
    const catZone    = document.getElementById('categoriesZone');
    const noResult   = document.getElementById('noSearchResult');

    if (q.length < 2) {
        searchZone.style.display = 'none';
        catZone.style.display    = '';
        noResult.style.display   = 'none';
        return;
    }

    // Collecter tous les examens correspondants
    const allCards = [...document.querySelectorAll('#categoriesZone .examen-card')];
    const matched  = allCards.filter(card => {
        return card.dataset.nom.includes(q) || card.dataset.code.includes(q);
    });

    if (matched.length === 0) {
        searchZone.style.display = 'none';
        catZone.style.display    = 'none';
        noResult.style.display   = '';
        return;
    }

    noResult.style.display   = 'none';
    catZone.style.display    = 'none';
    searchZone.style.display = '';

    const countEl = document.getElementById('searchResultsCount');
    countEl.textContent = matched.length + ' examen(s) trouvé(s)';

    // Cloner les cartes pour les afficher dans la zone de résultat
    const list = document.getElementById('searchResultsList');
    list.innerHTML = '';
    matched.forEach(card => {
        const clone = card.cloneNode(true);
        const origCb = card.querySelector('.examen-checkbox');
        const cloneCb = clone.querySelector('.examen-checkbox');
        cloneCb.checked = origCb.checked;
        if (cloneCb.checked) clone.classList.add('selected');
        cloneCb.onchange = function() { updateSelection(this); };
        list.appendChild(clone);
    });
});

function clearSearch() {
    const input = document.getElementById('examenSearch');
    input.value = '';
    input.dispatchEvent(new Event('input'));
    input.focus();
}

// ════════════════════════════════════════
// Récapitulatif
// ════════════════════════════════════════
function couvertureValidee() {
    if (!assuranceState.id) return false;
    if (assuranceState.statut === 'en_regle') return true;
    if (assuranceState.codeManuel) return true;
    return false;
}

// Priorité : 1) taux propre à l'assuré  2) taux compagnie/examen  3) taux par défaut
function tauxPourExamen(examenId) {
    if (assuranceState.tauxAssure && assuranceState.tauxAssure[examenId] !== undefined) {
        return assuranceState.tauxAssure[examenId];
    }
    const spec = tauxParExamen[assuranceState.id] || {};
    if (spec[examenId] !== undefined) return spec[examenId];
    return assuranceState.tauxDefaut;
}

// Indique si le taux appliqué à cet examen vient d'une surcharge
// propre à l'assuré (pour distinguer visuellement le badge/tag).
function tauxEstSpecifiqueAssure(examenId) {
    return !!(assuranceState.tauxAssure && assuranceState.tauxAssure[examenId] !== undefined);
}

function renderRecap() {
    const list  = document.getElementById('recapList');
    const count = Object.keys(selectedExamens).length;
    document.getElementById('examenCount').textContent = count + ' sélectionné(s)';

    if (count === 0) {
        list.innerHTML = `<div class="recap-empty">
            <i class="fas fa-flask"></i><p>Aucun examen sélectionné</p></div>`;
        document.getElementById('recapTotal').textContent = '0 FCFA';
        hideAssuranceLine();
        return;
    }

    const couverte = couvertureValidee();
    let html = '', totalBrut = 0, totalPatient = 0, totalAssurance = 0;

    for (const [id, ex] of Object.entries(selectedExamens)) {
        totalBrut += ex.prix;
        if (couverte) {
            const taux = tauxPourExamen(id);
            const specifique = tauxEstSpecifiqueAssure(id);
            const partAss = Math.round(ex.prix * taux / 100);
            const partPat = ex.prix - partAss;
            totalPatient   += partPat;
            totalAssurance += partAss;
            html += `<div class="recap-item">
                <span class="recap-item-nom" title="${ex.nom}">
                    ${ex.nom}<span class="recap-taux-tag ${specifique ? 'assure-specifique' : ''}">-${taux}%</span>
                </span>
                <span class="recap-item-prix">
                    ${partPat.toLocaleString('fr-FR')} F
                    <span class="recap-prix-original">${ex.prix.toLocaleString('fr-FR')} F</span>
                </span>
                <button type="button" class="recap-item-del" onclick="removeExamen('${id}')">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>`;
        } else {
            totalPatient += ex.prix;
            html += `<div class="recap-item">
                <span class="recap-item-nom" title="${ex.nom}">${ex.nom}</span>
                <span class="recap-item-prix">${ex.prix.toLocaleString('fr-FR')} F</span>
                <button type="button" class="recap-item-del" onclick="removeExamen('${id}')">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>`;
        }
    }
    list.innerHTML = html;

    if (couverte && totalAssurance > 0) {
        showAssuranceLine(totalBrut, totalAssurance, totalPatient);
    } else {
        hideAssuranceLine();
    }
    document.getElementById('recapTotal').textContent =
        totalPatient.toLocaleString('fr-FR') + ' FCFA';
}

function showAssuranceLine(brut, ass, pat) {
    let z = document.getElementById('recapAssuranceLine');
    if (!z) {
        z = document.createElement('div'); z.id = 'recapAssuranceLine';
        document.querySelector('.recap-divider').insertAdjacentElement('beforebegin', z);
    }
    z.innerHTML = `<div class="recap-assurance-box">
        <div class="recap-assurance-row">
            <span>Total examens</span><span>${brut.toLocaleString('fr-FR')} F</span>
        </div>
        <div class="recap-assurance-row assurance-color">
            <span><i class="fas fa-shield-heart"></i> Prise en charge assurance</span>
            <span>- ${ass.toLocaleString('fr-FR')} F</span>
        </div>
    </div>`;
}

function hideAssuranceLine() {
    const z = document.getElementById('recapAssuranceLine');
    if (z) z.innerHTML = '';
}

function removeExamen(id) {
    // Décocher dans la grille principale
    const cb = document.querySelector(`#categoriesZone input.examen-checkbox[value="${id}"]`);
    if (cb) { cb.checked = false; cb.closest('.examen-card').classList.remove('selected'); }
    // Décocher dans les résultats de recherche si visibles
    const cb2 = document.querySelector(`#searchResultsList input.examen-checkbox[value="${id}"]`);
    if (cb2) { cb2.checked = false; cb2.closest('.examen-card').classList.remove('selected'); }

    const cat = selectedExamens[id]?.cat;
    delete selectedExamens[id];
    if (cat) updateCatBadge(cat);
    renderRecap();
}

// ════════════════════════════════════════
// Assurance liée au patient (auto)
// ════════════════════════════════════════
function initPatientAssurance(assuranceId, assuranceNom, assuranceTaux, numAssurance) {
    if (!assuranceId) {
        document.getElementById('assuranceSearchWrap').style.display = 'none';
        document.getElementById('assuranceResults').style.display = 'none';
        document.getElementById('noAssuranceMsg').style.display = 'block';
        return;
    }
    document.getElementById('assuranceSearchWrap').style.display = 'none';
    document.getElementById('noAssuranceMsg').style.display = 'none';

    assuranceIdInput.value = assuranceId;
    assuranceState = {
        id: assuranceId, nom: assuranceNom,
        tauxDefaut: parseFloat(assuranceTaux) || 0,
        statut: null, codeManuel: false,
        assureId: null, tauxAssure: {}
    };

    document.getElementById('assSelName').textContent = assuranceNom;
    document.getElementById('assSelTaux').innerHTML =
        `<i class="fas fa-percent"></i> Taux défaut: ${assuranceTaux}%`;
    document.getElementById('assuranceSelected').style.display = 'flex';
    document.getElementById('assuranceDetails').style.display = 'block';

    const policeInput = document.getElementById('numeroPoliceInput');
    policeInput.value = numAssurance || '';
    if (numAssurance) verifierAssure(numAssurance);
    renderRecap();
}

function annoterExamensAvecTaux() {
    if (!couvertureValidee()) { retirerBadgesTaux(); return; }
    document.querySelectorAll('.examen-card').forEach(card => {
        const cb = card.querySelector('.examen-checkbox');
        if (!cb) return;
        const taux = tauxPourExamen(cb.value);
        const specifique = tauxEstSpecifiqueAssure(cb.value);
        let badge = card.querySelector('.examen-taux-badge');
        if (!badge) {
            badge = document.createElement('div');
            badge.className = 'examen-taux-badge';
            card.querySelector('.examen-card-content').appendChild(badge);
        }
        badge.classList.toggle('assure-specifique', specifique);
        badge.innerHTML = specifique
            ? `<i class="fas fa-user-shield"></i> ${taux}% (taux assuré)`
            : `<i class="fas fa-shield-heart"></i> ${taux}% couvert`;
    });
}
function retirerBadgesTaux() {
    document.querySelectorAll('.examen-taux-badge').forEach(b => b.remove());
}

// ════════════════════════════════════════
// Autocompletes patient / médecin / assurance
// ════════════════════════════════════════
<?php if (!$patientPreload): ?>
const patientSearch  = document.getElementById('patientSearch');
const patientResults = document.getElementById('patientResults');
const patientIdInput = document.getElementById('patientIdInput');
patientSearch?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    if (q.length < 2) { patientResults.style.display='none'; return; }
    const f = allPatients.filter(p=>p.nom.toLowerCase().includes(q)||p.phone.includes(q)).slice(0,8);
    if (!f.length) { patientResults.style.display='none'; return; }
    patientResults.innerHTML = f.map(p=>`
        <div class="patient-result-item" onclick="selectPatient(${p.id},'${p.nom.replace(/'/g,"\\'")}','${p.initials}','${p.phone}')">
            <div class="pr-avatar">${p.initials}</div>
            <div><div class="pr-nom">${p.nom}</div><div class="pr-tel">${p.phone}</div></div>
        </div>`).join('');
    patientResults.style.display = 'block';
});
function selectPatient(id,nom,initials,phone) {
    patientIdInput.value = id;
    patientResults.style.display='none'; patientSearch.style.display='none';
    const sel=document.getElementById('patientSelected');
    document.getElementById('selAvatar').textContent=initials;
    document.getElementById('selName').textContent=nom;
    document.getElementById('selPhone').innerHTML=`<i class="fas fa-phone"></i> ${phone}`;
    sel.style.display='flex';

    const p = allPatients.find(pp => pp.id === id);
    if (p) initPatientAssurance(p.assuranceId, p.assuranceNom, p.assuranceTaux, p.numAssurance);
}
function clearPatient() {
    patientIdInput.value='';
    document.getElementById('patientSelected').style.display='none';
    patientSearch.style.display=''; patientSearch.value='';
    patientResults.style.display='none';

    document.getElementById('assuranceSearchWrap').style.display='';
    document.getElementById('noAssuranceMsg').style.display='none';
    clearAssurance();
}
document.addEventListener('click',e=>{if(!e.target.closest('.patient-search-wrap'))patientResults.style.display='none';});
<?php endif; ?>

const medecinSearch=document.getElementById('medecinSearch');
const medecinResults=document.getElementById('medecinResults');
const medecinIdInput=document.getElementById('medecinIdInput');
medecinSearch?.addEventListener('input',function(){
    const q=this.value.toLowerCase();
    if(q.length<2){medecinResults.style.display='none';return;}
    const f=allMedecins.filter(m=>m.nom.toLowerCase().includes(q)||(m.specialite||'').toLowerCase().includes(q)).slice(0,8);
    medecinResults.innerHTML=f.length?f.map(m=>`
        <div class="patient-result-item" onclick="selectMedecin(${m.id},'${m.nom.replace(/'/g,"\\'")}','${m.initials}','${m.specialite}')">
            <div class="pr-avatar med">${m.initials}</div>
            <div><div class="pr-nom">${m.nom}</div><div class="pr-tel">${m.specialite}</div></div>
        </div>`).join(''):`<div class="patient-result-item" style="cursor:default;color:#6b7a99;">Aucun médecin trouvé</div>`;
    medecinResults.style.display='block';
});
function selectMedecin(id,nom,initials,specialite){
    medecinIdInput.value=id; medecinResults.style.display='none'; medecinSearch.style.display='none';
    const sel=document.getElementById('medecinSelected');
    document.getElementById('medSelAvatar').textContent=initials;
    document.getElementById('medSelName').textContent=nom;
    document.getElementById('medSelSpecialite').innerHTML=`<i class="fas fa-stethoscope"></i> ${specialite}`;
    sel.style.display='flex';
}
function clearMedecin(){
    medecinIdInput.value=''; document.getElementById('medecinSelected').style.display='none';
    medecinSearch.style.display=''; medecinSearch.value=''; medecinResults.style.display='none';
}
document.addEventListener('click',e=>{if(!e.target.closest('.medecin-search-wrap'))medecinResults.style.display='none';});

const assuranceSearch=document.getElementById('assuranceSearch');
const assuranceResults=document.getElementById('assuranceResults');
const assuranceIdInput=document.getElementById('assuranceIdInput');
assuranceSearch?.addEventListener('input',function(){
    const q=this.value.toLowerCase();
    if(q.length<2){assuranceResults.style.display='none';return;}
    const f=allAssurances.filter(a=>a.nom.toLowerCase().includes(q)).slice(0,8);
    assuranceResults.innerHTML=f.length?f.map(a=>`
        <div class="patient-result-item" onclick="selectAssurance(${a.id},'${a.nom.replace(/'/g,"\\'")}',${a.taux})">
            <div class="pr-avatar med"><i class="fas fa-shield-heart"></i></div>
            <div><div class="pr-nom">${a.nom}</div><div class="pr-tel">Taux défaut: ${a.taux}%</div></div>
        </div>`).join(''):`<div class="patient-result-item" style="cursor:default;color:#6b7a99;">Aucune compagnie trouvée</div>`;
    assuranceResults.style.display='block';
});
function selectAssurance(id,nom,taux){
    assuranceIdInput.value=id; assuranceResults.style.display='none'; assuranceSearch.style.display='none';
    assuranceState={id,nom,tauxDefaut:taux,statut:null,codeManuel:false,assureId:null,tauxAssure:{}};
    document.getElementById('assSelName').textContent=nom;
    document.getElementById('assSelTaux').innerHTML=`<i class="fas fa-percent"></i> Taux défaut: ${taux}%`;
    document.getElementById('assuranceSelected').style.display='flex';
    document.getElementById('assuranceDetails').style.display='block';
    document.getElementById('numeroPoliceInput').focus();
    renderRecap();
}
function clearAssurance(){
    assuranceIdInput.value='';
    document.getElementById('assuranceSelected').style.display='none';
    assuranceSearch.style.display=''; assuranceSearch.value=''; assuranceResults.style.display='none';
    document.getElementById('assuranceDetails').style.display='none';
    document.getElementById('numeroPoliceInput').value='';
    document.getElementById('verifFichierStatutInput').value='';
    document.getElementById('verifStatusZone').style.display='none';
    document.getElementById('manualFallbackZone').style.display='none';
    document.getElementById('hasCodeCheckbox').checked=false;
    assuranceState={id:null,nom:null,tauxDefaut:0,statut:null,codeManuel:false,assureId:null,tauxAssure:{}};
    retirerBadgesTaux();
    toggleCodeAccord(); renderRecap();
}
document.addEventListener('click',e=>{if(!e.target.closest('.assurance-search-wrap'))assuranceResults.style.display='none';});

// Vérification police assurance
let verifTimeout=null;
document.getElementById('numeroPoliceInput')?.addEventListener('input',function(){
    clearTimeout(verifTimeout);
    const police=this.value.trim();
    const zone=document.getElementById('verifStatusZone');
    if(police.length<3||!assuranceState.id){
        zone.style.display='none';
        document.getElementById('manualFallbackZone').style.display='none';
        assuranceState.statut=null;
        assuranceState.assureId=null;
        assuranceState.tauxAssure={};
        document.getElementById('verifFichierStatutInput').value='';
        retirerBadgesTaux();
        renderRecap(); return;
    }
    verifTimeout=setTimeout(()=>verifierAssure(police),400);
});
function verifierAssure(police){
    const zone=document.getElementById('verifStatusZone');
    zone.style.display='block';
    zone.innerHTML=`<div class="verif-status verif-loading"><i class="fas fa-spinner fa-spin"></i> Vérification...</div>`;
    fetch(`<?= BASE_URL ?>secretaire/demandes/check-assurance?assurance_id=${assuranceState.id}&numero_police=${encodeURIComponent(police)}`)
        .then(r=>r.json()).then(data=>{
            assuranceState.statut=data.statut==='non_trouve'?null:data.statut;
            assuranceState.assureId=data.assure_id||null;
            assuranceState.tauxAssure=data.taux_assure||{};
            document.getElementById('verifFichierStatutInput').value=assuranceState.statut||'';
            if(data.statut==='en_regle'){
                zone.innerHTML=`<div class="verif-status verif-ok"><i class="fas fa-circle-check"></i> <strong>${data.nom_assure}</strong> — en règle</div>`;
                document.getElementById('manualFallbackZone').style.display='none';
                annoterExamensAvecTaux();
            }else if(data.statut==='suspendu'){
                zone.innerHTML=`<div class="verif-status verif-blocked"><i class="fas fa-triangle-exclamation"></i> <strong>${data.nom_assure}</strong> — contrat suspendu</div>`;
                document.getElementById('manualFallbackZone').style.display='block';
                retirerBadgesTaux();
            }else{
                zone.innerHTML=`<div class="verif-status verif-unknown"><i class="fas fa-circle-question"></i> Numéro non trouvé</div>`;
                document.getElementById('manualFallbackZone').style.display='block';
                retirerBadgesTaux();
            }
            renderRecap();
        }).catch(()=>{
            zone.innerHTML=`<div class="verif-status verif-unknown"><i class="fas fa-circle-exclamation"></i> Vérification impossible</div>`;
        });
}
function toggleCodeAccord(){
    const checked=document.getElementById('hasCodeCheckbox').checked;
    document.getElementById('codeAccordWrap').style.display=checked?'block':'none';
    document.getElementById('enAttenteNote').style.display=checked?'none':'block';
    if(!checked){document.getElementById('codeAccordInput').value=''; assuranceState.codeManuel=false;}
    annoterExamensAvecTaux();
    renderRecap();
}
function recalculerRecap(){
    assuranceState.codeManuel=document.getElementById('codeAccordInput').value.trim()!=='';
    annoterExamensAvecTaux();
    renderRecap();
}

// Submit
document.getElementById('formDemande').addEventListener('submit',function(e){
    const pid=document.querySelector('[name="patient_id"]')?.value;
    if(!pid){e.preventDefault();alert('Veuillez sélectionner un patient.');return;}
    if(Object.keys(selectedExamens).length===0){e.preventDefault();alert('Veuillez sélectionner au moins un examen.');return;}
    const btn=document.getElementById('submitBtn');
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Création...'; btn.disabled=true;
});
</script>


<script>
// Offline-first demandes
(function () {
    const form = document.getElementById('formDemande');
    if (!form) return;
    form.addEventListener('submit', async function (e) {
        if (navigator.onLine) return;
        e.preventDefault();
        e.stopImmediatePropagation();

        const fd = new FormData(form);
        const patientId = fd.get('patient_id');
        if (!patientId) {
            alert('Sélectionnez un patient');
            return;
        }
        const examenIds = fd.getAll('examen_ids[]');
        if (!examenIds.length) {
            alert('Sélectionnez au moins un examen');
            return;
        }

        const fields = [];
        fd.forEach((v, k) => fields.push([k, v]));
        const payload = {
            patient_id: String(patientId),
            medecin_id: String(fd.get('medecin_id') || ''),
            assurance_id: String(fd.get('assurance_id') || ''),
            numero_police: String(fd.get('numero_police') || ''),
            code_accord: String(fd.get('code_accord') || ''),
            examen_ids: examenIds.map(String),
            statut: 'brouillon_local',
            created_label: 'Demande locale'
        };

        await LocalDB.create('demandes', payload, form.action, {
            type: 'demande',
            fields,
            label: 'Nouvelle demande — patient #' + patientId + ' (' + examenIds.length + ' examen(s))'
        });
        LocalDB.toast('Demande enregistrée sur cet appareil', 'success');
        window.location.href = '<?= BASE_URL ?>secretaire/demandes';
    }, true);
})();
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>