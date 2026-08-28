<?php
/**
 * AdminUserController — Gestion des utilisateurs
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class AdminUserController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE UTILISATEURS ───────────────────────────────
    public function index(): void {
        Auth::requireRole(['admin']);
        $user   = Auth::user();
        $search = trim($_GET['search'] ?? '');
        $role   = $_GET['role'] ?? '';

        $where  = ["deleted_at IS NULL"];
        $params = [];

        if ($search !== '') {
            $where[]       = "(name LIKE :s OR email LIKE :s2
                               OR telephone LIKE :s3)";
            $like          = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }
        if ($role !== '') {
            $where[]        = "role = :role";
            $params[':role']= $role;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE $whereStr
            ORDER BY
                CASE role
                    WHEN 'admin'      THEN 1
                    WHEN 'directeur'  THEN 2
                    WHEN 'biologiste' THEN 3
                    WHEN 'technicien' THEN 4
                    WHEN 'secretaire' THEN 5
                    WHEN 'caissier'   THEN 6
                    WHEN 'comptable'  THEN 7
                    WHEN 'infirmier'  THEN 8
                    ELSE 9
                END,
                name ASC
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Stats par rôle
        $statsRoles = $this->db->query("
            SELECT role, COUNT(*) AS total,
                   SUM(is_active) AS actifs
            FROM users
            WHERE deleted_at IS NULL
            GROUP BY role
            ORDER BY total DESC
        ")->fetchAll();

        include dirname(__DIR__) . '/views/admin/users/index.php';
    }

    // ── FORMULAIRE CRÉATION ──────────────────────────────
    public function create(): void {
        Auth::requireRole(['admin']);
        $user  = Auth::user();
        $error = $_SESSION['form_error'] ?? null;
        $old   = $_SESSION['form_old']   ?? [];
        unset($_SESSION['form_error'], $_SESSION['form_old']);
        include dirname(__DIR__) . '/views/admin/users/create.php';
    }

    // ── ENREGISTREMENT ───────────────────────────────────
    public function store(): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $name      = trim($_POST['name']      ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = $_POST['password']       ?? '';
        $password2 = $_POST['password2']      ?? '';
        $role      = $_POST['role']           ?? '';
        $telephone = trim($_POST['telephone'] ?? '');

        // Validations
        $roles_valides = [
            'admin','directeur','secretaire','caissier',
            'technicien','biologiste','comptable','infirmier'
        ];

        if (empty($name) || empty($email) ||
            empty($password) || empty($role)) {
            $_SESSION['form_error'] =
                'Tous les champs obligatoires doivent être remplis.';
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/users/create');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['form_error'] = 'Email invalide.';
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/users/create');
            exit;
        }

        if (strlen($password) < 6) {
            $_SESSION['form_error'] =
                'Le mot de passe doit contenir au moins 6 caractères.';
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/users/create');
            exit;
        }

        if ($password !== $password2) {
            $_SESSION['form_error'] =
                'Les mots de passe ne correspondent pas.';
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/users/create');
            exit;
        }

        if (!in_array($role, $roles_valides)) {
            $_SESSION['form_error'] = 'Rôle invalide.';
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/users/create');
            exit;
        }

        // Vérifier email unique
        $exists = $this->db->prepare("
            SELECT id FROM users
            WHERE email = :email AND deleted_at IS NULL
        ");
        $exists->execute([':email' => $email]);
        if ($exists->fetch()) {
            $_SESSION['form_error'] =
                'Cet email est déjà utilisé.';
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/users/create');
            exit;
        }

        try {
            $uuid = $this->generateUUID();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $this->db->prepare("
                INSERT INTO users
                    (uuid, name, email, password, role,
                     telephone, is_active, created_at)
                VALUES
                    (:uuid, :name, :email, :password, :role,
                     :tel, 1, NOW())
            ")->execute([
                ':uuid'     => $uuid,
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hash,
                ':role'     => $role,
                ':tel'      => $telephone ?: null,
            ]);

            $newId = $this->db->lastInsertId();

            // Audit
            $this->logAudit('CREATE_USER', 'users', (int)$newId);

            header('Location: ' . BASE_URL .
                'admin/users/' . $newId . '?created=1');
            exit;

        } catch (PDOException $e) {
            $_SESSION['form_error'] =
                'Erreur : ' . $e->getMessage();
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL . 'admin/users/create');
            exit;
        }
    }

    // ── FICHE UTILISATEUR ────────────────────────────────
    public function show(string $id): void {
        Auth::requireRole(['admin']);
        $user = Auth::user();

        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE id = :id AND deleted_at IS NULL LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $membre = $stmt->fetch();

        if (!$membre) {
            http_response_code(404);
            include dirname(__DIR__) . '/views/errors/404.php';
            return;
        }

        // Activités récentes
        $activites = $this->db->prepare("
            SELECT action, table_name, record_id, created_at
            FROM audit_logs
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $activites->execute([':uid' => $id]);
        $activites = $activites->fetchAll();

        $created = isset($_GET['created']);
        $updated = isset($_GET['updated']);

        include dirname(__DIR__) . '/views/admin/users/show.php';
    }

    // ── FORMULAIRE ÉDITION ───────────────────────────────
    public function edit(string $id): void {
        Auth::requireRole(['admin']);
        $user = Auth::user();

        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE id = :id AND deleted_at IS NULL LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $membre = $stmt->fetch();

        if (!$membre) {
            header('Location: ' . BASE_URL . 'admin/users');
            exit;
        }

        $error = $_SESSION['form_error'] ?? null;
        unset($_SESSION['form_error']);

        include dirname(__DIR__) . '/views/admin/users/edit.php';
    }

    // ── MISE À JOUR ──────────────────────────────────────
    public function update(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $name      = trim($_POST['name']      ?? '');
        $email     = trim($_POST['email']     ?? '');
        $role      = $_POST['role']           ?? '';
        $telephone = trim($_POST['telephone'] ?? '');

        $roles_valides = [
            'admin','directeur','secretaire','caissier',
            'technicien','biologiste','comptable','infirmier'
        ];

        if (empty($name) || empty($email) || empty($role)) {
            $_SESSION['form_error'] =
                'Les champs obligatoires sont manquants.';
            header('Location: ' . BASE_URL .
                'admin/users/' . $id . '/edit');
            exit;
        }

        if (!in_array($role, $roles_valides)) {
            $_SESSION['form_error'] = 'Rôle invalide.';
            header('Location: ' . BASE_URL .
                'admin/users/' . $id . '/edit');
            exit;
        }

        // Email unique (sauf pour cet utilisateur)
        $exists = $this->db->prepare("
            SELECT id FROM users
            WHERE email = :email
            AND id != :id
            AND deleted_at IS NULL
        ");
        $exists->execute([':email' => $email, ':id' => $id]);
        if ($exists->fetch()) {
            $_SESSION['form_error'] =
                'Cet email est déjà utilisé par un autre compte.';
            header('Location: ' . BASE_URL .
                'admin/users/' . $id . '/edit');
            exit;
        }

        $this->db->prepare("
            UPDATE users SET
                name = :name, email = :email,
                role = :role, telephone = :tel,
                updated_at = NOW()
            WHERE id = :id
        ")->execute([
            ':name'  => $name,
            ':email' => $email,
            ':role'  => $role,
            ':tel'   => $telephone ?: null,
            ':id'    => $id,
        ]);

        $this->logAudit('UPDATE_USER', 'users', (int)$id);

        header('Location: ' . BASE_URL .
            'admin/users/' . $id . '?updated=1');
        exit;
    }

    // ── ACTIVER / DÉSACTIVER ─────────────────────────────
    public function toggle(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Empêcher de désactiver son propre compte
        $currentUser = Auth::user();
        if ($currentUser['id'] == $id) {
            header('Location: ' . BASE_URL . 'admin/users');
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT is_active FROM users WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetchColumn();

        $newStatus = $current ? 0 : 1;

        $this->db->prepare("
            UPDATE users SET is_active = :status,
            updated_at = NOW() WHERE id = :id
        ")->execute([':status' => $newStatus, ':id' => $id]);

        $action = $newStatus ? 'ACTIVATE_USER' : 'DEACTIVATE_USER';
        $this->logAudit($action, 'users', (int)$id);

        header('Location: ' . BASE_URL . 'admin/users/' . $id);
        exit;
    }

    // ── RÉINITIALISER MOT DE PASSE ───────────────────────
    public function resetPassword(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $newPassword = $_POST['new_password'] ?? '';

        if (strlen($newPassword) < 6) {
            $_SESSION['form_error'] =
                'Le mot de passe doit contenir au moins 6 caractères.';
            header('Location: ' . BASE_URL .
                'admin/users/' . $id . '/edit');
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->db->prepare("
            UPDATE users SET password = :hash,
            updated_at = NOW() WHERE id = :id
        ")->execute([':hash' => $hash, ':id' => $id]);

        $this->logAudit('RESET_PASSWORD', 'users', (int)$id);

        header('Location: ' . BASE_URL .
            'admin/users/' . $id . '?updated=1');
        exit;
    }

    // ── SUPPRESSION LOGIQUE ──────────────────────────────
    public function delete(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Empêcher de supprimer son propre compte
        $currentUser = Auth::user();
        if ($currentUser['id'] == $id) {
            header('Location: ' . BASE_URL . 'admin/users');
            exit;
        }

        $this->db->prepare("
            UPDATE users SET
                deleted_at = NOW(),
                is_active  = 0,
                updated_at = NOW()
            WHERE id = :id
        ")->execute([':id' => $id]);

        $this->logAudit('DELETE_USER', 'users', (int)$id);

        header('Location: ' . BASE_URL .
            'admin/users?deleted=1');
        exit;
    }

    // ── HELPERS ──────────────────────────────────────────
    private function generateUUID(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0,0xffff), mt_rand(0,0xffff),
            mt_rand(0,0xffff),
            mt_rand(0,0x0fff)|0x4000,
            mt_rand(0,0x3fff)|0x8000,
            mt_rand(0,0xffff), mt_rand(0,0xffff),
            mt_rand(0,0xffff)
        );
    }

    private function logAudit(
        string $action,
        string $table,
        int $recordId
    ): void {
        $userId = Auth::user()['id'];
        $this->db->prepare("
            INSERT INTO audit_logs
                (user_id, action, table_name, record_id)
            VALUES (:uid, :action, :table, :rid)
        ")->execute([
            ':uid'    => $userId,
            ':action' => $action,
            ':table'  => $table,
            ':rid'    => $recordId,
        ]);
    }
}