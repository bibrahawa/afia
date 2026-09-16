<?php

namespace App\Http\Controllers;

use App\Models\Consentement;
use App\Models\ComptePatient;
use App\Models\Patient;
use App\Services\AccesDossierSanteService;
use App\Services\AppointmentStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortailPatientController extends Controller
{
    public function index()
    {
        $compte = Auth::guard('patient')->user();
        $dossiers = $compte->patients;

        if ($dossiers->count() === 1) {
            return redirect()->route('portail.dossier', $dossiers->first());
        }

        return view('portail.selection-dossier', compact('dossiers'));
    }

    public function dossier(Patient $patient)
    {
        $this->autoriserAcces($patient);

        $patient->load(['antecedant', 'relationsFamiliales.personneLiee']);

        $prochainRdv = $patient->appointments()
            ->with(['employee', 'motifRdv'])
            ->where('status', '!=', 'cancelled')
            ->where('appointment_datetime', '>', now())
            ->orderBy('appointment_datetime')
            ->first();

        $historiqueRdv = $patient->appointments()
            ->with(['employee', 'motifRdv'])
            ->where('appointment_datetime', '<=', now())
            ->orderByDesc('appointment_datetime')
            ->limit(20)
            ->get();

        $consentements = $patient->consentementsAccordes()->latest()->get();
        $demandesEnAttente = $patient->demandesAcces()->where('statut', 'en_attente')->latest()->get();
        $consultations = $patient->consultations()->latest()->limit(10)->get();

        $comptePatient = Auth::guard('patient')->user();
        $autresDossiers = $comptePatient->patients->reject(fn ($p) => $p->id === $patient->id);

        return view('portail.dossier', compact(
            'patient', 'prochainRdv', 'historiqueRdv', 'consentements',
            'demandesEnAttente', 'consultations', 'autresDossiers'
        ));
    }

    public function annulerRdv(Request $request, \App\Models\Appointment $appointment, AppointmentStatusService $statusService)
    {
        $this->autoriserAcces($appointment->patient);

        if (! $appointment->canBeCancelled()) {
            return back()->with('error', "Ce rendez-vous ne peut plus être annulé en ligne (moins de 2h avant, ou déjà passé) — appelez la clinique directement.");
        }

        $statusService->cancel($appointment, 'Annulé par le patient depuis le portail');

        return back()->with('success', 'Rendez-vous annulé.');
    }

    public function revoquerAcces(Consentement $consentement, AccesDossierSanteService $acces)
    {
        $this->autoriserAcces($consentement->patient);

        $acces->revoquer($consentement);

        return back()->with('success', 'Accès révoqué.');
    }

    /**
     * CORRIGÉ — cette méthode se contentait de vérifier que le patient
     * appartenait au compte (`compte_patient` existe), sans distinguer
     * `titulaire` de `tuteur`. Concrètement : un membre du personnel qui
     * rattachait n'importe quel patient avec le rôle 'tuteur' donnait un
     * accès complet et permanent au dossier, SANS jamais passer par le
     * système de consentement — exactement le contournement qu'on avait
     * explicitement exclu dans la conception ("être parent d'un patient
     * adulte ne donne aucun accès automatique").
     *
     * Règle appliquée maintenant :
     * - role = 'titulaire' → accès direct, c'est son propre dossier.
     * - role = 'tuteur' + patient mineur confirmé → accès direct (tutelle
     *   légale réelle).
     * - role = 'tuteur' + patient adulte OU minorité indéterminable (le
     *   doute profite à la prudence, jamais à l'accès) → un consentement
     *   actif du patient est exigé, comme pour tout accès élargi.
     */
    protected function autoriserAcces(Patient $patient): void
    {
        $compte = Auth::guard('patient')->user();
        $lien = $compte->patients->firstWhere('id', $patient->id);

        abort_if(! $lien, 403);

        if ($lien->pivot->role === 'titulaire') {
            return;
        }

        if ($patient->estMineur() === true) {
            return;
        }

        $consentementValide = $patient->consentementsAccordes()
            ->where('beneficiaire_type', ComptePatient::class)
            ->where('beneficiaire_id', $compte->id)
            ->get()
            ->contains(fn (Consentement $c) => $c->couvre(\App\Enums\PorteeAcces::CarnetComplet));

        abort_unless($consentementValide, 403,
            "Ce dossier appartient à une personne majeure : un consentement explicite de sa part est nécessaire pour y accéder en délégation."
        );
    }
}
