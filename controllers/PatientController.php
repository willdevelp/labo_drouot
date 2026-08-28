<?php
/**
 * PatientController — Gestion des patients
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class PatientController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE PATIENTS (paginée) ─────────────────────────
    public function index(): void {
        Auth::requireRole(['secretaire', 'admin']);
        $user = Auth::user();

        $search = trim($_GET['search'] ?? '');

        // Taille de page contrôlée (25/50/100 uniquement) et numéro
        // de page, tous deux repris depuis le querystring.
        $allowedPerPage = [25, 50, 100];
        $perPage = (int) ($_GET['per_page'] ?? 25);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 25;
        }
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        $where  = [];
        $params = [];
        if ($search !== '') {
            $where[] = "(nom LIKE :s OR prenom LIKE :s2 OR telephone LIKE :s3 OR uuid LIKE :s4)";
            $like = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
        }
        $whereStr = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total pour la pagination (mêmes filtres, sans LIMIT)
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM patients $whereStr");
        $countStmt->execute($params);
        $totalPatients = (int) $countStmt->fetchColumn();
        $totalPages    = max(1, (int) ceil($totalPatients / $perPage));

        // Si on demande une page hors bornes (ex: filtre réduit le total),
        // on se recale sur la dernière page valide plutôt que de renvoyer
        // une liste vide sans explication.
        if ($page > $totalPages) {
            $page   = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $stmt = $this->db->prepare("
            SELECT p.*, a.nom AS assurance_nom
            FROM patients p
            LEFT JOIN assurances a ON a.id = p.assurance_id
            $whereStr
            ORDER BY p.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $patients = $stmt->fetchAll();

        include __DIR__ . '/../views/secretaire/patients/index.php';
    }

    // ── FORMULAIRE CRÉATION ──────────────────────────────
    public function create(): void {
        Auth::requireRole(['secretaire', 'admin']);
        $user  = Auth::user();
        $error = $_SESSION['form_error'] ?? null;
        $old   = $_SESSION['form_old']   ?? [];
        unset($_SESSION['form_error'], $_SESSION['form_old']);

        // Liste des assurances actives pour l'autocomplete côté vue
        $assurances = $this->db->query("
            SELECT id, nom FROM assurances
            WHERE is_active = 1
            ORDER BY nom
        ")->fetchAll();

        include __DIR__ . '/../views/secretaire/patients/create.php';
    }

    // ── ENREGISTREMENT PATIENT ───────────────────────────
    public function store(): void {
        Auth::requireRole(['secretaire', 'admin']);

        if (session_status() === PHP_SESSION_NONE) session_start();

        // Validation
        $nom       = trim($_POST['nom']       ?? '');
        $prenom    = trim($_POST['prenom']    ?? '');
        $sexe      = $_POST['sexe']           ?? '';
        $dob       = $_POST['date_naissance'] ?? '';
        $telephone = trim($_POST['telephone'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $adresse   = trim($_POST['adresse']   ?? '');

        // Assurance : la case "le patient a une assurance" doit être
        // cochée ET une compagnie valide sélectionnée dans la liste
        // (assurance_id renseigné par l'autocomplete côté JS).
        $hasAssurance = !empty($_POST['assurance_id']);
        $assuranceId  = $hasAssurance ? (int) $_POST['assurance_id'] : null;
        $numAssurance = $hasAssurance ? trim($_POST['num_assurance'] ?? '') : null;

        if (empty($nom) || empty($prenom) || empty($sexe) || empty($telephone)) {
            $_SESSION['form_error'] = 'Les champs Nom, Prénom, Sexe et Téléphone sont obligatoires.';
            $_SESSION['form_old']   = $_POST;
            header('Location: ' . BASE_URL . 'secretaire/patients/create');
            exit;
        }

        // Vérifier que l'assurance choisie existe bel et bien et est active.
        // Si elle est invalide (manipulation du champ hidden, assurance
        // désactivée entre-temps...), on ignore le champ plutôt que de
        // bloquer la création du patient.
        if ($assuranceId) {
            $chkAss = $this->db->prepare("
                SELECT id FROM assurances WHERE id = :id AND is_active = 1 LIMIT 1
            ");
            $chkAss->execute([':id' => $assuranceId]);
            if (!$chkAss->fetch()) {
                $assuranceId  = null;
                $numAssurance = null;
            }
        }

        // Générer UUID unique
        $uuid = $this->generateUUID();

        // Générer token portail patient
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));

        // Gérer photo
        $photoPath = null;
        if (!empty($_FILES['photo']['name'])) {
            $photoPath = $this->uploadPhoto($_FILES['photo'], $uuid);
        }

        // Générer QR Code (données du patient)
        $qrData = json_encode([
            'uuid'      => $uuid,
            'nom'       => $nom . ' ' . $prenom,
            'telephone' => $telephone,
            'token'     => $token,
        ]);

        try {
            // Insérer patient
            $stmt = $this->db->prepare("
                INSERT INTO patients
                    (uuid, nom, prenom, sexe, date_naissance,
                     telephone, email, adresse, photo,
                     assurance_id, num_assurance,
                     qr_code, created_at)
                VALUES
                    (:uuid, :nom, :prenom, :sexe, :dob,
                     :tel, :email, :adresse, :photo,
                     :aid, :numass,
                     :qr, NOW())
            ");
            $stmt->execute([
                ':uuid'    => $uuid,
                ':nom'     => $nom,
                ':prenom'  => $prenom,
                ':sexe'    => $sexe,
                ':dob'     => $dob ?: null,
                ':tel'     => $telephone,
                ':email'   => $email ?: null,
                ':adresse' => $adresse ?: null,
                ':photo'   => $photoPath,
                ':aid'     => $assuranceId,
                ':numass'  => $numAssurance ?: null,
                ':qr'      => $qrData,
            ]);

            $patientId = $this->db->lastInsertId();

            // Insérer token portail
            $this->db->prepare("
                INSERT INTO patient_tokens
                    (patient_id, token, expires_at, is_active)
                VALUES (:pid, :token, :expires, 1)
            ")->execute([
                ':pid'     => $patientId,
                ':token'   => $token,
                ':expires' => $expiresAt,
            ]);

            // Log audit
            $this->logAudit('CREATE', 'patients', $patientId);

            // Rediriger vers fiche patient
            header('Location: ' . BASE_URL . 'secretaire/patients/' . $patientId . '?created=1');
            exit;

        } catch (PDOException $e) {
            $_SESSION['form_error'] = 'Erreur lors de la création : ' . $e->getMessage();
            $_SESSION['form_old']   = $_POST;
            header('Location: ' . BASE_URL . 'secretaire/patients/create');
            exit;
        }
    }

    // ── FICHE PATIENT ────────────────────────────────────
    public function show(string $id): void {
        Auth::requireRole(['secretaire', 'admin', 'technicien', 'biologiste']);
        $user = Auth::user();

        $stmt = $this->db->prepare("
            SELECT p.*, a.nom AS assurance_nom, a.taux_defaut AS assurance_taux_defaut
            FROM patients p
            LEFT JOIN assurances a ON a.id = p.assurance_id
            WHERE p.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $patient = $stmt->fetch();

        if (!$patient) {
            http_response_code(404);
            include __DIR__ . '/../views/errors/404.php';
            return;
        }

        // Token actif
        $tokenStmt = $this->db->prepare("
            SELECT * FROM patient_tokens
            WHERE patient_id = :pid
            AND is_active = 1
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $tokenStmt->execute([':pid' => $id]);
        $tokenData = $tokenStmt->fetch();

        // Historique demandes
        $demandes = $this->db->prepare("
            SELECT d.*, GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens
            FROM demandes_examens d
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            WHERE d.patient_id = :pid
            GROUP BY d.id
            ORDER BY d.created_at DESC
        ");
        $demandes->execute([':pid' => $id]);
        $historique = $demandes->fetchAll();

        $created = isset($_GET['created']);

        include __DIR__ . '/../views/secretaire/patients/show.php';
    }

    // ── ÉDITION ──────────────────────────────────────────
    public function edit(string $id): void {
        Auth::requireRole(['secretaire', 'admin']);
        $user = Auth::user();

        $stmt = $this->db->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $patient = $stmt->fetch();

        if (!$patient) {
            header('Location: ' . BASE_URL . 'secretaire/patients');
            exit;
        }

        // Assurances actives pour l'autocomplete (édition = même logique
        // que la création, avec pré-remplissage de l'assurance actuelle)
        $assurances = $this->db->query("
            SELECT id, nom FROM assurances
            WHERE is_active = 1
            ORDER BY nom
        ")->fetchAll();

        $error = $_SESSION['form_error'] ?? null;
        unset($_SESSION['form_error']);
        include __DIR__ . '/../views/secretaire/patients/edit.php';
    }

    // ── MISE À JOUR ──────────────────────────────────────
    public function update(string $id): void {
        Auth::requireRole(['secretaire', 'admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $nom       = trim($_POST['nom']       ?? '');
        $prenom    = trim($_POST['prenom']    ?? '');
        $sexe      = $_POST['sexe']           ?? '';
        $dob       = $_POST['date_naissance'] ?? '';
        $telephone = trim($_POST['telephone'] ?? '');
        $email     = trim($_POST['email']     ?? '');
        $adresse   = trim($_POST['adresse']   ?? '');

        $hasAssurance = !empty($_POST['assurance_id']);
        $assuranceId  = $hasAssurance ? (int) $_POST['assurance_id'] : null;
        $numAssurance = $hasAssurance ? trim($_POST['num_assurance'] ?? '') : null;

        if (empty($nom) || empty($prenom) || empty($sexe) || empty($telephone)) {
            $_SESSION['form_error'] = 'Les champs obligatoires sont manquants.';
            header('Location: ' . BASE_URL . 'secretaire/patients/' . $id . '/edit');
            exit;
        }

        if ($assuranceId) {
            $chkAss = $this->db->prepare("
                SELECT id FROM assurances WHERE id = :id AND is_active = 1 LIMIT 1
            ");
            $chkAss->execute([':id' => $assuranceId]);
            if (!$chkAss->fetch()) {
                $assuranceId  = null;
                $numAssurance = null;
            }
        }

        $this->db->prepare("
            UPDATE patients SET
                nom = :nom, prenom = :prenom, sexe = :sexe,
                date_naissance = :dob, telephone = :tel,
                email = :email, adresse = :adresse,
                assurance_id = :aid, num_assurance = :numass
            WHERE id = :id
        ")->execute([
            ':nom'     => $nom,      ':prenom' => $prenom,
            ':sexe'    => $sexe,     ':dob'    => $dob ?: null,
            ':tel'     => $telephone, ':email' => $email ?: null,
            ':adresse' => $adresse ?: null,
            ':aid'     => $assuranceId,
            ':numass'  => $numAssurance ?: null,
            ':id'      => $id,
        ]);

        $this->logAudit('UPDATE', 'patients', (int)$id);

        header('Location: ' . BASE_URL . 'secretaire/patients/' . $id . '?updated=1');
        exit;
    }

    // ── HELPERS PRIVÉS ───────────────────────────────────

    private function generateUUID(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function uploadPhoto(array $file, string $uuid): ?string {
        $uploadDir = __DIR__ . '/../public/uploads/patients/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed)) return null;
        if ($file['size'] > 2 * 1024 * 1024) return null;

        $filename = 'patient_' . $uuid . '.' . $ext;
        $dest     = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'uploads/patients/' . $filename;
        }
        return null;
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