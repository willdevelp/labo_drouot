<?php
/**
 * ProfileController — Mon profil (commun à tous les rôles)
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class ProfileController {

    private PDO $db;

    // Rôles autorisés à consulter/modifier leur propre profil.
    private const ROLES_AUTORISES = [
        'admin', 'secretaire', 'caissier', 'technicien',
        'biologiste', 'comptable', 'directeur', 'infirmier',
    ];

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── AFFICHER LE PROFIL ────────────────────────────────
    public function index(): void {
        Auth::requireRole(self::ROLES_AUTORISES);
        $sessionUser = Auth::user();

        $stmt = $this->db->prepare("
            SELECT id, uuid, name, email, telephone, role,
                   is_active, last_login, created_at
            FROM users
            WHERE id = :id AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $sessionUser['id']]);
        $profil = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profil) {
            // Le compte a été supprimé/désactivé entre-temps.
            header('Location: ' . BASE_URL . 'logout');
            exit;
        }

        $successInfo = $_SESSION['profil_success_info'] ?? null;
        $errorInfo   = $_SESSION['profil_error_info'] ?? null;
        $successPwd  = $_SESSION['profil_success_pwd'] ?? null;
        $errorPwd    = $_SESSION['profil_error_pwd'] ?? null;
        unset(
            $_SESSION['profil_success_info'], $_SESSION['profil_error_info'],
            $_SESSION['profil_success_pwd'], $_SESSION['profil_error_pwd']
        );

        include __DIR__ . '/../views/profil/index.php';
    }

    // ── MODIFIER NOM / EMAIL / TÉLÉPHONE ─────────────────
    public function updateInfo(): void {
        Auth::requireRole(self::ROLES_AUTORISES);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $user = Auth::user();

        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');

        // Validation basique
        if ($name === '' || $email === '') {
            $_SESSION['profil_error_info'] = 'Le nom et l\'email sont obligatoires.';
            header('Location: ' . BASE_URL . 'profil');
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['profil_error_info'] = 'Adresse email invalide.';
            header('Location: ' . BASE_URL . 'profil');
            exit;
        }

        // Unicité de l'email (hors son propre compte)
        $checkStmt = $this->db->prepare("
            SELECT id FROM users
            WHERE email = :email AND id != :id AND deleted_at IS NULL
            LIMIT 1
        ");
        $checkStmt->execute([':email' => $email, ':id' => $user['id']]);
        if ($checkStmt->fetch()) {
            $_SESSION['profil_error_info'] = 'Cet email est déjà utilisé par un autre compte.';
            header('Location: ' . BASE_URL . 'profil');
            exit;
        }

        try {
            $this->db->prepare("
                UPDATE users
                SET name = :name, email = :email, telephone = :tel, updated_at = NOW()
                WHERE id = :id
            ")->execute([
                ':name'  => $name,
                ':email' => $email,
                ':tel'   => $telephone ?: null,
                ':id'    => $user['id'],
            ]);

            // Audit
            $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address)
                VALUES (:uid, 'UPDATE', 'users', :rid, :ip)
            ")->execute([
                ':uid' => $user['id'],
                ':rid' => $user['id'],
                ':ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            // Rafraîchir la session pour que le header (nom affiché) soit à jour
            // sans attendre une reconnexion.
            if (isset($_SESSION['user'])) {
                $_SESSION['user']['name']  = $name;
                $_SESSION['user']['email'] = $email;
            }

            $_SESSION['profil_success_info'] = 'Vos informations ont été mises à jour.';
        } catch (PDOException $e) {
            $_SESSION['profil_error_info'] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . 'profil');
        exit;
    }

    // ── MODIFIER LE MOT DE PASSE ──────────────────────────
    public function updatePassword(): void {
        Auth::requireRole(self::ROLES_AUTORISES);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $user = Auth::user();

        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $_SESSION['profil_error_pwd'] = 'Tous les champs sont obligatoires.';
            header('Location: ' . BASE_URL . 'profil');
            exit;
        }

        if (strlen($new) < 8) {
            $_SESSION['profil_error_pwd'] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
            header('Location: ' . BASE_URL . 'profil');
            exit;
        }

        if ($new !== $confirm) {
            $_SESSION['profil_error_pwd'] = 'La confirmation ne correspond pas au nouveau mot de passe.';
            header('Location: ' . BASE_URL . 'profil');
            exit;
        }

        // Vérifier le mot de passe actuel
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !password_verify($current, $row['password'])) {
            $_SESSION['profil_error_pwd'] = 'Mot de passe actuel incorrect.';
            header('Location: ' . BASE_URL . 'profil');
            exit;
        }

        try {
            $hash = password_hash($new, PASSWORD_DEFAULT);

            $this->db->prepare("
                UPDATE users SET password = :pwd, updated_at = NOW() WHERE id = :id
            ")->execute([':pwd' => $hash, ':id' => $user['id']]);

            // Audit — on ne journalise jamais le mot de passe lui-même
            $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address)
                VALUES (:uid, 'PASSWORD_CHANGE', 'users', :rid, :ip)
            ")->execute([
                ':uid' => $user['id'],
                ':rid' => $user['id'],
                ':ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $_SESSION['profil_success_pwd'] = 'Mot de passe modifié avec succès.';
        } catch (PDOException $e) {
            $_SESSION['profil_error_pwd'] = 'Erreur lors du changement de mot de passe : ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . 'profil');
        exit;
    }
}
