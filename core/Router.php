<?php
/**
 * Router — Laboratoire Drouot
 * Gère le routing propre via URL rewriting
 */
//router.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';
class Router {
    private array $routes = [];
    /**
     * Enregistre une route GET
     */
    public function get(string $path, callable|array $handler): void {
        $this->routes['GET'][$path] = $handler;
    }
    /**
     * Enregistre une route POST
     */
    public function post(string $path, callable|array $handler): void {
        $this->routes['POST'][$path] = $handler;
    }
    /**
     * Dispatch la requête courante
     */
   public function dispatch(): void {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Calcul dynamique du sous-dossier de base, à partir du dossier
    // réel où se trouve le point d'entrée (index.php). Ça évite tout
    // décalage entre l'URL tapée dans le navigateur et un chemin codé
    // en dur — fonctionne pareil en local (WAMP, quel que soit le nom
    // du dossier) et en production (InfinityFree).
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

    // Le dossier /public est invisible pour le visiteur : le .htaccess
    // racine réécrit en interne vers public/$1 sans jamais apparaître
    // dans l'URL réellement tapée (REQUEST_URI). On le retire donc du
    // chemin de base, sinon on retirerait un segment qui n'existe pas
    // réellement dans l'URI à comparer.
    if (str_ends_with($basePath, '/public')) {
        $basePath = substr($basePath, 0, -strlen('/public'));
    }
    if ($basePath !== '' && str_starts_with($uri, $basePath)) {
        $uri = substr($uri, strlen($basePath));
    }
    $uri = '/' . trim($uri, '/');
    if ($uri === '') $uri = '/';

    // ── 1. Route EXACTE ─────────────────────────────────
    if (isset($this->routes[$method][$uri])) {
        $this->call($this->routes[$method][$uri]);
        return;
    }

    // ── 2. Routes DYNAMIQUES — triées du plus long au plus court
    $routes = $this->routes[$method] ?? [];
    uksort($routes, function($a, $b) {
        // Plus de segments = plus spécifique = priorité haute
        $diff = substr_count($b, '/') - substr_count($a, '/');
        if ($diff !== 0) return $diff;
        // À égalité de segments, les routes sans {param} passent avant
        $aHasParam = str_contains($a, '{') ? 1 : 0;
        $bHasParam = str_contains($b, '{') ? 1 : 0;
        return $aHasParam - $bHasParam;
    });
    foreach ($routes as $route => $handler) {
        if (!str_contains($route, '{')) continue;
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        $pattern = '#^' . $pattern . '$#';
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            $this->call($handler, $matches);
            return;
        }
    }

    // ── 3. 404 ──────────────────────────────────────────
    http_response_code(404);
    include dirname(__DIR__) . '/views/errors/404.php';
}
    /**
     * Appelle le handler (callable ou [Controller, 'method'])
     */
    private function call(callable|array $handler, array $params = []): void {
        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;
            require_once __DIR__ . '/../controllers/' . $controllerClass . '.php';
            $controller = new $controllerClass();
            call_user_func_array([$controller, $method], $params);
        } else {
            call_user_func_array($handler, $params);
        }
    }
}