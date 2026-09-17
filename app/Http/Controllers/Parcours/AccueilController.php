<?php

namespace App\Http\Controllers\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Http\Controllers\Assurance\Concerns\ResoutPatient;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\MotifRdv;
use App\Models\Parcours\Visite;
use App\Models\Service;
use App\Services\Parcours\AccueilService;
use Illuminate\Http\Request;

/** Tableau de l'accueil : rendez-vous du jour, arrivées, constantes, file d'attente. */
class AccueilController extends Controller
{
    use ResoutPatient;

    public function __construct(private AccueilService $accueil)
    {
    }

    public function index()
    {
        $arrives = Visite::duJour()->whereNotNull('appointment_id')->pluck('appointment_id');

        return view('parcours.accueil.index', [
            'rendezVous' => Appointment::with(['patient', 'employee', 'motifRdv'])
                ->whereDate('appointment_date', today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereNotIn('id', $arrives)
                ->orderBy('appointment_time')
                ->get(),
            'visites' => Visite::duJour()
                ->with(['patient', 'medecin', 'derniereConstante', 'consultation.transaction'])
                ->orderByRaw("FIELD(statut, 'en_consultation', 'en_attente', 'terminee', 'partie', 'annulee')")
                ->ordreFile()
                ->get(),
            'medecins' => Employee::where('type', 'Doctor')->where('is_active', true)->orderBy('first_name')->get(),
            'motifs' => MotifRdv::where('actif', true)->orderBy('ordre_affichage')->get(),
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function arriveeRendezVous(Request $request, Appointment $appointment)
    {
        $donnees = $request->validate([
            'service_id' => ['nullable', 'exists_etablissement:services,id'],
            'urgence' => ['nullable', 'boolean'],
        ]);

        $visite = $this->accueil->arriveeDepuisRendezVous($appointment, $donnees, $request->user());

        return back()->with('success', "{$visite->patient->full_name} est dans la file du Dr {$visite->medecin->full_name}." . $this->rappelCaisse($visite));
    }

    public function arriveeSansRendezVous(Request $request)
    {
        $donnees = $request->validate([
            'patient_id' => ['required', 'integer'],
            'medecin_id' => ['required', 'exists_etablissement:employees,id'],
            'motif_rdv_id' => ['nullable', 'exists_etablissement:motifs_rdv,id'],
            'motif' => ['nullable', 'string', 'max:255'],
            'service_id' => ['nullable', 'exists_etablissement:services,id'],
            'urgence' => ['nullable', 'boolean'],
            'notes_accueil' => ['nullable', 'string', 'max:1000'],
        ], ['patient_id.required' => 'Choisissez le patient.', 'medecin_id.required' => 'Choisissez le médecin.']);

        $visite = $this->accueil->arriveeSansRendezVous(
            $this->patientAutorise($request),
            Employee::findOrFail($donnees['medecin_id']),
            $donnees,
            $request->user()
        );

        return back()->with('success', "{$visite->patient->full_name} est dans la file du Dr {$visite->medecin->full_name}." . $this->rappelCaisse($visite));
    }

    public function constantes(Request $request, Visite $visite)
    {
        $donnees = $request->validate([
            'poids_kg' => ['nullable', 'numeric', 'min:0.3', 'max:350'],
            'taille_cm' => ['nullable', 'numeric', 'min:20', 'max:250'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'tension_systolique' => ['nullable', 'integer', 'min:40', 'max:300'],
            'tension_diastolique' => ['nullable', 'integer', 'min:20', 'max:200'],
            'pouls' => ['nullable', 'integer', 'min:20', 'max:250'],
            'frequence_respiratoire' => ['nullable', 'integer', 'min:5', 'max:80'],
            'saturation_o2' => ['nullable', 'integer', 'min:50', 'max:100'],
            'glycemie' => ['nullable', 'numeric', 'min:0.1', 'max:9.99'],
            'ddr' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->accueil->enregistrerConstantes($visite, $donnees, $request->user());

        return back()->with('success', 'Constantes enregistrées : le médecin les verra à l\'ouverture de la consultation.');
    }

    public function transferer(Request $request, Visite $visite)
    {
        $donnees = $request->validate(['medecin_id' => ['required', 'exists_etablissement:employees,id']]);
        $this->accueil->transferer($visite, Employee::findOrFail($donnees['medecin_id']));

        return back()->with('success', 'Patient transféré.');
    }

    public function partie(Request $request, Visite $visite)
    {
        $this->accueil->marquerPartie($visite, $request->input('motif'), $request->boolean('annuler_facture', true));

        return back()->with('success', 'Visite clôturée : patient reparti sans consulter.');
    }

    /** L'accueil décide qui passe : priorité immédiate, ou déplacement d'une place. */
    public function prioriser(Visite $visite)
    {
        $this->accueil->placerEnTete($visite);

        return back()->with('success', "{$visite->patient->full_name} passe en tête de la file.");
    }

    public function deplacer(Request $request, Visite $visite)
    {
        $donnees = $request->validate(['direction' => ['required', 'in:haut,bas']]);
        $this->accueil->deplacer($visite, $donnees['direction'] === 'haut' ? -1 : 1);

        return back();
    }

    public function reinitialiserOrdre(Visite $visite)
    {
        $this->accueil->reinitialiserOrdre($visite);

        return back()->with('success', 'Ordre remis à la règle de la clinique.');
    }

    /** Règle par défaut de la clinique : ordre d'arrivée, ou rendez-vous d'abord. */
    public function reglageOrdre(Request $request)
    {
        $donnees = $request->validate(['ordre_file' => ['required', 'in:arrivee,rendez_vous']]);

        $etablissement = \App\Support\EtablissementContext::current();
        abort_unless($etablissement, 403);
        $etablissement->update(['ordre_file' => $donnees['ordre_file']]);

        return back()->with('success', $donnees['ordre_file'] === 'arrivee'
            ? 'Les patients passent désormais dans leur ordre d\'arrivée.'
            : 'Les patients ayant un rendez-vous passent désormais avant les venues spontanées.');
    }

    public function absent(Appointment $appointment)
    {
        $this->accueil->marquerAbsent($appointment);

        return back()->with('success', 'Rendez-vous marqué « absent ».');
    }

    private function rappelCaisse(Visite $visite): string
    {
        $transaction = $visite->consultation?->transaction()->first();

        return $transaction && $transaction->status !== 'paid'
            ? ' Acte facturé : ' . number_format((float) $transaction->total, 0, ',', ' ') . ' GNF à encaisser.'
            : '';
    }
}
