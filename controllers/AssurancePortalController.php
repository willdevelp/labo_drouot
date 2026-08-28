<?php
/**
 * AssurancePortalController — Portail assureur sans login
 * Accès via token unique (assurances.token_acces)
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/CreanceHelper.php';

class AssurancePortalController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── PORTAIL PRINCIPAL ────────────────────────────────
    public function index(string $token): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions, $sousCompte] = $contexte;

        // Demandes en attente de validation (action requise)
        $stmtEnAttente = $this->db->prepare("
            SELECT
                d.id, d.numero_police, d.created_at, d.montant_net,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                GROUP_CONCAT(DISTINCT e.nom SEPARATOR ', ') AS examens,
                COUNT(DISTINCT e.id) AS nb_examens,
                f.statut AS facture_statut
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            LEFT JOIN factures f ON f.demande_id = d.id
            WHERE d.assurance_id = :aid AND d.assurance_statut = 'en_attente'
            GROUP BY d.id
            ORDER BY d.created_at ASC
        ");
        $stmtEnAttente->execute([':aid' => $assurance['id']]);
        $enAttente = $stmtEnAttente->fetchAll();

        // Historique de toutes les demandes liées (validées, rejetées)
        $stmtHistorique = $this->db->prepare("
            SELECT
                d.id, d.numero_police, d.code_accord, d.created_at,
                d.assurance_statut, d.montant_net,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                c.montant_creance, c.statut AS creance_statut
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN creances_assurance c ON c.demande_id = d.id
            WHERE d.assurance_id = :aid
            AND d.assurance_statut IN ('validee', 'rejetee')
            ORDER BY d.created_at DESC
            LIMIT 50
        ");
        $stmtHistorique->execute([':aid' => $assurance['id']]);
        $historique = $stmtHistorique->fetchAll();

        // Récapitulatif créances
        $creanceStats = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN statut = 'due' THEN montant_creance ELSE 0 END), 0) AS total_du,
                COALESCE(SUM(CASE WHEN statut = 'payee' THEN montant_creance ELSE 0 END), 0) AS total_paye
            FROM creances_assurance
            WHERE assurance_id = :aid
        ");
        $creanceStats->execute([':aid' => $assurance['id']]);
        $creanceStats = $creanceStats->fetch();

        $success = isset($_GET['action']);
        $importResult = $_SESSION['portal_import_result'] ?? null;
        $importError  = $_SESSION['portal_import_error'] ?? null;
        unset($_SESSION['portal_import_result'], $_SESSION['portal_import_error']);

        // Nom affiché dans le header : nom du sous-compte si applicable,
        // sinon "Portail principal".
        $contexteLabel = $sousCompte ? $sousCompte['sc_nom'] : 'Portail principal';

        // Sous-comptes existants — uniquement visibles/gérables depuis
        // le portail principal de la compagnie, jamais depuis un
        // sous-compte (pas de sous-sous-comptes).
        $sousComptes = [];
        if (!$sousCompte) {
            $stmtSc = $this->db->prepare("
                SELECT * FROM assurance_sous_comptes
                WHERE assurance_id = :aid
                ORDER BY created_at DESC
            ");
            $stmtSc->execute([':aid' => $assurance['id']]);
            $sousComptes = $stmtSc->fetchAll();
        }

        include dirname(__DIR__) . '/views/assurance/portal.php';
    }

    // ── REGISTRE — DOSSIERS TRAITÉS (page dédiée) ────────
    // Séparée de la page principale pour éviter la saturation :
    // même requête que celle utilisée pour les stats de index(),
    // mais avec recherche patient/police et sans limite basse.
    public function historique(string $token): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions, $sousCompte] = $contexte;

        $search = trim($_GET['search'] ?? '');
        $where  = ["d.assurance_id = :aid", "d.assurance_statut IN ('validee', 'rejetee')"];
        $params = [':aid' => $assurance['id']];

        if ($search !== '') {
            $where[]       = "(p.nom LIKE :s OR p.prenom LIKE :s2 OR d.numero_police LIKE :s3)";
            $like          = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                d.id, d.numero_police, d.code_accord, d.created_at,
                d.assurance_statut, d.montant_net,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                c.montant_creance, c.statut AS creance_statut
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN creances_assurance c ON c.demande_id = d.id
            WHERE $whereStr
            ORDER BY d.created_at DESC
            LIMIT 300
        ");
        $stmt->execute($params);
        $historique = $stmt->fetchAll();

        $contexteLabel = $sousCompte ? $sousCompte['sc_nom'] : 'Portail principal';

        include dirname(__DIR__) . '/views/assurance/historique.php';
    }

    // ── LISTE DES ASSURÉS ─────────────────────────────────
    // Permet à la compagnie (ou un sous-compte autorisé à importer)
    // de parcourir ses assurés et d'accéder à la config de taux
    // spécifiques par examen pour chacun d'eux.
    public function assures(string $token): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions, $sousCompte] = $contexte;

        if (!$permissions['peut_importer']) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=permission');
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $where  = 'a.assurance_id = :aid';
        $params = [':aid' => $assurance['id']];

        if ($search !== '') {
            $where           .= ' AND (a.numero_police LIKE :s OR a.nom_assure LIKE :s2)';
            $params[':s']     = '%' . $search . '%';
            $params[':s2']    = '%' . $search . '%';
        }

        $stmt = $this->db->prepare("
            SELECT a.*,
                (SELECT COUNT(*) FROM assure_taux_examens ate WHERE ate.assure_id = a.id) AS nb_taux_specifiques
            FROM assures a
            WHERE $where
            ORDER BY a.nom_assure
            LIMIT 300
        ");
        $stmt->execute($params);
        $assures = $stmt->fetchAll();

        $contexteLabel = $sousCompte ? $sousCompte['sc_nom'] : 'Portail principal';

        include dirname(__DIR__) . '/views/assurance/assures.php';
    }

    // ── CONFIGURER LES TAUX D'UN ASSURÉ ──────────────────
    // Affiche tous les examens actifs avec, pour chacun, le taux
    // effectif qui s'appliquerait (assuré > compagnie/examen > défaut),
    // et permet de fixer une surcharge propre à cet assuré.
    public function assureTaux(string $token, string $assureId): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions, $sousCompte] = $contexte;

        if (!$permissions['peut_importer']) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=permission');
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM assures
            WHERE id = :id AND assurance_id = :aid
            LIMIT 1
        ");
        $stmt->execute([':id' => $assureId, ':aid' => $assurance['id']]);
        $assure = $stmt->fetch();

        if (!$assure) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '/assures?error=introuvable');
            exit;
        }

        $stmtEx = $this->db->prepare("
            SELECT
                e.id, e.code, e.nom, e.categorie, e.prix,
                ate.taux AS taux_assure,
                ce.taux  AS taux_compagnie,
                COALESCE(ate.taux, ce.taux, :tauxDefaut) AS taux_effectif
            FROM examens e
            LEFT JOIN assure_taux_examens ate
                   ON ate.examen_id = e.id AND ate.assure_id = :assid
            LEFT JOIN assurance_taux_examens ce
                   ON ce.examen_id = e.id AND ce.assurance_id = :aid
            WHERE e.is_active = 1
            ORDER BY e.categorie, e.nom
        ");
        $stmtEx->execute([
            ':assid'      => $assureId,
            ':aid'        => $assurance['id'],
            ':tauxDefaut' => $assurance['taux_defaut'],
        ]);
        $examensRaw = $stmtEx->fetchAll();

        $examensGroupes = [];
        foreach ($examensRaw as $e) {
            $cat = $e['categorie'] ?? 'Autres';
            $examensGroupes[$cat][] = $e;
        }

        $contexteLabel = $sousCompte ? $sousCompte['sc_nom'] : 'Portail principal';
        $saved = isset($_GET['action']) && $_GET['action'] === 'saved';

        include dirname(__DIR__) . '/views/assurance/assure_taux.php';
    }

    // ── ENREGISTRER LES TAUX D'UN ASSURÉ ─────────────────
    // Un champ laissé vide supprime la surcharge existante (retombe
    // sur le taux compagnie/examen, ou le taux par défaut).
    public function storeAssureTaux(string $token, string $assureId): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions] = $contexte;

        if (!$permissions['peut_importer']) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=permission');
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT id FROM assures WHERE id = :id AND assurance_id = :aid LIMIT 1
        ");
        $stmt->execute([':id' => $assureId, ':aid' => $assurance['id']]);
        if (!$stmt->fetch()) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '/assures?error=introuvable');
            exit;
        }

        $tauxPostes = $_POST['taux'] ?? [];

        $stmtDelete = $this->db->prepare("
            DELETE FROM assure_taux_examens WHERE assure_id = :assid AND examen_id = :eid
        ");
        $stmtUpsert = $this->db->prepare("
            INSERT INTO assure_taux_examens (assure_id, examen_id, taux)
            VALUES (:assid, :eid, :taux)
            ON DUPLICATE KEY UPDATE taux = :taux2
        ");

        foreach ($tauxPostes as $examenId => $valeur) {
            $examenId = (int) $examenId;
            $valeur   = trim((string) $valeur);

            if ($valeur === '') {
                $stmtDelete->execute([':assid' => $assureId, ':eid' => $examenId]);
                continue;
            }

            $valeurFloat = (float) str_replace(',', '.', $valeur);
            $valeurFloat = max(0, min(100, $valeurFloat));

            $stmtUpsert->execute([
                ':assid'  => $assureId,
                ':eid'    => $examenId,
                ':taux'   => $valeurFloat,
                ':taux2'  => $valeurFloat,
            ]);
        }

        header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '/assures/' . $assureId . '/taux?action=saved');
        exit;
    }

    // ── IMPORT FICHIER ASSURÉS — DEPUIS LE PORTAIL ───────
    public function storeImport(string $token): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions] = $contexte;

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!$permissions['peut_importer']) {
            $_SESSION['portal_import_error'] = 'Vous n\'avez pas la permission d\'importer des assurés.';
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token);
            exit;
        }

        if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['portal_import_error'] = 'Aucun fichier valide reçu.';
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token);
            exit;
        }

        $tmpPath = $_FILES['fichier']['tmp_name'];
        $ext     = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $_SESSION['portal_import_error'] = 'Le fichier doit être au format .xlsx ou .xls.';
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token);
            exit;
        }

        require_once __DIR__ . '/../vendor/autoload.php';

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, false);
            array_shift($rows); // ligne d'en-tête

            $nbInseres  = 0;
            $nbMisAJour = 0;
            $nbIgnores  = 0;

            $stmtCheck = $this->db->prepare("
                SELECT id FROM assures
                WHERE assurance_id = :aid AND numero_police = :police
            ");
            $stmtUpsert = $this->db->prepare("
                INSERT INTO assures (assurance_id, numero_police, nom_assure, statut)
                VALUES (:aid, :police, :nom, :statut)
                ON DUPLICATE KEY UPDATE
                    nom_assure = :nom2,
                    statut     = :statut2
            ");

            foreach ($rows as $row) {
                $police    = trim((string)($row[0] ?? ''));
                $nom       = trim((string)($row[1] ?? ''));
                $statutRaw = trim((string)($row[2] ?? ''));

                if ($police === '' || $nom === '') {
                    $nbIgnores++;
                    continue;
                }

                $statut = strtolower($statutRaw) === 'suspendu' ? 'suspendu' : 'en_regle';

                $stmtCheck->execute([':aid' => $assurance['id'], ':police' => $police]);
                $exists = $stmtCheck->fetch();

                $stmtUpsert->execute([
                    ':aid'     => $assurance['id'],
                    ':police'  => $police,
                    ':nom'     => $nom,
                    ':statut'  => $statut,
                    ':nom2'    => $nom,
                    ':statut2' => $statut,
                ]);

                $exists ? $nbMisAJour++ : $nbInseres++;
            }

            $_SESSION['portal_import_result'] = [
                'inseres' => $nbInseres,
                'maj'     => $nbMisAJour,
                'ignores' => $nbIgnores,
            ];

        } catch (\Exception $e) {
            $_SESSION['portal_import_error'] = 'Erreur lors de la lecture du fichier : ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?action=imported');
        exit;
    }

    // ── VALIDER UNE DEMANDE EN ATTENTE ───────────────────
    public function valider(string $token, string $demandeId): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions] = $contexte;

        if (!$permissions['peut_valider']) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=permission');
            exit;
        }

        $codeAccord = trim($_POST['code_accord'] ?? '');
        if ($codeAccord === '') {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=code_requis');
            exit;
        }

        // Vérifie que la demande appartient bien à cette compagnie
        // et est bien en attente (empêche de revalider deux fois,
        // ou de valider la demande d'une autre compagnie).
        $stmt = $this->db->prepare("
            SELECT id, status AS demande_status FROM demandes_examens
            WHERE id = :did AND assurance_id = :aid AND assurance_statut = 'en_attente'
            LIMIT 1
        ");
        $stmt->execute([':did' => $demandeId, ':aid' => $assurance['id']]);
        $demande = $stmt->fetch();

        if (!$demande) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=introuvable');
            exit;
        }

        $this->db->prepare("
            UPDATE demandes_examens
            SET assurance_statut = 'validee', code_accord = :code
            WHERE id = :did
        ")->execute([':code' => $codeAccord, ':did' => $demandeId]);

        // Si le patient avait déjà tout payé en attendant (Cas B),
        // le paiement caissier ne se redéclenchera pas — on génère
        // donc la créance ici, au moment de la validation.
        CreanceHelper::genererCreanceAssurance($this->db, (int) $demandeId);

        header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?action=validated');
        exit;
    }

    // ── REJETER UNE DEMANDE EN ATTENTE ───────────────────
    public function rejeter(string $token, string $demandeId): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions] = $contexte;

        if (!$permissions['peut_valider']) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=permission');
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT id FROM demandes_examens
            WHERE id = :did AND assurance_id = :aid AND assurance_statut = 'en_attente'
            LIMIT 1
        ");
        $stmt->execute([':did' => $demandeId, ':aid' => $assurance['id']]);
        if (!$stmt->fetch()) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=introuvable');
            exit;
        }

        $this->db->prepare("
            UPDATE demandes_examens
            SET assurance_statut = 'rejetee'
            WHERE id = :did
        ")->execute([':did' => $demandeId]);

        header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?action=rejected');
        exit;
    }

    // ── CRÉER UN SOUS-COMPTE ──────────────────────────────
    // Uniquement accessible depuis le portail principal (un
    // sous-compte ne peut pas créer d'autres sous-comptes).
    public function creerSousCompte(string $token): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte) {
            include dirname(__DIR__) . '/views/assurance/invalid.php';
            return;
        }
        [$assurance, $permissions, $sousCompte] = $contexte;

        if ($sousCompte) {
            // Un sous-compte ne peut pas créer de sous-compte.
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=permission');
            exit;
        }

        $nom = trim($_POST['nom'] ?? '');
        if ($nom === '') {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=nom_requis');
            exit;
        }

        $peutImporter      = isset($_POST['peut_importer']) ? 1 : 0;
        $peutValider       = isset($_POST['peut_valider']) ? 1 : 0;
        $peutVoirCreances  = isset($_POST['peut_voir_creances']) ? 1 : 0;

        $nouveauToken = $this->genererToken();

        $this->db->prepare("
            INSERT INTO assurance_sous_comptes
                (assurance_id, nom, token_acces,
                 peut_importer, peut_valider, peut_voir_creances, is_active)
            VALUES
                (:aid, :nom, :token, :imp, :val, :cre, 1)
        ")->execute([
            ':aid'   => $assurance['id'],
            ':nom'   => $nom,
            ':token' => $nouveauToken,
            ':imp'   => $peutImporter,
            ':val'   => $peutValider,
            ':cre'   => $peutVoirCreances,
        ]);

        header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?action=souscompte_cree');
        exit;
    }

    // ── ACTIVER / DÉSACTIVER UN SOUS-COMPTE ──────────────
    public function toggleSousCompte(string $token, string $sousCompteId): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte || $contexte[2] !== null) {
            // Invalide, ou tentative depuis un sous-compte lui-même.
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=permission');
            exit;
        }
        [$assurance] = $contexte;

        // Vérifie que ce sous-compte appartient bien à cette compagnie
        $stmt = $this->db->prepare("
            SELECT is_active FROM assurance_sous_comptes
            WHERE id = :id AND assurance_id = :aid
        ");
        $stmt->execute([':id' => $sousCompteId, ':aid' => $assurance['id']]);
        $current = $stmt->fetchColumn();

        if ($current === false) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=introuvable');
            exit;
        }

        $this->db->prepare("
            UPDATE assurance_sous_comptes SET is_active = :status
            WHERE id = :id
        ")->execute([':status' => $current ? 0 : 1, ':id' => $sousCompteId]);

        header('Location: ' . BASE_URL . 'assurance/portal/' . $token);
        exit;
    }

    // ── SUPPRIMER UN SOUS-COMPTE ──────────────────────────
    public function supprimerSousCompte(string $token, string $sousCompteId): void {

        $contexte = $this->resolveToken($token);
        if (!$contexte || $contexte[2] !== null) {
            header('Location: ' . BASE_URL . 'assurance/portal/' . $token . '?error=permission');
            exit;
        }
        [$assurance] = $contexte;

        $this->db->prepare("
            DELETE FROM assurance_sous_comptes
            WHERE id = :id AND assurance_id = :aid
        ")->execute([':id' => $sousCompteId, ':aid' => $assurance['id']]);

        header('Location: ' . BASE_URL . 'assurance/portal/' . $token);
        exit;
    }

    // ── HELPERS ───────────────────────────────────────────

    private function genererToken(): string {
        do {
            $token = bin2hex(random_bytes(20));
            $exists = $this->db->prepare("
                SELECT id FROM assurance_sous_comptes WHERE token_acces = :t1
                UNION
                SELECT id FROM assurances WHERE token_acces = :t2
            ");
            $exists->execute([':t1' => $token, ':t2' => $token]);
        } while ($exists->fetch());

        return $token;
    }


    // Résout un token, qu'il appartienne au portail principal de la
    // compagnie ou à un sous-compte. Retourne [assurance, permissions,
    // sousCompte] ou false si le token est invalide/inactif.
    // Le portail principal a toutes les permissions à true par défaut.
    private function resolveToken(string $token): array|false {

        // 1. Essai en tant que portail principal de compagnie
        $stmt = $this->db->prepare("
            SELECT * FROM assurances
            WHERE token_acces = :token AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $assurance = $stmt->fetch();

        if ($assurance) {
            $permissions = [
                'peut_importer'      => true,
                'peut_valider'       => true,
                'peut_voir_creances' => true,
            ];
            return [$assurance, $permissions, null];
        }

        // 2. Essai en tant que sous-compte
        $stmtSc = $this->db->prepare("
            SELECT
                sc.id AS sc_id, sc.nom AS sc_nom,
                sc.peut_importer, sc.peut_valider, sc.peut_voir_creances,
                a.id AS assurance_id, a.nom AS assurance_nom,
                a.taux_defaut, a.is_active
            FROM assurance_sous_comptes sc
            JOIN assurances a ON a.id = sc.assurance_id
            WHERE sc.token_acces = :token
            AND sc.is_active = 1
            AND a.is_active = 1
            LIMIT 1
        ");
        $stmtSc->execute([':token' => $token]);
        $sousCompte = $stmtSc->fetch();

        if (!$sousCompte) {
            return false;
        }

        $permissions = [
            'peut_importer'      => (bool) $sousCompte['peut_importer'],
            'peut_valider'       => (bool) $sousCompte['peut_valider'],
            'peut_voir_creances' => (bool) $sousCompte['peut_voir_creances'],
        ];

        // On reconstruit un tableau "assurance" propre avec id = id de
        // la compagnie (assurance_id), pas l'id du sous-compte, pour
        // que tout le reste du contrôleur (filtres SQL sur assurance_id)
        // fonctionne identiquement, qu'on soit sur le portail principal
        // ou un sous-compte.
        $assuranceData = [
            'id'          => $sousCompte['assurance_id'],
            'nom'         => $sousCompte['assurance_nom'],
            'taux_defaut' => $sousCompte['taux_defaut'],
            'is_active'   => $sousCompte['is_active'],
        ];

        return [$assuranceData, $permissions, $sousCompte];
    }
}