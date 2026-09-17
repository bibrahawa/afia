<?php

namespace App\Services\Assurance;

use App\Enums\Assurance\StatutCouverture;
use App\Models\Assurance\Adhesion;
use App\Models\Assurance\Beneficiaire;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Formule;
use App\Models\PatientInsurance;
use Carbon\Carbon;

/**
 * Traduit chaque bénéficiaire du référentiel en une LIGNE DE COUVERTURE
 * (`patient_insurances`) : dates effectives (carence, âge limite, fins de
 * contrat/adhésion), statut, taux et plafond de base. C'est la ligne que
 * référencent les réclamations et les factures ; le moteur de prise en charge
 * (MoteurPriseEnCharge) la complète avec les règles de la formule (garanties
 * par famille, plafond familial, bons).
 *
 * Recalculée à chaque modification du contrat, de la formule, de l'adhésion
 * ou du bénéficiaire. `used_amount` n'est jamais écrasé (indicatif : les
 * plafonds sont calculés à partir des réclamations de l'exercice).
 */
class ProjectionCouvertureService
{
    public function pourContrat(Contrat $contrat): void
    {
        Formule::withoutGlobalScope('etablissement')->where('contrat_id', $contrat->id)->get()
            ->each(fn (Formule $f) => $this->pourFormule($f));
    }

    public function pourFormule(Formule $formule): void
    {
        Adhesion::withoutGlobalScope('etablissement')->where('formule_id', $formule->id)->get()
            ->each(fn (Adhesion $a) => $this->pourAdhesion($a));
    }

    public function pourAdhesion(Adhesion $adhesion): void
    {
        Beneficiaire::withoutGlobalScope('etablissement')->where('adhesion_id', $adhesion->id)->get()
            ->each(fn (Beneficiaire $b) => $this->synchroniser($b));
    }

    public function synchroniser(Beneficiaire $beneficiaire): PatientInsurance
    {
        $beneficiaire->loadMissing('patient', 'adhesion.formule.contrat');

        $adhesion = $beneficiaire->adhesion;
        $formule = $adhesion->formule;
        $contrat = $formule->contrat;

        $debut = collect([$contrat->date_debut, $adhesion->date_debut, $beneficiaire->date_debut])
            ->filter()->max()
            ->copy()->addDays((int) $formule->delai_carence_jours);

        $fin = collect([$contrat->date_fin, $adhesion->date_fin, $beneficiaire->date_fin, $beneficiaire->finDroitsParAge($formule)])
            ->filter()->min();

        $projection = PatientInsurance::withoutGlobalScope('etablissement')
            ->firstOrNew(['beneficiaire_id' => $beneficiaire->id]);

        $projection->forceFill([
            'etablissement_id' => $beneficiaire->etablissement_id,
            'patient_id' => $beneficiaire->patient_id,
            'insurance_company_id' => $contrat->insurance_company_id,
            'policy_number' => $beneficiaire->numero_carte ?: ($adhesion->numero_carte ?: $contrat->numero_police),
            'coverage_percentage' => $formule->taux_prise_en_charge,
            'annual_limit' => $formule->plafond_annuel_beneficiaire,
            'start_date' => $debut->toDateString(),
            'end_date' => $fin?->toDateString(),
            'status' => $this->statut($contrat, $formule, $adhesion, $beneficiaire, $debut, $fin),
            'notes' => sprintf('Projection automatique — %s, %s (%s).',
                $contrat->libelle ?: 'police ' . $contrat->numero_police,
                $formule->libelle,
                $beneficiaire->lien->libelle()),
        ]);

        if (! $projection->exists) {
            $projection->used_amount = 0;
        }

        $projection->save();

        return $projection;
    }

    /** Valeurs de patient_insurances.status : active | suspended | expired. */
    private function statut(Contrat $c, Formule $f, Adhesion $a, Beneficiaire $b, Carbon $debut, ?Carbon $fin): string
    {
        $statuts = [$c->statut, $a->statut, $b->statut];

        if (in_array(StatutCouverture::Resiliee, $statuts, true) || ($fin && $fin->lt($debut))) {
            return 'expired';
        }

        if (in_array(StatutCouverture::Suspendue, $statuts, true) || ! $f->actif) {
            return 'suspended';
        }

        return 'active';
    }
}
