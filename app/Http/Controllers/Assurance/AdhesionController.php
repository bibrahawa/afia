<?php

namespace App\Http\Controllers\Assurance;

use App\Enums\Assurance\LienBeneficiaire;
use App\Http\Controllers\Controller;
use App\Models\Assurance\Adhesion;
use App\Models\Assurance\Beneficiaire;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Formule;
use App\Models\Patient;
use App\Services\Assurance\ReferentielAssuranceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdhesionController extends Controller
{
    use Concerns\ResoutPatient;

    public function __construct(private ReferentielAssuranceService $referentiel)
    {
    }

    public function show(Adhesion $assuranceAdhesion)
    {
        $assuranceAdhesion->load([
            'patient', 'emploi.entreprise', 'formule.contrat.organismePayeur', 'formule.contrat.entreprise',
            'beneficiaires' => fn ($q) => $q->with(['patient', 'projection'])->orderByRaw("lien = 'adherent' DESC")->orderBy('date_debut'),
        ]);

        return view('assurance.adhesions.show', [
            'adhesion' => $assuranceAdhesion,
            'suggestions' => $this->referentiel->suggestionsAyantsDroit($assuranceAdhesion),
        ]);
    }

    public function store(Request $request, Contrat $assuranceContrat)
    {
        $donnees = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'formule_id' => ['required', Rule::exists('assurance_formules', 'id')->where('contrat_id', $assuranceContrat->id)],
            'patient_emploi_id' => ['nullable', 'exists_etablissement:patient_emplois,id'],
            'numero_carte' => ['nullable', 'string', 'max:100'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ], [
            'patient_id.required' => 'Choisissez l\'adhérent.',
            'formule_id.required' => 'Choisissez la formule.',
        ]);

        $adhesion = $this->referentiel->creerAdhesion(
            Formule::findOrFail($donnees['formule_id']),
            $this->patientAutorise($request),
            $donnees
        );

        return redirect()->route('assurance.adhesions.show', $adhesion)
            ->with('success', 'Adhésion enregistrée. Ajoutez maintenant ses ayants droit (conjoints, enfants).');
    }

    public function cloturer(Request $request, Adhesion $assuranceAdhesion)
    {
        $donnees = $request->validate(['date_fin' => ['required', 'date', 'after_or_equal:' . $assuranceAdhesion->date_debut->toDateString()]]);

        $this->referentiel->cloturerAdhesion($assuranceAdhesion, Carbon::parse($donnees['date_fin']));

        return back()->with('success', 'Adhésion clôturée : l\'adhérent et ses ayants droit ne sont plus couverts après cette date.');
    }

    public function ajouterBeneficiaire(Request $request, Adhesion $assuranceAdhesion)
    {
        $donnees = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'lien' => ['required', Rule::in(array_map(fn ($l) => $l->value, LienBeneficiaire::ajoutables()))],
            'etudiant' => ['nullable', 'boolean'],
            'numero_carte' => ['nullable', 'string', 'max:100'],
            'date_debut' => ['nullable', 'date'],
        ], ['patient_id.required' => 'Choisissez la personne à couvrir.']);

        // Un proche déjà enregistré dans les liens familiaux de l'adhérent est
        // proposé à l'écran : il peut être choisi sans preuve d'identité.
        $patient = $this->referentiel->suggestionsAyantsDroit($assuranceAdhesion)
            ->pluck('patient')
            ->first(fn ($p) => (int) $p->id === $request->integer('patient_id'))
            ?? $this->patientAutorise($request);

        $beneficiaire = $this->referentiel->ajouterBeneficiaire($assuranceAdhesion, $patient, $donnees);

        $message = "{$beneficiaire->patient->full_name} est désormais couvert(e) en tant que " . mb_strtolower($beneficiaire->lien->libelle()) . '.';

        if ($beneficiaire->lien === LienBeneficiaire::Enfant && ! Beneficiaire::dateNaissance($beneficiaire->patient)) {
            $message .= ' Date de naissance inconnue : la limite d\'âge ne pourra pas être appliquée. Complétez la fiche du patient.';
        }

        return back()->with('success', $message);
    }

    public function cloturerBeneficiaire(Request $request, Beneficiaire $assuranceBeneficiaire)
    {
        $donnees = $request->validate(['date_fin' => ['required', 'date']]);

        $this->referentiel->cloturerBeneficiaire($assuranceBeneficiaire, Carbon::parse($donnees['date_fin']));

        return back()->with('success', 'Fin de couverture enregistrée.');
    }
}
