
<?php
/**
 * Auth.php — Gestion authentification & sessions
 * Compatible table users : labo_drouot
 */
require_once __DIR__ . '/../config/database.php';

class Auth {

    /**
     * Tentative de connexion
     */
public static function login(string $login, string $password): array {

    $db = Database::getConnection();

    $stmt = $db->prepare("
        SELECT id, uuid, name, email, password, role, telephone, is_active
        FROM users
        WHERE (email = :login OR name = :login2)
        AND is_active = 1
        AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([
        ':login'  => trim($login),
        ':login2' => trim($login),
    ]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Identifiant ou mot de passe incorrect.'];
    }

    // Session
    if (session_status() === PHP_SESSION_NONE) session_start();

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_uuid']  = $user['uuid'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['logged_in']  = true;

    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")
       ->execute([':id' => $user['id']]);

    return [
        'success'  => true,
        'redirect' => self::getRedirectByRole($user['role'])
    ];
}

    /**
     * Déconnexion
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    /**
     * Vérifie si connecté
     */
    public static function check(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Vérifie le rôle — redirige si non autorisé
     */
    public static function requireRole(string|array $roles): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        $roles = is_array($roles) ? $roles : [$roles];
        if (!in_array($_SESSION['user_role'], $roles)) {
            header('Location: ' . BASE_URL . 'unauthorized');
            exit;
        }
    }

    /**
     * Redirection selon le rôle
     */
    public static function getRedirectByRole(string $role): string {
        return match(strtolower($role)) {
            'admin'      => BASE_URL . 'dashboard/admin',
            'directeur'  => BASE_URL . 'dashboard/directeur',
            'secretaire' => BASE_URL . 'dashboard/secretaire',
            'caissier'   => BASE_URL . 'dashboard/caissier',
            'technicien' => BASE_URL . 'dashboard/technicien',
            'biologiste' => BASE_URL . 'dashboard/biologiste',
            'comptable'  => BASE_URL . 'dashboard/comptable',
            'infirmier'  => BASE_URL . 'dashboard/infirmier',
            default      => BASE_URL . 'dashboard'
        };
    }

    /**
     * Retourne l'utilisateur connecté
     */
    public static function user(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return [
            'id'    => $_SESSION['user_id']    ?? null,
            'uuid'  => $_SESSION['user_uuid']  ?? '',
            'name'  => $_SESSION['user_name']  ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['user_role']  ?? '',
        ];
    }

    /**
     * Initiales pour l'avatar (ex: "Jean Dupont" → "JD")
     */
    public static function initials(): string {
        $name  = $_SESSION['user_name'] ?? 'U';
        $parts = explode(' ', trim($name));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}