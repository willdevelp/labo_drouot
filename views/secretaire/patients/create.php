<?php
/**
 * Formulaire création patient — Secrétaire
 */
include __DIR__ . '/../../../views/layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="page-title">
            <i class="fas fa-user-plus text-primary me-2"></i>
            Nouveau patient
        </h1>
        <p class="page-sub">Remplissez les informations du patient</p>
    </div>
    <a href="<?= BASE_URL ?>secretaire/patients" class="btn-outline-drouot">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert-drouot alert-danger mb-4">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<form method="POST"
      action="<?= BASE_URL ?>secretaire/patients/store"
      enctype="multipart/form-data"
      data-offline-store="examens"
      data-offline-file="true"
      id="formPatient">

    <div class="row g-4">

        <!-- Colonne gauche -->
        <div class="col-xl-8">

            <!-- Identité -->
            <div class="card-drouot mb-4">
                <h6 class="form-section-title">
                    <i class="fas fa-id-card"></i> Identité
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-drouot">
                            Nom <span class="required">*</span>
                        </label>
                        <input type="text" name="nom" class="form-input-drouot"
                               placeholder="Ex: DUPONT"
                               value="<?= htmlspecialchars($old['nom'] ?? '') ?>"
                               required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-drouot">
                            Prénom <span class="required">*</span>
                        </label>
                        <input type="text" name="prenom" class="form-input-drouot"
                               placeholder="Ex: Jean"
                               value="<?= htmlspecialchars($old['prenom'] ?? '') ?>"
                               required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-drouot">
                            Sexe <span class="required">*</span>
                        </label>
                        <select name="sexe" class="form-input-drouot" required>
                            <option value="">-- Choisir --</option>
                            <option value="M" <?= ($old['sexe'] ?? '') === 'M' ? 'selected' : '' ?>>
                                Masculin
                            </option>
                            <option value="F" <?= ($old['sexe'] ?? '') === 'F' ? 'selected' : '' ?>>
                                Féminin
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-drouot">Date de naissance</label>
                        <input type="date" name="date_naissance" class="form-input-drouot"
                               value="<?= htmlspecialchars($old['date_naissance'] ?? '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="checkbox-label-inline">
                            <input type="checkbox" id="hasAssuranceCheckbox"
                                   <?= !empty($old['assurance_id']) ? 'checked' : '' ?>
                                   onchange="toggleAssuranceZone()">
                            Le patient a une assurance
                        </label>
                    </div>
                </div>

                <div class="row g-3 mt-1" id="assuranceZone"
                     style="display:<?= !empty($old['assurance_id']) ? 'flex' : 'none' ?>;">
                    <div class="col-md-6">
                        <label class="form-label-drouot">Compagnie d'assurance</label>
                        <div class="autocomplete-wrap">
                            <input type="text" id="assuranceSearchInput" class="form-input-drouot"
                                   placeholder="Taper le nom de la compagnie..." autocomplete="off"
                                   value="<?= htmlspecialchars($old['assurance_nom'] ?? '') ?>">
                            <div id="assuranceAutoResults" class="autocomplete-results"></div>
                        </div>
                        <input type="hidden" name="assurance_id" id="assuranceIdField"
                               value="<?= htmlspecialchars($old['assurance_id'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-drouot">Numéro de police / carte d'assuré</label>
                        <input type="text" name="num_assurance" id="numAssuranceField"
                               class="form-input-drouot" placeholder="Numéro de police"
                               value="<?= htmlspecialchars($old['num_assurance'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="card-drouot mb-4">
                <h6 class="form-section-title">
                    <i class="fas fa-address-book"></i> Contact
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label-drouot">
                            Téléphone <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="telephone" class="form-input-drouot"
                                   placeholder="Ex: 6XXXXXXXX"
                                   value="<?= htmlspecialchars($old['telephone'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-drouot">Email</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-input-drouot"
                                   placeholder="Ex: patient@email.com"
                                   value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label-drouot">Adresse</label>
                        <textarea name="adresse" class="form-input-drouot"
                                  rows="2"
                                  placeholder="Quartier, ville..."><?= htmlspecialchars($old['adresse'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- Colonne droite — Photo -->
        <div class="col-xl-4">
            <div class="card-drouot mb-4">
                <h6 class="form-section-title">
                    <i class="fas fa-camera"></i> Photo patient
                </h6>
                <div class="photo-upload-zone" id="photoZone"
                     onclick="document.getElementById('photoInput').click()">
                    <div class="photo-preview" id="photoPreview">
                        <i class="fas fa-user-circle"></i>
                        <p>Cliquer pour ajouter une photo</p>
                        <span>JPG, PNG — max 2 Mo</span>
                    </div>
                    <img id="photoImg" src="" alt="Aperçu" style="display:none;">
                </div>
                <input type="file" name="photo" id="photoInput"
                       accept="image/jpeg,image/png,image/webp"
                       style="display:none;"
                       onchange="previewPhoto(this)">
                <button type="button" class="btn-outline-drouot w-100 mt-2"
                        onclick="document.getElementById('photoInput').click()">
                    <i class="fas fa-upload"></i> Choisir une photo
                </button>
            </div>

            <!-- Infos QR Code -->
            <div class="card-drouot info-box">
                <div class="info-box-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div>
                    <p class="info-box-title">QR Code automatique</p>
                    <p class="info-box-text">
                        Un QR Code unique et un lien sécurisé seront
                        générés automatiquement après la création.
                    </p>
                </div>
            </div>

            <div class="card-drouot info-box mt-3">
                <div class="info-box-icon" style="background:#E8F5E9;color:#2E7D32;">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div>
                    <p class="info-box-title">Lien WhatsApp</p>
                    <p class="info-box-text">
                        Le lien d'accès aux résultats sera prêt à
                        envoyer via WhatsApp ou SMS.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Boutons submit -->
    <div class="form-actions">
        <a href="<?= BASE_URL ?>secretaire/patients"
           class="btn-outline-drouot">
            <i class="fas fa-xmark"></i> Annuler
        </a>
        <button type="submit" class="btn-primary-drouot" id="submitBtn">
            <i class="fas fa-user-plus"></i> Créer le patient
        </button>
    </div>

</form>

<style>
.page-title { font-size:1.3rem; font-weight:600; color:#1a2340; margin:0; }
.page-sub   { font-size:0.85rem; color:#6b7a99; margin:2px 0 0; }

.form-section-title {
    font-size: 0.88rem; font-weight: 600; color: #1a2340;
    margin-bottom: 1rem;
    display: flex; align-items: center; gap: 8px;
    padding-bottom: 8px;
    border-bottom: 1.5px solid #f0f2f8;
}
.form-section-title i { color: #1E88E5; }

.form-label-drouot {
    display: block; font-size: 0.78rem;
    font-weight: 600; color: #6b7a99;
    text-transform: uppercase; letter-spacing: 0.05em;
    margin-bottom: 5px;
}
.required { color: #C62828; }

.form-input-drouot {
    width: 100%; padding: 0.7rem 0.9rem;
    border: 1.5px solid #dde3f0;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem; color: #1a2340;
    background: #fafbfd;
    transition: all 0.2s; outline: none;
}
.form-input-drouot:focus {
    border-color: #1E88E5;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(30,136,229,0.08);
}

.input-with-icon { position: relative; }
.input-with-icon i {
    position: absolute; left: 0.9rem; top: 50%;
    transform: translateY(-50%);
    color: #b0b8cc; font-size: 0.85rem;
    pointer-events: none;
}
.input-with-icon input { padding-left: 2.4rem; }

/* Checkbox assurance */
.checkbox-label-inline {
    display:flex; align-items:center; gap:8px;
    font-size:.85rem; color:#1a2340; cursor:pointer;
    padding-top:22px;
}
.checkbox-label-inline input[type="checkbox"] {
    width:16px; height:16px; cursor:pointer; accent-color:#1E88E5;
}

/* Autocomplete assurance */
.autocomplete-wrap { position:relative; }
.autocomplete-results {
    display:none; position:absolute; z-index:20; top:100%; left:0; right:0;
    background:#fff; border:1px solid #dde3f0; border-radius:10px;
    box-shadow:0 4px 16px rgba(0,0,0,.08); max-height:200px; overflow-y:auto; margin-top:4px;
}
.autocomplete-item {
    padding:9px 14px; font-size:.85rem; color:#1a2340; cursor:pointer;
    border-bottom:1px solid #f0f2f8; transition:background .15s;
}
.autocomplete-item:last-child { border-bottom:none; }
.autocomplete-item:hover { background:#f0f7ff; }
.autocomplete-item.disabled { color:#b0b8cc; cursor:default; }
.autocomplete-item.disabled:hover { background:#fff; }

/* Photo upload */
.photo-upload-zone {
    border: 2px dashed #dde3f0;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.photo-upload-zone:hover { border-color: #1E88E5; background: #f0f7ff; }
.photo-preview i { font-size: 3rem; color: #b0b8cc; display: block; margin-bottom: 8px; }
.photo-preview p { font-size: 0.85rem; color: #6b7a99; margin: 0; }
.photo-preview span { font-size: 0.72rem; color: #b0b8cc; }
#photoImg { width: 100%; border-radius: 8px; object-fit: cover; max-height: 180px; }

/* Info box */
.info-box {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 14px;
}
.info-box-icon {
    width: 36px; height: 36px; border-radius: 9px;
    background: #E3F2FD; color: #1565C0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.info-box-title { font-size: 0.83rem; font-weight: 600; color: #1a2340; margin: 0 0 2px; }
.info-box-text  { font-size: 0.75rem; color: #6b7a99; margin: 0; line-height: 1.4; }

/* Alert */
.alert-drouot {
    padding: 0.85rem 1rem; border-radius: 10px;
    display: flex; align-items: center; gap: 8px;
    font-size: 0.88rem;
}
.alert-danger {
    background: #FFEBEE; color: #C62828;
    border: 1px solid #FFCDD2;
}

/* Bouton outline */
.btn-outline-drouot {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 0.6rem 1.2rem;
    border: 1.5px solid #dde3f0;
    border-radius: 9px; background: #fff;
    color: #1a2340; font-size: 0.88rem; font-weight: 500;
    cursor: pointer; text-decoration: none;
    transition: all 0.2s;
    font-family: 'DM Sans', sans-serif;
}
.btn-outline-drouot:hover { border-color: #1E88E5; color: #1E88E5; }

/* Form actions */
.form-actions {
    display: flex; justify-content: flex-end;
    gap: 10px; margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1.5px solid #f0f2f8;
}
</style>

<script>
// ════════════════════════════════════════
// Photo preview
// ════════════════════════════════════════
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').style.display = 'none';
            const img = document.getElementById('photoImg');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ════════════════════════════════════════
// Assurance — checkbox + autocomplete
// ════════════════════════════════════════
const allAssurances = <?= json_encode(array_map(fn($a) => [
    'id'  => $a['id'],
    'nom' => $a['nom'],
], $assurances ?? [])) ?>;

function toggleAssuranceZone() {
    const checked = document.getElementById('hasAssuranceCheckbox').checked;
    const zone = document.getElementById('assuranceZone');
    zone.style.display = checked ? 'flex' : 'none';
    if (!checked) {
        document.getElementById('assuranceIdField').value = '';
        document.getElementById('assuranceSearchInput').value = '';
        document.getElementById('numAssuranceField').value = '';
        document.getElementById('assuranceAutoResults').style.display = 'none';
    }
}

const assSearchInput = document.getElementById('assuranceSearchInput');
const assResults     = document.getElementById('assuranceAutoResults');
const assIdField     = document.getElementById('assuranceIdField');

assSearchInput?.addEventListener('input', function () {
    assIdField.value = ''; // tant que rien n'est confirmé dans la liste
    const q = this.value.trim().toLowerCase();
    if (q.length < 1) { assResults.style.display = 'none'; return; }

    // priorité aux noms qui COMMENCENT par la saisie, puis complète avec "contient"
    const startsWith = allAssurances.filter(a => a.nom.toLowerCase().startsWith(q));
    const contains    = allAssurances.filter(a => !a.nom.toLowerCase().startsWith(q) && a.nom.toLowerCase().includes(q));
    const finalList   = [...startsWith, ...contains];

    if (!finalList.length) {
        assResults.innerHTML = `<div class="autocomplete-item disabled">Aucune compagnie trouvée</div>`;
        assResults.style.display = 'block';
        return;
    }
    assResults.innerHTML = finalList.slice(0, 8).map(a =>
        `<div class="autocomplete-item" onclick="selectAssuranceOpt(${a.id}, '${a.nom.replace(/'/g, "\\'")}')">${a.nom}</div>`
    ).join('');
    assResults.style.display = 'block';
});

function selectAssuranceOpt(id, nom) {
    assIdField.value = id;
    assSearchInput.value = nom;
    assResults.style.display = 'none';
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.autocomplete-wrap')) assResults.style.display = 'none';
});

// ════════════════════════════════════════
// Submit (avec prise en charge hors ligne)
// ════════════════════════════════════════
document.getElementById('formPatient').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = this;
    const hasAss = document.getElementById('hasAssuranceCheckbox').checked;
    if (hasAss && !assIdField.value) {
        alert('Veuillez sélectionner une compagnie d\'assurance dans la liste proposée.');
        return;
    }

    // Validation minimale, miroir des règles serveur, avant mise en file
    // (on ne veut pas stocker localement un formulaire qu'on sait déjà
    // incomplet).
    const nom       = form.querySelector('[name="nom"]').value.trim();
    const prenom    = form.querySelector('[name="prenom"]').value.trim();
    const sexe      = form.querySelector('[name="sexe"]').value;
    const telephone = form.querySelector('[name="telephone"]').value.trim();
    if (!nom || !prenom || !sexe || !telephone) {
        alert('Les champs Nom, Prénom, Sexe et Téléphone sont obligatoires.');
        return;
    }

    const btn = document.getElementById('submitBtn');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';
    btn.disabled = true;

    const fd = new FormData(form); // inclut la photo automatiquement

    try {
        if (!navigator.onLine) throw new Error('offline');
        const res = await fetch(form.action, {
            method: 'POST',
            body: fd,
            credentials: 'include'
        });
        window.location.href = res.url || '<?= BASE_URL ?>secretaire/patients';
    } catch (err) {
        const fields = [];
        fd.forEach((v, k) => fields.push([k, v]));
        const payload = {
            nom, prenom, sexe, telephone,
            date_naissance: form.querySelector('[name="date_naissance"]')?.value || '',
            adresse: form.querySelector('[name="adresse"]')?.value || ''
        };
        await LocalDB.create('patients', payload, form.action, {
            type: 'patient',
            fields,
            label: 'Nouveau patient — ' + nom + ' ' + prenom
        });
        LocalDB.toast(
            'Patient enregistré sur cet appareil. Tu peux continuer hors ligne.',
            'success'
        );
        window.location.href = '<?= BASE_URL ?>secretaire/patients';
    }
});
</script>

<?php include __DIR__ . '/../../../views/layouts/footer.php'; ?>