<?php

namespace App\Http\Controllers;

use App\Enums\Assurance\TypeOrganismePayeur;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\PatientInsurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Organismes payeurs (compagnies d'assurance, mutuelles, organismes publics).
 *
 * Lot Fix :
 *  - chaque organisme affiche ce qu'il représente : patients couverts, contrats, créance ouverte ;
 *  - validation complète (statut, dates de contrat) au lieu de $request->all() ;
 *  - CORRIGÉ : la suppression effaçait un organisme même avec des factures, des réclamations
 *    ou des patients couverts ; elle est refusée dès qu'il y a un historique (désactiver à la place).
 */
class InsuranceCompanyController extends Controller
{
    public function index()
    {
        $organismes = InsuranceCompany::orderByRaw("status = 'active' DESC")->orderBy('name')->get();
        $ids = $organismes->pluck('id');

        // Une requête groupée par indicateur (cloisonnées par établissement par les modèles).
        $assures = PatientInsurance::whereIn('insurance_company_id', $ids)->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', today()))
            ->selectRaw('insurance_company_id, COUNT(*) AS n')->groupBy('insurance_company_id')->pluck('n', 'insurance_company_id');
        $contrats = class_exists(\App\Models\Assurance\Contrat::class)
            ? \App\Models\Assurance\Contrat::whereIn('insurance_company_id', $ids)->selectRaw('insurance_company_id, COUNT(*) AS n')->groupBy('insurance_company_id')->pluck('n', 'insurance_company_id')
            : collect();
        $creances = \Illuminate\Support\Facades\Schema::hasColumn('insurance_claims', 'reste_du_calcule')
            ? InsuranceClaim::whereIn('insurance_company_id', $ids)->where('status', '!=', 'paid')
                ->selectRaw('insurance_company_id, SUM(reste_du_calcule) AS du')->groupBy('insurance_company_id')->pluck('du', 'insurance_company_id')
            : collect();

        return view('insurance_companies.index', [
            'organismes' => $organismes,
            'assures' => $assures,
            'contrats' => $contrats,
            'creances' => $creances,
            'types' => TypeOrganismePayeur::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);
        $donnees['status'] ??= 'active';
        InsuranceCompany::create($donnees);

        return back()->with('success', $donnees['name'] . ' ajouté.');
    }

    public function update(Request $request)
    {
        $organisme = InsuranceCompany::findOrFail($request->input('id'));
        $organisme->update($this->valider($request, $organisme));

        return back()->with('success', $organisme->name . ' mis à jour.');
    }

    public function destroy(Request $request)
    {
        $organisme = InsuranceCompany::findOrFail($request->input('id'));

        $historique = PatientInsurance::where('insurance_company_id', $organisme->id)->exists()
            || InsuranceClaim::where('insurance_company_id', $organisme->id)->exists()
            || Invoice::where('insurance_company_id', $organisme->id)->exists()
            || (class_exists(\App\Models\Assurance\Contrat::class) && \App\Models\Assurance\Contrat::where('insurance_company_id', $organisme->id)->exists());

        if ($historique) {
            // Rien n'est supprimé : on propose de désactiver (il n'est plus proposé, l'historique reste lisible).
            $organisme->update(['status' => 'inactive']);

            return back()->with('success', "{$organisme->name} a un historique (patients, factures ou réclamations) : il a été désactivé plutôt que supprimé.");
        }

        DB::transaction(fn () => $organisme->delete());

        return back()->with('success', $organisme->name . ' supprimé.');
    }

    private function valider(Request $request, ?InsuranceCompany $organisme = null): array
    {
        $donnees = $request->validate([
            'type' => ['required', Rule::enum(TypeOrganismePayeur::class)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'unique_etablissement:insurance_companies,code' . ($organisme ? ',' . $organisme->id : '')],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'default_coverage_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Indiquez le nom de l\'organisme.',
            'code.required' => 'Indiquez un code court (ex. NSIA).',
            'code.unique_etablissement' => 'Ce code est déjà utilisé par un autre organisme.',
            'contract_end_date.after_or_equal' => 'La fin du contrat doit suivre son début.',
            'default_coverage_percentage.max' => 'La prise en charge ne peut pas dépasser 100 %.',
        ]);
        $donnees['default_coverage_percentage'] = $donnees['default_coverage_percentage'] ?? 0;
        $donnees['code'] = mb_strtoupper(trim($donnees['code']));

        return $donnees;
    }
}
