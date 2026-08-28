<?php
/**
 * MedecinPortalController — Portail médecin prescripteur sans login
 * Accès via token unique (medecins.token_acces)
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../config/database.php';

class MedecinPortalController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── PORTAIL PRINCIPAL ────────────────────────────────
    public function index(string $token): void {

        $stmt = $this->db->prepare("
            SELECT *
            FROM medecins
            WHERE token_acces = :token
            AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $medecin = $stmt->fetch();

        if (!$medecin) {
            include dirname(__DIR__) . '/views/medecin/invalid.php';
            return;
        }

        // Patients envoyés par ce médecin (toutes demandes liées)
        $stmt = $this->db->prepare("
            SELECT
                d.id, d.status, d.payment_status,
                d.created_at, d.montant_net,
                p.id AS patient_id,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone,
                GROUP_CONCAT(DISTINCT e.nom SEPARATOR ', ') AS examens,
                COUNT(DISTINCT e.id) AS nb_examens,
                v.validated_at,
                c.statut AS commission_statut,
                c.montant_commission
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            LEFT JOIN validations v ON v.demande_id = d.id
            LEFT JOIN commissions c ON c.demande_id = d.id
            WHERE d.medecin_id = :mid
            GROUP BY d.id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([':mid' => $medecin['id']]);
        $patients = $stmt->fetchAll();

        // Stats rapides
        $stats = [
            'total'      => count($patients),
            'disponible' => 0,
            'en_cours'   => 0,
        ];
        foreach ($patients as $p) {
            if ($p['status'] === 'terminee') {
                $stats['disponible']++;
            } else {
                $stats['en_cours']++;
            }
        }

        // Récapitulatif commissions
        $commissionStats = $this->db->prepare("
            SELECT
                COUNT(*) AS nb_total,
                COALESCE(SUM(
                    CASE WHEN statut = 'due' THEN montant_commission ELSE 0 END
                ), 0) AS total_du,
                COALESCE(SUM(
                    CASE WHEN statut = 'payee' THEN montant_commission ELSE 0 END
                ), 0) AS total_paye
            FROM commissions
            WHERE medecin_id = :mid
        ");
        $commissionStats->execute([':mid' => $medecin['id']]);
        $commissionStats = $commissionStats->fetch();

        include dirname(__DIR__) . '/views/medecin/portal.php';
    }

    // ── RÉSULTATS DÉTAILLÉS (uniquement si validés) ──────
    public function resultat(string $token, string $demandeId): void {

        $stmt = $this->db->prepare("
            SELECT * FROM medecins
            WHERE token_acces = :token
            AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $medecin = $stmt->fetch();

        if (!$medecin) {
            include dirname(__DIR__) . '/views/medecin/invalid.php';
            return;
        }

        // Vérifie que la demande appartient bien à ce médecin
        // ET que les résultats sont validés (status = terminee),
        // exactement comme pour le portail patient.
        $stmt = $this->db->prepare("
            SELECT d.*,
                   ech.id AS ech_id, ech.code_barre,
                   p.id AS patient_id,
                   CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                   p.sexe, p.date_naissance,
                   v.commentaire, v.validated_at,
                   u.name AS biologiste_nom
            FROM demandes_examens d
            JOIN echantillons ech ON ech.demande_id = d.id
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN validations v ON v.demande_id = d.id
            LEFT JOIN users u ON v.biologiste_id = u.id
            WHERE d.id = :did
            AND d.medecin_id = :mid
            AND d.status = 'terminee'
            LIMIT 1
        ");
        $stmt->execute([
            ':did' => $demandeId,
            ':mid' => $medecin['id'],
        ]);
        $demande = $stmt->fetch();

        if (!$demande) {
            include dirname(__DIR__) . '/views/medecin/invalid.php';
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

        include dirname(__DIR__) . '/views/medecin/resultat.php';
    }
}
