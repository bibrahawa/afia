<?php

namespace App\Http\Controllers\Labo;

use App\Enums\Labo\StatutDemande;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Employee;
use App\Models\Labo\LaboPartenariat;
use App\Models\Patient;
use App\Services\Labo\CompteRenduService;
use App\Services\Labo\LaboReseauService;
use App\Support\ContexteTemporaire;
use Illuminate\Http\Request;

/**
 * Côté CLINIQUE prescriptrice : envoyer des analyses à un laboratoire
 * partenaire et suivre les résultats, sans que la clinique ait elle-même
 * le module laboratoire.
 */
class DemandeExterneController extends Controller
{
    public function __construct(private LaboReseauService $reseau)
    {
    }

    public function index(Request $request)
    {
        $filtres = $request->validate([
            'partenariat_id' => ['nullable', 'integer'],
            'statut' => ['nullable', 'string'],
            'patient' => ['nullable', 'string', 'max:60'],
        ]);

        return view('labo.reseau.index', [
            'demandes' => $this->reseau->demandesEnvoyees($filtres)->paginate(25)->withQueryString(),
            'nonVus' => $this->reseau->resultatsNonVus(),
            'partenaires' => $this->reseau->partenaires(),
            'statuts' => StatutDemande::cases(),
            'filtres' => $filtres,
        ]);
    }

    public function create(Request $request)
    {
        $donnees = $request->validate([
            'partenariat_id' => ['nullable', 'integer'],
            'patient_id' => ['nullable', 'integer'],
            'consultation_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:60'],
        ]);

        $partenaires = $this->reseau->partenaires();
        $partenariat = ! empty($donnees['partenariat_id'])
            ? $this->reseau->partenariat((int) $donnees['partenariat_id'])
            : $partenaires->first();

        $consultation = ! empty($donnees['consultation_id'])
            ? Consultation::with('patient')->find($donnees['consultation_id'])
            : null;

        return view('labo.reseau.create', [
            'partenaires' => $partenaires,
            'partenariat' => $partenariat,
            'catalogue' => $partenariat ? $this->reseau->catalogue($partenariat, $donnees['q'] ?? null) : collect(),
            'recherche' => $donnees['q'] ?? '',
            'patient' => $consultation?->patient ?? (! empty($donnees['patient_id']) ? Patient::suivisParEtablissement()->find($donnees['patient_id']) : null),
            'consultation' => $consultation,
            'medecins' => Employee::where('type', 'Doctor')->where('is_active', true)->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'partenariat_id' => ['required', 'integer'],
            'patient_id' => ['required', 'integer'],
            'consultation_id' => ['nullable', 'integer'],
            'prescripteur_employee_id' => ['nullable', 'exists_etablissement:employees,id'],
            'examens' => ['required', 'array', 'min:1'],
            'examens.*' => ['integer'],
            'renseignements_cliniques' => ['nullable', 'string', 'max:2000'],
            'grossesse' => ['nullable', 'boolean'],
            'semaines_amenorrhee' => ['nullable', 'integer', 'min:1', 'max:45'],
            'urgence' => ['nullable', 'boolean'],
        ], [
            'examens.required' => 'Sélectionnez au moins un examen.',
            'patient_id.required' => 'Choisissez le patient.',
        ]);

        $partenariat = $this->reseau->partenariat((int) $donnees['partenariat_id']);
        $patient = Patient::suivisParEtablissement()->findOrFail($donnees['patient_id']);

        $demande = $this->reseau->envoyer($partenariat, $patient, $donnees, $request->user());

        return redirect()->route('labo.reseau.show', $demande->id)
            ->with('success', "Demande {$demande->numero} envoyée à {$partenariat->laboratoire->nom}.");
    }

    public function show(int $demande, \App\Services\Labo\NotificationReseauService $notifications)
    {
        $demande = $this->reseau->demandePrescrite($demande);
        $notifications->marquerVue($demande, request()->user());

        return view('labo.reseau.show', [
            'demande' => $demande,
            'compteRendu' => $this->reseau->compteRendu($demande),
        ]);
    }

    /**
     * Recherche de patient propre au réseau : la clinique n'a pas forcément le
     * module Assurance, dont dépendait l'ancien sélecteur.
     */
    public function patients(Request $request)
    {
        return app(\App\Http\Controllers\PatientController::class)->rechercheRapide($request);
    }

    /** Bon d'analyses remis au patient qui se présente au laboratoire. */
    public function bon(int $demande)
    {
        $demande = $this->reseau->demandePrescrite($demande);

        return view('labo.reseau.bon', [
            'demande' => $demande,
            'identite' => \App\Support\Etablissement\IdentiteDocument::courante(),
        ]);
    }

    /** La clinique retire une demande envoyée par erreur (avant tout prélèvement). */
    public function annuler(Request $request, int $demande)
    {
        $donnees = $request->validate(['motif' => ['nullable', 'string', 'max:255']]);

        $this->reseau->annulerEnvoi($this->reseau->demandePrescrite($demande), $donnees['motif'] ?? '', $request->user());

        return redirect()->route('labo.reseau.index')->with('success', 'Demande annulée auprès du laboratoire.');
    }

    /** Relevés reçus des laboratoires partenaires. */
    public function factures()
    {
        return view('labo.reseau.factures', [
            'releves' => $this->reseau->relevesRecus()->paginate(25),
            'resteDu' => $this->reseau->resteDuPartenaires(),
        ]);
    }

    public function facture(int $releve)
    {
        return view('labo.reseau.facture', ['releve' => $this->reseau->releveRecu($releve)]);
    }

    /** Compte rendu PDF du laboratoire, lu par la clinique qui a prescrit. */
    public function compteRendu(int $demande, CompteRenduService $comptesRendus)
    {
        $demande = $this->reseau->demandePrescrite($demande);
        $compteRendu = $this->reseau->compteRendu($demande);

        abort_unless($compteRendu, 404, 'Aucun compte rendu publié pour cette demande.');

        $binaire = ContexteTemporaire::pour($demande->etablissement_id, fn () => $comptesRendus->pdf($compteRendu));

        return response($binaire, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $demande->numero . '.pdf"',
        ]);
    }
}
