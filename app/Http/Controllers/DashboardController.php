<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Chambre;
use App\Models\Consultation;
use App\Models\Hospitalisation;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\Medicament;
use App\Models\Patient;
use App\Models\Transaction;

class DashboardController extends Controller
{
    /**
     * Tableau de bord : la journée en cours, pas des totaux de toujours.
     *
     * L'ancienne version affichait un « chiffre d'affaires » calculé sur les
     * dix dernières transactions et un nombre de « patients assurés » égal au
     * nombre total de patients. Tout est recalculé ici, sur des périodes
     * explicites, et les soldes passent par SoldeTransaction comme partout.
     */
    public function admin()
    {
        $aujourdhui = [today()->startOfDay(), today()->endOfDay()];

        // ----------------------------------------------------------- Journée
        $visites = class_exists(\App\Models\Parcours\Visite::class)
            ? \App\Models\Parcours\Visite::whereBetween('arrivee_le', $aujourdhui)->with(['patient', 'medecin'])->get()
            : collect();

        $enAttente = $visites->filter(fn ($v) => $v->statut->value === 'en_attente');
        $enConsultation = $visites->filter(fn ($v) => $v->statut->value === 'en_consultation');

        $rdvDuJour = Appointment::with(['patient', 'employee', 'motifRdv'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get();

        // ----------------------------------------------------------- Caisse
        $paiements = \App\Models\Paiement::whereBetween('created_at', $aujourdhui)->get();

        $resteAEncaisser = Transaction::whereBetween('created_at', $aujourdhui)
            ->where('status', '!=', 'cancel')
            ->get()
            ->sum(fn (Transaction $t) => \App\Support\Facturation\SoldeTransaction::pour($t)->resteDuPatient());

        // ----------------------------------------------------------- Activité des 7 jours
        $semaine = collect(range(6, 0))->map(function (int $recul) {
            $jour = today()->subDays($recul);

            return [
                'date' => $jour,
                'consultations' => Consultation::whereBetween('created_at', [$jour->copy()->startOfDay(), $jour->copy()->endOfDay()])->count(),
            ];
        });

        return view('dashboard', [
            'visites' => $visites,
            'enAttente' => $enAttente->sortBy('arrivee_le'),
            'enConsultation' => $enConsultation,
            'terminees' => $visites->filter(fn ($v) => $v->statut->value === 'terminee')->count(),
            'attenteMoyenne' => $this->attenteMoyenne($visites),

            'rdvDuJour' => $rdvDuJour,
            'rdvHonores' => $rdvDuJour->where('status', 'completed')->count(),
            'rdvAbsents' => $rdvDuJour->where('status', 'no_show')->count(),

            'encaisseCejour' => round($paiements->where('type', '!=', 'remise')->sum('montant')),
            'resteAEncaisser' => round($resteAEncaisser),

            'hospitalisesActifs' => Hospitalisation::where('statut', 'active')->count(),
            'chambresLibres' => Chambre::where('statut', 'libre')->count(),

            'creancesAssurance' => round(InsuranceClaim::whereIn('status', ['draft', 'submitted', 'approved'])->get()
                ->sum(fn (InsuranceClaim $c) => $c->resteDu())),
            'reclamationsAEnvoyer' => InsuranceClaim::where('status', 'draft')->count(),

            'laboEnCours' => class_exists(\App\Models\Labo\LaboDemande::class)
                ? \App\Models\Labo\LaboDemande::whereNotIn('statut', ['publiee', 'annulee'])->count()
                : 0,

            'nouveauxPatients' => Patient::suivisParEtablissement()->whereBetween('patients.created_at', $aujourdhui)->count(),
            'derniersPatients' => Patient::suivisParEtablissement()->latest('patients.id')->limit(6)->get(),
            'semaine' => $semaine,
        ]);
    }

    /** Minutes écoulées entre l'arrivée et l'appel, pour les patients déjà appelés. */
    private function attenteMoyenne($visites): ?int
    {
        $attentes = $visites->filter(fn ($v) => $v->appele_le)
            ->map(fn ($v) => (int) $v->arrivee_le->diffInMinutes($v->appele_le, true));

        return $attentes->isEmpty() ? null : (int) round($attentes->avg());
    }

    public function indexProfessionel()
    {
        $professional = auth()->user();

        return view('professional.dashboard', compact('professional'));
    }
}