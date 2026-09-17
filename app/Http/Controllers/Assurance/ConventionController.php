<?php

namespace App\Http\Controllers\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Http\Controllers\Controller;
use App\Models\Assurance\ConventionFamille;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Services\Assurance\ConventionService;
use App\Support\Assurance\CatalogueActes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConventionController extends Controller
{
    public function __construct(private ConventionService $conventions, private CatalogueActes $catalogue)
    {
    }

    public function index()
    {
        $organismes = InsuranceCompany::orderBy('name')->get()->map(fn (InsuranceCompany $o) => [
            'organisme' => $o,
            'actes' => InsuranceCoverage::where('insurance_company_id', $o->id)->count(),
            'familles' => ConventionFamille::where('insurance_company_id', $o->id)->pluck('famille_acte'),
        ]);

        return view('assurance.conventions.index', ['lignes' => $organismes]);
    }

    public function show(InsuranceCompany $assuranceOrganisme)
    {
        $lignes = InsuranceCoverage::where('insurance_company_id', $assuranceOrganisme->id)->with('coverageable')->get()
            ->groupBy(fn ($l) => $this->catalogue->alias((string) $l->coverageable_type) ?? 'autre')
            ->map(fn ($groupe) => $groupe->sortBy(fn ($l) => $this->catalogue->libelle($l->coverageable)));

        return view('assurance.conventions.show', [
            'organisme' => $assuranceOrganisme,
            'regles' => ConventionFamille::where('insurance_company_id', $assuranceOrganisme->id)->get()->keyBy(fn ($r) => $r->famille_acte->value),
            'lignes' => $lignes,
            'actes' => $this->catalogue->parType(),
            'catalogue' => $this->catalogue,
            'autresOrganismes' => InsuranceCompany::where('id', '!=', $assuranceOrganisme->id)->orderBy('name')->get(),
        ]);
    }

    public function enregistrerFamilles(Request $request, InsuranceCompany $assuranceOrganisme)
    {
        $donnees = $request->validate([
            'familles' => ['nullable', 'array'],
            'familles.*.actif' => ['nullable', 'boolean'],
            'familles.*.remise_pourcentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'familles.*.plafond_par_acte' => ['nullable', 'numeric', 'min:0'],
            'familles.*.accord_prealable' => ['nullable', 'boolean'],
            'familles.*.valid_from' => ['nullable', 'date'],
            'familles.*.valid_to' => ['nullable', 'date'],
        ]);

        $this->conventions->enregistrerFamilles($assuranceOrganisme, $donnees['familles'] ?? []);

        return back()->with('success', 'Règles par famille enregistrées.');
    }

    public function ajouterActe(Request $request, InsuranceCompany $assuranceOrganisme)
    {
        $donnees = $this->validerLigne($request, true);
        [$type, $id] = array_pad(explode(':', $donnees['acte'], 2), 2, null);

        $ligne = $this->conventions->ajouterActe($assuranceOrganisme, (string) $type, (int) $id, $donnees);

        return back()->with('success', '« ' . $this->catalogue->libelle($ligne->coverageable) . ' » ajouté à la convention.');
    }

    public function modifierActe(Request $request, InsuranceCoverage $insuranceCoverage)
    {
        $this->conventions->modifierActe($insuranceCoverage, $this->validerLigne($request, false));

        return back()->with('success', 'Ligne de convention mise à jour.');
    }

    public function supprimerActe(InsuranceCoverage $insuranceCoverage)
    {
        $libelle = $this->catalogue->libelle($insuranceCoverage->coverageable);
        $this->conventions->supprimerActe($insuranceCoverage);

        return back()->with('success', "« {$libelle} » retiré de la convention. La règle de sa famille s'applique désormais, s'il y en a une.");
    }

    public function copier(Request $request, InsuranceCompany $assuranceOrganisme)
    {
        $donnees = $request->validate([
            'source_id' => ['required', 'exists_etablissement:insurance_companies,id'],
            'mode' => ['required', Rule::in(['completer', 'remplacer'])],
        ]);

        $resultat = $this->conventions->copier(InsuranceCompany::findOrFail($donnees['source_id']), $assuranceOrganisme, $donnees['mode'] === 'remplacer');

        return back()->with('success', "Convention copiée : {$resultat['actes']} ligne(s) d'acte et {$resultat['familles']} règle(s) de famille ajoutées.");
    }

    private function validerLigne(Request $request, bool $creation): array
    {
        return $request->validate([
            'acte' => [$creation ? 'required' : 'nullable', 'string', 'regex:/^[a-z_]+:\d+$/'],
            'acte_price' => [$creation ? 'nullable' : 'required', 'numeric', 'min:0'],
            'coverage_amount_limit' => ['nullable', 'numeric', 'min:0'],
            'max_usage_count' => ['nullable', 'integer', 'min:1', 'max:365'],
            'usage_period' => ['nullable', Rule::in(['mois', 'trimestre', 'annee']), 'required_with:max_usage_count'],
            'requires_preauthorization' => ['nullable', 'boolean'],
            'exclu' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date'],
        ], [
            'acte.required' => 'Choisissez l\'acte.',
            'usage_period.required_with' => 'Indiquez la période de la limite.',
        ]);
    }
}
