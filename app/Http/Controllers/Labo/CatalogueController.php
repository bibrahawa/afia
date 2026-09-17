<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Enums\Labo\TypeExamen;
use App\Enums\Labo\TypeResultat;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboParametre;
use App\Models\Labo\LaboSection;
use App\Services\Labo\CatalogueImportService;
use App\Support\Labo\ContexteLabo;
use App\Support\Labo\FormuleEvaluateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Catalogue du TENANT uniquement (le scope global masque le modèle
 * plateforme). Un prix ou une norme modifiés ici n'affectent jamais les
 * demandes passées : elles portent leurs propres snapshots.
 */
class CatalogueController extends Controller
{
    public function index(Request $request)
    {
        $sections = LaboSection::orderBy('ordre')
            ->with(['examens' => fn ($q) => $q->withCount('parametres')->orderBy('ordre')])
            ->get();

        return view('labo.catalogue.index', [
            'sections' => $sections,
            'catalogueVide' => $sections->isEmpty(),
        ]);
    }

    public function importerModele(Request $request, CatalogueImportService $import)
    {
        $bilan = $import->importer(ContexteLabo::etablissementId());
        ContexteLabo::journaliser('catalogue_modele_importe', null, null, $bilan);

        return back()->with('success', 'Catalogue modèle importé : ' . collect($bilan)->map(fn ($n, $k) => "{$n} {$k}")->implode(', ')
            . '. Vérifiez les PRIX (tous à 0) et faites valider les normes par votre biologiste.');
    }

    public function storeSection(Request $request)
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('labo_sections')->where('etablissement_id', ContexteLabo::etablissementId())],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        LaboSection::create($donnees + ['actif' => true, 'ordre' => $donnees['ordre'] ?? 99]);

        return back()->with('success', 'Section ajoutée.');
    }

    public function create()
    {
        return view('labo.catalogue.form', $this->donneesFormulaire(new LaboExamen(['actif' => true, 'delai_rendu_heures' => 24])));
    }

    public function store(Request $request)
    {
        $examen = DB::transaction(function () use ($request) {
            $examen = LaboExamen::create($this->validerExamen($request) + ['ordre' => 99]);
            $this->synchroniserParametres($examen, $request);

            return $examen;
        });

        ContexteLabo::journaliser('catalogue_examen_cree', $examen);

        return redirect()->route('labo.catalogue.examens.edit', $examen)->with('success', "Examen « {$examen->nom} » créé.");
    }

    public function edit(LaboExamen $laboExamen)
    {
        $laboExamen->load('parametres.valeursReference');

        return view('labo.catalogue.form', $this->donneesFormulaire($laboExamen));
    }

    public function update(Request $request, LaboExamen $laboExamen)
    {
        $avant = $laboExamen->only(['prix', 'nom', 'delai_rendu_heures']);

        DB::transaction(function () use ($request, $laboExamen) {
            $laboExamen->update($this->validerExamen($request, $laboExamen));
            $this->synchroniserParametres($laboExamen, $request);
        });

        ContexteLabo::journaliser('catalogue_examen_modifie', $laboExamen, null, ['avant' => $avant, 'apres' => $laboExamen->only(array_keys($avant))]);

        return back()->with('success', 'Examen mis à jour. Les demandes déjà enregistrées conservent leurs prix et normes d\'origine.');
    }

    /** Désactivation plutôt que suppression : l'historique des demandes référence l'examen. */
    public function basculer(LaboExamen $laboExamen)
    {
        $laboExamen->update(['actif' => ! $laboExamen->actif]);

        return back()->with('success', $laboExamen->actif ? 'Examen réactivé.' : 'Examen désactivé (plus proposé à la demande).');
    }

    // ------------------------------------------------------------------

    private function donneesFormulaire(LaboExamen $examen): array
    {
        return [
            'examen' => $examen,
            'sections' => LaboSection::orderBy('ordre')->get(),
            'typesExamen' => TypeExamen::cases(),
            'typesResultat' => TypeResultat::cases(),
            'typesEchantillon' => LaboExamen::TYPES_ECHANTILLON,
            'tubes' => LaboExamen::TUBES,
            // Anciens tests (prescrits en consultation) pas encore reliés à un autre examen du labo.
            'anciensTests' => \App\Models\Test::orderBy('name')
                ->whereNotIn('id', LaboExamen::whereNotNull('test_id')->where('id', '!=', $examen->id ?? 0)->pluck('test_id'))
                ->get(['id', 'name']),
        ];
    }

    private function validerExamen(Request $request, ?LaboExamen $examen = null): array
    {
        $etab = ContexteLabo::etablissementId();

        $donnees = $request->validate([
            'section_id' => ['required', Rule::exists('labo_sections', 'id')->where('etablissement_id', $etab)],
            // Correspondance avec l'ancien catalogue « tests » : permet « Envoyer au labo » depuis une consultation.
            'test_id' => ['nullable', 'integer', 'exists_etablissement:tests,id', Rule::unique('labo_examens', 'test_id')->where('etablissement_id', $etab)->ignore($examen?->id)],
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('labo_examens')->where('etablissement_id', $etab)->ignore($examen?->id)],
            'nom' => ['required', 'string', 'max:150'],
            'abreviation' => ['nullable', 'string', 'max:30'],
            'type_examen' => ['required', Rule::enum(TypeExamen::class)],
            'methode' => ['nullable', 'string', 'max:150'],
            'type_echantillon' => ['required', Rule::in(array_keys(LaboExamen::TYPES_ECHANTILLON))],
            'tube' => ['nullable', Rule::in(array_keys(LaboExamen::TUBES))],
            'volume_ml' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'instructions_patient' => ['nullable', 'string', 'max:500'],
            'delai_rendu_heures' => ['required', 'integer', 'min:1', 'max:2160'],
            'prix' => ['required', 'numeric', 'min:0'],
            'laboratoire_sous_traitant' => ['nullable', 'required_if:sous_traite,1', 'string', 'max:150'],
            'mdo_maladie' => ['nullable', 'string', 'max:150'],
        ]);

        return $donnees + [
            'a_jeun' => $request->boolean('a_jeun'),
            'sous_traite' => $request->boolean('sous_traite'),
            'mdo_immediate' => $request->filled('mdo_maladie') && $request->boolean('mdo_immediate'),
            'actif' => $request->boolean('actif', true),
        ];
    }

    /**
     * parametres[i][id|code|libelle|groupe|type_resultat|unite|decimales|formule|options|obligatoire|imprimable]
     * parametres[i][normes][j][sexe|age_min_jours|age_max_jours|grossesse|min|max|critique_min|critique_max|valeur_attendue|texte_affiche]
     *
     * Un paramètre retiré du formulaire est supprimé seulement s'il n'a
     * jamais servi ; sinon la suppression est refusée (les résultats
     * existants le référencent).
     */
    private function synchroniserParametres(LaboExamen $examen, Request $request): void
    {
        $lignes = $request->validate([
            'parametres' => ['array'],
            'parametres.*.id' => ['nullable', 'integer'],
            'parametres.*.code' => ['required', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/', 'max:30', 'distinct'],
            'parametres.*.libelle' => ['required', 'string', 'max:150'],
            'parametres.*.groupe' => ['nullable', 'string', 'max:100'],
            'parametres.*.type_resultat' => ['required', Rule::enum(TypeResultat::class)],
            'parametres.*.unite' => ['nullable', 'string', 'max:30'],
            'parametres.*.decimales' => ['nullable', 'integer', 'min:0', 'max:4'],
            'parametres.*.formule' => ['nullable', 'string', 'max:255'],
            'parametres.*.options' => ['nullable', 'string', 'max:1000'],
            'parametres.*.normes' => ['array'],
            'parametres.*.normes.*.sexe' => ['nullable', Rule::in(['M', 'F'])],
            'parametres.*.normes.*.age_min_jours' => ['nullable', 'integer', 'min:0'],
            'parametres.*.normes.*.age_max_jours' => ['nullable', 'integer', 'min:0'],
            'parametres.*.normes.*.min' => ['nullable', 'numeric'],
            'parametres.*.normes.*.max' => ['nullable', 'numeric'],
            'parametres.*.normes.*.critique_min' => ['nullable', 'numeric'],
            'parametres.*.normes.*.critique_max' => ['nullable', 'numeric'],
            'parametres.*.normes.*.valeur_attendue' => ['nullable', 'string', 'max:100'],
            'parametres.*.normes.*.texte_affiche' => ['nullable', 'string', 'max:150'],
        ])['parametres'] ?? [];

        $codes = collect($lignes)->pluck('code')->all();
        $conserves = [];

        foreach (array_values($lignes) as $ordre => $ligne) {
            $type = TypeResultat::from($ligne['type_resultat']);

            if ($type === TypeResultat::CALCULE) {
                $this->verifierFormule($ligne, $codes, $ordre);
            }

            $parametre = ! empty($ligne['id'])
                ? $examen->parametres()->findOrFail($ligne['id']) // un id d'un autre examen → 404
                : new LaboParametre(['examen_id' => $examen->id]);

            $parametre->fill([
                'code' => strtoupper($ligne['code']),
                'libelle' => $ligne['libelle'],
                'groupe' => $ligne['groupe'] ?? null,
                'type_resultat' => $type,
                'unite' => $ligne['unite'] ?? null,
                'decimales' => $ligne['decimales'] ?? 1,
                'formule' => $type === TypeResultat::CALCULE ? $ligne['formule'] : null,
                'options' => $type->utiliseOptions()
                    ? array_values(array_filter(array_map('trim', explode("\n", $ligne['options'] ?? ''))))
                    : null,
                'obligatoire' => ! empty($ligne['obligatoire']),
                'imprimable' => ! array_key_exists('imprimable', $ligne) || ! empty($ligne['imprimable']),
                'ordre' => $ordre + 1,
            ])->save();

            $parametre->valeursReference()->delete();
            foreach ($ligne['normes'] ?? [] as $norme) {
                $vide = collect($norme)->except(['sexe', 'grossesse'])->filter(fn ($v) => $v !== null && $v !== '')->isEmpty();
                if ($vide) {
                    continue;
                }
                $parametre->valeursReference()->create([
                    'sexe' => $norme['sexe'] ?? null,
                    'age_min_jours' => $norme['age_min_jours'] ?? null,
                    'age_max_jours' => $norme['age_max_jours'] ?? null,
                    'grossesse' => ! empty($norme['grossesse']),
                    'min' => $norme['min'] ?? null,
                    'max' => $norme['max'] ?? null,
                    'critique_min' => $norme['critique_min'] ?? null,
                    'critique_max' => $norme['critique_max'] ?? null,
                    'valeur_attendue' => $norme['valeur_attendue'] ?? null,
                    'texte_affiche' => $norme['texte_affiche'] ?? null,
                ]);
            }

            $conserves[] = $parametre->id;
        }

        $aSupprimer = $examen->parametres()->whereNotIn('id', $conserves)->get();
        foreach ($aSupprimer as $parametre) {
            if (DB::table('labo_resultats')->where('parametre_id', $parametre->id)->exists()) {
                throw ValidationException::withMessages([
                    'parametres' => "Le paramètre « {$parametre->libelle} » a déjà des résultats : il ne peut pas être supprimé (marquez-le non imprimable).",
                ]);
            }
            $parametre->valeursReference()->delete();
            $parametre->delete();
        }
    }

    private function verifierFormule(array $ligne, array $codes, int $index): void
    {
        $formule = $ligne['formule'] ?? '';
        $evaluateur = new FormuleEvaluateur();

        try {
            $evaluateur->valider($formule);
            $inconnues = array_diff(array_map('strtoupper', $evaluateur->variables($formule)), array_map('strtoupper', $codes));
            $erreur = $inconnues ? 'variable(s) inconnue(s) : ' . implode(', ', $inconnues) : null;
        } catch (\InvalidArgumentException $e) {
            $erreur = $e->getMessage();
        }

        if ($erreur !== null) {
            throw ValidationException::withMessages(["parametres.{$index}.formule" => "Formule de « {$ligne['libelle']} » invalide — {$erreur}"]);
        }
    }
}
