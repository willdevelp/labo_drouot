<?php
/**
 * AdminRapportController — Rapports & Statistiques
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

// ── Helper formatFcfa disponible partout ─────────────────
if (!function_exists('formatFcfa')) {
    function formatFcfa(float $montant): string {
        if ($montant >= 1_000_000) {
            return number_format($montant / 1_000_000, 1, ',', ' ') . ' M FCFA';
        }
        if ($montant >= 1_000) {
            return number_format($montant / 1_000, 0, ',', ' ') . ' k FCFA';
        }
        return number_format($montant, 0, ',', ' ') . ' FCFA';
    }
}

class AdminRapportController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE / DASHBOARD RAPPORTS ───────────────────────
    public function index(): void {
        Auth::requireRole(['admin', 'directeur', 'biologiste']);
        $user    = Auth::user();
        $periode = (int)($_GET['periode'] ?? 30);
        if (!in_array($periode, [7, 30, 90, 365])) $periode = 30;

        // ── Stats globales ───────────────────────────────
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM patients
                 WHERE deleted_at IS NULL
                 AND created_at >= DATE_SUB(NOW(), INTERVAL :p1 DAY)
                ) AS total_patients,

                (SELECT COUNT(*) FROM demandes_examens
                 WHERE deleted_at IS NULL
                 AND created_at >= DATE_SUB(NOW(), INTERVAL :p2 DAY)
                ) AS total_demandes,

                (SELECT COUNT(*) FROM validations
                 WHERE validated_at >= DATE_SUB(NOW(), INTERVAL :p3 DAY)
                ) AS total_validations,

                (SELECT COALESCE(SUM(montant), 0) FROM paiements
                 WHERE statut = 'valide'
                 AND created_at >= DATE_SUB(NOW(), INTERVAL :p4 DAY)
                ) AS revenus
        ");
        $stmt->execute([
            ':p1' => $periode,
            ':p2' => $periode,
            ':p3' => $periode,
            ':p4' => $periode,
        ]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // ── Revenus par jour ─────────────────────────────
        $stmt = $this->db->prepare("
            SELECT
                DATE(created_at) AS jour,
                COALESCE(SUM(montant), 0) AS total
            FROM paiements
            WHERE statut = 'valide'
            AND created_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
            GROUP BY DATE(created_at)
            ORDER BY jour ASC
        ");
        $stmt->execute([':p' => $periode]);
        $revenusJour = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Top examens ──────────────────────────────────
        $stmt = $this->db->prepare("
            SELECT
                e.nom,
                COUNT(dei.id)  AS total,
                SUM(dei.price) AS revenus
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            JOIN demandes_examens d ON dei.demande_id = d.id
            WHERE d.created_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
            AND d.deleted_at IS NULL
            GROUP BY e.id
            ORDER BY total DESC
            LIMIT 8
        ");
        $stmt->execute([':p' => $periode]);
        $topExamens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Activité par rôle ────────────────────────────
        $stmt = $this->db->prepare("
            SELECT
                u.role,
                COUNT(al.id) AS nb_actions
            FROM audit_logs al
            JOIN users u ON al.user_id = u.id
            WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
            GROUP BY u.role
            ORDER BY nb_actions DESC
        ");
        $stmt->execute([':p' => $periode]);
        $activiteRole = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Rapports générés ─────────────────────────────
        $stmt = $this->db->prepare("
            SELECT
                r.id, r.file_path, r.generated_at,
                d.id AS demande_id, d.total_price,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                GROUP_CONCAT(DISTINCT e.nom SEPARATOR ', ') AS examens,
                ub.name AS biologiste_nom
            FROM rapports r
            JOIN demandes_examens d ON r.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN validations v ON v.demande_id = d.id
            LEFT JOIN users ub ON v.biologiste_id = ub.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            WHERE r.generated_at >= DATE_SUB(NOW(), INTERVAL :p DAY)
            GROUP BY r.id
            ORDER BY r.generated_at DESC
            LIMIT 50
        ");
        $stmt->execute([':p' => $periode]);
        $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include dirname(__DIR__) . '/views/admin/rapports/index.php';
    }

    // ── DÉTAIL RAPPORT ───────────────────────────────────
    public function show(string $id): void {
        Auth::requireRole(['admin', 'directeur', 'biologiste']);
        $user = Auth::user();

        $stmt = $this->db->prepare("
            SELECT
                r.*,
                d.id AS demande_id, d.reference, d.total_price,
                d.status AS demande_status,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.email, p.adresse,
                p.sexe, p.date_naissance,
                v.commentaire AS bio_commentaire,
                v.validated_at,
                ub.name AS biologiste_nom,
                ug.name AS generateur_nom
            FROM rapports r
            JOIN demandes_examens d ON r.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN validations v ON v.demande_id = d.id
            LEFT JOIN users ub ON v.biologiste_id = ub.id
            LEFT JOIN users ug ON r.generated_by = ug.id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $rapport = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rapport) {
            http_response_code(404);
            include dirname(__DIR__) . '/views/errors/404.php';
            return;
        }

        // Examens et résultats
        $examens = $this->db->prepare("
            SELECT e.id, e.nom, e.code, e.categorie
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            WHERE dei.demande_id = :did
            ORDER BY e.categorie, e.nom
        ");
        $examens->execute([':did' => $rapport['demande_id']]);
        $examens = $examens->fetchAll(PDO::FETCH_ASSOC);

        $resultatsData = [];
        foreach ($examens as $ex) {
            $stmt = $this->db->prepare("
                SELECT
                    ep.libelle, ep.unite,
                    ep.valeur_min, ep.valeur_max,
                    r.valeur, r.interpretation, r.is_abnormal
                FROM examen_parametres ep
                LEFT JOIN resultats r
                    ON r.examen_parametre_id = ep.id
                LEFT JOIN echantillons ech
                    ON r.echantillon_id = ech.id
                    AND ech.demande_id = :did
                WHERE ep.examen_id = :eid
                ORDER BY ep.ordre ASC
            ");
            $stmt->execute([
                ':did' => $rapport['demande_id'],
                ':eid' => $ex['id'],
            ]);
            $resultatsData[$ex['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        include dirname(__DIR__) . '/views/admin/rapports/show.php';
    }
}