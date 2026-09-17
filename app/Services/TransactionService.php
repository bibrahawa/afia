<?php

namespace App\Services;

use App\Models\Patient;

/**
 * @deprecated Priorité 3 — nettoyage.
 *
 * Cette classe contenait trois méthodes jamais appelées et incohérentes
 * (`Paiement()` écrivait des colonnes inexistantes : consultation_id,
 * montant, type, mode_paiement… ; `paiementTransaction()` et
 * `paiementPartPatientOrPartInsurance()` renvoyaient des redirections
 * depuis un service et posaient des statuts absents de la base :
 * waiting_insurance, waiting_patient). Elles sont supprimées.
 *
 * Il ne reste qu'un relais pour l'ancien appel statique ; utilisez
 * directement PatientAccountService (paiements : PaymentService).
 */
class TransactionService
{
    /** @deprecated utiliser PatientAccountService::credit()/debit() */
    public static function mettreAJourCompte($owner_id, $owner_type, $montant, $type)
    {
        if (! \App\Support\Facturation\TypesFacturables::est($owner_type, Patient::class)) {
            throw new \InvalidArgumentException('Seuls les comptes patients sont gérés.');
        }

        return app(PatientAccountService::class)
            ->adjust(Patient::findOrFail($owner_id), (float) $montant, $type)
            ->id;
    }
}
