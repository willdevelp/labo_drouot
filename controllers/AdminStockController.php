<?php
/**
 * AdminStockController — Gestion stocks & réactifs
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class AdminStockController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE ────────────────────────────────────────────
    public function index(): void {
        Auth::requireRole(['admin', 'directeur']);
        $user   = Auth::user();
        $search = trim($_GET['search'] ?? '');
        $filtre = $_GET['filtre'] ?? '';

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]      = "name LIKE :s";
            $params[':s'] = '%' . $search . '%';
        }

        if ($filtre === 'faible') {
            $where[] = "quantity <= threshold";
        } elseif ($filtre === 'expire') {
            $where[] = "expiry_date IS NOT NULL
                        AND expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)";
        } elseif ($filtre === 'ok') {
            $where[] = "quantity > threshold";
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT *,
                CASE
                    WHEN quantity = 0             THEN 'rupture'
                    WHEN quantity <= threshold    THEN 'faible'
                    WHEN expiry_date IS NOT NULL
                         AND expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                                                  THEN 'expiration'
                    ELSE 'ok'
                END AS statut
            FROM stocks
            WHERE $whereStr
            ORDER BY
                CASE
                    WHEN quantity = 0          THEN 1
                    WHEN quantity <= threshold THEN 2
                    ELSE 3
                END,
                name ASC
        ");
        $stmt->execute($params);
        $stocks = $stmt->fetchAll();

        // Stats
        $stats = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) AS ruptures,
                SUM(CASE WHEN quantity > 0
                    AND quantity <= threshold THEN 1 ELSE 0 END) AS faibles,
                SUM(CASE WHEN expiry_date IS NOT NULL
                    AND expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                    AND expiry_date > NOW() THEN 1 ELSE 0 END) AS expirations
            FROM stocks
        ")->fetch();

        $success = $_SESSION['stock_success'] ?? null;
        $error   = $_SESSION['stock_error']   ?? null;
        unset($_SESSION['stock_success'], $_SESSION['stock_error']);

        include dirname(__DIR__) . '/views/admin/stocks/index.php';
    }

    // ── FORMULAIRE CRÉATION ──────────────────────────────
    public function create(): void {
        Auth::requireRole(['admin']);
        $user  = Auth::user();
        $error = $_SESSION['form_error'] ?? null;
        $old   = $_SESSION['form_old']   ?? [];
        unset($_SESSION['form_error'], $_SESSION['form_old']);
        include dirname(__DIR__) . '/views/admin/stocks/create.php';
    }

    // ── ENREGISTREMENT ───────────────────────────────────
   public function store(): void {
    Auth::requireRole(['admin']);
    if (session_status() === PHP_SESSION_NONE) session_start();

    $name         = trim($_POST['name']          ?? '');
    $code         = trim($_POST['code']          ?? '');
    $categorie    = trim($_POST['categorie']     ?? '');
    $quantity     = (int)($_POST['quantity']     ?? 0);
    $unite        = trim($_POST['unite']         ?? '');
    $threshold    = (int)($_POST['threshold']    ?? 0);
    $fournisseur  = trim($_POST['fournisseur']   ?? '');
    $prix         = $_POST['prix_unitaire']      ?? null;
    $expiry_date  = $_POST['expiry_date']        ?? '';
    $lot_number   = trim($_POST['lot_number']    ?? '');

    if (empty($name)) {
        $_SESSION['form_error'] = 'Le nom est obligatoire.';
        $_SESSION['form_old']   = $_POST;
        header('Location: ' . BASE_URL . 'admin/stocks/create');
        exit;
    }

    $this->db->prepare("
        INSERT INTO stocks
            (code, name, categorie, quantity, unite, threshold,
             fournisseur, prix_unitaire, expiry_date, lot_number)
        VALUES
            (:code, :name, :categorie, :qty, :unite, :threshold,
             :fournisseur, :prix, :expiry, :lot)
    ")->execute([
        ':code'        => $code        ?: null,
        ':name'        => $name,
        ':categorie'   => $categorie   ?: null,
        ':qty'         => $quantity,
        ':unite'       => $unite       ?: null,
        ':threshold'   => $threshold,
        ':fournisseur' => $fournisseur ?: null,
        ':prix'        => $prix        ?: null,
        ':expiry'      => $expiry_date ?: null,
        ':lot'         => $lot_number  ?: null,
    ]);

    $id = $this->db->lastInsertId();
    $this->logAudit('CREATE_STOCK', 'stocks', (int)$id);

    $_SESSION['stock_success'] = "Réactif \"$name\" ajouté avec succès.";
    header('Location: ' . BASE_URL . 'admin/stocks');
    exit;
}

    // ── ÉDITION ──────────────────────────────────────────
    public function edit(string $id): void {
        Auth::requireRole(['admin']);
        $user = Auth::user();

        $stmt = $this->db->prepare("
            SELECT * FROM stocks WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $stock = $stmt->fetch();

        if (!$stock) {
            header('Location: ' . BASE_URL . 'admin/stocks');
            exit;
        }

        $error = $_SESSION['form_error'] ?? null;
        unset($_SESSION['form_error']);
        include dirname(__DIR__) . '/views/admin/stocks/edit.php';
    }

    // ── MISE À JOUR ──────────────────────────────────────
    public function update(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $name      = trim($_POST['name']      ?? '');
        $quantity  = (int)($_POST['quantity'] ?? 0);
        $threshold = (int)($_POST['threshold']?? 0);
        $expiry    = $_POST['expiry_date']    ?? '';

        if (empty($name)) {
            $_SESSION['form_error'] = 'Le nom est obligatoire.';
            header('Location: ' . BASE_URL .
                'admin/stocks/' . $id . '/edit');
            exit;
        }

        $this->db->prepare("
            UPDATE stocks SET
                name = :name, quantity = :qty,
                threshold = :threshold, expiry_date = :expiry
            WHERE id = :id
        ")->execute([
            ':name'      => $name,
            ':qty'       => $quantity,
            ':threshold' => $threshold,
            ':expiry'    => $expiry ?: null,
            ':id'        => $id,
        ]);

        $this->logAudit('UPDATE_STOCK', 'stocks', (int)$id);

        $_SESSION['stock_success'] = "Stock mis à jour avec succès.";
        header('Location: ' . BASE_URL . 'admin/stocks');
        exit;
    }

    // ── AJUSTEMENT QUANTITÉ ──────────────────────────────
    public function ajuster(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $type    = $_POST['type']    ?? 'add';
        $qty     = (int)($_POST['quantite'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT quantity FROM stocks WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetchColumn();

        if ($type === 'add') {
            $newQty = $current + $qty;
        } else {
            $newQty = max(0, $current - $qty);
        }

        $this->db->prepare("
            UPDATE stocks SET quantity = :qty WHERE id = :id
        ")->execute([':qty' => $newQty, ':id' => $id]);

        $this->logAudit('ADJUST_STOCK', 'stocks', (int)$id);

        $_SESSION['stock_success'] =
            "Stock ajusté : $current → $newQty.";
        header('Location: ' . BASE_URL . 'admin/stocks');
        exit;
    }

    // ── SUPPRESSION ──────────────────────────────────────
    public function delete(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $this->db->prepare("
            DELETE FROM stocks WHERE id = :id
        ")->execute([':id' => $id]);

        $this->logAudit('DELETE_STOCK', 'stocks', (int)$id);

        $_SESSION['stock_success'] = "Réactif supprimé.";
        header('Location: ' . BASE_URL . 'admin/stocks');
        exit;
    }

    private function logAudit(
        string $action, string $table, int $id
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
            ':rid'    => $id,
        ]);
    }
}