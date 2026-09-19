<?php

namespace App\Http\Controllers;

use App\Enums\Assurance\LienBeneficiaire;
use App\Enums\Assurance\StatutCouverture;
use App\Models\Assurance\Beneficiaire;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Services\Assurance\ReferentielAssuranceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ancien écran « Patients assurés », conservé pour la saisie rapide.
 *
 * RÉVISION lot 2a : il n'écrit plus directement dans patient_insurances.
 *  - création  → contrat individuel complet dans le référentiel (contrat,
 *    formule, adhésion, bénéficiaire) ; la ligne affichée en est la projection ;
 *  - modification → répercutée sur le référentiel (formule, adhésion) ;
 *  - suppression → résiliation à la date du jour (plus aucune suppression :
 *    les factures passées gardent leur couverture).
 *  - `used_amount` (consommation du plafond) n'est plus modifiable ici.
 * Pour les contrats de groupe, conjoints et enfants : écrans Assurance > Contrats.
 */
class PatientInsuranceController extends Controller
{
    public function __construct(private ReferentielAssuranceService $referentiel)
    {
    }

    public function index()
    {
        // Lot Fix : on ne charge plus TOUS les patients de la clinique pour une liste déroulante
        // (inutilisable au-delà de quelques centaines) ; le formulaire les cherche au fil de la frappe.
        $patientInsurances = PatientInsurance::with([
            'patient', 'insuranceCompany',
            'beneficiaire.adhesion.patient', 'beneficiaire.adhesion.formule.contrat.entreprise',
        ])->latest()->get();
        $insuranceCompanies = InsuranceCompany::where('status', 'active')->orderBy('name')->get();

        return view('patient_insurance.index', compact('patientInsurances', 'insuranceCompanies'));
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);

        // Un patient d'une autre clinique donnait une page 404 : message clair à la place.
        $patient = Patient::suivisParEtablissement()->find($donnees['patient_id']);
        if (! $patient) {
            return back()->withInput()->with('error', 'Ce patient n\'est pas suivi par votre clinique : enregistrez-le d\'abord à l\'accueil.');
        }

        $this->referentiel->creerContratIndividuel(
            $patient,
            InsuranceCompany::findOrFail($donnees['insurance_company_id']),
            $donnees
        );

        return back()->with('success', 'Assurance enregistrée.');
    }

    public function update(Request $request)
    {
        $donnees = $this->valider($request);
        $ligne = PatientInsurance::with('beneficiaire.adhesion.formule.contrat')->findOrFail($request->input('id'));
        $beneficiaire = $ligne->beneficiaire;

        if (! $beneficiaire) {
            return back()->with('error', 'Cette ligne n\'est pas reliée au référentiel. Lancez les migrations du module Assurance.');
        }

        if ($beneficiaire->lien !== LienBeneficiaire::Adherent) {
            return back()->with('error', 'Couverture d\'ayant droit : modifiez-la depuis l\'adhésion de l\'assuré principal (Assurance > Contrats).');
        }

        DB::transaction(function () use ($beneficiaire, $donnees) {
            $adhesion = $beneficiaire->adhesion;
            $contrat = $adhesion->formule->contrat;

            if ((int) $contrat->insurance_company_id !== (int) $donnees['insurance_company_id']) {
                throw new \App\Exceptions\Assurance\OperationAssuranceImpossible('Changer d\'organisme payeur revient à un nouveau contrat : résiliez celui-ci puis créez le nouveau.');
            }

            // Formule : si elle est partagée avec d'autres adhérents, on ne la modifie pas
            // pour eux — l'adhérent passe sur une formule correspondant aux nouvelles valeurs.
            $formule = $adhesion->formule;
            $partagee = $formule->adhesions()->where('id', '!=', $adhesion->id)->exists();

            if ($partagee) {
                $adhesion->formule_id = $this->referentiel->formulePour($contrat, (float) $donnees['coverage_percentage'], $donnees['annual_limit'] ?? null)->id;
            } else {
                $formule->update([
                    'taux_prise_en_charge' => $donnees['coverage_percentage'],
                    'plafond_annuel_beneficiaire' => $donnees['annual_limit'] ?? null,
                ]);
            }

            $adhesion->fill([
                'numero_carte' => $donnees['policy_number'],
                'date_debut' => $donnees['start_date'],
                'date_fin' => $donnees['end_date'] ?? null,
            ])->save();

            $beneficiaire->update([
                'numero_carte' => $donnees['policy_number'],
                'date_debut' => $donnees['start_date'],
                'date_fin' => $donnees['end_date'] ?? null,
            ]);
        });

        return back()->with('success', 'Contrat mis à jour.');
    }

    public function destroy(Request $request)
    {
        $ligne = PatientInsurance::with('beneficiaire.adhesion')->findOrFail($request->input('id'));

        if (! $ligne->beneficiaire) {
            $ligne->update(['status' => 'expired', 'end_date' => today()->toDateString()]);

            return back()->with('success', 'Contrat résilié.');
        }

        if ($ligne->beneficiaire->lien === LienBeneficiaire::Adherent) {
            $this->referentiel->cloturerAdhesion($ligne->beneficiaire->adhesion, today());
        } else {
            $this->referentiel->cloturerBeneficiaire($ligne->beneficiaire, today());
        }

        return back()->with('success', 'Couverture résiliée à la date du jour (conservée pour l\'historique des factures).');
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'insurance_company_id' => 'required|exists_etablissement:insurance_companies,id',
            'policy_number' => 'required|string|max:100',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'annual_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
    }
}
