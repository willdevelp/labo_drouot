<?php
/**
 * PatientPortalController — Portail patient sans login
 * Accès via token unique sécurisé
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../config/database.php';

class PatientPortalController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── PORTAIL PRINCIPAL ────────────────────────────────
    public function index(string $token): void {

        $stmt = $this->db->prepare("
            SELECT
                pt.*, p.id AS patient_id, p.nom, p.prenom,
                p.sexe, p.date_naissance, p.telephone, p.email
            FROM patient_tokens pt
            JOIN patients p ON pt.patient_id = p.id
            WHERE pt.token = :token
            AND pt.is_active = 1
            AND pt.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $tokenData = $stmt->fetch();

        if (!$tokenData) {
            include dirname(__DIR__) . '/views/patient/invalid.php'; // ✅
            return;
        }

        $patient = [
            'id'             => $tokenData['patient_id'],
            'nom'            => $tokenData['nom'],
            'prenom'         => $tokenData['prenom'],
            'sexe'           => $tokenData['sexe'],
            'date_naissance' => $tokenData['date_naissance'],
            'telephone'      => $tokenData['telephone'],
            'email'          => $tokenData['email'],
        ];

        $stmt = $this->db->prepare("
            SELECT
                d.id, d.status, d.created_at, d.total_price,
                d.payment_status,
                GROUP_CONCAT(DISTINCT e.nom SEPARATOR ', ') AS examens,
                COUNT(DISTINCT e.id)  AS nb_examens,
                COUNT(DISTINCT r.id)  AS nb_resultats,
                SUM(r.is_abnormal)    AS nb_anormaux,
                v.validated_at,
                v.commentaire,
                ech.status            AS ech_status,
                ech.id                AS ech_id
            FROM demandes_examens d
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            LEFT JOIN echantillons ech ON ech.demande_id = d.id
            LEFT JOIN resultats r ON r.echantillon_id = ech.id
            LEFT JOIN validations v ON v.demande_id = d.id
            WHERE d.patient_id = :pid
            GROUP BY d.id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([':pid' => $patient['id']]);
        $analyses = $stmt->fetchAll();

        $stats = [
            'total'      => count($analyses),
            'disponible' => 0,
            'en_cours'   => 0,
            'archive'    => 0,
        ];
     foreach ($analyses as $a) {
    if ($a['status'] === 'terminee' && $a['validated_at']) {
        $stats['disponible']++;
    } elseif ($a['status'] === 'terminee') {
        $stats['archive']++;
    } else {
        $stats['en_cours']++;
    }
}

        include dirname(__DIR__) . '/views/patient/portal.php'; // ✅
    }

    // ── RÉSULTATS DÉTAILLÉS ──────────────────────────────
    public function resultat(string $token, string $demandeId): void {

        $stmt = $this->db->prepare("
            SELECT pt.*, p.id AS patient_id,
                   p.nom, p.prenom, p.sexe, p.date_naissance
            FROM patient_tokens pt
            JOIN patients p ON pt.patient_id = p.id
            WHERE pt.token = :token
            AND pt.is_active = 1
            AND pt.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $tokenData = $stmt->fetch();

        if (!$tokenData) {
            include dirname(__DIR__) . '/views/patient/invalid.php'; // ✅
            return;
        }

        $stmt = $this->db->prepare("
            SELECT d.*,
                   ech.id AS ech_id, ech.code_barre,
                   v.commentaire, v.validated_at,
                   u.name AS biologiste_nom
            FROM demandes_examens d
            JOIN echantillons ech ON ech.demande_id = d.id
            LEFT JOIN validations v ON v.demande_id = d.id
            LEFT JOIN users u ON v.biologiste_id = u.id
            WHERE d.id = :did
            AND d.patient_id = :pid
            AND d.status = 'terminee'
            LIMIT 1
        ");
        $stmt->execute([
            ':did' => $demandeId,
            ':pid' => $tokenData['patient_id'],
        ]);
        $demande = $stmt->fetch();

        if (!$demande) {
            include dirname(__DIR__) . '/views/patient/invalid.php'; // ✅
            return;
        }

        $examens = $this->db->prepare("
            SELECT e.id, e.nom, e.code, e.categorie
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            WHERE dei.demande_id = :did
            ORDER BY e.categorie, e.nom
        ");
        $examens->execute([':did' => $demandeId]);
        $examens = $examens->fetchAll();

        $resultatsData = [];
        foreach ($examens as $ex) {
            $stmt = $this->db->prepare("
                SELECT
                    ep.libelle, ep.unite,
                    ep.valeur_min, ep.valeur_max, ep.type,
                    r.valeur, r.interpretation, r.is_abnormal
                FROM examen_parametres ep
                LEFT JOIN resultats r
                    ON r.examen_parametre_id = ep.id
                    AND r.echantillon_id = :eid
                WHERE ep.examen_id = :exid
                ORDER BY ep.ordre ASC
            ");
            $stmt->execute([
                ':eid'  => $demande['ech_id'],
                ':exid' => $ex['id'],
            ]);
            $resultatsData[$ex['id']] = $stmt->fetchAll();
        }

        $patient = [
            'id'             => $tokenData['patient_id'],
            'nom'            => $tokenData['nom'],
            'prenom'         => $tokenData['prenom'],
            'sexe'           => $tokenData['sexe'],
            'date_naissance' => $tokenData['date_naissance'],
        ];

        include dirname(__DIR__) . '/views/patient/resultat.php'; // ✅
    }
}