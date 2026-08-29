<?php
/**
 * Configuration base de données — Laboratoire Drouot
 * Utilisation prioritaire d'une URL de connexion (DATABASE_URL / DB_URL)
 */

function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    return (string) $value;
}

// 1. Récupération de l'URL de base de données si elle existe
$dbUrl = env('DATABASE_URL', env('DB_URL', env('MYSQL_URL')));

if (!empty($dbUrl)) {
    // Analyse de l'URL de type mysql://user:password@host:port/dbname
    $urlComponents = parse_url($dbUrl);

    $host = $urlComponents['host'] ?? '127.0.0.1';
    $port = $urlComponents['port'] ?? 3306;
    $user = $urlComponents['user'] ?? 'root';
    $pass = $urlComponents['pass'] ?? '';
    $name = isset($urlComponents['path']) ? ltrim($urlComponents['path'], '/') : 'labo_drouot';
} else {
    // Repli sur les variables individuelles ou valeurs par défaut locales
    $host = env('MYSQLHOST', env('DB_HOST', '127.0.0.1'));
    $port = env('MYSQLPORT', env('DB_PORT', '3306'));
    $user = env('MYSQLUSER', env('DB_USER', 'root'));
    $pass = env('MYSQLPASSWORD', env('DB_PASS', ''));
    $name = env('MYSQLDATABASE', env('DB_NAME', 'labo_drouot'));
}

define('DB_HOST', $host);
define('DB_PORT', $port);
define('DB_USER', $user);
define('DB_PASS', $pass);
define('DB_NAME', $name);
define('DB_CHARSET', 'utf8mb4');

// Informations de l'application
define('APP_NAME', 'Labo Drouot');
define('APP_VERSION', '1.0.0');
define('BASE_URL', env('BASE_URL', 'http://localhost:8080'));

// Configuration SMTP
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
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erreur de connexion à la base de données : ' . $e->getMessage()
                ]);
                exit;
            }
        }
        return self::$instance;
    }
}