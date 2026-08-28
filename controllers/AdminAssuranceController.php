<?php
/**
 * AdminAssuranceController — Gestion des compagnies d'assurance
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class AdminAssuranceController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE + FORMULAIRE AJOUT ─────────────────────────
    public function index(): void {
        Auth::requireRole(['admin', 'directeur']);
        $user = Auth::user();

        $search = trim($_GET['search'] ?? '');

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]       = "(nom LIKE :s OR contact_nom LIKE :s2)";
            $like          = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                a.*,
                COUNT(DISTINCT ate.id) AS nb_accords,
                COUNT(DISTINCT d.id) AS nb_demandes,
                COALESCE(SUM(
                    CASE WHEN c.statut = 'due' THEN c.montant_creance ELSE 0 END
                ), 0) AS creances_dues,
                COALESCE(SUM(
                    CASE WHEN c.statut = 'payee' THEN c.montant_creance ELSE 0 END
                ), 0) AS creances_payees
            FROM assurances a
            LEFT JOIN assurance_taux_examens ate ON ate.assurance_id = a.id
            LEFT JOIN demandes_examens d ON d.assurance_id = a.id
            LEFT JOIN creances_assurance c ON c.assurance_id = a.id
            WHERE $whereStr
            GROUP BY a.id
            ORDER BY a.nom ASC
        ");
        $stmt->execute($params);
        $assurances = $stmt->fetchAll();

        // Détail des créances par assurance (pour affichage dépliable)
        $creancesParAssurance = [];
        if (!empty($assurances)) {
            $ids = array_column($assurances, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmtCre = $this->db->prepare("
                SELECT
                    c.id, c.assurance_id, c.demande_id,
                    c.montant_base, c.taux_applique,
                    c.montant_creance, c.statut,
                    c.created_at, c.paid_at,
                    CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                    d.reference
                FROM creances_assurance c
                JOIN demandes_examens d ON c.demande_id = d.id
                JOIN patients p ON d.patient_id = p.id
                WHERE c.assurance_id IN ($placeholders)
                ORDER BY c.created_at DESC
            ");
            $stmtCre->execute($ids);
            foreach ($stmtCre->fetchAll() as $c) {
                $creancesParAssurance[$c['assurance_id']][] = $c;
            }
        }

        // Stats globales
        $stats = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(is_active) AS actifs,
                (SELECT COALESCE(SUM(montant_creance),0)
                 FROM creances_assurance WHERE statut = 'due') AS total_du
            FROM assurances
        ")->fetch();

        $success = isset($_GET['saved']);
        $error   = $_SESSION['assurance_error'] ?? null;
        unset($_SESSION['assurance_error']);

        include dirname(__DIR__) . '/views/admin/assurances/index.php';
    }

    // ── ENREGISTREMENT NOUVELLE ASSURANCE ────────────────
    public function store(): void {
        Auth::requireRole(['admin', 'directeur']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $nom        = trim($_POST['nom']         ?? '');
        $contactNom = trim($_POST['contact_nom'] ?? '');
        $telephone  = trim($_POST['telephone']   ?? '');
        $email      = trim($_POST['email']       ?? '');
        $tauxDefaut = (float)($_POST['taux_defaut'] ?? 0);

        if (empty($nom)) {
            $_SESSION['assurance_error'] = 'Le nom de la compagnie est obligatoire.';
            header('Location: ' . BASE_URL . 'admin/assurances');
            exit;
        }

        if ($tauxDefaut < 0 || $tauxDefaut > 100) {
            $_SESSION['assurance_error'] = 'Le taux doit être entre 0 et 100%.';
            header('Location: ' . BASE_URL . 'admin/assurances');
            exit;
        }

        $token = $this->genererToken();

        $this->db->prepare("
            INSERT INTO assurances
                (nom, contact_nom, telephone, email, taux_defaut, token_acces, is_active)
            VALUES
                (:nom, :contact, :tel, :email, :taux, :token, 1)
        ")->execute([
            ':nom'     => $nom,
            ':contact' => $contactNom ?: null,
            ':tel'     => $telephone ?: null,
            ':email'   => $email ?: null,
            ':taux'    => $tauxDefaut,
            ':token'   => $token,
        ]);

        $newId = $this->db->lastInsertId();
        $this->logAudit('CREATE_ASSURANCE', 'assurances', (int)$newId);

        header('Location: ' . BASE_URL . 'admin/assurances?saved=1');
        exit;
    }

    // ── MODIFIER ASSURANCE (inline) ──────────────────────
    public function update(string $id): void {
        Auth::requireRole(['admin', 'directeur']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $nom        = trim($_POST['nom']         ?? '');
        $contactNom = trim($_POST['contact_nom'] ?? '');
        $telephone  = trim($_POST['telephone']   ?? '');
        $email      = trim($_POST['email']       ?? '');
        $tauxDefaut = (float)($_POST['taux_defaut'] ?? 0);

        if (empty($nom)) {
            $_SESSION['assurance_error'] = 'Le nom de la compagnie est obligatoire.';
            header('Location: ' . BASE_URL . 'admin/assurances');
            exit;
        }

        if ($tauxDefaut < 0 || $tauxDefaut > 100) {
            $_SESSION['assurance_error'] = 'Le taux doit être entre 0 et 100%.';
            header('Location: ' . BASE_URL . 'admin/assurances');
            exit;
        }

        $this->db->prepare("
            UPDATE assurances SET
                nom = :nom, contact_nom = :contact,
                telephone = :tel, email = :email,
                taux_defaut = :taux
            WHERE id = :id
        ")->execute([
            ':nom'     => $nom,
            ':contact' => $contactNom ?: null,
            ':tel'     => $telephone ?: null,
            ':email'   => $email ?: null,
            ':taux'    => $tauxDefaut,
            ':id'      => $id,
        ]);

        $this->logAudit('UPDATE_ASSURANCE', 'assurances', (int)$id);

        header('Location: ' . BASE_URL . 'admin/assurances?saved=1');
        exit;
    }

    // ── RÉGÉNÉRER LE TOKEN ────────────────────────────────
    public function regenererToken(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $token = $this->genererToken();

        $this->db->prepare("
            UPDATE assurances SET token_acces = :token WHERE id = :id
        ")->execute([':token' => $token, ':id' => $id]);

        $this->logAudit('REGEN_TOKEN_ASSURANCE', 'assurances', (int)$id);

        header('Location: ' . BASE_URL . 'admin/assurances?saved=1');
        exit;
    }

    // ── TOGGLE ACTIF ──────────────────────────────────────
    public function toggle(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $stmt = $this->db->prepare("SELECT is_active FROM assurances WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetchColumn();

        $this->db->prepare("
            UPDATE assurances SET is_active = :status WHERE id = :id
        ")->execute([
            ':status' => $current ? 0 : 1,
            ':id'     => $id,
        ]);

        $this->logAudit('TOGGLE_ASSURANCE', 'assurances', (int)$id);

        header('Location: ' . BASE_URL . 'admin/assurances');
        exit;
    }

    // ── SUPPRESSION ───────────────────────────────────────
    public function delete(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $used = $this->db->prepare("
            SELECT COUNT(*) FROM demandes_examens WHERE assurance_id = :id
        ");
        $used->execute([':id' => $id]);

        if ($used->fetchColumn() > 0) {
            $this->db->prepare("
                UPDATE assurances SET is_active = 0 WHERE id = :id
            ")->execute([':id' => $id]);
        } else {
            $this->db->prepare("
                DELETE FROM assurances WHERE id = :id
            ")->execute([':id' => $id]);
        }

        $this->logAudit('DELETE_ASSURANCE', 'assurances', (int)$id);

        header('Location: ' . BASE_URL . 'admin/assurances?saved=1');
        exit;
    }

    // ── GESTION DES TAUX PAR EXAMEN ───────────────────────
    public function taux(string $id): void {
        Auth::requireRole(['admin', 'directeur']);
        $user = Auth::user();

        $stmt = $this->db->prepare("SELECT * FROM assurances WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $assurance = $stmt->fetch();

        if (!$assurance) {
            header('Location: ' . BASE_URL . 'admin/assurances');
            exit;
        }

        // Tous les examens actifs avec leur taux spécifique s'il existe
        $examens = $this->db->prepare("
            SELECT
                e.id, e.code, e.nom, e.categorie, e.prix,
                ate.id AS taux_id, ate.taux
            FROM examens e
            LEFT JOIN assurance_taux_examens ate
                ON ate.examen_id = e.id AND ate.assurance_id = :aid
            WHERE e.is_active = 1
            ORDER BY e.categorie, e.nom
        ");
        $examens->execute([':aid' => $id]);
        $examensRaw = $examens->fetchAll();

        // Grouper par catégorie
        $examensGroupes = [];
        foreach ($examensRaw as $e) {
            $cat = $e['categorie'] ?? 'Autres';
            $examensGroupes[$cat][] = $e;
        }

        $success = isset($_GET['saved']);

        include dirname(__DIR__) . '/views/admin/assurances/taux.php';
    }

    // ── SAUVEGARDER LES TAUX (upsert en masse) ───────────
    public function storeTaux(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $taux = $_POST['taux'] ?? []; // [examen_id => valeur ou '']

        foreach ($taux as $examenId => $valeur) {
            $valeur = trim((string)$valeur);

            if ($valeur === '') {
                // Champ vide = pas d'accord spécifique, retombe sur taux_defaut
                $this->db->prepare("
                    DELETE FROM assurance_taux_examens
                    WHERE assurance_id = :aid AND examen_id = :eid
                ")->execute([':aid' => $id, ':eid' => $examenId]);
                continue;
            }

            $valeurFloat = (float) $valeur;
            if ($valeurFloat < 0 || $valeurFloat > 100) {
                continue; // ignore les valeurs hors plage
            }

            $this->db->prepare("
                INSERT INTO assurance_taux_examens (assurance_id, examen_id, taux)
                VALUES (:aid, :eid, :taux)
                ON DUPLICATE KEY UPDATE taux = :taux2
            ")->execute([
                ':aid'   => $id,
                ':eid'   => $examenId,
                ':taux'  => $valeurFloat,
                ':taux2' => $valeurFloat,
            ]);
        }

        $this->logAudit('UPDATE_TAUX_ASSURANCE', 'assurances', (int)$id);

        header('Location: ' . BASE_URL . 'admin/assurances/' . $id . '/taux?saved=1');
        exit;
    }

    // ── MARQUER UNE CRÉANCE COMME PAYÉE (individuel) ─────
    public function payerCreance(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $stmt = $this->db->prepare("
            SELECT id, statut FROM creances_assurance WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $creance = $stmt->fetch();

        if ($creance && $creance['statut'] === 'due') {
            $this->db->prepare("
                UPDATE creances_assurance
                SET statut = 'payee', paid_at = NOW()
                WHERE id = :id
            ")->execute([':id' => $id]);

            $this->logAudit('PAY_CREANCE', 'creances_assurance', (int)$id);
        }

        header('Location: ' . BASE_URL . 'admin/assurances?saved=1');
        exit;
    }

    // ── PAYER TOUTES LES CRÉANCES DUES D'UNE ASSURANCE ───
    public function payerToutesCreances(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $this->db->prepare("
            UPDATE creances_assurance
            SET statut = 'payee', paid_at = NOW()
            WHERE assurance_id = :aid AND statut = 'due'
        ")->execute([':aid' => $id]);

        $this->logAudit('PAY_ALL_CREANCES', 'assurances', (int)$id);

        header('Location: ' . BASE_URL . 'admin/assurances?saved=1');
        exit;
    }

    // ── IMPORT FICHIER ASSURÉS — FORMULAIRE ──────────────
    public function assures(string $id): void {
        Auth::requireRole(['admin', 'directeur']);
        $user = Auth::user();

        $stmt = $this->db->prepare("SELECT * FROM assurances WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $assurance = $stmt->fetch();

        if (!$assurance) {
            header('Location: ' . BASE_URL . 'admin/assurances');
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $where  = ['assurance_id = :aid'];
        $params = [':aid' => $id];

        if ($search !== '') {
            $where[]      = "(numero_police LIKE :s OR nom_assure LIKE :s2)";
            $like         = '%' . $search . '%';
            $params[':s'] = $like;
            $params[':s2']= $like;
        }
        $whereStr = implode(' AND ', $where);

        $stmtAssures = $this->db->prepare("
            SELECT * FROM assures
            WHERE $whereStr
            ORDER BY nom_assure ASC
            LIMIT 300
        ");
        $stmtAssures->execute($params);
        $assures = $stmtAssures->fetchAll();

        $totalAssures = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(statut = 'en_regle') AS en_regle,
                SUM(statut = 'suspendu') AS suspendus
            FROM assures WHERE assurance_id = :aid
        ");
        $totalAssures->execute([':aid' => $id]);
        $statsAssures = $totalAssures->fetch();

        $success = isset($_GET['imported']);
        $importResult = $_SESSION['import_result'] ?? null;
        $importError  = $_SESSION['import_error'] ?? null;
        unset($_SESSION['import_result'], $_SESSION['import_error']);

        include dirname(__DIR__) . '/views/admin/assurances/assures.php';
    }

    // ── IMPORT FICHIER ASSURÉS — TRAITEMENT ──────────────
    public function storeImportAssures(string $id): void {
        Auth::requireRole(['admin', 'directeur']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['import_error'] = 'Aucun fichier valide reçu.';
            header('Location: ' . BASE_URL . 'admin/assurances/' . $id . '/assures');
            exit;
        }

        $tmpPath = $_FILES['fichier']['tmp_name'];
        $ext     = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls'])) {
            $_SESSION['import_error'] = 'Le fichier doit être au format .xlsx ou .xls.';
            header('Location: ' . BASE_URL . 'admin/assurances/' . $id . '/assures');
            exit;
        }

        require_once __DIR__ . '/../../vendor/autoload.php';

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, false);

            // Ligne 0 = en-têtes, on les ignore
            array_shift($rows);

            $nbInseres   = 0;
            $nbMisAJour  = 0;
            $nbIgnores   = 0;

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
                $police = trim((string)($row[0] ?? ''));
                $nom    = trim((string)($row[1] ?? ''));
                $statutRaw = trim((string)($row[2] ?? ''));

                if ($police === '' || $nom === '') {
                    $nbIgnores++;
                    continue;
                }

                $statut = strtolower($statutRaw) === 'suspendu' ? 'suspendu' : 'en_regle';

                $stmtCheck->execute([':aid' => $id, ':police' => $police]);
                $exists = $stmtCheck->fetch();

                $stmtUpsert->execute([
                    ':aid'     => $id,
                    ':police'  => $police,
                    ':nom'     => $nom,
                    ':statut'  => $statut,
                    ':nom2'    => $nom,
                    ':statut2' => $statut,
                ]);

                if ($exists) {
                    $nbMisAJour++;
                } else {
                    $nbInseres++;
                }
            }

            $_SESSION['import_result'] = [
                'inseres'  => $nbInseres,
                'maj'      => $nbMisAJour,
                'ignores'  => $nbIgnores,
            ];

            $this->logAudit('IMPORT_ASSURES', 'assurances', (int)$id);

        } catch (\Exception $e) {
            $_SESSION['import_error'] = 'Erreur lors de la lecture du fichier : ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . 'admin/assurances/' . $id . '/assures?imported=1');
        exit;
    }

    // ── SUPPRIMER UN ASSURÉ ────────────────────────────────
    public function deleteAssure(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $assuranceId = $_POST['assurance_id'] ?? 0;

        $this->db->prepare("DELETE FROM assures WHERE id = :id")
            ->execute([':id' => $id]);

        header('Location: ' . BASE_URL . 'admin/assurances/' . $assuranceId . '/assures?imported=1');
        exit;
    }

    // ── HELPERS ───────────────────────────────────────────
    private function genererToken(): string {
        do {
            $token = bin2hex(random_bytes(20));
            $exists = $this->db->prepare("SELECT id FROM assurances WHERE token_acces = :t");
            $exists->execute([':t' => $token]);
        } while ($exists->fetch());

        return $token;
    }

    private function logAudit(string $action, string $table, int $recordId): void {
        $userId = Auth::user()['id'];
        $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id)
            VALUES (:uid, :action, :table, :rid)
        ")->execute([
            ':uid'    => $userId,
            ':action' => $action,
            ':table'  => $table,
            ':rid'    => $recordId,
        ]);
    }
}