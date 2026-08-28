<?php
/**
 * FactureController — Devis proforma (éphémère) + Notifications
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class FactureController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── DEVIS PROFORMA (éphémère, rien stocké en base) ───
    public function proforma(): void {
        Auth::requireRole(['secretaire', 'admin']);
        $user = Auth::user();

        // Charger les examens groupés par catégorie, comme pour
        // le formulaire de demande — mais ici, aucune sélection
        // de patient et rien n'est enregistré au final.
        $stmt = $this->db->query("
            SELECT id, code, nom, categorie, prix, description
            FROM examens
            WHERE is_active = 1
            ORDER BY categorie, nom
        ");
        $examensRaw = $stmt->fetchAll();

        $examensGroupes = [];
        foreach ($examensRaw as $e) {
            $cat = $e['categorie'] ?? 'Autres';
            $examensGroupes[$cat][] = $e;
        }

        include __DIR__ . '/../views/secretaire/factures/proforma.php';
    }

    // ── NOTIFICATIONS — alertes à traiter ────────────────
    public function notifications(): void {
        Auth::requireRole(['secretaire', 'admin']);
        $user = Auth::user();

        $notifications = [];

        // 1. Demandes en attente de paiement depuis plus de 24h
        $stmt = $this->db->query("
            SELECT
                d.id, d.reference, d.created_at, d.montant_net,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone,
                TIMESTAMPDIFF(HOUR, d.created_at, NOW()) AS heures_ecoulees
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            WHERE d.payment_status = 'unpaid'
            AND d.created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY d.created_at ASC
        ");
        foreach ($stmt->fetchAll() as $row) {
            $notifications[] = [
                'type'     => 'paiement_attente',
                'priorite' => 'warning',
                'icon'     => 'fa-hourglass-half',
                'titre'    => 'Paiement en attente depuis ' . $this->formatDuree($row['heures_ecoulees']),
                'detail'   => htmlspecialchars($row['patient_nom']) . ' — Réf. ' . htmlspecialchars($row['reference'])
                              . ' — ' . number_format((float)$row['montant_net'], 0, ',', ' ') . ' FCFA',
                'lien'     => BASE_URL . 'secretaire/demandes/' . $row['id'],
                'date'     => $row['created_at'],
            ];
        }

        // 2. Demandes assurance en attente depuis plus de 48h
        $stmt = $this->db->query("
            SELECT
                d.id, d.reference, d.created_at,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                a.nom AS assurance_nom,
                TIMESTAMPDIFF(HOUR, d.created_at, NOW()) AS heures_ecoulees
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            JOIN assurances a ON d.assurance_id = a.id
            WHERE d.assurance_statut = 'en_attente'
            AND d.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
            ORDER BY d.created_at ASC
        ");
        foreach ($stmt->fetchAll() as $row) {
            $notifications[] = [
                'type'     => 'assurance_attente',
                'priorite' => 'danger',
                'icon'     => 'fa-shield-heart',
                'titre'    => 'Assurance sans réponse depuis ' . $this->formatDuree($row['heures_ecoulees']),
                'detail'   => htmlspecialchars($row['patient_nom']) . ' — ' . htmlspecialchars($row['assurance_nom'])
                              . ' — Réf. ' . htmlspecialchars($row['reference']),
                'lien'     => BASE_URL . 'secretaire/demandes/' . $row['id'],
                'date'     => $row['created_at'],
            ];
        }

        // 3. Résultats validés disponibles à communiquer au patient
        $stmt = $this->db->query("
            SELECT
                d.id, d.reference,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.uuid AS patient_uuid,
                v.validated_at
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            JOIN validations v ON v.demande_id = d.id
            WHERE d.status = 'terminee'
            ORDER BY v.validated_at DESC
            LIMIT 20
        ");
        foreach ($stmt->fetchAll() as $row) {
            $notifications[] = [
                'type'     => 'resultats_disponibles',
                'priorite' => 'info',
                'icon'     => 'fa-circle-check',
                'titre'    => 'Résultats disponibles',
                'detail'   => htmlspecialchars($row['patient_nom']) . ' — Réf. ' . htmlspecialchars($row['reference'])
                              . ' — validés le ' . date('d/m/Y à H:i', strtotime($row['validated_at'])),
                'lien'     => BASE_URL . 'secretaire/demandes/' . $row['id'],
                'date'     => $row['validated_at'],
                'telephone'=> $row['telephone'],
            ];
        }

        // Tri global par date décroissante (plus récent en premier)
        usort($notifications, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        $stats = [
            'total'    => count($notifications),
            'paiement' => count(array_filter($notifications, fn($n) => $n['type'] === 'paiement_attente')),
            'assurance'=> count(array_filter($notifications, fn($n) => $n['type'] === 'assurance_attente')),
            'resultats'=> count(array_filter($notifications, fn($n) => $n['type'] === 'resultats_disponibles')),
        ];

        include __DIR__ . '/../views/secretaire/notifications/index.php';
    }

    // ── HELPERS ───────────────────────────────────────────
    private function formatDuree(int $heures): string {
        if ($heures < 24) {
            return $heures . 'h';
        }
        $jours = intdiv($heures, 24);
        return $jours . ' jour' . ($jours > 1 ? 's' : '');
    }
}
