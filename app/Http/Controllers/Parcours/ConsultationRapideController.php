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

    /** Catalogue complet envoyé une fois à l'écran : la recherche se fait sans aller-retour. */
    private function catalogue(): array
    {
        return [
            'services' => Service::orderBy('name')->get()->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->amount])->values(),
            'packages' => Package::orderBy('name')->get()->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->price])->values(),
            'examens' => Test::orderBy('name')->get()->map(fn ($a) => ['id' => $a->id, 'nom' => $a->name, 'prix' => (float) $a->amount])->values(),
            'medicaments' => Medicament::orderBy('nom')->get()->map(fn ($a) => [
                'id' => $a->id, 'nom' => $a->nom, 'prix' => (float) $a->amount,
                'dose' => $a->dosage, 'frequence' => $a->frequence, 'duree' => $a->duree, 'instructions' => $a->instructions,
            ])->values(),
        ];
    }
}
