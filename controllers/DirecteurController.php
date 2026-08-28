<?php
/**
 * DirecteurController — Tableau de bord Direction
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class DirecteurController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function kpi(): void {
        Auth::requireRole(['directeur', 'admin']);
        $user = Auth::user();
        include dirname(__DIR__) . '/views/dashboard/directeur.php';
    }

    public function revenus(): void {
        Auth::requireRole(['directeur', 'admin']);
        $user = Auth::user();
        include dirname(__DIR__) . '/views/directeur/revenus.php';
    }

    public function examens(): void {
        Auth::requireRole(['directeur', 'admin']);
        $user = Auth::user();
        include dirname(__DIR__) . '/views/directeur/examens.php';
    }

    public function rapports(): void {
        Auth::requireRole(['directeur', 'admin']);
        $user = Auth::user();
        include dirname(__DIR__) . '/views/directeur/rapports.php';
    }
}