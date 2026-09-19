<?php

namespace App\Http\Controllers\Parcours;

use App\Http\Controllers\Concerns\InteractsWithAuthenticatedEmployee;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Employee;
use App\Models\Medicament;
use App\Models\MotifRdv;
use App\Models\Package;
use App\Models\Parcours\ModeleConsultation;
use App\Models\Service;
use App\Models\Test;
use App\Services\Parcours\ConsultationRapideService;
use App\Services\Parcours\GrossesseService;
use App\Services\Parcours\ModeleConsultationService;
use App\Services\Parcours\SuggestionsConsultationService;
use Illuminate\Http\Request;

/**
 * Écran de consultation en une page : tout est pré-rempli ou proposé, le
 * médecin clique et enregistre une seule fois.
 */
class ConsultationRapideController extends Controller
{
    use InteractsWithAuthenticatedEmployee;

    public function __construct(
        private ConsultationRapideService $consultations,
        private ModeleConsultationService $modeles,
        private SuggestionsConsultationService $suggestions,
        private GrossesseService $grossesses,
    ) {
    }

    public function show(Consultation $consultation)
    {
        $medecin = $this->medecinAutorise($consultation);
        $this->refuserSiAnnulee($consultation);

        $consultation->load([
            'patient.antecedant', 'services', 'packages', 'tests', 'medicaments',
            'visite.derniereConstante', 'visite.motifRdv', 'transaction',
        ]);

        // Suivi de grossesse en cours : la consultation y est rattachée sans action du médecin.
        $grossesse = $this->grossesses->rattacher($consultation);

        $precedente = Consultation::where('patient_id', $consultation->patient_id)
            ->where('id', '!=', $consultation->id)
            ->whereNotNull('diagnostic')
            ->latest('id')
            ->first();

        return view('parcours.consultation.rapide', [
            'consultation' => $consultation,
            'medecin' => $medecin,
            'grossesse' => $grossesse,
            'precedente' => $precedente,
            'modeles' => ModeleConsultation::pour($medecin)->withCount('lignes')->orderByDesc('utilisations')->limit(20)->get(),
            'diagnosticsFrequents' => $this->suggestions->diagnostics($medecin, $consultation->visite?->motif_rdv_id),
            'medicamentsFrequents' => $this->suggestions->medicaments($medecin),
            'examensFrequents' => $this->suggestions->examens($medecin),
            'derniereOrdonnance' => $this->suggestions->derniereOrdonnance($consultation->patient, $consultation->id),
            'motifs' => MotifRdv::where('actif', true)->orderBy('ordre_affichage')->get(),
            'catalogue' => $this->catalogue(),
        ]);
    }

    public function enregistrer(Request $request, Consultation $consultation)
    {
        $this->medecinAutorise($consultation);
        $this->refuserSiAnnulee($consultation);

        $donnees = $request->validate([
            'action' => ['required', 'in:terminer,brouillon'],
            'diagnostic' => ['nullable', 'string', 'max:5000'],
            'observation' => ['nullable', 'string', 'max:5000'],
            'signes' => ['nullable', 'array'],
            'signes.*' => ['nullable', 'string', 'max:255'],
            'actes' => ['nullable', 'array'],
            'actes.*' => ['nullable', 'array'],
            'actes.*.*.id' => ['required', 'integer'],
            'actes.*.*.quantite' => ['nullable', 'integer', 'min:1', 'max:999'],
            'actes.*.*.dose' => ['nullable', 'string', 'max:60'],
            'actes.*.*.frequence' => ['nullable', 'string', 'max:60'],
            'actes.*.*.duree' => ['nullable', 'string', 'max:60'],
            'actes.*.*.instructions' => ['nullable', 'string', 'max:255'],
            'antecedents' => ['nullable', 'array'],
            'antecedents.allergies' => ['nullable', 'string', 'max:2000'],
            'antecedents.antecedents_medicaux' => ['nullable', 'string', 'max:2000'],
            'antecedents.traitements_cours' => ['nullable', 'string', 'max:2000'],
            'prochain_rdv_jours' => ['nullable', 'integer', 'min:0', 'max:365'],
            'prochain_rdv_motif_id' => ['nullable', 'exists_etablissement:motifs_rdv,id'],
        ]);

        $resultat = $this->consultations->enregistrer($consultation, $donnees, $request->user());
        $termine = $donnees['action'] === 'terminer';

        $redirection = $termine
            ? redirect()->route('parcours.file.index')->with('success', 'Consultation enregistrée. Patient suivant ?')
            : back()->with('success', 'Brouillon enregistré.');

        return $resultat['avertissements'] ? $redirection->with('error', implode(' ', $resultat['avertissements'])) : $redirection;
    }

    /** Recherche d'actes côté serveur : les gros catalogues ne sont plus envoyés en entier. */
    public function actes(Request $request, Consultation $consultation)
    {
        $this->medecinAutorise($consultation);

        $donnees = $request->validate([
            'categorie' => ['required', 'in:services,packages,examens,medicaments'],
            'q' => ['required', 'string', 'min:2', 'max:60'],
        ]);

        $terme = '%' . $donnees['q'] . '%';

        $resultats = match ($donnees['categorie']) {
            'services' => Service::actifs()->where('name', 'like', $terme)->orderBy('name')->limit(15)->get()
                ->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->amount]),
            'packages' => Package::where('name', 'like', $terme)->orderBy('name')->limit(15)->get()
                ->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->price]),
            'examens' => Test::where('name', 'like', $terme)->orderBy('name')->limit(15)->get()
                ->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->amount]),
            'medicaments' => Medicament::actifs()->where('nom', 'like', $terme)->orderBy('nom')->limit(15)->get()
                ->map(fn ($a) => ['id' => $a->id, 'nom' => $a->nom, 'prix' => (float) $a->amount,
                    'dose' => $a->dosage, 'frequence' => $a->frequence, 'duree' => $a->duree, 'instructions' => $a->instructions]),
        };

        return response()->json($resultats->values());
    }

    /** Constantes prises pendant la consultation (le médecin n'a plus à appeler l'accueil). */
    public function constantes(Request $request, Consultation $consultation, \App\Services\Parcours\AccueilService $accueil)
    {
        $this->medecinAutorise($consultation);
        abort_unless($consultation->visite, 404, 'Cette consultation n\'est pas rattachée à une visite.');

        $donnees = $request->validate([
            'poids_kg' => ['nullable', 'numeric', 'min:0.3', 'max:350'],
            'taille_cm' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'tension_systolique' => ['nullable', 'integer', 'min:40', 'max:300'],
            'tension_diastolique' => ['nullable', 'integer', 'min:20', 'max:200'],
            'pouls' => ['nullable', 'integer', 'min:20', 'max:250'],
            'saturation_o2' => ['nullable', 'integer', 'min:50', 'max:100'],
            'glycemie' => ['nullable', 'numeric', 'min:0.1', 'max:9.99'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $accueil->enregistrerConstantes($consultation->visite, $donnees, $request->user());

        return back()->with('success', 'Constantes enregistrées.');
    }

    /** Contenu d'un modèle, chargé sans quitter l'écran. */
    public function modele(Consultation $consultation, ModeleConsultation $modele)
    {
        $medecin = $this->medecinAutorise($consultation);

        return response()->json($this->modeles->contenu($modele, $medecin));
    }

    public function enregistrerModele(Request $request, Consultation $consultation)
    {
        $this->medecinAutorise($consultation);

        $donnees = $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'partager' => ['nullable', 'boolean'],
            'avec_observation' => ['nullable', 'boolean'],
        ], ['libelle.required' => 'Donnez un nom au modèle.']);

        $modele = $this->modeles->enregistrerDepuis($consultation, $donnees, $request->user());

        return back()->with('success', "Modèle « {$modele->libelle} » enregistré : il sera proposé à vos prochaines consultations.");
    }

    public function supprimerModele(Request $request, ModeleConsultation $modele)
    {
        $medecin = Employee::findOrFail($this->authenticatedEmployeeId());
        $this->modeles->supprimer($modele, $medecin, (bool) $request->user()?->hasRole(['admin', 'super-admin']));

        return back()->with('success', 'Modèle retiré de vos propositions.');
    }

    /** Patient reparti sans consulter : rien à remplir, rien à facturer. */
    private function refuserSiAnnulee(Consultation $consultation): void
    {
        if ($consultation->statut === Consultation::ANNULEE) {
            throw new \App\Exceptions\Parcours\OperationParcoursImpossible(
                'Ce patient est reparti sans consulter : cette consultation est close. S\'il revient, l\'accueil l\'ajoute de nouveau à la file.'
            );
        }
    }

    private function medecinAutorise(Consultation $consultation): Employee
    {
        $medecin = Employee::findOrFail($this->authenticatedEmployeeId());

        abort_unless(
            ! $consultation->medecin_id || (int) $consultation->medecin_id === (int) $medecin->id || auth()->user()?->hasRole(['admin', 'super-admin']),
            403,
            'Seul le médecin de cette consultation peut la remplir.'
        );

        return $medecin;
    }

    public const CATALOGUE_EMBARQUE_MAX = 300;

    /**
     * Catalogue embarqué dans la page tant qu'il reste petit (recherche instantanée) ;
     * au-delà, l'écran interroge le serveur — une clinique avec 800 médicaments ne
     * téléchargeait plus rien d'utile sur une connexion lente.
     */
    private function catalogue(): array
    {
        $limiter = fn ($collection) => $collection->count() > self::CATALOGUE_EMBARQUE_MAX ? collect() : $collection;

        return array_map($limiter, [
            'services' => Service::actifs()->orderBy('name')->get()->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->amount])->values(),
            'packages' => Package::orderBy('name')->get()->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->price])->values(),
            'examens' => Test::orderBy('name')->get()->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->amount])->values(),
            'medicaments' => Medicament::actifs()->orderBy('nom')->get()->map(fn ($a) => [
                'id' => $a->id, 'nom' => $a->nom, 'prix' => (float) $a->amount,
                'dose' => $a->dosage, 'frequence' => $a->frequence, 'duree' => $a->duree, 'instructions' => $a->instructions,
            ])->values(),
        ]);
    }
}
