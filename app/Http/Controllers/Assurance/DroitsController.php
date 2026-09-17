<?php

namespace App\Http\Controllers\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Enums\Assurance\StatutCouverture;
use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Http\Controllers\Controller;
use App\Models\Assurance\Beneficiaire;
use App\Models\Assurance\PriseEnCharge;
use App\Models\Patient;
use App\Services\Assurance\DroitsPatientService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DroitsController extends Controller
{
    /** Vérification des droits d'un patient suivi par l'établissement. */
    public function show(int $patientId, DroitsPatientService $droits)
    {
        $patient = Patient::suivisParEtablissement()->findOrFail($patientId);

        return view('assurance.droits.show', [
            'patient' => $patient,
            'droits' => $droits->resume($patient),
        ]);
    }

    public function enregistrerBon(Request $request, Beneficiaire $assuranceBeneficiaire)
    {
        $donnees = $request->validate([
            'numero' => ['required', 'string', 'max:100',
                Rule::unique('assurance_prises_en_charge', 'numero')->where('beneficiaire_id', $assuranceBeneficiaire->id)],
            'famille_acte' => ['nullable', Rule::enum(FamilleActe::class)],
            'montant_accorde' => ['nullable', 'numeric', 'min:0'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'numero.unique' => 'Ce numéro de bon est déjà enregistré pour ce bénéficiaire.',
            'date_fin.after_or_equal' => 'La fin de validité doit suivre le début.',
        ]);

        if ($assuranceBeneficiaire->statut === StatutCouverture::Resiliee) {
            throw new OperationAssuranceImpossible('Ce bénéficiaire n\'est plus couvert : impossible d\'enregistrer un bon.');
        }

        PriseEnCharge::create($donnees + [
            'beneficiaire_id' => $assuranceBeneficiaire->id,
            'statut' => PriseEnCharge::ACCORDE,
            'enregistre_par' => $request->user()?->id,
        ]);

        return back()->with('success', 'Bon de prise en charge enregistré. Il s\'appliquera aux factures des actes concernés (recalculez une facture déjà émise si besoin).');
    }

    public function annulerBon(PriseEnCharge $assurancePriseEnCharge)
    {
        if ($assurancePriseEnCharge->utilisations()->exists()) {
            throw new OperationAssuranceImpossible('Ce bon est déjà utilisé par une facture : il ne peut plus être annulé.');
        }

        $assurancePriseEnCharge->update(['statut' => PriseEnCharge::ANNULE]);

        return back()->with('success', 'Bon annulé.');
    }
}
