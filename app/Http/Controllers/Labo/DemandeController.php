<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Employee;
use App\Enums\Labo\StatutDemande;
use App\Http\Requests\Labo\StoreDemandeRequest;
use App\Models\Labo\LaboBilan;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboSection;
use App\Services\Labo\DemandeService;
use App\Services\Labo\FacturationLaboService;
use App\Support\Labo\ContexteLabo;
use Illuminate\Http\Request;

class DemandeController extends Controller
{
    public function __construct(private DemandeService $demandes)
    {
    }

    public function index(Request $request)
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'statut' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
            'urgence' => ['nullable', 'boolean'],
        ]);

        $demandes = LaboDemande::with(['patient', 'prescripteur', 'examens', 'transaction'])
            ->when($filtres['q'] ?? null, function ($q, $terme) {
                $q->where(fn ($w) => $w->where('numero', 'like', "%{$terme}%")
                    ->orWhereHas('patient', fn ($p) => $p->where('first_name', 'like', "%{$terme}%")
                        ->orWhere('last_name', 'like', "%{$terme}%")
                        ->orWhere('identifiant_national_sante', 'like', "%{$terme}%")));
            })
            ->when($filtres['statut'] ?? null, fn ($q, $s) => $q->where('statut', $s))
            ->when($filtres['date'] ?? null, fn ($q, $d) => $q->whereDate('created_at', $d))
            ->when($request->boolean('urgence'), fn ($q) => $q->where('urgence', true))
            ->orderByDesc('urgence')->latest()
            ->paginate(25)->withQueryString();

        return view('labo.demandes.index', [
            'demandes' => $demandes,
            'filtres' => $filtres,
            'statuts' => StatutDemande::cases(),
        ]);
    }

    public function create(Request $request)
    {
        return view('labo.demandes.create', [
            'sections' => LaboSection::where('actif', true)->orderBy('ordre')
                ->with(['examens' => fn ($q) => $q->where('actif', true)])->get(),
            'bilans' => LaboBilan::where('actif', true)->with('examens:id')->orderBy('nom')->get(),
            'medecins' => Employee::where('type', 'Doctor')->where('is_active', true)->orderBy('last_name')->get(),
        ]);
    }

    public function store(StoreDemandeRequest $request)
    {
        $demande = $this->demandes->creer($request->validated(), $request->user());

        return redirect()->route('labo.demandes.show', $demande)
            ->with('success', "Demande {$demande->numero} enregistrée.");
    }

    public function show(LaboDemande $laboDemande)
    {
        $laboDemande->load([
            'patient.comptesPatients', 'prescripteur', 'enregistrePar', 'consultation',
            'examens.examen.section', 'examens.validateurBiologique',
            'echantillons.examens', 'echantillons.preleveur',
            'comptesRendus.publiePar', 'transaction.invoice', 'transaction.paiements',
        ]);

        ContexteLabo::journaliser('demande_consultee', $laboDemande);

        return view('labo.demandes.show', ['demande' => $laboDemande]);
    }

    public function depuisConsultation(Request $request, Consultation $consultation)
    {
        ['demande' => $demande, 'non_convertis' => $nonConvertis] = $this->demandes->creerDepuisConsultation($consultation, $request->user());

        $redirection = redirect()->route('labo.demandes.show', $demande)->with('success', "Demande {$demande->numero} créée depuis la consultation.");

        return $nonConvertis
            ? $redirection->with('error', 'Tests non reliés au catalogue labo (à ajouter manuellement) : ' . implode(', ', $nonConvertis))
            : $redirection;
    }

    public function annuler(Request $request, LaboDemande $laboDemande)
    {
        $motif = $request->validate(['motif' => ['required', 'string', 'max:255']])['motif'];
        $this->demandes->annuler($laboDemande, $motif, $request->user());

        return back()->with('success', 'Demande annulée.');
    }

    public function annulerExamen(Request $request, LaboDemandeExamen $laboLigne)
    {
        $motif = $request->validate(['motif' => ['required', 'string', 'max:255']])['motif'];
        $this->demandes->annulerExamen($laboLigne, $motif);

        return back()->with('success', "Examen « {$laboLigne->examen_nom} » annulé.");
    }

    public function facturer(LaboDemande $laboDemande, FacturationLaboService $facturation)
    {
        ContexteLabo::verifierAppartenance($laboDemande);
        $transaction = $facturation->facturer($laboDemande);

        return back()->with('success', "Facture {$transaction->invoice_no} créée.");
    }
}
