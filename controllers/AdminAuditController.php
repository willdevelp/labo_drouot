<?php
/**
 * AdminAuditController — Journal d'audit complet
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class AdminAuditController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function index(): void {
        Auth::requireRole(['admin']);
        $user   = Auth::user();
        $search = trim($_GET['search'] ?? '');
        $action = $_GET['action']     ?? '';
        $userId = $_GET['user_id']    ?? '';
        $date   = $_GET['date']       ?? '';

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]       = "(a.action LIKE :s
                               OR a.table_name LIKE :s2
                               OR u.name LIKE :s3)";
            $like          = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }
        if ($action !== '') {
            $where[]          = "a.action LIKE :action";
            $params[':action']= '%' . $action . '%';
        }
        if ($userId !== '') {
            $where[]          = "a.user_id = :uid";
            $params[':uid']   = $userId;
        }
        if ($date !== '') {
            $where[]          = "DATE(a.created_at) = :date";
            $params[':date']  = $date;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                a.id, a.action, a.table_name,
                a.record_id, a.created_at,
                u.name AS user_name,
                u.role AS user_role
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE $whereStr
            ORDER BY a.created_at DESC
            LIMIT 200
        ");
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        // Stats audit
        $statsAudit = $this->db->query("
            SELECT
                COUNT(*) AS total,
                COUNT(DISTINCT user_id) AS nb_users,
                COUNT(DISTINCT DATE(created_at)) AS nb_jours
            FROM audit_logs
        ")->fetch();

        // Actions les plus fréquentes
        $topActions = $this->db->query("
            SELECT action, COUNT(*) AS total
            FROM audit_logs
            GROUP BY action
            ORDER BY total DESC
            LIMIT 8
        ")->fetchAll();

        // Utilisateurs actifs
        $topUsers = $this->db->query("
            SELECT u.name, u.role,
                   COUNT(a.id) AS nb_actions,
                   MAX(a.created_at) AS derniere_action
            FROM audit_logs a
            JOIN users u ON a.user_id = u.id
            GROUP BY a.user_id
            ORDER BY nb_actions DESC
            LIMIT 6
        ")->fetchAll();

        // Liste utilisateurs pour filtre
        $allUsers = $this->db->query("
            SELECT id, name, role FROM users
            WHERE deleted_at IS NULL
            ORDER BY name ASC
        ")->fetchAll();

        include dirname(__DIR__) .
            '/views/admin/audit/index.php';
    }

    // ── EXPORT CSV ───────────────────────────────────────
    public function export(): void {
        Auth::requireRole(['admin']);

        $stmt = $this->db->query("
            SELECT
                a.id, a.action, a.table_name,
                a.record_id,
                a.created_at,
                u.name AS user_name,
                u.role AS user_role
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT 5000
        ");
        $logs = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_drouot_' .
            date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            'ID', 'Utilisateur', 'Rôle',
            'Action', 'Table', 'ID Enregistrement',
            'Date/Heure'
        ], ';');

        foreach ($logs as $log) {
            fputcsv($out, [
                $log['id'],
                $log['user_name'] ?? 'Système',
                $log['user_role'] ?? '—',
                $log['action'],
                $log['table_name'] ?? '—',
                $log['record_id']  ?? '—',
                $log['created_at'],
            ], ';');
        }

        fclose($out);
        exit;
    }
}