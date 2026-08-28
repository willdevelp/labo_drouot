<?php
/**
 * DemandeController — Gestion des demandes d'examens
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class DemandeController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ── LISTE DEMANDES ───────────────────────────────────
    public function index(): void {
        Auth::requireRole(['secretaire', 'admin', 'caissier']);
        $user = Auth::user();

        $search = trim($_GET['search'] ?? '');
        $statut = $_GET['statut'] ?? '';

        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]          = "(p.nom LIKE :s OR p.prenom LIKE :s2
                                  OR p.telephone LIKE :s3)";
            $like             = '%' . $search . '%';
            $params[':s']     = $like;
            $params[':s2']    = $like;
            $params[':s3']    = $like;
        }
        if ($statut !== '') {
            $where[]          = "d.status = :statut";
            $params[':statut']= $statut;
        }

        $whereStr = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                d.id, d.total_price, d.status, d.payment_status, d.created_at,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone,
                u.name AS createur,
                GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
            LEFT JOIN examens e ON dei.examen_id = e.id
            WHERE $whereStr
            GROUP BY d.id
            ORDER BY d.created_at DESC
            LIMIT 60
        ");
        $stmt->execute($params);
        $demandes = $stmt->fetchAll();

        include __DIR__ . '/../views/secretaire/demandes/index.php';
    }

    // ── FORMULAIRE CRÉATION ──────────────────────────────
    public function create(): void {
        Auth::requireRole(['secretaire', 'admin']);
        $user = Auth::user();

        // Patient pré-sélectionné via ?patient_id=X
        // On récupère aussi son assurance liée (si renseignée à la
        // création du patient) pour la pré-afficher dans le formulaire.
        $patientId      = $_GET['patient_id'] ?? null;
        $patientPreload = null;

        if ($patientId) {
            $stmt = $this->db->prepare("
                SELECT p.id, p.uuid, p.nom, p.prenom, p.telephone,
                       p.assurance_id, p.num_assurance,
                       a.nom AS assurance_nom, a.taux_defaut AS assurance_taux_defaut
                FROM patients p
                LEFT JOIN assurances a ON a.id = p.assurance_id AND a.is_active = 1
                WHERE p.id = :id LIMIT 1
            ");
            $stmt->execute([':id' => $patientId]);
            $patientPreload = $stmt->fetch();
        }

        // Charger les examens groupés par catégorie
        $stmt = $this->db->query("
            SELECT id, code, nom, categorie, prix, description
            FROM examens
            WHERE is_active = 1
            ORDER BY categorie, nom
        ");
        $examensRaw = $stmt->fetchAll();

        // Grouper par catégorie
        $examensGroupes = [];
        foreach ($examensRaw as $e) {
            $cat = $e['categorie'] ?? 'Autres';
            $examensGroupes[$cat][] = $e;
        }

        // Liste patients pour recherche (avec assurance liée, si existante)
        $patients = $this->db->query("
            SELECT p.id, p.nom, p.prenom, p.telephone,
                   p.assurance_id, p.num_assurance,
                   a.nom AS assurance_nom, a.taux_defaut AS assurance_taux_defaut
            FROM patients p
            LEFT JOIN assurances a ON a.id = p.assurance_id AND a.is_active = 1
            ORDER BY p.nom, p.prenom
            LIMIT 200
        ")->fetchAll();

        // Liste médecins prescripteurs actifs pour autocomplete
        $medecins = $this->db->query("
            SELECT id, nom, prenom, specialite
            FROM medecins
            WHERE is_active = 1
            ORDER BY nom, prenom
            LIMIT 200
        ")->fetchAll();

        // Liste assurances actives pour autocomplete
        $assurances = $this->db->query("
            SELECT id, nom, taux_defaut
            FROM assurances
            WHERE is_active = 1
            ORDER BY nom
            LIMIT 200
        ")->fetchAll();

        // Taux spécifiques par examen, pour chaque assurance active
        // (sert au calcul du reste à charge en direct côté JS).
        // NB : ce sont les taux "compagnie" — le taux propre à un
        // assuré précis (le plus prioritaire) n'est connu qu'après
        // vérification du numéro de police, via checkAssurance().
        $tauxParExamen = [];
        if (!empty($assurances)) {
            $stmtTaux = $this->db->query("
                SELECT assurance_id, examen_id, taux
                FROM assurance_taux_examens
            ");
            foreach ($stmtTaux->fetchAll() as $t) {
                $tauxParExamen[$t['assurance_id']][$t['examen_id']] = (float) $t['taux'];
            }
        }

        $error = $_SESSION['form_error'] ?? null;
        unset($_SESSION['form_error']);

        include __DIR__ . '/../views/secretaire/demandes/create.php';
    }

    // ── VÉRIFICATION ASSURÉ (AJAX) ───────────────────────
    // Cherche le numéro de police dans le fichier importé pour
    // cette assurance. Retourne en JSON le statut trouvé, ainsi que
    // les taux spécifiques configurés pour CET assuré précis (les plus
    // prioritaires dans le calcul du reste à charge), ou 'non_trouve'
    // si absent — dans ce cas le système retombe sur le code d'accord
    // manuel ou le portail assureur.
    public function checkAssurance(): void {
        Auth::requireRole(['secretaire', 'admin']);
        header('Content-Type: application/json');

        $assuranceId = (int)($_GET['assurance_id'] ?? 0);
        $police      = trim($_GET['numero_police'] ?? '');

        if ($assuranceId <= 0 || $police === '') {
            echo json_encode(['statut' => 'non_trouve']);
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT id, nom_assure, statut
            FROM assures
            WHERE assurance_id = :aid AND numero_police = :police
            LIMIT 1
        ");
        $stmt->execute([':aid' => $assuranceId, ':police' => $police]);
        $assure = $stmt->fetch();

        if (!$assure) {
            echo json_encode(['statut' => 'non_trouve']);
            exit;
        }

        // Taux propres à cet assuré (configurés par l'assureur depuis
        // son portail) — prioritaires sur les taux compagnie/examen.
        $stmtTauxAssure = $this->db->prepare("
            SELECT examen_id, taux FROM assure_taux_examens WHERE assure_id = :assid
        ");
        $stmtTauxAssure->execute([':assid' => $assure['id']]);
        $tauxAssure = [];
        foreach ($stmtTauxAssure->fetchAll() as $t) {
            $tauxAssure[$t['examen_id']] = (float) $t['taux'];
        }

        echo json_encode([
            'statut'      => $assure['statut'], // 'en_regle' ou 'suspendu'
            'nom_assure'  => $assure['nom_assure'],
            'assure_id'   => $assure['id'],
            'taux_assure' => $tauxAssure,
        ]);
        exit;
    }

    // ── EXAMENS COUVERTS PAR UNE ASSURANCE (AJAX) ────────
    // Retourne, pour une assurance donnée, la liste des examens
    // pour lesquels un taux spécifique est configuré. Utilisé
    // côté vue pour afficher clairement "vos droits" une fois
    // le numéro de police validé (au-delà du simple calcul silencieux
    // déjà fait via $tauxParExamen).
    public function examensCouverts(): void {
        Auth::requireRole(['secretaire', 'admin']);
        header('Content-Type: application/json');

        $assuranceId = (int)($_GET['assurance_id'] ?? 0);
        if ($assuranceId <= 0) {
            echo json_encode(['examens' => []]);
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT a.taux_defaut
            FROM assurances a WHERE a.id = :aid AND a.is_active = 1 LIMIT 1
        ");
        $stmt->execute([':aid' => $assuranceId]);
        $assurance = $stmt->fetch();

        if (!$assurance) {
            echo json_encode(['examens' => []]);
            exit;
        }

        $stmtEx = $this->db->prepare("
            SELECT e.id, e.nom, e.code,
                   COALESCE(ate.taux, :tauxDefaut) AS taux
            FROM examens e
            LEFT JOIN assurance_taux_examens ate
                   ON ate.examen_id = e.id AND ate.assurance_id = :aid
            WHERE e.is_active = 1
            ORDER BY e.nom
        ");
        $stmtEx->execute([':aid' => $assuranceId, ':tauxDefaut' => $assurance['taux_defaut']]);

        echo json_encode(['examens' => $stmtEx->fetchAll()]);
        exit;
    }

    // ── ENREGISTREMENT DEMANDE ───────────────────────────
    public function store(): void {
        Auth::requireRole(['secretaire', 'admin']);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $patientId     = (int)($_POST['patient_id'] ?? 0);
        $examenIds     = $_POST['examen_ids'] ?? [];
        $medecinId     = (int)($_POST['medecin_id'] ?? 0);
        $assuranceId   = (int)($_POST['assurance_id'] ?? 0);
        $numeroPolice  = trim($_POST['numero_police'] ?? '');
        $codeAccord    = trim($_POST['code_accord'] ?? '');
        // Renseigné par le JS après vérification AJAX dans le fichier importé :
        // 'en_regle', 'suspendu', ou vide si non trouvé / non vérifié.
        $verifFichier  = trim($_POST['verif_fichier_statut'] ?? '');
        $userId        = Auth::user()['id'];

        // Validations
        if (!$patientId) {
            $_SESSION['form_error'] = 'Veuillez sélectionner un patient.';
            header('Location: ' . BASE_URL . 'secretaire/demandes/create');
            exit;
        }
        if (empty($examenIds)) {
            $_SESSION['form_error'] = 'Veuillez sélectionner au moins un examen.';
            header('Location: ' . BASE_URL . 'secretaire/demandes/create?patient_id=' . $patientId);
            exit;
        }

        // Vérifier patient existe
        $stmt = $this->db->prepare("SELECT id FROM patients WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([':id' => $patientId]);
        if (!$stmt->fetch()) {
            $_SESSION['form_error'] = 'Patient introuvable.';
            header('Location: ' . BASE_URL . 'secretaire/demandes/create');
            exit;
        }

        // Vérifier médecin prescripteur si renseigné (optionnel)
        if ($medecinId > 0) {
            $stmtMed = $this->db->prepare("
                SELECT id FROM medecins
                WHERE id = :id AND is_active = 1 LIMIT 1
            ");
            $stmtMed->execute([':id' => $medecinId]);
            if (!$stmtMed->fetch()) {
                // Médecin invalide ou désactivé : on continue sans bloquer
                // la création de la demande, on ignore juste le champ.
                $medecinId = 0;
            }
        }

        // Vérifier assurance si renseignée (optionnel) et déterminer le statut
        $assuranceStatut = 'aucune';
        if ($assuranceId > 0) {
            $stmtAss = $this->db->prepare("
                SELECT id FROM assurances
                WHERE id = :id AND is_active = 1 LIMIT 1
            ");
            $stmtAss->execute([':id' => $assuranceId]);
            if (!$stmtAss->fetch()) {
                // Assurance invalide ou désactivée : on ignore le champ,
                // la demande est traitée comme sans assurance.
                $assuranceId  = 0;
                $numeroPolice = '';
                $codeAccord   = '';
            } elseif ($verifFichier === 'en_regle') {
                // Priorité 1 : trouvé dans le fichier d'assurés importé,
                // statut "en règle" → validation automatique immédiate,
                // pas besoin de code d'accord.
                $assuranceStatut = 'validee';
            } elseif ($verifFichier === 'suspendu') {
                // Trouvé mais suspendu : pas de prise en charge possible,
                // sauf si la secrétaire a malgré tout un code d'accord
                // manuel (cas rare, contrat réactivé entre-temps par ex.)
                $assuranceStatut = $codeAccord !== '' ? 'validee' : 'rejetee';
            } else {
                // Priorité 2 : non trouvé dans le fichier — repli sur le
                // système existant : code d'accord manuel (Cas A) ou
                // envoi au portail assureur en attente (Cas B).
                $assuranceStatut = $codeAccord !== '' ? 'validee' : 'en_attente';
            }
        }

        // Résoudre l'assuré correspondant au numéro de police, s'il
        // existe, pour pouvoir appliquer ses éventuels taux propres
        // (priorité la plus haute) lors du calcul plus bas.
        $assureId = null;
        if ($assuranceId > 0 && $numeroPolice !== '') {
            $stmtAssureLookup = $this->db->prepare("
                SELECT id FROM assures
                WHERE assurance_id = :aid AND numero_police = :police
                LIMIT 1
            ");
            $stmtAssureLookup->execute([':aid' => $assuranceId, ':police' => $numeroPolice]);
            $assureId = $stmtAssureLookup->fetchColumn() ?: null;
        }

        // Récupérer examens valides
        $placeholders = implode(',', array_fill(0, count($examenIds), '?'));
        $stmt = $this->db->prepare("
            SELECT id, prix FROM examens
            WHERE id IN ($placeholders) AND is_active = 1 AND deleted_at IS NULL
        ");
        $stmt->execute(array_values($examenIds));
        $examensChoisis = $stmt->fetchAll();

        if (empty($examensChoisis)) {
            $_SESSION['form_error'] = 'Examens invalides.';
            header('Location: ' . BASE_URL . 'secretaire/demandes/create?patient_id=' . $patientId);
            exit;
        }

        $total = array_sum(array_column($examensChoisis, 'prix'));

        // Calcul du reste à charge patient (montant_patient) si la
        // couverture assurance est validée. Le taux s'applique par
        // examen, avec la priorité suivante :
        //   1. taux propre à l'assuré (assure_taux_examens)
        //   2. taux propre à la compagnie pour cet examen (assurance_taux_examens)
        //   3. taux par défaut de la compagnie (assurances.taux_defaut)
        // Sans assurance validée, le patient paie le total normalement.
        $montantPatient = $total;
        $montantAssurancePrevisionnel = 0;

        if ($assuranceId > 0 && $assuranceStatut === 'validee') {
            $stmtTauxDefaut = $this->db->prepare("
                SELECT taux_defaut FROM assurances WHERE id = :id
            ");
            $stmtTauxDefaut->execute([':id' => $assuranceId]);
            $tauxDefaut = (float) $stmtTauxDefaut->fetchColumn();

            $stmtTauxExamen = $this->db->prepare("
                SELECT taux FROM assurance_taux_examens
                WHERE assurance_id = :aid AND examen_id = :eid
            ");
            $stmtTauxAssureExamen = $this->db->prepare("
                SELECT taux FROM assure_taux_examens
                WHERE assure_id = :assid AND examen_id = :eid
            ");

            $montantPatient = 0;
            foreach ($examensChoisis as $ex) {
                // Priorité 3 : taux par défaut
                $taux = $tauxDefaut;

                // Priorité 2 : taux compagnie/examen
                $stmtTauxExamen->execute([':aid' => $assuranceId, ':eid' => $ex['id']]);
                $tauxCompagnie = $stmtTauxExamen->fetchColumn();
                if ($tauxCompagnie !== false) {
                    $taux = (float) $tauxCompagnie;
                }

                // Priorité 1 : taux propre à l'assuré (le plus spécifique)
                if ($assureId) {
                    $stmtTauxAssureExamen->execute([':assid' => $assureId, ':eid' => $ex['id']]);
                    $tauxAssure = $stmtTauxAssureExamen->fetchColumn();
                    if ($tauxAssure !== false) {
                        $taux = (float) $tauxAssure;
                    }
                }

                $partAssurance   = round($ex['prix'] * $taux / 100, 2);
                $partPatient     = $ex['prix'] - $partAssurance;
                $montantPatient += $partPatient;
                $montantAssurancePrevisionnel += $partAssurance;
            }
        }

        try {
            $this->db->beginTransaction();

            // 1. Créer la demande
            $reference = 'DEM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $stmt = $this->db->prepare("
                INSERT INTO demandes_examens
                    (reference, patient_id, medecin_id,
                     assurance_id, numero_police, code_accord, assurance_statut,
                     created_by,
                     total_price, montant_net,
                     status, payment_status)
                VALUES
                    (:ref, :pid, :mid,
                     :aid, :police, :code, :astatut,
                     :uid,
                     :total, :net,
                     'en_attente_paiement', 'unpaid')
            ");
            $stmt->execute([
                ':ref'     => $reference,
                ':pid'     => $patientId,
                ':mid'     => $medecinId > 0 ? $medecinId : null,
                ':aid'     => $assuranceId > 0 ? $assuranceId : null,
                ':police'  => $numeroPolice ?: null,
                ':code'    => $codeAccord ?: null,
                ':astatut' => $assuranceStatut,
                ':uid'     => $userId,
                ':total'   => $total,
                ':net'     => $total,
            ]);
            $demandeId = (int) $this->db->lastInsertId();

            // 2. Insérer les items
            $stmtItem = $this->db->prepare("
                INSERT INTO demande_examens_items (demande_id, examen_id, price)
                VALUES (:did, :eid, :price)
            ");
            foreach ($examensChoisis as $ex) {
                $stmtItem->execute([
                    ':did'   => $demandeId,
                    ':eid'   => $ex['id'],
                    ':price' => $ex['prix'],
                ]);
            }

            // 3. Créer la facture proforma
            $numFacture = 'FAC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $this->db->prepare("
                INSERT INTO factures
                    (numero, demande_id, patient_id,
                     montant_total, montant_net, montant_patient,
                     statut)
                VALUES
                    (:num, :did, :pid,
                     :total, :net, :patientPart,
                     'emise')
            ")->execute([
                ':num'         => $numFacture,
                ':did'         => $demandeId,
                ':pid'         => $patientId,
                ':total'       => $total,
                ':net'         => $total,
                ':patientPart' => $montantPatient,
            ]);

            // 4. Créer l'échantillon
            $codeBarre = 'ECH-' . date('Y') . '-' . str_pad($demandeId, 5, '0', STR_PAD_LEFT);

            $this->db->prepare("
                INSERT INTO echantillons
                    (demande_id, code_barre, type, status, collected_at)
                VALUES
                    (:did, :cb, 'sang', 'collecte', NOW())
            ")->execute([
                ':did' => $demandeId,
                ':cb'  => $codeBarre,
            ]);

            // 5. Audit
            $this->db->prepare("
                INSERT INTO audit_logs (user_id, action, table_name, record_id)
                VALUES (:uid, 'CREATE', 'demandes_examens', :rid)
            ")->execute([':uid' => $userId, ':rid' => $demandeId]);

            $this->db->commit();

            header('Location: ' . BASE_URL . 'secretaire/demandes/' . $demandeId . '?created=1');
            exit;

        } catch (PDOException $e) {
            $this->db->rollBack();
            $_SESSION['form_error'] = 'Erreur : ' . $e->getMessage();
            header('Location: ' . BASE_URL . 'secretaire/demandes/create?patient_id=' . $patientId);
            exit;
        }
    }

    // ── DÉTAIL DEMANDE ───────────────────────────────────
    public function show(string $id): void {
        Auth::requireRole(['secretaire', 'admin', 'caissier',
                           'technicien', 'biologiste']);
        $user = Auth::user();

        // Demande
        $stmt = $this->db->prepare("
            SELECT d.*,
                CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
                p.telephone, p.uuid AS patient_uuid, p.id AS pid,
                u.name AS createur,
                CONCAT(m.prenom, ' ', m.nom) AS medecin_nom,
                m.specialite AS medecin_specialite,
                a.nom AS assurance_nom,
                a.taux_defaut AS assurance_taux_defaut
            FROM demandes_examens d
            JOIN patients p ON d.patient_id = p.id
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN medecins m ON d.medecin_id = m.id
            LEFT JOIN assurances a ON d.assurance_id = a.id
            WHERE d.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $demande = $stmt->fetch();

        if (!$demande) {
            http_response_code(404);
            include __DIR__ . '/../views/errors/404.php';
            return;
        }

        // Items examens
        $items = $this->db->prepare("
            SELECT dei.price, e.nom, e.code, e.categorie, e.description
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            WHERE dei.demande_id = :did
        ");
        $items->execute([':did' => $id]);
        $examensItems = $items->fetchAll();

        // Facture
        $facture = $this->db->prepare("
            SELECT * FROM factures
            WHERE demande_id = :did
            ORDER BY created_at DESC LIMIT 1
        ");
        $facture->execute([':did' => $id]);
        $facture = $facture->fetch();

        // Échantillon
        $echantillon = $this->db->prepare("
            SELECT * FROM echantillons
            WHERE demande_id = :did LIMIT 1
        ");
        $echantillon->execute([':did' => $id]);
        $echantillon = $echantillon->fetch();

        $created = isset($_GET['created']);

        include __DIR__ . '/../views/secretaire/demandes/show.php';
    }
}