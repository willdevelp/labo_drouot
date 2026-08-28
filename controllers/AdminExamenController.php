<?php
/**
 * AdminExamenController — Gestion examens & paramètres
 * Moteur dynamique — Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class AdminExamenController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE EXAMENS ────────────────────────────────────
    public function index(): void {
        Auth::requireRole(['admin']);
        $user   = Auth::user();
        $search = trim($_GET['search'] ?? '');
        $cat    = $_GET['categorie'] ?? '';

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]       = "(e.nom LIKE :s OR e.code LIKE :s2
                               OR e.description LIKE :s3)";
            $like          = '%' . $search . '%';
            $params[':s']  = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
        }
        if ($cat !== '') {
            $where[]       = "e.categorie = :cat";
            $params[':cat']= $cat;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                e.*,
                COUNT(DISTINCT ep.id)  AS nb_parametres,
                COUNT(DISTINCT dei.id) AS nb_realises
            FROM examens e
            LEFT JOIN examen_parametres ep ON ep.examen_id = e.id
            LEFT JOIN demande_examens_items dei ON dei.examen_id = e.id
            WHERE $whereStr
            GROUP BY e.id
            ORDER BY e.categorie, e.nom ASC
        ");
        $stmt->execute($params);
        $examens = $stmt->fetchAll();

        // Catégories distinctes
        $categories = $this->db->query("
            SELECT DISTINCT categorie FROM examens
            WHERE categorie IS NOT NULL
            ORDER BY categorie ASC
        ")->fetchAll(PDO::FETCH_COLUMN);

        // Stats
        $stats = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(is_active) AS actifs,
                COUNT(DISTINCT categorie) AS nb_categories
            FROM examens
        ")->fetch();

        include dirname(__DIR__) .
            '/views/admin/examens/index.php';
    }

    // ── FORMULAIRE CRÉATION ──────────────────────────────
    public function create(): void {
        Auth::requireRole(['admin']);
        $user  = Auth::user();
        $error = $_SESSION['form_error'] ?? null;
        $old   = $_SESSION['form_old']   ?? [];
        unset($_SESSION['form_error'], $_SESSION['form_old']);

        // Catégories existantes pour suggestion
        $categories = $this->db->query("
            SELECT DISTINCT categorie FROM examens
            WHERE categorie IS NOT NULL
            ORDER BY categorie ASC
        ")->fetchAll(PDO::FETCH_COLUMN);

        include dirname(__DIR__) .
            '/views/admin/examens/create.php';
    }

    // ── ENREGISTREMENT EXAMEN ────────────────────────────
    public function store(): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $code        = trim($_POST['code']        ?? '');
        $nom         = trim($_POST['nom']         ?? '');
        $description = trim($_POST['description'] ?? '');
        $categorie   = trim($_POST['categorie']   ?? '');
        $prix        = (float)($_POST['prix']     ?? 0);

        if (empty($nom) || empty($categorie) || $prix <= 0) {
            $_SESSION['form_error'] =
                'Nom, catégorie et prix sont obligatoires.';
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL .
                'admin/examens/create');
            exit;
        }

        // Code unique auto si vide
        if (empty($code)) {
            $code = strtoupper(substr(preg_replace(
                '/[^A-Za-z0-9]/', '', $nom
            ), 0, 6)) . rand(100, 999);
        }

        // Vérifier code unique
        $exists = $this->db->prepare("
            SELECT id FROM examens WHERE code = :code
        ");
        $exists->execute([':code' => $code]);
        if ($exists->fetch()) {
            $_SESSION['form_error'] =
                'Ce code existe déjà. Choisissez un autre.';
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL .
                'admin/examens/create');
            exit;
        }

        try {
            $this->db->prepare("
                INSERT INTO examens
                    (code, nom, description, categorie,
                     prix, is_active)
                VALUES
                    (:code, :nom, :desc, :cat, :prix, 1)
            ")->execute([
                ':code' => $code,
                ':nom'  => $nom,
                ':desc' => $description ?: null,
                ':cat'  => $categorie,
                ':prix' => $prix,
            ]);

            $newId = $this->db->lastInsertId();
            $this->logAudit('CREATE_EXAMEN', 'examens', (int)$newId);

            header('Location: ' . BASE_URL .
                'admin/examens/' . $newId .
                '/parametres?created=1');
            exit;

        } catch (PDOException $e) {
            $_SESSION['form_error'] =
                'Erreur : ' . $e->getMessage();
            $_SESSION['form_old'] = $_POST;
            header('Location: ' . BASE_URL .
                'admin/examens/create');
            exit;
        }
    }

    // ── DÉTAIL EXAMEN ────────────────────────────────────
    public function show(string $id): void {
        Auth::requireRole(['admin']);
        $user = Auth::user();

        $stmt = $this->db->prepare("
            SELECT * FROM examens WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $examen = $stmt->fetch();

        if (!$examen) {
            http_response_code(404);
            include dirname(__DIR__) .
                '/views/errors/404.php';
            return;
        }

        // Paramètres
        $parametres = $this->db->prepare("
            SELECT * FROM examen_parametres
            WHERE examen_id = :id
            ORDER BY ordre ASC
        ");
        $parametres->execute([':id' => $id]);
        $parametres = $parametres->fetchAll();

        // Statistiques utilisation
        $utilisation = $this->db->prepare("
            SELECT COUNT(*) FROM demande_examens_items
            WHERE examen_id = :id
        ");
        $utilisation->execute([':id' => $id]);
        $nbRealises = $utilisation->fetchColumn();

        include dirname(__DIR__) .
            '/views/admin/examens/show.php';
    }

    // ── ÉDITION EXAMEN ───────────────────────────────────
    public function edit(string $id): void {
        Auth::requireRole(['admin']);
        $user = Auth::user();

        $stmt = $this->db->prepare("
            SELECT * FROM examens WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $examen = $stmt->fetch();

        if (!$examen) {
            header('Location: ' . BASE_URL . 'admin/examens');
            exit;
        }

        $categories = $this->db->query("
            SELECT DISTINCT categorie FROM examens
            WHERE categorie IS NOT NULL
            ORDER BY categorie ASC
        ")->fetchAll(PDO::FETCH_COLUMN);

        $error = $_SESSION['form_error'] ?? null;
        unset($_SESSION['form_error']);

        include dirname(__DIR__) .
            '/views/admin/examens/edit.php';
    }

    // ── MISE À JOUR EXAMEN ───────────────────────────────
    public function update(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $nom         = trim($_POST['nom']         ?? '');
        $description = trim($_POST['description'] ?? '');
        $categorie   = trim($_POST['categorie']   ?? '');
        $prix        = (float)($_POST['prix']     ?? 0);

        if (empty($nom) || empty($categorie) || $prix <= 0) {
            $_SESSION['form_error'] =
                'Nom, catégorie et prix sont obligatoires.';
            header('Location: ' . BASE_URL .
                'admin/examens/' . $id . '/edit');
            exit;
        }

        $this->db->prepare("
            UPDATE examens SET
                nom = :nom, description = :desc,
                categorie = :cat, prix = :prix
            WHERE id = :id
        ")->execute([
            ':nom'  => $nom,
            ':desc' => $description ?: null,
            ':cat'  => $categorie,
            ':prix' => $prix,
            ':id'   => $id,
        ]);

        $this->logAudit('UPDATE_EXAMEN', 'examens', (int)$id);

        header('Location: ' . BASE_URL .
            'admin/examens/' . $id . '?updated=1');
        exit;
    }

    // ── TOGGLE ACTIF ─────────────────────────────────────
    public function toggle(string $id): void {
        Auth::requireRole(['admin']);

        $stmt = $this->db->prepare("
            SELECT is_active FROM examens WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetchColumn();

        $this->db->prepare("
            UPDATE examens SET is_active = :status WHERE id = :id
        ")->execute([
            ':status' => $current ? 0 : 1,
            ':id'     => $id,
        ]);

        $this->logAudit('TOGGLE_EXAMEN', 'examens', (int)$id);

        header('Location: ' . BASE_URL .
            'admin/examens/' . $id);
        exit;
    }

    // ── SUPPRESSION ──────────────────────────────────────
    public function delete(string $id): void {
        Auth::requireRole(['admin']);

        // Vérifier si utilisé
        $used = $this->db->prepare("
            SELECT COUNT(*) FROM demande_examens_items
            WHERE examen_id = :id
        ");
        $used->execute([':id' => $id]);
        if ($used->fetchColumn() > 0) {
            // Désactiver seulement si utilisé
            $this->db->prepare("
                UPDATE examens SET is_active = 0 WHERE id = :id
            ")->execute([':id' => $id]);
        } else {
            $this->db->prepare("
                DELETE FROM examens WHERE id = :id
            ")->execute([':id' => $id]);
        }

        $this->logAudit('DELETE_EXAMEN', 'examens', (int)$id);

        header('Location: ' . BASE_URL .
            'admin/examens?deleted=1');
        exit;
    }

    // ── GESTION PARAMÈTRES ───────────────────────────────
    public function parametres(string $id): void {
        Auth::requireRole(['admin']);
        $user = Auth::user();

        $stmt = $this->db->prepare("
            SELECT * FROM examens WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $examen = $stmt->fetch();

        if (!$examen) {
            header('Location: ' . BASE_URL . 'admin/examens');
            exit;
        }

        $parametres = $this->db->prepare("
            SELECT * FROM examen_parametres
            WHERE examen_id = :id
            ORDER BY ordre ASC
        ");
        $parametres->execute([':id' => $id]);
        $parametres = $parametres->fetchAll();

        $created = isset($_GET['created']);
        $success = isset($_GET['saved']);
        $error   = $_SESSION['param_error'] ?? null;
        unset($_SESSION['param_error']);

        include dirname(__DIR__) .
            '/views/admin/examens/parametres.php';
    }

    // ── AJOUTER PARAMÈTRE ────────────────────────────────
    public function storeParametre(string $id): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $code     = trim($_POST['code']    ?? '');
        $libelle  = trim($_POST['libelle'] ?? '');
        $type     = $_POST['type']         ?? 'numeric';
        $unite    = trim($_POST['unite']   ?? '');
        $min      = $_POST['valeur_min']   ?? '';
        $max      = $_POST['valeur_max']   ?? '';
        $options  = trim($_POST['valeurs_possibles'] ?? '');
        $ordre    = (int)($_POST['ordre']  ?? 0);

        if (empty($libelle)) {
            $_SESSION['param_error'] =
                'Le libellé est obligatoire.';
            header('Location: ' . BASE_URL .
                'admin/examens/' . $id . '/parametres');
            exit;
        }

        // Cohérence type / normes : si min ou max sont renseignés,
        // le paramètre doit être numérique pour que l'interprétation
        // automatique (normal/bas/élevé) puisse fonctionner.
        if (($min !== '' || $max !== '') && $type !== 'numeric') {
            $type = 'numeric';
        }

        // Valeurs possibles en JSON pour select
        $valeursPossibles = null;
        if ($type === 'select' && !empty($options)) {
            $optionsArray = array_filter(
                array_map('trim', explode("\n", $options))
            );
            $valeursPossibles = json_encode(
                array_values($optionsArray)
            );
        }

        // Ordre auto
        if ($ordre === 0) {
            $maxOrdre = $this->db->prepare("
                SELECT COALESCE(MAX(ordre), 0)
                FROM examen_parametres WHERE examen_id = :id
            ");
            $maxOrdre->execute([':id' => $id]);
            $ordre = $maxOrdre->fetchColumn() + 1;
        }

        $this->db->prepare("
            INSERT INTO examen_parametres
                (examen_id, code, libelle, type, unite,
                 valeur_min, valeur_max,
                 valeurs_possibles, ordre)
            VALUES
                (:eid, :code, :lib, :type, :unite,
                 :min, :max, :vals, :ordre)
        ")->execute([
            ':eid'   => $id,
            ':code'  => $code ?: null,
            ':lib'   => $libelle,
            ':type'  => $type,
            ':unite' => $unite ?: null,
            ':min'   => $min !== '' ? $min : null,
            ':max'   => $max !== '' ? $max : null,
            ':vals'  => $valeursPossibles,
            ':ordre' => $ordre,
        ]);

        $this->logAudit(
            'CREATE_PARAMETRE', 'examen_parametres', (int)$id
        );

        header('Location: ' . BASE_URL .
            'admin/examens/' . $id . '/parametres?saved=1');
        exit;
    }

    // ── MODIFIER PARAMÈTRE ───────────────────────────────
    public function updateParametre(string $pid): void {
        Auth::requireRole(['admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $examenId = $_POST['examen_id'] ?? 0;
        $libelle  = trim($_POST['libelle'] ?? '');
        $type     = $_POST['type']         ?? 'numeric';
        $unite    = trim($_POST['unite']   ?? '');
        $min      = $_POST['valeur_min']   ?? '';
        $max      = $_POST['valeur_max']   ?? '';
        $options  = trim($_POST['valeurs_possibles'] ?? '');
        $ordre    = (int)($_POST['ordre']  ?? 0);

        // Cohérence type / normes : si min ou max sont renseignés,
        // le paramètre doit être numérique pour que l'interprétation
        // automatique (normal/bas/élevé) puisse fonctionner.
        if (($min !== '' || $max !== '') && $type !== 'numeric') {
            $type = 'numeric';
        }

        $valeursPossibles = null;
        if ($type === 'select' && !empty($options)) {
            $optionsArray = array_filter(
                array_map('trim', explode("\n", $options))
            );
            $valeursPossibles = json_encode(
                array_values($optionsArray)
            );
        }

        $this->db->prepare("
            UPDATE examen_parametres SET
                libelle = :lib, type = :type,
                unite = :unite,
                valeur_min = :min, valeur_max = :max,
                valeurs_possibles = :vals, ordre = :ordre
            WHERE id = :pid
        ")->execute([
            ':lib'   => $libelle,
            ':type'  => $type,
            ':unite' => $unite ?: null,
            ':min'   => $min !== '' ? $min : null,
            ':max'   => $max !== '' ? $max : null,
            ':vals'  => $valeursPossibles,
            ':ordre' => $ordre,
            ':pid'   => $pid,
        ]);

        header('Location: ' . BASE_URL .
            'admin/examens/' . $examenId .
            '/parametres?saved=1');
        exit;
    }

    // ── SUPPRIMER PARAMÈTRE ──────────────────────────────
    public function deleteParametre(string $pid): void {
        Auth::requireRole(['admin']);

        $examenId = $_POST['examen_id'] ?? 0;

        $this->db->prepare("
            DELETE FROM examen_parametres WHERE id = :id
        ")->execute([':id' => $pid]);

        header('Location: ' . BASE_URL .
            'admin/examens/' . $examenId .
            '/parametres?saved=1');
        exit;
    }

    // ── HELPERS ──────────────────────────────────────────
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