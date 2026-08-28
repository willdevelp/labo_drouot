<?php
/**
 * CreanceHelper — Génération des créances assurance
 * Logique partagée entre CaissierController (paiement complet)
 * et AssurancePortalController (validation différée, Cas B)
 * Laboratoire Drouot
 */
class CreanceHelper {

    /**
     * Génère une créance assurance pour une demande donnée, si les
     * conditions sont réunies. Appelable depuis deux points d'entrée :
     *
     * 1. CaissierController::payer() — quand le patient règle le
     *    reste à charge et que la couverture était déjà validée
     *    (Cas A, ou Cas B déjà validé avant le paiement).
     *
     * 2. AssurancePortalController::valider() — quand l'assureur
     *    valide une demande après coup (Cas B), alors que le patient
     *    a déjà payé le montant total en attendant. Dans ce cas la
     *    créance doit être générée à la validation, puisque le
     *    paiement caissier est déjà passé et ne se redéclenchera pas.
     *
     * Conditions requises pour générer la créance :
     *   - La demande a une assurance associée
     *   - assurance_statut = 'validee'
     *   - La compagnie est active
     *   - Aucune créance n'existe déjà pour cette demande (anti-doublon)
     *
     * @param PDO $db
     * @param int $demandeId
     * @return bool true si une créance a été créée, false sinon
     */
    public static function genererCreanceAssurance(PDO $db, int $demandeId): bool {
        $stmt = $db->prepare("
            SELECT d.id, d.assurance_id, d.assurance_statut,
                   a.taux_defaut, a.is_active
            FROM demandes_examens d
            LEFT JOIN assurances a ON a.id = d.assurance_id
            WHERE d.id = :did
            LIMIT 1
        ");
        $stmt->execute([':did' => $demandeId]);
        $demande = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$demande || empty($demande['assurance_id'])) {
            return false; // Pas d'assurance associée
        }

        if ($demande['assurance_statut'] !== 'validee') {
            return false; // Couverture non validée
        }

        if (!$demande['is_active']) {
            return false; // Compagnie désactivée entre-temps
        }

        // Anti-doublon applicatif (la contrainte UNIQUE en base protège
        // aussi contre les appels concurrents)
        $exists = $db->prepare("
            SELECT id FROM creances_assurance WHERE demande_id = :did LIMIT 1
        ");
        $exists->execute([':did' => $demandeId]);
        if ($exists->fetch()) {
            return false; // Créance déjà existante
        }

        // Détail des examens de la demande, avec leur taux applicable
        $items = $db->prepare("
            SELECT dei.examen_id, dei.price,
                   ate.taux AS taux_specifique
            FROM demande_examens_items dei
            LEFT JOIN assurance_taux_examens ate
                ON ate.examen_id = dei.examen_id
                AND ate.assurance_id = :aid
            WHERE dei.demande_id = :did
        ");
        $items->execute([
            ':aid' => $demande['assurance_id'],
            ':did' => $demandeId,
        ]);
        $lignes = $items->fetchAll(PDO::FETCH_ASSOC);

        $tauxDefaut     = (float) $demande['taux_defaut'];
        $montantBase    = 0;
        $montantCreance = 0;

        foreach ($lignes as $ligne) {
            $prix = (float) $ligne['price'];
            $taux = $ligne['taux_specifique'] !== null
                ? (float) $ligne['taux_specifique']
                : $tauxDefaut;

            $montantBase    += $prix;
            $montantCreance += round($prix * $taux / 100, 2);
        }

        if ($montantCreance <= 0) {
            return false; // Rien à réclamer (taux nul sur tous les examens)
        }

        // Taux moyen pondéré, juste pour la traçabilité affichée
        $tauxApplique = $montantBase > 0
            ? round($montantCreance / $montantBase * 100, 2)
            : $tauxDefaut;

        try {
            $db->prepare("
                INSERT INTO creances_assurance
                    (assurance_id, demande_id, montant_base,
                     taux_applique, montant_creance, statut)
                VALUES
                    (:aid, :did, :base, :taux, :montant, 'due')
            ")->execute([
                ':aid'     => $demande['assurance_id'],
                ':did'     => $demandeId,
                ':base'    => $montantBase,
                ':taux'    => $tauxApplique,
                ':montant' => $montantCreance,
            ]);
            return true;
        } catch (PDOException $e) {
            // Doublon concurrent bloqué par la contrainte UNIQUE —
            // la créance existe déjà, état désiré, on ignore.
            return false;
        }
    }
}
