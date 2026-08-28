<?php
/**
 * DashboardController — Redirection vers le bon dashboard selon le rôle
 * Laboratoire Drouot
 */
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';

class DashboardController {

    public function index(): void {
        if (!Auth::check()) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        $user = Auth::user();
        header('Location: ' . Auth::getRedirectByRole($user['role']));
        exit;
    }

    public function admin(): void {
        Auth::requireRole(['admin']);
        $user = Auth::user();
        include __DIR__ . '/../views/dashboard/admin.php';
    }

    public function directeur(): void {
        Auth::requireRole(['directeur', 'admin']);
        $user = Auth::user();
        include __DIR__ . '/../views/dashboard/directeur.php';
    }

    // ── DONNÉES DASHBOARD DIRECTEUR (AJAX) ───────────────
    // Calcule toutes les métriques financières/opérationnelles pour
    // une période donnée, ainsi que la même période un an plus tôt
    // (N-1) pour comparaison. Retourne tout en JSON, consommé par le
    // JS du dashboard directeur sans rechargement de page.
    public function directeurData(): void {
        Auth::requireRole(['directeur', 'admin']);
        header('Content-Type: application/json');

        $db = \Database::getConnection();

        [$debut, $fin, $debutN1, $finN1, $label] = $this->resolvePeriode(
            $_GET['periode'] ?? 'mois',
            $_GET['debut'] ?? null,
            $_GET['fin'] ?? null
        );

        $actuel = $this->calculerMetriques($db, $debut, $fin);
        $precedent = $this->calculerMetriques($db, $debutN1, $finN1);

        echo json_encode([
            'periode_label' => $label,
            'periode' => [
                'debut' => $debut->format('Y-m-d'),
                'fin'   => $fin->format('Y-m-d'),
            ],
            'periode_n1' => [
                'debut' => $debutN1->format('Y-m-d'),
                'fin'   => $finN1->format('Y-m-d'),
            ],
            'actuel'    => $actuel,
            'precedent' => $precedent,
            'variation' => $this->calculerVariations($actuel, $precedent),
        ]);
        exit;
    }

    // ── RÉSOLUTION DE LA PÉRIODE DEMANDÉE ────────────────
    // Retourne [debutPeriode, finPeriode, debutN1, finN1, label]
    // N-1 = même période, un an plus tôt exactement (pas la période
    // précédente immédiate), conformément au choix retenu.
    private function resolvePeriode(string $periode, ?string $debutStr, ?string $finStr): array {
        $now = new \DateTime('now');

        switch ($periode) {
            case 'semaine':
                $debut = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
                $fin   = (clone $debut)->modify('+6 days')->setTime(23, 59, 59);
                $label = 'Cette semaine';
                break;

            case 'annee':
                $debut = new \DateTime($now->format('Y') . '-01-01 00:00:00');
                $fin   = new \DateTime($now->format('Y') . '-12-31 23:59:59');
                $label = 'Cette année';
                break;

            case 'custom':
                $debut = $debutStr
                    ? new \DateTime($debutStr . ' 00:00:00')
                    : new \DateTime('first day of this month 00:00:00');
                $fin = $finStr
                    ? new \DateTime($finStr . ' 23:59:59')
                    : new \DateTime('last day of this month 23:59:59');
                $label = 'Période personnalisée';
                break;

            case 'mois':
            default:
                $debut = new \DateTime('first day of this month 00:00:00');
                $fin   = new \DateTime('last day of this month 23:59:59');
                $label = 'Ce mois';
                break;
        }

        // N-1 : même période, un an plus tôt jour pour jour.
        $debutN1 = (clone $debut)->modify('-1 year');
        $finN1   = (clone $fin)->modify('-1 year');

        return [$debut, $fin, $debutN1, $finN1, $label];
    }

    // ── CALCUL DES MÉTRIQUES POUR UNE PÉRIODE DONNÉE ─────
    private function calculerMetriques(\PDO $db, \DateTime $debut, \DateTime $fin): array {
        $d = $debut->format('Y-m-d H:i:s');
        $f = $fin->format('Y-m-d H:i:s');

        // ── Cash-flow réel ───────────────────────────────
        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(f.montant_total), 0) AS facture_brut,
                COALESCE(SUM(f.montant_patient), 0) AS facture_patient_part,
                COUNT(DISTINCT f.id) AS nb_factures
            FROM factures f
            WHERE f.created_at BETWEEN :d AND :f
            AND f.deleted_at IS NULL
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $facture = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(montant), 0) AS encaisse, COUNT(*) AS nb_paiements
            FROM paiements
            WHERE statut = 'valide' AND created_at BETWEEN :d AND :f
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $encaisse = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmt = $db->prepare("
            SELECT COALESCE(SUM(f.montant_patient), 0) AS en_attente
            FROM factures f
            WHERE f.statut IN ('emise', 'partiellement_payee')
            AND f.created_at BETWEEN :d AND :f
            AND f.deleted_at IS NULL
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $enAttente = (float) $stmt->fetchColumn();

        // ── Créances assurance générées sur la période ───
        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN statut = 'due' THEN montant_creance ELSE 0 END), 0) AS due,
                COALESCE(SUM(CASE WHEN statut = 'payee' THEN montant_creance ELSE 0 END), 0) AS payee
            FROM creances_assurance
            WHERE created_at BETWEEN :d AND :f
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $creances = $stmt->fetch(\PDO::FETCH_ASSOC);

        // ── Commissions médecins générées sur la période ─
        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN statut = 'due' THEN montant_commission ELSE 0 END), 0) AS due,
                COALESCE(SUM(CASE WHEN statut = 'payee' THEN montant_commission ELSE 0 END), 0) AS payee
            FROM commissions
            WHERE created_at BETWEEN :d AND :f
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $commissions = $stmt->fetch(\PDO::FETCH_ASSOC);

        $chargesExternes = (float)$creances['due'] + (float)$creances['payee']
            + (float)$commissions['due'] + (float)$commissions['payee'];

        // ── Revenus par catégorie d'examen ───────────────
        $stmt = $db->prepare("
            SELECT
                e.categorie,
                COUNT(dei.id) AS nb,
                COALESCE(SUM(dei.price), 0) AS total
            FROM demande_examens_items dei
            JOIN examens e ON dei.examen_id = e.id
            JOIN demandes_examens de ON dei.demande_id = de.id
            WHERE de.created_at BETWEEN :d AND :f
            GROUP BY e.categorie
            ORDER BY total DESC
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $parCategorie = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // ── Patients et demandes ─────────────────────────
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM patients WHERE created_at BETWEEN :d AND :f
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $nbPatients = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM demandes_examens WHERE created_at BETWEEN :d AND :f
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $nbDemandes = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM demandes_examens
            WHERE created_at BETWEEN :d AND :f AND status = 'terminee'
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $nbTerminees = (int) $stmt->fetchColumn();

        $tauxValidation = $nbDemandes > 0 ? round($nbTerminees / $nbDemandes * 100) : 0;

        $netReel = (float)$facture['facture_brut'] - $chargesExternes;

        $operationnel = $this->calculerOperationnel($db, $debut, $fin);

        return [
            'cashflow' => [
                'facture_brut'   => (float)$facture['facture_brut'],
                'encaisse'       => (float)$encaisse['encaisse'],
                'en_attente'     => $enAttente,
                'nb_factures'    => (int)$facture['nb_factures'],
                'nb_paiements'   => (int)$encaisse['nb_paiements'],
            ],
            'creances' => [
                'due'   => (float)$creances['due'],
                'payee' => (float)$creances['payee'],
            ],
            'commissions' => [
                'due'   => (float)$commissions['due'],
                'payee' => (float)$commissions['payee'],
            ],
            'net_reel'       => $netReel,
            'par_categorie'  => $parCategorie,
            'nb_patients'    => $nbPatients,
            'nb_demandes'    => $nbDemandes,
            'taux_validation'=> $tauxValidation,
            'operationnel'   => $operationnel,
        ];
    }

    // ── PILOTAGE OPÉRATIONNEL : TEMPS PAR ÉTAPE + CHARGE ─
    // Reconstitue le temps moyen passé à chaque étape du pipeline en
    // s'appuyant sur audit_logs pour les transitions qui n'ont pas de
    // colonne d'horodatage dédiée (approximation acceptée). La charge
    // par rôle est calculée en instantané (indépendante de la période,
    // c'est l'état actuel de la file d'attente qui compte).
    private function calculerOperationnel(\PDO $db, \DateTime $debut, \DateTime $fin): array {
        $d = $debut->format('Y-m-d H:i:s');
        $f = $fin->format('Y-m-d H:i:s');

        // Demandes créées sur la période, avec leurs jalons
        $stmt = $db->prepare("
            SELECT
                de.id, de.created_at AS demande_creee,
                ech.id AS ech_id, ech.collected_at,
                v.validated_at,
                (
                    SELECT MIN(al.created_at) FROM audit_logs al
                    WHERE al.table_name = 'resultats'
                    AND al.action = 'SAVE_RESULTS'
                    AND al.record_id = ech.id
                ) AS premiere_saisie,
                (
                    SELECT al.created_at FROM audit_logs al
                    WHERE al.table_name = 'echantillons'
                    AND al.action = 'TRANSMIT_BIOLOGISTE'
                    AND al.record_id = ech.id
                    ORDER BY al.created_at DESC LIMIT 1
                ) AS transmission
            FROM demandes_examens de
            LEFT JOIN echantillons ech ON ech.demande_id = de.id
            LEFT JOIN validations v ON v.demande_id = de.id
            WHERE de.created_at BETWEEN :d AND :f
        ");
        $stmt->execute([':d' => $d, ':f' => $f]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $etapes = [
            'creation_collecte'  => [],
            'collecte_saisie'    => [],
            'saisie_transmission'=> [],
            'transmission_validation' => [],
        ];

        foreach ($rows as $r) {
            $tCree   = $r['demande_creee']   ? new \DateTime($r['demande_creee'])   : null;
            $tCollec = $r['collected_at']    ? new \DateTime($r['collected_at'])    : null;
            $tSaisie = $r['premiere_saisie'] ? new \DateTime($r['premiere_saisie']) : null;
            $tTrans  = $r['transmission']    ? new \DateTime($r['transmission'])   : null;
            $tValide = $r['validated_at']    ? new \DateTime($r['validated_at'])   : null;

            if ($tCree && $tCollec) {
                $etapes['creation_collecte'][] = ($tCollec->getTimestamp() - $tCree->getTimestamp()) / 3600;
            }
            if ($tCollec && $tSaisie) {
                $etapes['collecte_saisie'][] = ($tSaisie->getTimestamp() - $tCollec->getTimestamp()) / 3600;
            }
            if ($tSaisie && $tTrans) {
                $etapes['saisie_transmission'][] = ($tTrans->getTimestamp() - $tSaisie->getTimestamp()) / 3600;
            }
            if ($tTrans && $tValide) {
                $etapes['transmission_validation'][] = ($tValide->getTimestamp() - $tTrans->getTimestamp()) / 3600;
            }
        }

        $moyenne = fn(array $vals) => count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;

        $tempsParEtape = [
            ['cle' => 'creation_collecte',  'label' => 'Création → Collecte',        'heures' => $moyenne($etapes['creation_collecte'])],
            ['cle' => 'collecte_saisie',    'label' => 'Collecte → Saisie résultats', 'heures' => $moyenne($etapes['collecte_saisie'])],
            ['cle' => 'saisie_transmission','label' => 'Saisie → Transmission',       'heures' => $moyenne($etapes['saisie_transmission'])],
            ['cle' => 'transmission_validation', 'label' => 'Transmission → Validation', 'heures' => $moyenne($etapes['transmission_validation'])],
        ];

        // Goulot d'étranglement = étape avec le temps moyen le plus élevé
        $goulot = null;
        $maxHeures = -1;
        foreach ($tempsParEtape as $e) {
            if ($e['heures'] !== null && $e['heures'] > $maxHeures) {
                $maxHeures = $e['heures'];
                $goulot = $e['label'];
            }
        }

        // ── Charge actuelle par rôle (instantané, hors période) ──
        $stmt = $db->query("
            SELECT COUNT(*) FROM echantillons ech
            JOIN demandes_examens d ON ech.demande_id = d.id
            WHERE d.payment_status = 'paid'
            AND ech.status IN ('collecte', 'en_analyse')
        ");
        $chargeTechnicien = (int) $stmt->fetchColumn();

        $stmt = $db->query("
            SELECT COUNT(*) FROM echantillons ech
            JOIN demandes_examens d ON ech.demande_id = d.id
            WHERE ech.status = 'analyse' AND d.status != 'terminee'
        ");
        $chargeBiologiste = (int) $stmt->fetchColumn();

        $stmt = $db->query("
            SELECT COUNT(*) FROM factures
            WHERE statut IN ('emise', 'partiellement_payee') AND deleted_at IS NULL
        ");
        $chargeCaissier = (int) $stmt->fetchColumn();

        return [
            'temps_par_etape' => $tempsParEtape,
            'goulot'          => $goulot,
            'charge' => [
                'technicien' => $chargeTechnicien,
                'biologiste' => $chargeBiologiste,
                'caissier'   => $chargeCaissier,
            ],
        ];
    }

    // ── CALCUL DES VARIATIONS EN % ENTRE DEUX PÉRIODES ───
    private function calculerVariations(array $actuel, array $precedent): array {
        $pct = function (float $now, float $before): ?float {
            if ($before == 0) {
                return $now > 0 ? 100.0 : 0.0;
            }
            return round(($now - $before) / $before * 100, 1);
        };

        return [
            'facture_brut'    => $pct($actuel['cashflow']['facture_brut'], $precedent['cashflow']['facture_brut']),
            'encaisse'        => $pct($actuel['cashflow']['encaisse'], $precedent['cashflow']['encaisse']),
            'net_reel'        => $pct($actuel['net_reel'], $precedent['net_reel']),
            'nb_patients'     => $pct($actuel['nb_patients'], $precedent['nb_patients']),
            'nb_demandes'     => $pct($actuel['nb_demandes'], $precedent['nb_demandes']),
            'taux_validation' => $pct($actuel['taux_validation'], $precedent['taux_validation']),
        ];
    }

    public function secretaire(): void {
        Auth::requireRole(['secretaire', 'admin']);
        $user = Auth::user();
        include __DIR__ . '/../views/dashboard/secretaire.php';
    }

    public function caissier(): void {
        Auth::requireRole(['caissier', 'admin']);
        $user = Auth::user();
        include __DIR__ . '/../views/dashboard/caissier.php';
    }

    public function technicien(): void {
        Auth::requireRole(['technicien', 'admin']);
        $user = Auth::user();
        include __DIR__ . '/../views/dashboard/technicien.php';
    }

public function biologiste(): void {
    Auth::requireRole(['biologiste', 'admin']);
    $user = Auth::user();
    $db   = \Database::getConnection();

    // ── KPI ─────────────────────────────────────────────
    $kpi['a_valider'] = $db->query("
        SELECT COUNT(*) FROM echantillons ech
        JOIN demandes_examens d ON ech.demande_id = d.id
        WHERE ech.status = 'analyse'
        AND d.status != 'terminee'
    ")->fetchColumn();

    $kpi['valides_jour'] = $db->query("
        SELECT COUNT(*) FROM validations
        WHERE DATE(validated_at) = CURDATE()
    ")->fetchColumn();

    $kpi['rejetes_jour'] = $db->query("
        SELECT COUNT(DISTINCT record_id) FROM audit_logs
        WHERE action LIKE 'REJECT:%'
        AND DATE(created_at) = CURDATE()
    ")->fetchColumn();

    $kpi['commentaires'] = $db->query("
        SELECT COUNT(*) FROM validations
        WHERE commentaire IS NOT NULL AND commentaire != ''
    ")->fetchColumn();

    // ── RÉSULTATS À VALIDER ──────────────────────────────
    $aValider = $db->query("
        SELECT
            ech.id, ech.code_barre, ech.status, ech.collected_at,
            d.id AS demande_id, d.total_price,
            CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
            p.telephone, p.sexe, p.date_naissance,
            GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens,
            COUNT(DISTINCT e.id) AS nb_examens,
            SUM(r.is_abnormal)   AS nb_anormaux,
            COUNT(DISTINCT r.id) AS nb_resultats
        FROM echantillons ech
        JOIN demandes_examens d ON ech.demande_id = d.id
        JOIN patients p ON d.patient_id = p.id
        LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
        LEFT JOIN examens e ON dei.examen_id = e.id
        LEFT JOIN resultats r ON r.echantillon_id = ech.id
        WHERE ech.status = 'analyse'
        AND d.status != 'terminee'
        GROUP BY ech.id
        ORDER BY nb_anormaux DESC, ech.collected_at ASC
        LIMIT 10
    ")->fetchAll();

    // ── VALIDATIONS RÉCENTES ─────────────────────────────
    $validations = $db->query("
        SELECT
            v.id, v.commentaire, v.validated_at,
            CONCAT(p.prenom, ' ', p.nom) AS patient_nom,
            ech.code_barre,
            GROUP_CONCAT(e.nom SEPARATOR ', ') AS examens
        FROM validations v
        JOIN demandes_examens d ON v.demande_id = d.id
        JOIN patients p ON d.patient_id = p.id
        JOIN echantillons ech ON ech.demande_id = d.id
        LEFT JOIN demande_examens_items dei ON dei.demande_id = d.id
        LEFT JOIN examens e ON dei.examen_id = e.id
        GROUP BY v.id
        ORDER BY v.validated_at DESC
        LIMIT 5
    ")->fetchAll();

    // ── RÉPARTITION ──────────────────────────────────────
    $repartition = $db->query("
        SELECT
            SUM(CASE WHEN d.status = 'terminee' THEN 1 ELSE 0 END) AS valides,
            SUM(CASE WHEN d.status = 'en_cours'
                AND ech.status != 'analyse'     THEN 1 ELSE 0 END) AS en_attente,
            SUM(CASE WHEN ech.status = 'analyse'
                AND d.status != 'terminee'      THEN 1 ELSE 0 END) AS a_valider
        FROM demandes_examens d
        LEFT JOIN echantillons ech ON ech.demande_id = d.id
    ")->fetch();

    include __DIR__ . '/../views/dashboard/biologiste.php';
}

    // ── AJOUTS ──────────────────────────────────────────

    public function comptable(): void {
        Auth::requireRole(['comptable', 'admin']);
        $user = Auth::user();
        include __DIR__ . '/../views/dashboard/comptable.php';
    }

    public function infirmier(): void {
        Auth::requireRole(['infirmier', 'admin']);
        $user = Auth::user();
        include __DIR__ . '/../views/dashboard/infirmier.php';
    }
}