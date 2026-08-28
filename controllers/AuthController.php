<?php
/**
 * AuthController — Gestion login/logout
 */
//authcontroller.php
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Mailer.php';
require_once __DIR__ . '/../config/database.php';

class AuthController {

    /**
     * Affiche la page de login
     */
    public function loginPage(): void {
        if (Auth::check()) {
            $user = Auth::user();
            header('Location: ' . Auth::getRedirectByRole($user['role']));
            exit;
        }
        $error   = $_SESSION['login_error']   ?? null;
        $success = $_SESSION['login_success'] ?? null;
        unset($_SESSION['login_error'], $_SESSION['login_success']);
        include __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Traitement du formulaire login
     */
    public function loginPost(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $login    = trim($_POST['login'] ?? '');    // ← était 'email'
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            $_SESSION['login_error'] = 'Veuillez remplir tous les champs.';
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $result = Auth::login($login, $password);   // ← passer $login

        if ($result['success']) {
            header('Location: ' . $result['redirect']);
            exit;
        } else {
            $_SESSION['login_error'] = $result['message'];
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
    }

    /**
     * Déconnexion
     */
    public function logout(): void {
        Auth::logout();
    }

    // ── MOT DE PASSE OUBLIÉ — Formulaire de demande ──────
    public function forgotPasswordPage(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $error   = $_SESSION['forgot_error']   ?? null;
        $success = $_SESSION['forgot_success'] ?? null;
        unset($_SESSION['forgot_error'], $_SESSION['forgot_success']);
        include __DIR__ . '/../views/auth/forgot-password.php';
    }

    // ── MOT DE PASSE OUBLIÉ — Envoi du lien ──────────────
    public function forgotPasswordSend(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $_SESSION['forgot_error'] = 'Veuillez saisir votre adresse email.';
            header('Location: ' . BASE_URL . 'forgot-password');
            exit;
        }

        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT id, name, email FROM users
            WHERE email = :email AND is_active = 1 AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        // Toujours afficher le même message de succès, que l'email existe
        // ou non. Révéler l'existence d'un compte via ce formulaire est
        // une fuite d'information (permettrait de deviner quels emails
        // sont enregistrés dans le système).
        $_SESSION['forgot_success'] = 'Si cette adresse existe dans notre système, un lien de réinitialisation vient de lui être envoyé.';

        if ($user) {
            // Invalider les anciens tokens non utilisés de cet utilisateur
            // avant d'en créer un nouveau, pour qu'un seul lien reste valide
            // à la fois.
            $db->prepare("
                UPDATE password_resets SET used = 1
                WHERE user_id = :uid AND used = 0
            ")->execute([':uid' => $user['id']]);

            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db->prepare("
                INSERT INTO password_resets (user_id, token, expires_at, used)
                VALUES (:uid, :token, :expires, 0)
            ")->execute([
                ':uid'     => $user['id'],
                ':token'   => $token,
                ':expires' => $expiresAt,
            ]);

            $resetLink = BASE_URL . 'reset-password/' . $token;

            $html = "
                <p>Bonjour {$user['name']},</p>
                <p>Vous avez demandé la réinitialisation de votre mot de passe sur " . APP_NAME . ".</p>
                <p><a href=\"{$resetLink}\" style=\"background:#1E88E5;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;\">Réinitialiser mon mot de passe</a></p>
                <p>Ce lien expire dans 1 heure. Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.</p>
            ";

            Mailer::send($user['email'], 'Réinitialisation de votre mot de passe', $html, $user['name']);
        }

        header('Location: ' . BASE_URL . 'forgot-password');
        exit;
    }

    // ── RÉINITIALISATION — Formulaire nouveau mot de passe ───
    public function resetPasswordPage(string $token): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT pr.id, pr.expires_at, pr.used, u.email
            FROM password_resets pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.token = :token LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $reset = $stmt->fetch();

        $valid = $reset && !$reset['used'] && strtotime($reset['expires_at']) > time();

        $error = $_SESSION['reset_error'] ?? null;
        unset($_SESSION['reset_error']);

        include __DIR__ . '/../views/auth/reset-password.php';
    }

    // ── RÉINITIALISATION — Enregistrement du nouveau mot de passe ──
    public function resetPasswordSave(string $token): void {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $password        = $_POST['password']         ?? '';
        $passwordConfirm = $_POST['password_confirm']  ?? '';

        if (strlen($password) < 6) {
            $_SESSION['reset_error'] = 'Le mot de passe doit contenir au moins 6 caractères.';
            header('Location: ' . BASE_URL . 'reset-password/' . $token);
            exit;
        }
        if ($password !== $passwordConfirm) {
            $_SESSION['reset_error'] = 'Les deux mots de passe ne correspondent pas.';
            header('Location: ' . BASE_URL . 'reset-password/' . $token);
            exit;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, user_id, expires_at, used
            FROM password_resets
            WHERE token = :token LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $reset = $stmt->fetch();

        if (!$reset || $reset['used'] || strtotime($reset['expires_at']) <= time()) {
            $_SESSION['login_error'] = 'Ce lien de réinitialisation est invalide ou a expiré. Veuillez refaire une demande.';
            header('Location: ' . BASE_URL . 'forgot-password');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $db->beginTransaction();

            $db->prepare("UPDATE users SET password = :hash WHERE id = :id")
               ->execute([':hash' => $hash, ':id' => $reset['user_id']]);

            $db->prepare("UPDATE password_resets SET used = 1 WHERE id = :id")
               ->execute([':id' => $reset['id']]);

            $db->commit();

            $_SESSION['login_success'] = 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.';
            header('Location: ' . BASE_URL . 'login');
            exit;

        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['reset_error'] = 'Une erreur est survenue. Veuillez réessayer.';
            header('Location: ' . BASE_URL . 'reset-password/' . $token);
            exit;
        }
    }
}