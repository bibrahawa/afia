<?php

namespace App\Http\Controllers\Assurance;

use App\Enums\Assurance\StatutCouverture;
use App\Http\Controllers\Controller;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Entreprise;
use App\Models\Assurance\Formule;
use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContratController extends Controller
{
    public function index(Request $request)
    {
        $recherche = trim((string) $request->input('q'));

        $contrats = Contrat::query()
            ->with(['organismePayeur', 'entreprise'])
            ->withCount('formules')
            ->when($recherche !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('numero_police', 'like', "%{$recherche}%")
                ->orWhere('libelle', 'like', "%{$recherche}%")
                ->orWhereHas('entreprise', fn ($e) => $e->where('nom', 'like', "%{$recherche}%"))
                ->orWhereHas('organismePayeur', fn ($o) => $o->where('name', 'like', "%{$recherche}%"))))
            ->when($request->filled('organisme'), fn ($q) => $q->where('insurance_company_id', $request->integer('organisme')))
            ->latest('date_debut')
            ->paginate(25)
            ->withQueryString();

        return view('assurance.contrats.index', [
            'contrats' => $contrats,
            'recherche' => $recherche,
            'organismes' => InsuranceCompany::orderBy('name')->get(),
            'entreprises' => Entreprise::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function show(Contrat $assuranceContrat)
    {
        $assuranceContrat->load(['organismePayeur', 'entreprise', 'formules.garanties']);

        $adhesions = \App\Models\Assurance\Adhesion::query()
            ->whereIn('formule_id', $assuranceContrat->formules->pluck('id'))
            ->with(['patient', 'formule', 'emploi'])
            ->withCount('ayantsDroit')
            ->orderByRaw("statut = 'resiliee'")
            ->orderBy('date_debut', 'desc')
            ->paginate(50);

        $emploisDisponibles = $assuranceContrat->entreprise
            ? $assuranceContrat->entreprise->emploisEnCours()->with('patient')->get()
            : collect();

        return view('assurance.contrats.show', [
            'contrat' => $assuranceContrat,
            'adhesions' => $adhesions,
            'emploisDisponibles' => $emploisDisponibles,
            'organismes' => InsuranceCompany::orderBy('name')->get(),
            'entreprises' => Entreprise::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $contrat = Contrat::create($this->validerContrat($request));

        return redirect()->route('assurance.contrats.show', $contrat)
            ->with('success', 'Contrat enregistré. Ajoutez maintenant au moins une formule (taux, plafonds), puis les adhérents.');
    }

    public function update(Request $request, Contrat $assuranceContrat)
    {
        $assuranceContrat->update($this->validerContrat($request, $assuranceContrat));

        return back()->with('success', 'Contrat mis à jour. Les couvertures des bénéficiaires ont été recalculées.');
    }

    public function storeFormule(Request $request, Contrat $assuranceContrat)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $assuranceContrat) {
            $formule = $assuranceContrat->formules()->create($this->validerFormule($request, $assuranceContrat));
            $this->enregistrerGaranties($request, $formule);
        });

        return back()->with('success', 'Formule ajoutée.');
    }

    public function updateFormule(Request $request, Formule $assuranceFormule)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $assuranceFormule) {
            $assuranceFormule->update($this->validerFormule($request, $assuranceFormule->contrat, $assuranceFormule));
            $this->enregistrerGaranties($request, $assuranceFormule);
        });

        return back()->with('success', 'Formule mise à jour. Les couvertures des bénéficiaires ont été recalculées.');
    }

    /** Une ligne par famille seulement si elle s'écarte des règles générales de la formule. */
    private function enregistrerGaranties(Request $request, Formule $formule): void
    {
        $garanties = $request->validate([
            'garanties' => ['nullable', 'array'],
            'garanties.*.taux' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'garanties.*.plafond_par_acte' => ['nullable', 'numeric', 'min:0'],
            'garanties.*.exclu' => ['nullable', 'boolean'],
            'garanties.*.accord_prealable' => ['nullable', 'boolean'],
        ])['garanties'] ?? [];

        foreach (\App\Enums\Assurance\FamilleActe::cases() as $famille) {
            $valeurs = $garanties[$famille->value] ?? [];
            $ligne = [
                'taux' => ($valeurs['taux'] ?? '') !== '' ? $valeurs['taux'] : null,
                'plafond_par_acte' => ($valeurs['plafond_par_acte'] ?? '') !== '' ? $valeurs['plafond_par_acte'] : null,
                'exclu' => (bool) ($valeurs['exclu'] ?? false),
                'accord_prealable' => (bool) ($valeurs['accord_prealable'] ?? false),
            ];

            $utile = $ligne['taux'] !== null || $ligne['plafond_par_acte'] !== null || $ligne['exclu'] || $ligne['accord_prealable'];

            if ($utile) {
                $formule->garanties()->updateOrCreate(['famille_acte' => $famille->value], $ligne);
            } else {
                $formule->garanties()->where('famille_acte', $famille->value)->delete();
            }
        }
    }

    private function validerContrat(Request $request, ?Contrat $contrat = null): array
    {
        $donnees = $request->validate([
            'insurance_company_id' => ['required', 'exists_etablissement:insurance_companies,id'],
            'entreprise_id' => ['nullable', 'exists_etablissement:entreprises,id'],
            'numero_police' => ['required', 'string', 'max:100'],
            'libelle' => ['nullable', 'string', 'max:255'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['required', Rule::enum(StatutCouverture::class)],
            'notes' => ['nullable', 'string'],
        ], [
            'insurance_company_id.required' => 'Choisissez l\'organisme payeur.',
            'numero_police.required' => 'Le numéro de police est obligatoire.',
        ]);

        $doublon = Contrat::where('insurance_company_id', $donnees['insurance_company_id'])
            ->where('numero_police', $donnees['numero_police'])
            ->when($contrat, fn ($q) => $q->where('id', '!=', $contrat->id))
            ->exists();

        if ($doublon) {
            throw \Illuminate\Validation\ValidationException::withMessages(['numero_police' => 'Ce numéro de police existe déjà chez cet organisme.']);
        }

        return $donnees;
    }

    private function validerFormule(Request $request, Contrat $contrat, ?Formule $formule = null): array
    {
        $donnees = $request->validate([
            'libelle' => ['required', 'string', 'max:255',
                Rule::unique('assurance_formules', 'libelle')->where('contrat_id', $contrat->id)->ignore($formule?->id)],
            'taux_prise_en_charge' => ['required', 'numeric', 'min:0', 'max:100'],
            'plafond_annuel_beneficiaire' => ['nullable', 'numeric', 'min:0'],
            'plafond_annuel_famille' => ['nullable', 'numeric', 'min:0'],
            'delai_carence_jours' => ['nullable', 'integer', 'min:0', 'max:730'],
            'age_max_enfant' => ['required', 'integer', 'min:0', 'max:30'],
            'age_max_enfant_etudiant' => ['required', 'integer', 'gte:age_max_enfant', 'max:35'],
        ], [
            'libelle.unique' => 'Ce contrat a déjà une formule portant ce nom.',
            'age_max_enfant_etudiant.gte' => 'L\'âge limite des étudiants doit être au moins égal à celui des enfants.',
        ]);

        $donnees['delai_carence_jours'] = (int) ($donnees['delai_carence_jours'] ?? 0);
        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }
}
