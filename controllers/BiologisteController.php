<?php
/**
 * BiologisteController — Validation biologique
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class BiologisteController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE VALIDATIONS ────────────────────────────────
    public function validations(): void {
        Auth::requireRole(['biologiste', 'admin']);
        $user   = Auth::user();
        $search = trim($_GET['search'] ?? '');

        // ✅ 'analyse' = statut ENUM correct après transmission technicien
   $where = ["ech.status = 'analyse'", "d.status != 'terminee'"];
        $params = [];

        if ($search !== '') {
            $where[]       = "(p.nom LIKE :s OR p.prenom LIKE :s2
                               OR ech.code_barre LIKE :s3)";
            $like          = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                ech.id, ech.code_barre, ech.collected_at,
                d.id AS demande_id,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.sexe, p.date_naissance,
                p.id AS patient_id,
                GROUP_CONCAT(DISTINCT e.nom SEPARATOR ', ') AS examens,
                COUNT(DISTINCT e.id)  AS nb_examens,
                COUNT(DISTINCT r.id)  AS nb_resultats,
                SUM(r.is_abnormal)    AS nb_anormaux
            FROM echantillons ech
            JOIN demandes_examens d ON ech.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            LEFT JOIN resultats r ON r.echantillon_id = ech.id
            WHERE $whereStr
            GROUP BY ech.id
            ORDER BY nb_anormaux DESC, ech.collected_at ASC
        ");
        $stmt->execute($params);
        $validations = $stmt->fetchAll();

        include __DIR__ . '/../views/biologiste/validations/index.php';
    }

    // ── DÉTAIL VALIDATION ────────────────────────────────
    public function showValidation(string $id): void {
        Auth::requireRole(['biologiste', 'admin']);
        $user = Auth::user();

        // Échantillon
        $stmt = $this->db->prepare("
            SELECT
                ech.*,
                d.id AS demande_id,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.sexe,
                p.date_naissance, p.adresse,
                p.id AS patient_id
            FROM echantillons ech
            JOIN demandes_examens d ON ech.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            WHERE ech.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $echantillon = $stmt->fetch();

        if (!$echantillon) {
            http_response_code(404);
            include __DIR__ . '/../views/errors/404.php';
            return;
        }

        // Examens
        $examens = $this->db->prepare("
            SELECT e.id, e.nom, e.code, e.categorie, e.description
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            WHERE dei.demande_id = :did
            ORDER BY e.categorie, e.nom
        ");
        $examens->execute([':did' => $echantillon['demande_id']]);
        $examens = $examens->fetchAll();

        // Résultats avec paramètres
        $resultatsData = [];
        foreach ($examens as $ex) {
            $stmt = $this->db->prepare("
                SELECT
                    ep.id AS param_id,
                    ep.libelle, ep.type, ep.unite,
                    ep.valeur_min, ep.valeur_max, ep.ordre,
                    r.id AS resultat_id,
                    r.valeur, r.interpretation,
                    r.is_abnormal, r.created_at
                FROM examen_parametres ep
                LEFT JOIN resultats r
                    ON r.examen_parametre_id = ep.id
                    AND r.echantillon_id = :eid
                WHERE ep.examen_id = :exid
                ORDER BY ep.ordre ASC
            ");
            $stmt->execute([
                ':eid'  => $id,
                ':exid' => $ex['id'],
            ]);
            $resultatsData[$ex['id']] = $stmt->fetchAll();
        }

        // Validation existante
        $validationExist = $this->db->prepare("
            SELECT v.*, u.name AS biologiste_nom
            FROM validations v
            LEFT JOIN users u ON v.biologiste_id = u.id
            WHERE v.demande_id = :did
            ORDER BY v.validated_at DESC LIMIT 1
        ");
        $validationExist->execute([':did' => $echantillon['demande_id']]);
        $validationExist = $validationExist->fetch();

        $success = $_GET['validated'] ?? false;
        $error   = $_SESSION['bio_error'] ?? null;
        unset($_SESSION['bio_error']);

        include __DIR__ . '/../views/biologiste/validations/show.php';
    }

    // ── VALIDER ──────────────────────────────────────────
    public function valider(string $id): void {
        Auth::requireRole(['biologiste', 'admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $commentaire = trim($_POST['commentaire'] ?? '');
        $userId      = Auth::user()['id'];

        // Récupérer demande_id
        $stmt = $this->db->prepare("
            SELECT demande_id FROM echantillons WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $demandeId = $stmt->fetchColumn();

        if (!$demandeId) {
            $_SESSION['bio_error'] = 'Échantillon introuvable.';
            header('Location: ' . BASE_URL . 'biologiste/validations/' . $id);
            exit;
        }

        try {
            $this->db->beginTransaction();

            // Upsert validation
            $existing = $this->db->prepare("
                SELECT id FROM validations WHERE demande_id = :did
            ");
            $existing->execute([':did' => $demandeId]);
            $existingId = $existing->fetchColumn();

            if ($existingId) {
                $this->db->prepare("
                    UPDATE validations SET
                        biologiste_id = :uid,
                        commentaire   = :comm,
                        status        = 'validee',
                        validated_at  = NOW()
                    WHERE id = :vid
                ")->execute([
                    ':uid'  => $userId,
                    ':comm' => $commentaire ?: null,
                    ':vid'  => $existingId,
                ]);
            } else {
                $this->db->prepare("
                    INSERT INTO validations
                        (demande_id, biologiste_id, commentaire,
                         status, validated_at)
                    VALUES
                        (:did, :uid, :comm, 'validee', NOW())
                ")->execute([
                    ':did'  => $demandeId,
                    ':uid'  => $userId,
                    ':comm' => $commentaire ?: null,
                ]);
            }

            // ✅ Demande → 'terminee' (valeur ENUM correcte)
            $this->db->prepare("
                UPDATE demandes_examens
                SET status = 'terminee', updated_at = NOW()
                WHERE id = :did
            ")->execute([':did' => $demandeId]);

            // ✅ Échantillon reste 'analyse' — statut final correct
            // Pas de changement nécessaire ici

            // Générer entrée rapport
            $this->db->prepare("
                INSERT INTO rapports
                    (demande_id, file_path, generated_by, generated_at)
                VALUES (:did, :path, :uid, NOW())
                ON DUPLICATE KEY UPDATE
                    generated_at = NOW(),
                    generated_by = :uid2
            ")->execute([
                ':did'  => $demandeId,
                ':path' => 'rapports/rapport_' . $demandeId . '.pdf',
                ':uid'  => $userId,
                ':uid2' => $userId,
            ]);

            // Audit
            $this->db->prepare("
                INSERT INTO audit_logs
                    (user_id, action, table_name, record_id)
                VALUES (:uid, 'VALIDATE_RESULTS', 'validations', :rid)
            ")->execute([':uid' => $userId, ':rid' => $id]);

            $this->db->commit();

            header('Location: ' . BASE_URL .
                'biologiste/validations/' . $id . '?validated=1');
            exit;

        } catch (PDOException $e) {
            $this->db->rollBack();
            $_SESSION['bio_error'] = 'Erreur : ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'biologiste/validations/' . $id);
            exit;
        }
    }

    // ── REJETER ──────────────────────────────────────────
    public function rejeter(string $id): void {
        Auth::requireRole(['biologiste', 'admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $motif  = trim($_POST['motif_rejet'] ?? '');
        $userId = Auth::user()['id'];

        if (empty($motif)) {
            $_SESSION['bio_error'] = 'Veuillez indiquer un motif de rejet.';
            header('Location: ' . BASE_URL . 'biologiste/validations/' . $id);
            exit;
        }

        // Récupérer demande_id avant update
        $stmt = $this->db->prepare("
            SELECT demande_id FROM echantillons WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $demandeId = $stmt->fetchColumn();

        // ✅ Remettre en 'en_analyse' pour correction technicien
        $this->db->prepare("
            UPDATE echantillons
            SET status = 'en_analyse', updated_at = NOW()
            WHERE id = :id
        ")->execute([':id' => $id]);

        // ✅ Demande → 'en_cours' (valeur ENUM correcte)
        $this->db->prepare("
            UPDATE demandes_examens
            SET status = 'en_cours', updated_at = NOW()
            WHERE id = :did
        ")->execute([':did' => $demandeId]);

        // Audit avec motif tronqué (max 90 chars pour action varchar(100))
        $this->db->prepare("
            INSERT INTO audit_logs
                (user_id, action, table_name, record_id)
            VALUES (:uid, :action, 'echantillons', :rid)
        ")->execute([
            ':uid'    => $userId,
            ':action' => 'REJECT: ' . mb_substr($motif, 0, 90),
            ':rid'    => $id,
        ]);

        header('Location: ' . BASE_URL . 'biologiste/validations?rejected=1');
        exit;
    }

    // ── RÉSULTATS VALIDÉS ────────────────────────────────
    public function resultatsValides(): void {
        Auth::requireRole(['biologiste', 'admin']);
        $user = Auth::user();

        $stmt = $this->db->query("
            SELECT
                v.id AS val_id, v.commentaire, v.validated_at,
                ech.code_barre,
                d.id AS demande_id,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone,
                u.name AS biologiste_nom,
                GROUP_CONCAT(DISTINCT e.nom SEPARATOR ', ') AS examens,
                COUNT(DISTINCT r.id) AS nb_resultats,
                SUM(r.is_abnormal)   AS nb_anormaux
            FROM validations v
            JOIN demandes_examens d ON v.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            JOIN echantillons ech ON ech.demande_id = d.id
            LEFT JOIN users u ON v.biologiste_id = u.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            LEFT JOIN resultats r ON r.echantillon_id = ech.id
            GROUP BY v.id
            ORDER BY v.validated_at DESC
            LIMIT 60
        ");
        $resultatsValides = $stmt->fetchAll();

        include __DIR__ . '/../views/biologiste/resultats-valides/index.php';
    }

    // ── COMMENTAIRES ─────────────────────────────────────
    public function commentaires(): void {
        Auth::requireRole(['biologiste', 'admin']);
        $user = Auth::user();

        $stmt = $this->db->query("
            SELECT
                v.id, v.commentaire, v.validated_at,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                ech.code_barre,
                u.name AS biologiste_nom
            FROM validations v
            JOIN demandes_examens d ON v.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            JOIN echantillons ech ON ech.demande_id = d.id
            LEFT JOIN users u ON v.biologiste_id = u.id
            WHERE v.commentaire IS NOT NULL
            AND v.commentaire != ''
            ORDER BY v.validated_at DESC
            LIMIT 50
        ");
        $commentaires = $stmt->fetchAll();

        include __DIR__ . '/../views/biologiste/commentaires/index.php';
    }

    // ── RAPPORTS ─────────────────────────────────────────
    public function rapports(): void {
        Auth::requireRole(['biologiste', 'admin']);
        $user = Auth::user();

        $stmt = $this->db->query("
            SELECT
                r.id, r.file_path, r.generated_at,
                d.id AS demande_id, d.total_price,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone,
                v.validated_at, v.commentaire
            FROM rapports r
            JOIN demandes_examens d ON r.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN validations v ON v.demande_id = d.id
            ORDER BY r.generated_at DESC
            LIMIT 60
        ");
        $rapports = $stmt->fetchAll();

        include __DIR__ . '/../views/biologiste/rapports/index.php';
    }
}