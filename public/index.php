<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Auth.php';

$router = new Router();

// ─── Routes Auth ────────────────────────────────────────
$router->get('/login',   ['AuthController', 'loginPage']);
$router->post('/login',  ['AuthController', 'loginPost']);
$router->get('/logout',  ['AuthController', 'logout']);

// ─── Mot de passe oublié ─────────────────────────────────
$router->get('/forgot-password',            ['AuthController', 'forgotPasswordPage']);
$router->post('/forgot-password',           ['AuthController', 'forgotPasswordSend']);
$router->get('/reset-password/{token}',     ['AuthController', 'resetPasswordPage']);
$router->post('/reset-password/{token}',    ['AuthController', 'resetPasswordSave']);

// ─── Routes Profil (commun à tous les rôles) ────────────
$router->get('/profil',                  ['ProfileController', 'index']);
$router->post('/profil/update-info',     ['ProfileController', 'updateInfo']);
$router->post('/profil/update-password', ['ProfileController', 'updatePassword']);

// ─── Routes Dashboard ───────────────────────────────────
$router->get('/',                     ['DashboardController', 'index']);
$router->get('/dashboard',            ['DashboardController', 'index']);
$router->get('/dashboard/admin',      ['DashboardController', 'admin']);
$router->get('/dashboard/directeur',  ['DashboardController', 'directeur']);
$router->get('/dashboard/directeur/data', ['DashboardController', 'directeurData']);
$router->get('/dashboard/secretaire', ['DashboardController', 'secretaire']);
$router->get('/dashboard/caissier',   ['DashboardController', 'caissier']);
$router->get('/dashboard/technicien', ['DashboardController', 'technicien']);
$router->get('/dashboard/biologiste', ['DashboardController', 'biologiste']);
$router->get('/dashboard/comptable',  ['DashboardController', 'comptable']);
$router->get('/dashboard/infirmier',  ['DashboardController', 'infirmier']);

// ─── Routes Secrétaire — Patients ───────────────────────
$router->get('/secretaire/patients',            ['PatientController', 'index']);
$router->get('/secretaire/patients/create',     ['PatientController', 'create']);
$router->post('/secretaire/patients/store',     ['PatientController', 'store']);
$router->get('/secretaire/patients/{id}/edit',  ['PatientController', 'edit']);
$router->post('/secretaire/patients/{id}/edit', ['PatientController', 'update']);
$router->get('/secretaire/patients/{id}',       ['PatientController', 'show']);

// ─── Routes Secrétaire — Demandes ───────────────────────
$router->get('/secretaire/demandes',            ['DemandeController', 'index']);
$router->get('/secretaire/demandes/create',     ['DemandeController', 'create']);
$router->post('/secretaire/demandes/store',     ['DemandeController', 'store']);
$router->get('/secretaire/demandes/check-assurance', ['DemandeController', 'checkAssurance']);

// ─── Routes Secrétaire — Factures proforma & Notifications ──
$router->get('/secretaire/factures',       ['FactureController', 'proforma']);
$router->get('/secretaire/notifications',  ['FactureController', 'notifications']);
$router->get('/secretaire/demandes/{id}',       ['DemandeController', 'show']);

// ─── Routes Caissier ────────────────────────────────────
$router->get('/caissier/factures',              ['CaissierController', 'factures']);
$router->post('/caissier/factures/{id}/payer',  ['CaissierController', 'payer']);
$router->get('/caissier/factures/{id}',         ['CaissierController', 'showFacture']);
$router->get('/caissier/paiements',             ['CaissierController', 'paiements']);
$router->get('/caissier/recus',                 ['CaissierController', 'recus']);
$router->get('/caissier/recus/{id}',            ['CaissierController', 'recu']);
$router->get('/caissier/historique',            ['CaissierController', 'historique']);

// ─── Routes Technicien ──────────────────────────────────
$router->get('/technicien/echantillons',               ['TechnicienController', 'echantillons']);
$router->get('/technicien/echantillons/{id}',          ['TechnicienController', 'showEchantillon']);
$router->get('/technicien/resultats',                  ['TechnicienController', 'resultats']);
$router->post('/technicien/resultats/{id}/save',       ['TechnicienController', 'saveResultats']);
$router->post('/technicien/resultats/{id}/transmettre',['TechnicienController', 'transmettre']);
$router->get('/technicien/resultats/{id}',             ['TechnicienController', 'saisirResultats']);
$router->get('/technicien/encours',                    ['TechnicienController', 'encours']);

// ─── Routes Biologiste ──────────────────────────────────
$router->get('/biologiste/validations',               ['BiologisteController', 'validations']);
$router->post('/biologiste/validations/{id}/valider', ['BiologisteController', 'valider']);
$router->post('/biologiste/validations/{id}/rejeter', ['BiologisteController', 'rejeter']);
$router->get('/biologiste/validations/{id}',          ['BiologisteController', 'showValidation']);
$router->get('/biologiste/resultats-valides',         ['BiologisteController', 'resultatsValides']);
$router->get('/biologiste/commentaires',              ['BiologisteController', 'commentaires']);
$router->get('/biologiste/rapports',                  ['BiologisteController', 'rapports']);

// ─── Routes Portail Patient ──────────────────────────────
$router->get('/patient/portal/{token}',               ['PatientPortalController', 'index']);
$router->get('/patient/resultat/{token}/{demande_id}',['PatientPortalController', 'resultat']);

// ─── Routes Directeur ────────────────────────────────────
$router->get('/directeur/kpi',      ['DirecteurController', 'kpi']);
$router->get('/directeur/revenus',  ['DirecteurController', 'revenus']);
$router->get('/directeur/examens',  ['DirecteurController', 'examens']);
$router->get('/directeur/rapports', ['DirecteurController', 'rapports']);

// ─── Routes Admin — Utilisateurs ────────────────────────
$router->get('/admin/users',                          ['AdminUserController', 'index']);
$router->get('/admin/users/create',                   ['AdminUserController', 'create']);
$router->post('/admin/users/store',                   ['AdminUserController', 'store']);
$router->get('/admin/users/{id}/edit',                ['AdminUserController', 'edit']);
$router->post('/admin/users/{id}/edit',               ['AdminUserController', 'update']);
$router->post('/admin/users/{id}/toggle',             ['AdminUserController', 'toggle']);
$router->post('/admin/users/{id}/delete',             ['AdminUserController', 'delete']);
$router->post('/admin/users/{id}/reset-password',     ['AdminUserController', 'resetPassword']);
$router->get('/admin/users/{id}',                     ['AdminUserController', 'show']);

// ─── Routes Admin — Examens ──────────────────────────────
$router->get('/admin/examens',                        ['AdminExamenController', 'index']);
$router->get('/admin/examens/create',                 ['AdminExamenController', 'create']);
$router->post('/admin/examens/store',                 ['AdminExamenController', 'store']);

// Paramètres — avant {id} seul
$router->post('/admin/parametres/{pid}/delete',       ['AdminExamenController', 'deleteParametre']);
$router->post('/admin/parametres/{pid}/edit',         ['AdminExamenController', 'updateParametre']);
$router->get('/admin/examens/{id}/parametres',        ['AdminExamenController', 'parametres']);
$router->post('/admin/examens/{id}/parametres/store', ['AdminExamenController', 'storeParametre']);
$router->get('/admin/examens/{id}/edit',              ['AdminExamenController', 'edit']);
$router->post('/admin/examens/{id}/edit',             ['AdminExamenController', 'update']);
$router->post('/admin/examens/{id}/toggle',           ['AdminExamenController', 'toggle']);
$router->post('/admin/examens/{id}/delete',           ['AdminExamenController', 'delete']);
$router->get('/admin/examens/{id}',                   ['AdminExamenController', 'show']);

// ─── Routes Admin — Médecins prescripteurs ───────────────
$router->get('/admin/medecins',                       ['AdminMedecinController', 'index']);
$router->post('/admin/medecins/store',                ['AdminMedecinController', 'store']);
$router->post('/admin/medecins/{id}/edit',            ['AdminMedecinController', 'update']);
$router->post('/admin/medecins/{id}/toggle',          ['AdminMedecinController', 'toggle']);
$router->post('/admin/medecins/{id}/delete',          ['AdminMedecinController', 'delete']);
$router->post('/admin/medecins/{id}/regenerer-token', ['AdminMedecinController', 'regenererToken']);
$router->post('/admin/medecins/commissions/{id}/payer',        ['AdminMedecinController', 'payerCommission']);
$router->post('/admin/medecins/{id}/payer-toutes-commissions', ['AdminMedecinController', 'payerToutesCommissions']);

// ─── Routes Admin — Assurances ───────────────────────────
$router->get('/admin/assurances',                              ['AdminAssuranceController', 'index']);
$router->post('/admin/assurances/store',                       ['AdminAssuranceController', 'store']);
$router->post('/admin/assurances/{id}/edit',                   ['AdminAssuranceController', 'update']);
$router->post('/admin/assurances/{id}/toggle',                 ['AdminAssuranceController', 'toggle']);
$router->post('/admin/assurances/{id}/delete',                 ['AdminAssuranceController', 'delete']);
$router->post('/admin/assurances/{id}/regenerer-token',        ['AdminAssuranceController', 'regenererToken']);
$router->get('/admin/assurances/{id}/taux',                    ['AdminAssuranceController', 'taux']);
$router->post('/admin/assurances/{id}/taux/store',             ['AdminAssuranceController', 'storeTaux']);
$router->post('/admin/assurances/creances/{id}/payer',         ['AdminAssuranceController', 'payerCreance']);
$router->post('/admin/assurances/{id}/payer-toutes-creances',  ['AdminAssuranceController', 'payerToutesCreances']);
$router->get('/admin/assurances/{id}/assures',                 ['AdminAssuranceController', 'assures']);
$router->post('/admin/assurances/{id}/assures/import',         ['AdminAssuranceController', 'storeImportAssures']);
$router->post('/admin/assures/{id}/delete',                    ['AdminAssuranceController', 'deleteAssure']);

// ─── Routes Portail Médecin prescripteur ─────────────────
$router->get('/medecin/portal/{token}',                  ['MedecinPortalController', 'index']);
$router->get('/medecin/portal/{token}/resultat/{demande_id}', ['MedecinPortalController', 'resultat']);

// ─── Routes Portail Assurance ─────────────────────────────
$router->get('/assurance/portal/{token}',                       ['AssurancePortalController', 'index']);
$router->post('/assurance/portal/{token}/valider/{demande_id}', ['AssurancePortalController', 'valider']);
$router->post('/assurance/portal/{token}/rejeter/{demande_id}', ['AssurancePortalController', 'rejeter']);
$router->post('/assurance/portal/{token}/import',                       ['AssurancePortalController', 'storeImport']);
$router->get('/assurance/portal/{token}/historique',                    ['AssurancePortalController', 'historique']);
$router->get('/assurance/portal/{token}/assures',                       ['AssurancePortalController', 'assures']);
$router->get('/assurance/portal/{token}/assures/{assure_id}/taux',      ['AssurancePortalController', 'assureTaux']);
$router->post('/assurance/portal/{token}/assures/{assure_id}/taux',     ['AssurancePortalController', 'storeAssureTaux']);
$router->post('/assurance/portal/{token}/sous-comptes/store',           ['AssurancePortalController', 'creerSousCompte']);
$router->post('/assurance/portal/{token}/sous-comptes/{sc_id}/toggle',  ['AssurancePortalController', 'toggleSousCompte']);
$router->post('/assurance/portal/{token}/sous-comptes/{sc_id}/delete',  ['AssurancePortalController', 'supprimerSousCompte']);

// ─── Routes Admin — Stocks ───────────────────────────────
$router->get('/admin/stocks',                ['AdminStockController', 'index']);
$router->get('/admin/stocks/create',         ['AdminStockController', 'create']);
$router->post('/admin/stocks/store',         ['AdminStockController', 'store']);
$router->get('/admin/stocks/{id}/edit',      ['AdminStockController', 'edit']);
$router->post('/admin/stocks/{id}/edit',     ['AdminStockController', 'update']);
$router->post('/admin/stocks/{id}/delete',   ['AdminStockController', 'delete']);
$router->post('/admin/stocks/{id}/ajuster',  ['AdminStockController', 'ajuster']);

// ─── Routes Admin — Rapports ─────────────────────────────
$router->get('/admin/rapports',              ['AdminRapportController', 'index']);
$router->get('/admin/rapports/{id}',         ['AdminRapportController', 'show']);

// ─── Routes Admin — Audit ────────────────────────────────
$router->get('/admin/audit',                 ['AdminAuditController', 'index']);
$router->get('/admin/audit/export',          ['AdminAuditController', 'export']);
// ─── Dispatch ────────────────────────────────────────────
$router->dispatch();