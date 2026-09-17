<?php

namespace App\Support\Facturation;

use Illuminate\Support\Facades\DB;

/**
 * Montants de règlement assurance soldés sans ligne dans `paiements` :
 *  - écarts (applied_discount_amount) : part refusée ou remise négociée, passée en perte ;
 *  - parts payées des règlements antérieurs au lot 2c (paiement_id NULL).
 * Utilisé par SoldeTransaction et PatientAccountService::soldeAttendu, pour que
 * la même règle s'applique partout.
 */
final class ReglementsSansPaiement
{
    /** @param array<int>|\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $invoiceIds */
    public static function pourFactures($invoiceIds): float
    {
        return round((float) DB::table('insurance_settlement_items')
            ->whereIn('invoice_id', $invoiceIds)
            ->selectRaw('COALESCE(SUM(applied_discount_amount + CASE WHEN paiement_id IS NULL THEN applied_paid_amount ELSE 0 END), 0) as total')
            ->value('total'), 2);
    }
}
