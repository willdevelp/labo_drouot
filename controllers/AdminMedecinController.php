<?php
/**
 * AdminMedecinController — Gestion des médecins prescripteurs
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class AdminMedecinController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE + FORMULAIRE AJOUT ─────────────────────────
    public function index(): void {
        Auth::requireRole(['admin', 'directeur']);
        $user = Auth::user();

        $search = trim($_GET['search'] ?? '');

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]       = "(nom LIKE :s OR prenom LIKE :s2
                               OR specialite LIKE :s3)";
            $like          = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                m.*,
                COUNT(DISTINCT d.id) AS nb_patients_envoyes,
                COUNT(DISTINCT c.id) AS nb_commissions,
                COALESCE(SUM(
                    CASE WHEN c.statut = 'due' THEN c.montant_commission ELSE 0 END
                ), 0) AS commissions_dues,
                COALESCE(SUM(
                    CASE WHEN c.statut = 'payee' THEN c.montant_commission ELSE 0 END
                ), 0) AS commissions_payees
            FROM medecins m
            LEFT JOIN demandes_examens d ON d.medecin_id = m.id
            LEFT JOIN commissions c ON c.medecin_id = m.id
            WHERE $whereStr
            GROUP BY m.id
            ORDER BY m.nom, m.prenom ASC
        ");
        $stmt->execute($params);
        $medecins = $stmt->fetchAll();

        // Stats globales
        $stats = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(is_active) AS actifs,
                (SELECT COALESCE(SUM(montant_commission),0)
                 FROM commissions WHERE statut = 'due') AS total_du
            FROM medecins
        ")->fetch();

        $success = isset($_GET['saved']);
        $error   = $_SESSION['medecin_error'] ?? null;
        unset($_SESSION['medecin_error']);

        include dirname(__DIR__) . '/views/admin/medecins/index.php';
    }

    // ── ENREGISTREMENT NOUVEAU MÉDECIN ───────────────────
    public function store(): void {
        Auth::requireRole(['admin', 'directeur']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $nom        = trim($_POST['nom']        ?? '');
        $prenom     = trim($_POST['prenom']     ?? '');
        $specialite = trim($_POST['specialite'] ?? '');
        $telephone  = trim($_POST['telephone']  ?? '');
        $email      = trim($_POST['email']      ?? '');
        $clinique   = trim($_POST['clinique']   ?? '');
        $taux       = (float)($_POST['taux_commission'] ?? 0);

        if (empty($nom) || empty($prenom)) {
            $_SESSION['medecin_error'] =
                'Le nom et le prénom sont obligatoires.';
            header('Location: ' . BASE_URL . 'admin/medecins');
            exit;
        }

        if ($taux < 0 || $taux > 100) {
            $_SESSION['medecin_error'] =
                'Le taux de commission doit être entre 0 et 100%.';
            header('Location: ' . BASE_URL . 'admin/medecins');
            exit;
        }

        $token = $this->genererToken();

        $this->db->prepare("
            INSERT INTO medecins
                (nom, prenom, specialite, telephone, email,
                 clinique, taux_commission, token_acces, is_active)
            VALUES
                (:nom, :prenom, :spec, :tel, :email,
                 :clin, :taux, :token, 1)
        ")->execute([
            ':nom'   => $nom,
            ':prenom'=> $prenom,
            ':spec'  => $specialite ?: null,
            ':tel'   => $telephone ?: null,
            ':email' => $email ?: null,
            ':clin'  => $clinique ?: null,
            ':taux'  => $taux,
            ':token' => $token,
        ]);

        $newId = $this->db->lastInsertId();
        $this->logAudit('CREATE_MEDECIN', 'medecins', (int)$newId);

        header('Location: ' . BASE_URL . 'admin/medecins?saved=1');
        exit;
    }

    // ── MODIFIER MÉDECIN (inline) ────────────────────────
    public function update(string $id): void {
        Auth::requireRole(['admin', 'directeur']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $nom        = trim($_POST['nom']        ?? '');
        $prenom     = trim($_POST['prenom']     ?? '');
        $specialite = trim($_POST['specialite'] ?? '');
        $telephone  = trim($_POST['telephone']  ?? '');
        $email      = trim($_POST['email']      ?? '');
        $clinique   = trim($_POST['clinique']   ?? '');
        $taux       = (float)($_POST['taux_commission'] ?? 0);

        if (empty($nom) || empty($prenom)) {
            $_SESSION['medecin_error'] =
                'Le nom et le prénom sont obligatoires.';
            header('Location: ' . BASE_URL . 'admin/medecins');
            exit;
        }

        if ($taux < 0 || $taux > 100) {
            $_SESSION['medecin_error'] =
                'Le taux de commission doit être entre 0 et 100%.';
            header('Location: ' . BASE_URL . 'admin/medecins');
            exit;
        }

        $this->db->prepare("
            UPDATE medecins SET
                nom = :nom, prenom = :prenom,
                specialite = :spec, telephone = :tel,
                email = :email, clinique = :clin,
                taux_commission = :taux
            WHERE id = :id
        ")->execute([
            ':nom'   => $nom,
            ':prenom'=> $prenom,
            ':spec'  => $specialite ?: null,
            ':tel'   => $telephone ?: null,
            ':email' => $email ?: null,
            ':clin'  => $clinique ?: null,
            ':taux'  => $taux,
            ':id'    => $id,
        ]);

        $this->logAudit('UPDATE_MEDECIN', 'medecins', (int)$id);

        header('Location: ' . BASE_URL . 'admin/medecins?saved=1');
        exit;
    }

    // ── RÉGÉNÉRER LE TOKEN (si lien compromis) ───────────
    public function regenererToken(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $token = $this->genererToken();

        $this->db->prepare("
            UPDATE medecins SET token_acces = :token WHERE id = :id
        ")->execute([':token' => $token, ':id' => $id]);

        $this->logAudit('REGEN_TOKEN_MEDECIN', 'medecins', (int)$id);

        header('Location: ' . BASE_URL . 'admin/medecins?saved=1');
        exit;
    }

    // ── TOGGLE ACTIF ─────────────────────────────────────
    public function toggle(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        $stmt = $this->db->prepare("
            SELECT is_active FROM medecins WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetchColumn();

        $this->db->prepare("
            UPDATE medecins SET is_active = :status WHERE id = :id
        ")->execute([
            ':status' => $current ? 0 : 1,
            ':id'     => $id,
        ]);

        $this->logAudit('TOGGLE_MEDECIN', 'medecins', (int)$id);

        header('Location: ' . BASE_URL . 'admin/medecins');
        exit;
    }

    // ── SUPPRESSION ──────────────────────────────────────
    public function delete(string $id): void {
        Auth::requireRole(['admin', 'directeur']);

        // Vérifier si utilisé (demandes liées)
        $used = $this->db->prepare("
            SELECT COUNT(*) FROM demandes_examens
            WHERE medecin_id = :id
        ");
        $used->execute([':id' => $id]);

        if ($used->fetchColumn() > 0) {
            // Désactiver seulement si déjà utilisé dans des demandes
            $this->db->prepare("
                UPDATE medecins SET is_active = 0 WHERE id = :id
            ")->execute([':id' => $id]);
        } else {
            $this->db->prepare("
                DELETE FROM medecins WHERE id = :id
            ")->execute([':id' => $id]);
        }

        $this->logAudit('DELETE_MEDECIN', 'medecins', (int)$id);

        header('Location: ' . BASE_URL . 'admin/medecins?saved=1');
        exit;
    }

    // ── HELPERS ──────────────────────────────────────────
    private function genererToken(): string {
        // Token unique, retry en cas de collision improbable
        do {
            $token = bin2hex(random_bytes(20));
            $exists = $this->db->prepare("
                SELECT id FROM medecins WHERE token_acces = :t
            ");
            $exists->execute([':t' => $token]);
        } while ($exists->fetch());

        return $token;
    }

    private function logAudit(
        string $action,
        string $table,
        int $recordId
    ): void {
        $userId = Auth::user()['id'];
        $this->db->prepare("
            INSERT INTO audit_logs
                (user_id, action, table_name, record_id)
            VALUES (:uid, :action, :table, :rid)
        ")->execute([
            ':uid'    => $userId,
            ':action' => $action,
            ':table'  => $table,
            ':rid'    => $recordId,
        ]);
    }
}