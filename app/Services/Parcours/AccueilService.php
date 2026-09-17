<?php

namespace App\Services\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Employee;
use App\Models\MotifRdv;
use App\Models\Parcours\Constante;
use App\Models\Parcours\Visite;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentStatusService;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;

/**
 * Accueil du patient : arrivée (avec ou sans rendez-vous), constantes,
 * facturation de l'acte, file d'attente, sortie.
 *
 * Principe : tout ce que l'accueil peut saisir, le médecin ne le ressaisit
 * jamais. À l'arrivée, la consultation est ouverte avec le patient, le
 * médecin, le motif et l'acte déjà facturé ; le médecin n'a plus qu'à la
 * compléter.
 */
class AccueilService
{
    public function __construct(
        private BillingService $facturation,
        private AppointmentStatusService $rendezVous,
    ) {
    }

    /** Arrivée d'un patient qui a rendez-vous aujourd'hui. */
    public function arriveeDepuisRendezVous(Appointment $rdv, array $donnees, ?User $auteur): Visite
    {
        if (! $rdv->appointment_date?->isToday()) {
            throw new OperationParcoursImpossible('Ce rendez-vous n\'est pas prévu aujourd\'hui.');
        }

        if (! in_array($rdv->status, ['pending', 'confirmed'], true)) {
            throw new OperationParcoursImpossible('Ce rendez-vous est annulé ou déjà terminé.');
        }

        if (Visite::where('appointment_id', $rdv->id)->exists()) {
            throw new OperationParcoursImpossible('L\'arrivée de ce patient a déjà été enregistrée.');
        }

        $rdv->loadMissing('motifRdv', 'employee', 'patient');

        return $this->ouvrir($rdv->patient, $rdv->employee, [
            'appointment_id' => $rdv->id,
            'motif_rdv_id' => $rdv->motif_rdv_id,
            'motif' => $rdv->motifRdv?->nom ?? 'Consultation',
            'service_id' => $donnees['service_id'] ?? $rdv->motifRdv?->service_id,
            'urgence' => (bool) ($donnees['urgence'] ?? false),
            'notes_accueil' => $donnees['notes_accueil'] ?? $rdv->description,
        ], $auteur);
    }

    /** Arrivée sans rendez-vous. */
    public function arriveeSansRendezVous(Patient $patient, Employee $medecin, array $donnees, ?User $auteur): Visite
    {
        $motif = ! empty($donnees['motif_rdv_id']) ? MotifRdv::findOrFail($donnees['motif_rdv_id']) : null;

        return $this->ouvrir($patient, $medecin, [
            'motif_rdv_id' => $motif?->id,
            'motif' => trim((string) ($donnees['motif'] ?? '')) ?: ($motif?->nom ?? 'Consultation'),
            'service_id' => $donnees['service_id'] ?? $motif?->service_id,
            'urgence' => (bool) ($donnees['urgence'] ?? false),
            'notes_accueil' => $donnees['notes_accueil'] ?? null,
        ], $auteur);
    }

    public function enregistrerConstantes(Visite $visite, array $donnees, ?User $auteur): Constante
    {
        $valeurs = array_filter(
            array_intersect_key($donnees, array_flip([
                'poids_kg', 'taille_cm', 'temperature', 'tension_systolique', 'tension_diastolique',
                'pouls', 'frequence_respiratoire', 'saturation_o2', 'glycemie', 'ddr', 'notes',
            ])),
            fn ($v) => $v !== null && $v !== ''
        );

        if (! $valeurs) {
            throw new OperationParcoursImpossible('Saisissez au moins une mesure.');
        }

        // Taille rarement remesurée chez l'adulte : reprise de la dernière mesure.
        if (! isset($valeurs['taille_cm'])) {
            $derniere = Constante::where('patient_id', $visite->patient_id)->whereNotNull('taille_cm')->latest('mesure_le')->value('taille_cm');
            if ($derniere && ($visite->patient->age ?? 0) >= 18) {
                $valeurs['taille_cm'] = $derniere;
            }
        }

        return Constante::create($valeurs + [
            'patient_id' => $visite->patient_id,
            'visite_id' => $visite->id,
            'mesure_par' => $auteur?->id,
            'mesure_le' => now(),
        ]);
    }

    /** Le médecin prend le patient suivant. */
    public function appeler(Visite $visite, Employee $medecin, bool $administrateur = false): Visite
    {
        if (! $visite->statut->estActive()) {
            throw new OperationParcoursImpossible('Cette visite est déjà terminée.');
        }

        if ((int) $visite->medecin_id !== (int) $medecin->id && ! $administrateur) {
            throw new OperationParcoursImpossible('Ce patient attend un autre médecin. Demandez à l\'accueil de le transférer.');
        }

        if ($visite->statut === StatutVisite::EnAttente) {
            $visite->update(['statut' => StatutVisite::EnConsultation, 'appele_le' => now()]);
        }

        return $visite;
    }

    /** Fin de consultation : visite terminée, rendez-vous honoré. */
    public function terminer(Visite $visite): Visite
    {
        if (! $visite->statut->estActive()) {
            return $visite;
        }

        return DB::transaction(function () use ($visite) {
            $visite->update([
                'statut' => StatutVisite::Terminee,
                'appele_le' => $visite->appele_le ?? now(),
                'terminee_le' => now(),
            ]);

            $visite->consultation?->update(['statut' => Consultation::TERMINEE]);

            if ($visite->appointment && in_array($visite->appointment->status, ['pending', 'confirmed'], true)) {
                $this->rendezVous->complete($visite->appointment);
            }

            return $visite;
        });
    }

    public function transferer(Visite $visite, Employee $medecin): Visite
    {
        if ($visite->statut !== StatutVisite::EnAttente) {
            throw new OperationParcoursImpossible('Seul un patient encore en attente peut être transféré.');
        }

        return DB::transaction(function () use ($visite, $medecin) {
            $visite->update(['medecin_id' => $medecin->id, 'department_id' => $medecin->department_id]);
            $visite->consultation?->update(['medecin_id' => $medecin->id, 'department_id' => $medecin->department_id]);

            return $visite;
        });
    }

    /**
     * Le patient est reparti sans être vu. Si rien n'a été encaissé, la facture
     * de l'acte d'accueil peut être annulée dans la foulée — sinon elle resterait
     * due sur le compte du patient.
     */
    public function marquerPartie(Visite $visite, ?string $motif = null, bool $annulerFacture = true): Visite
    {
        if ($visite->statut !== StatutVisite::EnAttente) {
            throw new OperationParcoursImpossible('Seul un patient encore en attente peut être marqué comme reparti.');
        }

        if ($annulerFacture) {
            $this->annulerFactureAccueil($visite);
        }

        $visite->update([
            'statut' => StatutVisite::Partie,
            'terminee_le' => now(),
            'notes_accueil' => trim(($visite->notes_accueil ? $visite->notes_accueil . "\n" : '') . 'Reparti sans consulter' . ($motif ? ' : ' . $motif : '')),
        ]);

        return $visite;
    }

    /** Rendez-vous du jour non honoré. */
    public function marquerAbsent(Appointment $rdv): Appointment
    {
        if (! in_array($rdv->status, ['pending', 'confirmed'], true) || Visite::where('appointment_id', $rdv->id)->exists()) {
            throw new OperationParcoursImpossible('Ce rendez-vous ne peut pas être marqué absent.');
        }

        $rdv->update(['status' => 'no_show']);

        return $rdv;
    }

    /**
     * « Faire passer maintenant » : la visite prend la tête de la file du médecin.
     * Toute la file est renumérotée (1, 2, 3…) — la colonne `rang` est un entier
     * non signé, un rang négatif serait refusé par la base.
     */
    public function placerEnTete(Visite $visite): Visite
    {
        return DB::transaction(function () use ($visite) {
            $file = Visite::where('medecin_id', $visite->medecin_id)->duJour()->actives()->ordreFile()->get()
                ->reject(fn (Visite $v) => $v->id === $visite->id)
                ->values()
                ->prepend($visite);

            foreach ($file as $position => $ligne) {
                $ligne->update(['rang' => $position + 1]);
            }

            return $visite->fresh();
        });
    }

    /** Monter (-1) ou descendre (+1) d'une place dans la file du médecin. */
    public function deplacer(Visite $visite, int $direction): Visite
    {
        $file = Visite::where('medecin_id', $visite->medecin_id)->duJour()->actives()->ordreFile()->get();
        $position = $file->search(fn (Visite $v) => $v->id === $visite->id);
        $cible = $position + ($direction < 0 ? -1 : 1);

        if ($position === false || $cible < 0 || $cible >= $file->count()) {
            return $visite;
        }

        $ordonnee = $file->values()->all();
        [$ordonnee[$position], $ordonnee[$cible]] = [$ordonnee[$cible], $ordonnee[$position]];

        // L'ordre imposé remplace la règle par défaut pour toute la file du jour.
        foreach ($ordonnee as $rang => $ligne) {
            $ligne->update(['rang' => $rang + 1]);
        }

        return $visite->fresh();
    }

    /** Retour à la règle par défaut de la clinique pour la file de ce médecin. */
    public function reinitialiserOrdre(Visite $visite): void
    {
        Visite::where('medecin_id', $visite->medecin_id)->duJour()->actives()->update(['rang' => null]);
    }

    /** Facture de l'acte d'accueil, si elle n'a reçu aucun encaissement. */
    private function annulerFactureAccueil(Visite $visite): void
    {
        $transaction = $visite->consultation?->transaction()->first();

        if (! $transaction) {
            return;
        }

        if ($transaction->paiements()->exists()) {
            throw new OperationParcoursImpossible('Un encaissement existe déjà sur cette visite : remboursez-le avant de la clôturer.');
        }

        DB::transaction(function () use ($transaction, $visite) {
            if ($invoice = $transaction->invoice()->first()) {
                app(\App\Services\InsuranceConsumptionService::class)->rollbackConsumption($invoice);
            }

            app(\App\Services\PatientAccountService::class)->retirerTransaction($transaction);
            $transaction->update(['status' => 'cancel']);
            $visite->consultation?->update(['statut' => \App\Models\Consultation::TERMINEE]);
        });
    }

    private function ouvrir(Patient $patient, Employee $medecin, array $donnees, ?User $auteur): Visite
    {
        // Relu en base : un modèle fraîchement créé n'a pas les valeurs par défaut
        // de la table (is_active), ce qui faisait passer un médecin actif pour inactif.
        $medecin = Employee::findOrFail($medecin->id);

        if ($medecin->type !== 'Doctor' || ! $medecin->is_active) {
            throw new OperationParcoursImpossible('Choisissez un médecin actif.');
        }

        $service = ! empty($donnees['service_id']) ? Service::findOrFail($donnees['service_id']) : null;

        return DB::transaction(function () use ($patient, $medecin, $donnees, $auteur, $service) {
            // Verrou : deux clics rapides créaient deux visites, donc deux consultations et deux factures.
            $dejaPresent = Visite::where('patient_id', $patient->id)->duJour()->actives()->lockForUpdate()->exists();

            if ($dejaPresent) {
                throw new OperationParcoursImpossible("{$patient->full_name} est déjà dans une file d'attente aujourd'hui.");
            }

            $visite = Visite::create([
                'patient_id' => $patient->id,
                'appointment_id' => $donnees['appointment_id'] ?? null,
                'medecin_id' => $medecin->id,
                'department_id' => $medecin->department_id,
                'motif_rdv_id' => $donnees['motif_rdv_id'] ?? null,
                'motif' => mb_substr($donnees['motif'], 0, 255),
                'statut' => StatutVisite::EnAttente,
                'urgence' => $donnees['urgence'],
                'arrivee_le' => now(),
                'notes_accueil' => $donnees['notes_accueil'] ?? null,
                'cree_par' => $auteur?->id,
            ]);

            // Consultation ouverte d'office : le médecin la complète, il ne la crée pas.
            $consultation = Consultation::create([
                'visite_id' => $visite->id,
                'appointment_id' => $visite->appointment_id,
                'patient_id' => $patient->id,
                'medecin_id' => $medecin->id,
                'department_id' => $medecin->department_id,
                'motif' => $visite->motif,
                'statut' => Consultation::EN_COURS,
            ]);

            // Acte du motif facturé dès l'arrivée : le patient passe en caisse avant la consultation.
            if ($service) {
                $consultation->services()->attach($service->id);
                $this->facturation->createFromConsultation($consultation->fresh());
            }

            return $visite->fresh(['consultation', 'patient', 'medecin']);
        });
    }
}
