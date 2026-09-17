<?php

namespace App\Http\Controllers\Assurance;

use App\Http\Controllers\Controller;
use App\Models\Assurance\Entreprise;
use App\Models\Assurance\PatientEmploi;
use App\Models\Patient;
use App\Services\Assurance\ReferentielAssuranceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EntrepriseController extends Controller
{
    use Concerns\ResoutPatient;

    public function index(Request $request)
    {
        $recherche = trim((string) $request->input('q'));

        $entreprises = Entreprise::query()
            ->when($recherche !== '', fn ($q) => $q->where(fn ($w) => $w->where('nom', 'like', "%{$recherche}%")->orWhere('nif', 'like', "%{$recherche}%")))
            ->withCount(['emplois as employes_count' => fn ($q) => $q->enCours(), 'contrats'])
            ->orderBy('nom')
            ->paginate(25)
            ->withQueryString();

        return view('assurance.entreprises.index', [
            'entreprises' => $entreprises,
            'recherche' => $recherche,
        ]);
    }

    public function show(Entreprise $assuranceEntreprise)
    {
        $assuranceEntreprise->load([
            'emplois' => fn ($q) => $q->with('patient')->orderByRaw('date_fin IS NOT NULL')->orderByDesc('date_debut'),
            'contrats.organismePayeur',
        ]);

        return view('assurance.entreprises.show', [
            'entreprise' => $assuranceEntreprise,
        ]);
    }

    public function store(Request $request)
    {
        $entreprise = Entreprise::create($this->valider($request));

        return redirect()->route('assurance.entreprises.show', $entreprise)->with('success', 'Entreprise enregistrée.');
    }

    public function update(Request $request, Entreprise $assuranceEntreprise)
    {
        $assuranceEntreprise->update($this->valider($request, $assuranceEntreprise));

        return back()->with('success', 'Entreprise mise à jour.');
    }

    public function rattacher(Request $request, Entreprise $assuranceEntreprise, ReferentielAssuranceService $referentiel)
    {
        $donnees = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'matricule' => ['nullable', 'string', 'max:50'],
            'poste' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['nullable', 'date'],
        ], ['patient_id.required' => 'Choisissez le patient à rattacher.']);

        $emploi = $referentiel->rattacherEmploi($this->patientAutorise($request), $assuranceEntreprise, $donnees);

        return back()->with('success', "{$emploi->patient->full_name} rattaché(e) à {$assuranceEntreprise->nom}.");
    }

    public function terminerEmploi(Request $request, PatientEmploi $assuranceEmploi, ReferentielAssuranceService $referentiel)
    {
        $donnees = $request->validate(['date_fin' => ['required', 'date']]);

        $referentiel->terminerEmploi($assuranceEmploi, Carbon::parse($donnees['date_fin']));

        return back()->with('success', 'Fin d\'emploi enregistrée. Les adhésions liées à cet emploi sont clôturées à la même date.');
    }

    private function valider(Request $request, ?Entreprise $entreprise = null): array
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique_etablissement:entreprises,nom' . ($entreprise ? ',' . $entreprise->id : '')],
            'nif' => ['nullable', 'string', 'max:50'],
            'secteur' => ['nullable', 'string', 'max:100'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'contact_nom' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'actif' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ], ['nom.unique_etablissement' => 'Une entreprise porte déjà ce nom.']);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }
}
