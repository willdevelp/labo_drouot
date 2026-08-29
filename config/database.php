<?php
/**
 * Configuration base de données — Laboratoire Drouot
 * Connexion PDO avec gestion d'erreurs
 *
 * Toutes les valeurs peuvent être surchargées par des variables
 * d'environnement (utilisé en production sur Render — voir Dockerfile).
 * En local (WAMP/XAMPP sans variables d'environnement définies), les
 * valeurs par défaut ci-dessous s'appliquent automatiquement.
 */
function env(string $key, string $default): string {
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

define('DB_HOST', env('DB_HOST'));
define('DB_NAME', env('DB_NAME'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));
define('DB_CHARSET', 'utf8mb4');

// Informations de l'application
define('APP_NAME', 'Labo Drouot');
define('APP_VERSION', '1.0.0');
// Le dossier /public n'apparaît jamais dans les URLs visibles : le
// .htaccess à la racine du projet réécrit déjà en interne vers
// public/$1 pour toute requête. Mettre /public/ ici produirait des
// liens invalides (Apache chercherait public/public/...).
// Sur Railway/Render (voir Dockerfile), le DocumentRoot pointe directement
// sur /public : BASE_URL doit alors être l'URL fournie par la plateforme
// telle quelle, sans suffixe /public/.
define('BASE_URL', env('BASE_URL'));

// Configuration SMTP (envoi d'emails — réinitialisation mot de passe, etc.)
// À adapter avec tes vrais identifiants une fois le compte email créé.
// Pour Gmail : activer la validation en 2 étapes puis générer un
// "mot de passe d'application" dédié (Google Account → Sécurité →
// Mots de passe des applications) — le mot de passe normal du compte
// ne fonctionne plus pour l'envoi SMTP.
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_USER', env('SMTP_USER', 'ton-adresse@gmail.com'));
define('SMTP_PASS', env('SMTP_PASS', 'mot-de-passe-application'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587'));
define('SMTP_SECURE', env('SMTP_SECURE', 'tls'));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'ton-adresse@gmail.com'));
define('SMTP_FROM_NAME', APP_NAME);

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die(json_encode([
                    'success' => false,
                    'message' => 'Erreur de connexion à la base de données : ' . $e->getMessage()
                ]));
            }
        }
        return self::$instance;
    }
}