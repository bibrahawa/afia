<?php

namespace App\Services\Assurance;

use App\Models\PatientInsurance;
use App\Support\Assurance\Couverture;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Couvertures d'un patient à une date, dans l'ordre où elles s'appliquent,
 * et consommation des plafonds.
 *
 * La consommation n'est plus un compteur (patient_insurances.used_amount,
 * qui ne se remettait jamais à zéro et dérivait) : c'est la somme des
 * réclamations non rejetées des factures émises pendant l'exercice.
 */
class CouverturesApplicables
{
    /** @return Collection<int, Couverture> */
    public function pour(int $patientId, Carbon $date): Collection
    {
        return PatientInsurance::query()
            ->where('patient_id', $patientId)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date))
            ->with([
                'insuranceCompany',
                'beneficiaire.adhesion.formule.garanties',
                'beneficiaire.adhesion.formule.contrat',
            ])
            ->get()
            ->map(fn (PatientInsurance $p) => new Couverture($p))
            ->sort(fn (Couverture $a, Couverture $b) => $a->rang() <=> $b->rang())
            ->values();
    }

    /** Montant déjà pris en charge sur l'exercice pour ces lignes de couverture. */
    public function consomme(array $projectionIds, array $exercice, ?int $exclureInvoiceId = null): float
    {
        if (! $projectionIds) {
            return 0.0;
        }

        return (float) DB::table('insurance_claims')
            ->join('invoices', 'invoices.id', '=', 'insurance_claims.invoice_id')
            ->whereIn('insurance_claims.patient_insurance_id', $projectionIds)
            ->where('insurance_claims.status', '!=', 'rejected')
            ->whereBetween('invoices.created_at', [$exercice[0], $exercice[1]])
            ->when($exclureInvoiceId, fn ($q) => $q->where('invoices.id', '!=', $exclureInvoiceId))
            // Après réponse de l'assureur, seul l'accepté consomme le plafond.
            ->sum(DB::raw('COALESCE(insurance_claims.approved_amount, insurance_claims.claimed_amount)'));
    }

    /**
     * Nombre d'actes déjà pris en charge sur la période pour cette ligne de
     * couverture — pour une famille d'actes, ou pour un acte précis.
     * Les lignes refusées par l'assureur (accepté = 0) ne comptent pas.
     */
    public function utilisations(int $projectionId, array $periode, ?string $famille, ?array $acte, ?int $exclureInvoiceId = null): int
    {
        return (int) DB::table('insurance_claim_lignes as l')
            ->join('insurance_claims as c', 'c.id', '=', 'l.insurance_claim_id')
            ->join('invoices as i', 'i.id', '=', 'c.invoice_id')
            ->leftJoin('invoice_items as it', 'it.id', '=', 'l.invoice_item_id')
            ->where('c.patient_insurance_id', $projectionId)
            ->where('c.status', '!=', 'rejected')
            ->where(fn ($q) => $q->whereNull('l.montant_accepte')->orWhere('l.montant_accepte', '>', 0))
            ->whereBetween('i.created_at', [$periode[0], $periode[1]])
            ->when($famille, fn ($q) => $q->where('it.famille_acte', $famille))
            ->when($acte, fn ($q) => $q->whereIn('it.coverage_type_type', \App\Support\Facturation\TypesFacturables::variantes($acte[0]))->where('it.coverage_type_id', $acte[1]))
            ->when($exclureInvoiceId, fn ($q) => $q->where('i.id', '!=', $exclureInvoiceId))
            ->sum('l.quantite');
    }

    /** Lignes de couverture de toute la famille (adhérent + ayants droit) de cette couverture. */
    public function projectionsFamille(Couverture $couverture): array
    {
        if (! $couverture->beneficiaire) {
            return [$couverture->projection->id];
        }

        return DB::table('patient_insurances')
            ->join('assurance_beneficiaires', 'assurance_beneficiaires.id', '=', 'patient_insurances.beneficiaire_id')
            ->where('assurance_beneficiaires.adhesion_id', $couverture->beneficiaire->adhesion_id)
            ->pluck('patient_insurances.id')
            ->all();
    }
}
