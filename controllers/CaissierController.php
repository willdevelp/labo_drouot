<?php
/**
 * CaissierController — Gestion des paiements
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CreanceHelper.php';
require_once __DIR__ . '/../config/database.php';

class CaissierController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE FACTURES ───────────────────────────────────
    public function factures(): void {
        Auth::requireRole(['caissier', 'admin', 'secretaire']);
        $user = Auth::user();

        $statut = $_GET['statut'] ?? '';
        $search = trim($_GET['search'] ?? '');

        $where  = ['f.deleted_at IS NULL'];
        $params = [];

        if ($statut !== '') {
            $where[]           = "f.statut = :statut";
            $params[':statut'] = $statut;
        }
        if ($search !== '') {
            $where[]       = "(p.nom LIKE :s OR p.prenom LIKE :s2 OR p.telephone LIKE :s3 OR f.numero LIKE :s4)";
            $like          = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                f.id, f.numero, f.montant_total, f.montant_net,
                f.remise, f.statut, f.created_at,
                d.id AS demande_id, d.payment_status, d.reference,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.id AS patient_id,
                (
                    SELECT mode_paiement FROM paiements
                    WHERE facture_id = f.id AND statut = 'valide'
                    ORDER BY created_at DESC LIMIT 1
                ) AS mode_paiement,
                (
                    SELECT SUM(montant) FROM paiements
                    WHERE facture_id = f.id AND statut = 'valide'
                ) AS montant_paye,
                GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens
            FROM factures f
            JOIN demandes_examens d ON f.demande_id = d.id
            JOIN patients p ON f.patient_id = p.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            WHERE $whereStr
            GROUP BY f.id
            ORDER BY f.created_at DESC
            LIMIT 80
        ");
        $stmt->execute($params);
        $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats rapides
        $stats = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN statut = 'payee' THEN 1 ELSE 0 END) AS payees,
                SUM(CASE WHEN statut = 'emise' THEN 1 ELSE 0 END) AS attente,
                (
                    SELECT COALESCE(SUM(montant), 0) FROM paiements
                    WHERE statut = 'valide' AND DATE(created_at) = CURDATE()
                ) AS revenus_jour
            FROM factures
            WHERE deleted_at IS NULL
        ")->fetch(PDO::FETCH_ASSOC);

        include __DIR__ . '/../views/caissier/factures/index.php';
    }

    // ── DÉTAIL FACTURE ───────────────────────────────────
    public function showFacture(string $id): void {
        Auth::requireRole(['caissier', 'admin', 'secretaire']);

        $stmt = $this->db->prepare("
            SELECT
                f.*,
                d.id AS demande_id, d.reference, d.total_price,
                d.status AS demande_status, d.patient_id,
                d.assurance_id, d.assurance_statut, d.numero_police,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.email, p.adresse, p.uuid AS patient_uuid,
                a.nom AS assurance_nom,
                (
                    SELECT mode_paiement FROM paiements
                    WHERE facture_id = f.id AND statut = 'valide'
                    ORDER BY created_at DESC LIMIT 1
                ) AS mode_paiement
            FROM factures f
            JOIN demandes_examens d ON f.demande_id = d.id
            JOIN patients p ON f.patient_id = p.id
            LEFT JOIN assurances a ON d.assurance_id = a.id
            WHERE f.id = :id AND f.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $facture = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$facture) {
            http_response_code(404);
            include __DIR__ . '/../views/errors/404.php';
            return;
        }

        // Items examens
        $items = $this->db->prepare("
            SELECT dei.price, e.nom, e.code, e.categorie
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            WHERE dei.demande_id = :did
        ");
        $items->execute([':did' => $facture['demande_id']]);
        $examensItems = $items->fetchAll(PDO::FETCH_ASSOC);

        // Paiements déjà effectués
        $paiementsExistants = $this->db->prepare("
            SELECT p.*, u.name AS caissier_name
            FROM paiements p
            JOIN users u ON p.caissier_id = u.id
            WHERE p.facture_id = :fid
            ORDER BY p.created_at DESC
        ");
        $paiementsExistants->execute([':fid' => $id]);
        $paiements = $paiementsExistants->fetchAll(PDO::FETCH_ASSOC);

        $success = $_GET['paid'] ?? false;
        $error   = $_SESSION['pay_error'] ?? null;
        unset($_SESSION['pay_error']);

        include __DIR__ . '/../views/caissier/factures/show.php';
    }

    // ── ENCAISSER PAIEMENT ───────────────────────────────
    public function payer(string $id): void {
        Auth::requireRole(['caissier', 'admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $mode      = $_POST['mode_paiement'] ?? '';
        $montant   = (float)($_POST['montant'] ?? 0);
        $reference = trim($_POST['reference_paiement'] ?? '');
        $userId    = Auth::user()['id'];

        $allowed = ['cash', 'momo', 'card', 'insurance', 'virement'];
        if (!in_array($mode, $allowed)) {
            $_SESSION['pay_error'] = 'Mode de paiement invalide.';
            header('Location: ' . BASE_URL . 'caissier/factures/' . $id);
            exit;
        }

        // Vérifier facture existe et pas encore totalement payée
        $stmt = $this->db->prepare("
            SELECT * FROM factures
            WHERE id = :id
            AND statut IN ('emise', 'partiellement_payee')
            AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $facture = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$facture) {
            $_SESSION['pay_error'] = 'Facture introuvable ou déjà payée.';
            header('Location: ' . BASE_URL . 'caissier/factures/' . $id);
            exit;
        }

        if ($montant <= 0 || $montant > $facture['montant_patient']) {
            $_SESSION['pay_error'] = 'Montant invalide.';
            header('Location: ' . BASE_URL . 'caissier/factures/' . $id);
            exit;
        }

        try {
            $this->db->beginTransaction();

            // Insérer le paiement
            $this->db->prepare("
                INSERT INTO paiements
                    (facture_id, caissier_id, montant, mode_paiement, reference_paiement, statut)
                VALUES
                    (:fid, :uid, :montant, :mode, :ref, 'valide')
            ")->execute([
                ':fid'     => $id,
                ':uid'     => $userId,
                ':montant' => $montant,
                ':mode'    => $mode,
                ':ref'     => $reference ?: null,
            ]);

            // Calculer total payé
            $stmtTotal = $this->db->prepare("
                SELECT COALESCE(SUM(montant), 0) FROM paiements
                WHERE facture_id = :fid AND statut = 'valide'
            ");
            $stmtTotal->execute([':fid' => $id]);
            $totalPaye = (float) $stmtTotal->fetchColumn();

            // Nouveau statut facture — comparé au reste à charge réel
            // du patient (montant_patient), pas au montant brut total
            // (montant_net), qui peut être partiellement couvert par
            // une assurance.
            $nouveauStatut = ($totalPaye >= $facture['montant_patient'])
                ? 'payee'
                : 'partiellement_payee';

            $this->db->prepare("
                UPDATE factures SET statut = :statut, updated_at = NOW()
                WHERE id = :id
            ")->execute([':statut' => $nouveauStatut, ':id' => $id]);

            // Mettre à jour la demande
            $paymentStatus = ($nouveauStatut === 'payee') ? 'paid'    : 'partial';
            $demandeStatus = ($nouveauStatut === 'payee') ? 'en_cours' : 'en_attente_paiement';

            $this->db->prepare("
                UPDATE demandes_examens
                SET payment_status = :ps, status = :ds, updated_at = NOW()
                WHERE id = :did
            ")->execute([
                ':ps'  => $paymentStatus,
                ':ds'  => $demandeStatus,
                ':did' => $facture['demande_id'],
            ]);

            // Génération de la commission médecin si paiement complet
            if ($paymentStatus === 'paid') {
                $this->genererCommission($facture['demande_id']);
                CreanceHelper::genererCreanceAssurance($this->db, $facture['demande_id']);
            }

            // Audit
            $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address)
                VALUES (:uid, 'PAYMENT', 'factures', :rid, :ip)
            ")->execute([
                ':uid' => $userId,
                ':rid' => $id,
                ':ip'  => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $this->db->commit();

            header('Location: ' . BASE_URL . 'caissier/factures/' . $id . '?paid=1');
            exit;

        } catch (PDOException $e) {
            $this->db->rollBack();
            $_SESSION['pay_error'] = 'Erreur base de données : ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'caissier/factures/' . $id);
            exit;
        }
    }

    // ── GÉNÉRER COMMISSION MÉDECIN ───────────────────────
    // Appelée uniquement quand la demande passe en payment_status = 'paid'.
    // Ne crée la ligne que si :
    //   - la demande a un médecin prescripteur associé (medecin_id)
    //   - le médecin a un taux_commission > 0
    //   - aucune commission n'existe déjà pour cette demande
    //     (sécurité anti-doublon, en plus de la contrainte UNIQUE en base)
    private function genererCommission(int $demandeId): void {
        $stmt = $this->db->prepare("
            SELECT d.id, d.medecin_id, d.montant_net,
                   m.taux_commission, m.is_active
            FROM demandes_examens d
            LEFT JOIN medecins m ON m.id = d.medecin_id
            WHERE d.id = :did
            LIMIT 1
        ");
        $stmt->execute([':did' => $demandeId]);
        $demande = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$demande || empty($demande['medecin_id'])) {
            return; // Pas de médecin prescripteur associé
        }

        if (!$demande['is_active']) {
            return; // Médecin désactivé, pas de commission
        }

        $taux = (float) $demande['taux_commission'];
        if ($taux <= 0) {
            return; // Taux nul, rien à générer
        }

        // Anti-doublon applicatif (la contrainte UNIQUE en base protège
        // aussi contre les appels concurrents)
        $exists = $this->db->prepare("
            SELECT id FROM commissions WHERE demande_id = :did LIMIT 1
        ");
        $exists->execute([':did' => $demandeId]);
        if ($exists->fetch()) {
            return;
        }

        $montantBase       = (float) $demande['montant_net'];
        $montantCommission = round($montantBase * $taux / 100, 2);

        try {
            $this->db->prepare("
                INSERT INTO commissions
                    (medecin_id, demande_id, montant_base,
                     taux_applique, montant_commission, statut)
                VALUES
                    (:mid, :did, :base, :taux, :montant, 'due')
            ")->execute([
                ':mid'     => $demande['medecin_id'],
                ':did'     => $demandeId,
                ':base'    => $montantBase,
                ':taux'    => $taux,
                ':montant' => $montantCommission,
            ]);
        } catch (PDOException $e) {
            // Si la contrainte UNIQUE sur demande_id bloque un doublon
            // concurrent, on l'ignore silencieusement — la commission
            // existe déjà, ce qui est l'état désiré.
        }
    }

    // ── SUIVI DE CAISSE — PAIEMENTS (avec filtres) ───────
    // Filtres dispo : date_debut, date_fin, mode, search (patient/facture/réf),
    // et caissier_id (admin/comptable uniquement). Pagination 25/page.
    public function paiements(): void {
        Auth::requireRole(['caissier', 'admin', 'comptable']);
        $user = Auth::user();

        $dateDebut  = $_GET['date_debut']  ?? '';
        $dateFin    = $_GET['date_fin']    ?? '';
        $mode       = $_GET['mode']        ?? '';
        $search     = trim($_GET['search'] ?? '');
        $caissierId = $_GET['caissier_id'] ?? '';

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;

        $where  = ["p.statut = 'valide'"];
        $params = [];

        if ($dateDebut !== '') {
            $where[] = "DATE(p.created_at) >= :date_debut";
            $params[':date_debut'] = $dateDebut;
        }
        if ($dateFin !== '') {
            $where[] = "DATE(p.created_at) <= :date_fin";
            $params[':date_fin'] = $dateFin;
        }
        if ($mode !== '') {
            $where[] = "p.mode_paiement = :mode";
            $params[':mode'] = $mode;
        }
        if ($search !== '') {
            $where[] = "(pat.nom LIKE :s OR pat.prenom LIKE :s2 OR f.numero LIKE :s3 OR p.reference_paiement LIKE :s4)";
            $like = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
        }
        // Filtre par caissier réservé à l'admin/comptable — un caissier
        // ne doit filtrer que dans ses propres données déjà visibles.
        if ($caissierId !== '' && in_array($user['role'], ['admin', 'comptable'])) {
            $where[] = "p.caissier_id = :cid";
            $params[':cid'] = $caissierId;
        }

        $whereStr = implode(' AND ', $where);

        // Total pour pagination
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM paiements p
            JOIN factures f ON p.facture_id = f.id
            JOIN patients pat ON f.patient_id = pat.id
            WHERE $whereStr
        ");
        $countStmt->execute($params);
        $totalRows  = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $stmt = $this->db->prepare("
            SELECT
                p.id, p.facture_id, p.montant, p.mode_paiement,
                p.statut, p.reference_paiement, p.created_at,
                f.numero AS facture_numero, f.montant_net,
                CONCAT(pat.prenom, ' ', pat.nom) AS patient_nom,
                pat.telephone,
                u.name AS caissier_name
            FROM paiements p
            JOIN factures f ON p.facture_id = f.id
            JOIN patients pat ON f.patient_id = pat.id
            JOIN users u ON p.caissier_id = u.id
            WHERE $whereStr
            ORDER BY p.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Totaux sur la période filtrée (sans LIMIT)
        $sumStmt = $this->db->prepare("
            SELECT COALESCE(SUM(p.montant), 0) AS total, COUNT(*) AS nb
            FROM paiements p
            JOIN factures f ON p.facture_id = f.id
            JOIN patients pat ON f.patient_id = pat.id
            WHERE $whereStr
        ");
        $sumStmt->execute($params);
        $sumRow       = $sumStmt->fetch(PDO::FETCH_ASSOC);
        $totalFiltre  = (float) $sumRow['total'];
        $nbFiltre     = (int) $sumRow['nb'];

        // Répartition par mode sur la période filtrée
        $modeStmt = $this->db->prepare("
            SELECT p.mode_paiement, COUNT(*) AS nb, COALESCE(SUM(p.montant), 0) AS total
            FROM paiements p
            JOIN factures f ON p.facture_id = f.id
            JOIN patients pat ON f.patient_id = pat.id
            WHERE $whereStr
            GROUP BY p.mode_paiement
            ORDER BY total DESC
        ");
        $modeStmt->execute($params);
        $repartitionModes = $modeStmt->fetchAll(PDO::FETCH_ASSOC);

        // Indicateurs fixes (non filtrés) — jour / mois en cours
        $totalJour = $this->db->query("
            SELECT COALESCE(SUM(montant), 0) FROM paiements
            WHERE statut = 'valide' AND DATE(created_at) = CURDATE()
        ")->fetchColumn();

        $totalMois = $this->db->query("
            SELECT COALESCE(SUM(montant), 0) FROM paiements
            WHERE statut = 'valide'
            AND MONTH(created_at) = MONTH(NOW())
            AND YEAR(created_at)  = YEAR(NOW())
        ")->fetchColumn();

        // Liste des caissiers pour le filtre (admin/comptable uniquement)
        $caissiers = [];
        if (in_array($user['role'], ['admin', 'comptable'])) {
            $caissiers = $this->db->query("
                SELECT id, name FROM users
                WHERE role = 'caissier' AND is_active = 1
                ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        include __DIR__ . '/../views/caissier/paiements/index.php';
    }

    // ── LISTE DES REÇUS (factures payées) ────────────────
    public function recus(): void {
        Auth::requireRole(['caissier', 'admin', 'secretaire']);

        $dateDebut = $_GET['date_debut'] ?? '';
        $dateFin   = $_GET['date_fin']   ?? '';
        $search    = trim($_GET['search'] ?? '');

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $where  = ["f.statut = 'payee'", "f.deleted_at IS NULL"];
        $params = [];

        if ($dateDebut !== '') {
            $where[] = "DATE(f.updated_at) >= :date_debut";
            $params[':date_debut'] = $dateDebut;
        }
        if ($dateFin !== '') {
            $where[] = "DATE(f.updated_at) <= :date_fin";
            $params[':date_fin'] = $dateFin;
        }
        if ($search !== '') {
            $where[] = "(p.nom LIKE :s OR p.prenom LIKE :s2 OR f.numero LIKE :s3)";
            $like = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM factures f
            JOIN patients p ON f.patient_id = p.id
            WHERE $whereStr
        ");
        $countStmt->execute($params);
        $totalRows  = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $stmt = $this->db->prepare("
            SELECT
                f.id, f.numero, f.montant_net, f.updated_at,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone,
                (
                    SELECT mode_paiement FROM paiements
                    WHERE facture_id = f.id AND statut = 'valide'
                    ORDER BY created_at DESC LIMIT 1
                ) AS mode_paiement
            FROM factures f
            JOIN patients p ON f.patient_id = p.id
            WHERE $whereStr
            ORDER BY f.updated_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $factures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../views/caissier/recus/index.php';
    }

    // ── REÇU (impression individuelle) ───────────────────
    public function recu(string $id): void {
        Auth::requireRole(['caissier', 'admin', 'secretaire']);

        $stmt = $this->db->prepare("
            SELECT
                f.*,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.adresse,
                d.reference AS demande_ref,
                d.assurance_id, d.assurance_statut,
                a.nom AS assurance_nom,
                (
                    SELECT mode_paiement FROM paiements
                    WHERE facture_id = f.id AND statut = 'valide'
                    ORDER BY created_at DESC LIMIT 1
                ) AS mode_paiement
            FROM factures f
            JOIN demandes_examens d ON f.demande_id = d.id
            JOIN patients p ON f.patient_id = p.id
            LEFT JOIN assurances a ON d.assurance_id = a.id
            WHERE f.id = :id AND f.statut = 'payee'
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $facture = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$facture) {
            header('Location: ' . BASE_URL . 'caissier/factures');
            exit;
        }

        // Items examens
        $items = $this->db->prepare("
            SELECT dei.price, e.nom, e.code
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            WHERE dei.demande_id = :did
        ");
        $items->execute([':did' => $facture['demande_id']]);
        $examensItems = $items->fetchAll(PDO::FETCH_ASSOC);

        // Détail paiements
        $paiementsRecu = $this->db->prepare("
            SELECT p.montant, p.mode_paiement, p.reference_paiement,
                   p.created_at, u.name AS caissier
            FROM paiements p
            JOIN users u ON p.caissier_id = u.id
            WHERE p.facture_id = :fid AND p.statut = 'valide'
            ORDER BY p.created_at ASC
        ");
        $paiementsRecu->execute([':fid' => $id]);
        $paiementsDetail = $paiementsRecu->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../views/caissier/recus/show.php';
    }

    // ── HISTORIQUE DES ACTIONS DU CAISSIER (audit) ───────
    // Un caissier ne voit que ses propres actions. L'admin voit
    // l'activité de tous les caissiers par défaut, et peut filtrer
    // sur un caissier précis via caissier_id.
    public function historique(): void {
        Auth::requireRole(['caissier', 'admin']);
        $user = Auth::user();

        $dateDebut  = $_GET['date_debut']  ?? '';
        $dateFin    = $_GET['date_fin']    ?? '';
        $action     = $_GET['action']      ?? '';
        $caissierId = $_GET['caissier_id'] ?? '';

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 30;
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if ($user['role'] === 'caissier') {
            // Scope strict : un caissier ne voit jamais les actions
            // des autres, quel que soit le paramètre GET envoyé.
            $where[] = "a.user_id = :uid";
            $params[':uid'] = $user['id'];
        } elseif ($caissierId !== '') {
            $where[] = "a.user_id = :uid";
            $params[':uid'] = $caissierId;
        } else {
            $where[] = "a.user_id IN (SELECT id FROM users WHERE role = 'caissier')";
        }

        if ($dateDebut !== '') {
            $where[] = "DATE(a.created_at) >= :date_debut";
            $params[':date_debut'] = $dateDebut;
        }
        if ($dateFin !== '') {
            $where[] = "DATE(a.created_at) <= :date_fin";
            $params[':date_fin'] = $dateFin;
        }
        if ($action !== '') {
            $where[] = "a.action = :action";
            $params[':action'] = $action;
        }

        $whereStr = !empty($where) ? implode(' AND ', $where) : '1=1';

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM audit_logs a WHERE $whereStr");
        $countStmt->execute($params);
        $totalRows  = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($totalRows / $perPage));

        $stmt = $this->db->prepare("
            SELECT
                a.id, a.user_id, a.action, a.table_name, a.record_id,
                a.ip_address, a.created_at,
                u.name AS user_name,
                f.numero AS facture_numero
            FROM audit_logs a
            JOIN users u ON a.user_id = u.id
            LEFT JOIN factures f ON a.table_name = 'factures' AND a.record_id = f.id
            WHERE $whereStr
            ORDER BY a.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Types d'action distincts, pour peupler le filtre
        $typesActions = $this->db->query("
            SELECT DISTINCT action FROM audit_logs ORDER BY action
        ")->fetchAll(PDO::FETCH_COLUMN);

        // Liste des caissiers pour le filtre admin
        $caissiers = [];
        if ($user['role'] === 'admin') {
            $caissiers = $this->db->query("
                SELECT id, name FROM users WHERE role = 'caissier' ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);
        }

        include __DIR__ . '/../views/caissier/historique/index.php';
    }
}