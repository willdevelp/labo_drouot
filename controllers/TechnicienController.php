<?php
/**
 * TechnicienController — Gestion analyses et résultats
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class TechnicienController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE ÉCHANTILLONS ───────────────────────────────
    public function echantillons(): void {
        Auth::requireRole(['technicien', 'admin']);
        $user   = Auth::user();
        $statut = $_GET['statut'] ?? '';
        $search = trim($_GET['search'] ?? '');

        $where  = ["d.payment_status = 'paid'"];
        $params = [];

        if ($statut !== '') {
            $where[]           = "ech.status = :statut";
            $params[':statut'] = $statut;
        } else {
            $where[] = "ech.status IN ('collecte','en_analyse')";
        }
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
                ech.id, ech.code_barre, ech.status, ech.collected_at,
                d.id AS demande_id, d.total_price,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone,
                GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens,
                COUNT(DISTINCT e.id) AS nb_examens,
                COUNT(DISTINCT r.id) AS nb_resultats
            FROM echantillons ech
            JOIN demandes_examens d ON ech.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            LEFT JOIN resultats r ON r.echantillon_id = ech.id
            WHERE $whereStr
            GROUP BY ech.id
            ORDER BY
                CASE ech.status
                    WHEN 'collecte'   THEN 1
                    WHEN 'en_analyse' THEN 2
                    WHEN 'analyse'    THEN 3
                END,
                ech.collected_at ASC
        ");
        $stmt->execute($params);
        $echantillons = $stmt->fetchAll();

        include __DIR__ . '/../views/technicien/echantillons/index.php';
    }

    // ── SAISIE RÉSULTATS ─────────────────────────────────
    public function saisirResultats(string $id): void {
        Auth::requireRole(['technicien', 'admin']);
        $user = Auth::user();

        // Charger échantillon
        $stmt = $this->db->prepare("
            SELECT
                ech.*,
                d.id AS demande_id,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.id AS patient_id,
                p.date_naissance, p.sexe
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

        // Examens de cette demande avec leurs paramètres
        $examens = $this->db->prepare("
            SELECT e.id, e.nom, e.code, e.categorie, e.description
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            WHERE dei.demande_id = :did
            ORDER BY e.categorie, e.nom
        ");
        $examens->execute([':did' => $echantillon['demande_id']]);
        $examens = $examens->fetchAll();

        // Paramètres de chaque examen
        $parametres = [];
        foreach ($examens as $ex) {
            $stmt = $this->db->prepare("
                SELECT id, code, libelle, type, unite,
                       valeur_min, valeur_max, valeurs_possibles, ordre
                FROM examen_parametres
                WHERE examen_id = :eid
                ORDER BY ordre ASC
            ");
            $stmt->execute([':eid' => $ex['id']]);
            $parametres[$ex['id']] = $stmt->fetchAll();
        }

        // Résultats déjà saisis
        $resultatsExistants = [];
        $stmt = $this->db->prepare("
            SELECT r.examen_parametre_id, r.valeur,
                   r.interpretation, r.is_abnormal
            FROM resultats r
            WHERE r.echantillon_id = :eid
        ");
        $stmt->execute([':eid' => $id]);
        foreach ($stmt->fetchAll() as $r) {
            $resultatsExistants[$r['examen_parametre_id']] = $r;
        }

        // Marquer comme en_analyse si collecte
        if ($echantillon['status'] === 'collecte') {
            $this->db->prepare("
                UPDATE echantillons SET status = 'en_analyse'
                WHERE id = :id
            ")->execute([':id' => $id]);
            $echantillon['status'] = 'en_analyse';
        }

        $success = $_GET['saved'] ?? false;
        $error   = $_SESSION['save_error'] ?? null;
        unset($_SESSION['save_error']);

        include __DIR__ . '/../views/technicien/resultats/saisir.php';
    }

    // ── SAUVEGARDER RÉSULTATS ────────────────────────────
public function saveResultats(string $id): void {
    Auth::requireRole(['technicien', 'admin']);
    if (session_status() === PHP_SESSION_NONE) session_start();

    $valeurs = $_POST['valeurs'] ?? [];
    $userId  = Auth::user()['id'];

    if (empty($valeurs)) {
        $_SESSION['save_error'] = 'Aucune valeur saisie.';
        header('Location: ' . BASE_URL . 'technicien/resultats/' . $id);
        exit;
    }

    // Récupérer l'échantillon pour avoir demande_id
    $stmtEch = $this->db->prepare("
        SELECT demande_id FROM echantillons WHERE id = :id LIMIT 1
    ");
    $stmtEch->execute([':id' => $id]);
    $echantillon = $stmtEch->fetch();

    if (!$echantillon) {
        $_SESSION['save_error'] = 'Échantillon introuvable.';
        header('Location: ' . BASE_URL . 'technicien/resultats/' . $id);
        exit;
    }

    $demandeId = $echantillon['demande_id'];

    try {
        $this->db->beginTransaction();

        foreach ($valeurs as $parametreId => $valeur) {
            if ($valeur === '' || $valeur === null) continue;

            // Récupérer le paramètre pour interprétation
            $stmtParam = $this->db->prepare("
                SELECT ep.valeur_min, ep.valeur_max, ep.type, ep.examen_id
                FROM examen_parametres ep
                WHERE ep.id = :id
            ");
            $stmtParam->execute([':id' => $parametreId]);
            $param = $stmtParam->fetch();

            // Récupérer le demande_item_id correspondant à cet examen
            $stmtItem = $this->db->prepare("
                SELECT id FROM demande_examens_items
                WHERE demande_id = :did AND examen_id = :eid
                LIMIT 1
            ");
            $stmtItem->execute([
                ':did' => $demandeId,
                ':eid' => $param['examen_id'],
            ]);
            $demandeItem = $stmtItem->fetch();

            if (!$demandeItem) continue; // Sécurité

            $demandeItemId = $demandeItem['id'];

            // Calcul interprétation automatique
            $interpretation = 'normal';
            $isAbnormal     = 0;

            if ($param && $param['type'] === 'numeric' && is_numeric($valeur)) {
                $val = (float) $valeur;
                if ($param['valeur_min'] !== null && $val < (float)$param['valeur_min']) {
                    $interpretation = 'bas';
                    $isAbnormal     = 1;
                } elseif ($param['valeur_max'] !== null && $val > (float)$param['valeur_max']) {
                    $interpretation = 'eleve';
                    $isAbnormal     = 1;
                }
            }

            // Upsert résultat
            $stmtExist = $this->db->prepare("
                SELECT id FROM resultats
                WHERE echantillon_id = :eid
                AND examen_parametre_id = :pid
                LIMIT 1
            ");
            $stmtExist->execute([':eid' => $id, ':pid' => $parametreId]);
            $exists = $stmtExist->fetchColumn();

            if ($exists) {
                $this->db->prepare("
                    UPDATE resultats SET
                        valeur              = :val,
                        interpretation      = :interp,
                        is_abnormal         = :abnormal,
                        technicien_id       = :tid,
                        updated_at          = NOW()
                    WHERE id = :rid
                ")->execute([
                    ':val'     => $valeur,
                    ':interp'  => $interpretation,
                    ':abnormal'=> $isAbnormal,
                    ':tid'     => $userId,
                    ':rid'     => $exists,
                ]);
            } else {
                $this->db->prepare("
                    INSERT INTO resultats
                        (echantillon_id, demande_item_id, examen_parametre_id,
                         technicien_id, valeur, interpretation, is_abnormal)
                    VALUES
                        (:eid, :did, :pid,
                         :tid, :val, :interp, :abnormal)
                ")->execute([
                    ':eid'     => $id,
                    ':did'     => $demandeItemId,  // ← clé manquante corrigée
                    ':pid'     => $parametreId,
                    ':tid'     => $userId,
                    ':val'     => $valeur,
                    ':interp'  => $interpretation,
                    ':abnormal'=> $isAbnormal,
                ]);
            }
        }

        // Audit
        $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id)
            VALUES (:uid, 'SAVE_RESULTS', 'resultats', :rid)
        ")->execute([':uid' => $userId, ':rid' => $id]);

        $this->db->commit();

        header('Location: ' . BASE_URL . 'technicien/resultats/' . $id . '?saved=1');
        exit;

    } catch (PDOException $e) {
        $this->db->rollBack();
        $_SESSION['save_error'] = 'Erreur : ' . $e->getMessage();
        header('Location: ' . BASE_URL . 'technicien/resultats/' . $id);
        exit;
    }
}
    // ── TRANSMETTRE AU BIOLOGISTE ────────────────────────
    public function transmettre(string $id): void {
        Auth::requireRole(['technicien', 'admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        // Vérifier qu'il y a des résultats
        $nbResultats = $this->db->prepare("
            SELECT COUNT(*) FROM resultats
            WHERE echantillon_id = :eid
        ");
        $nbResultats->execute([':eid' => $id]);
        $nb = $nbResultats->fetchColumn();

        if ($nb == 0) {
            $_SESSION['save_error'] =
                'Impossible de transmettre : aucun résultat saisi.';
            header('Location: ' . BASE_URL .
                'technicien/resultats/' . $id);
            exit;
        }

        // Mettre à jour statut échantillon
        $this->db->prepare("
            UPDATE echantillons SET status = 'analyse'
            WHERE id = :id
        ")->execute([':id' => $id]);

        // Mettre à jour statut demande
        $demande = $this->db->prepare("
            SELECT demande_id FROM echantillons WHERE id = :id
        ");
        $demande->execute([':id' => $id]);
        $demandeId = $demande->fetchColumn();

      $this->db->prepare("
    UPDATE demandes_examens SET status = 'en_cours'
    WHERE id = :did
")->execute([':did' => $demandeId]);
        // Audit
        $userId = Auth::user()['id'];
        $this->db->prepare("
            INSERT INTO audit_logs
                (user_id, action, table_name, record_id)
            VALUES (:uid, 'TRANSMIT_BIOLOGISTE', 'echantillons', :rid)
        ")->execute([':uid' => $userId, ':rid' => $id]);

        header('Location: ' . BASE_URL .
            'technicien/echantillons?transmitted=1');
        exit;
    }

    // ── LISTE RÉSULTATS ──────────────────────────────────
    public function resultats(): void {
        Auth::requireRole(['technicien', 'admin']);
        $user = Auth::user();

        $stmt = $this->db->query("
            SELECT
                ech.id, ech.code_barre, ech.status,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                COUNT(DISTINCT r.id) AS nb_resultats,
                SUM(r.is_abnormal) AS nb_anormaux,
                MAX(r.created_at) AS last_update
            FROM echantillons ech
            JOIN demandes_examens d ON ech.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN resultats r ON r.echantillon_id = ech.id
            WHERE d.payment_status = 'paid'
            GROUP BY ech.id
            ORDER BY last_update DESC
            LIMIT 50
        ");
        $resultats = $stmt->fetchAll();

        include __DIR__ . '/../views/technicien/resultats/index.php';
    }

    // ── ANALYSES EN COURS ────────────────────────────────
    public function encours(): void {
        Auth::requireRole(['technicien', 'admin']);
        $user = Auth::user();

        $stmt = $this->db->query("
            SELECT
                ech.id, ech.code_barre, ech.status, ech.collected_at,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens
            FROM echantillons ech
            JOIN demandes_examens d ON ech.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            WHERE ech.status = 'en_analyse'
            AND d.payment_status = 'paid'
            GROUP BY ech.id
            ORDER BY ech.collected_at ASC
        ");
        $encours = $stmt->fetchAll();

        include __DIR__ . '/../views/technicien/encours/index.php';
    }
}