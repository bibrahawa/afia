<?php

namespace App\Services\Parcours;

use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Models\Consultation;
use App\Models\Employee;
use App\Models\Medicament;
use App\Models\MotifRdv;
use App\Models\Package;
use App\Models\Service;
use App\Models\Test;
use App\Models\User;
use App\Services\AppointmentBookingService;
use App\Services\BillingService;
use App\Services\DisponibiliteService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Enregistrement d'une consultation depuis l'écran rapide : un seul appel
 * écrit le clinique, les actes avec leur posologie, la facture, le prochain
 * rendez-vous et clôt la visite.
 */
class ConsultationRapideService
{
    private const MODELES = ['services' => Service::class, 'packages' => Package::class, 'examens' => Test::class, 'medicaments' => Medicament::class];

    public function __construct(
        private BillingService $facturation,
        private AccueilService $accueil,
        private AppointmentBookingService $rendezVous,
    ) {
    }

    /**
     * @param array $donnees diagnostic, observation, signes[], actes[type][], prochain_rdv_jours, prochain_rdv_motif_id, action
     * @return array{consultation: Consultation, avertissements: array}
     */
    public function enregistrer(Consultation $consultation, array $donnees, ?User $auteur): array
    {
        $terminer = ($donnees['action'] ?? 'terminer') === 'terminer';
        $diagnostic = trim((string) ($donnees['diagnostic'] ?? ''));

        if ($terminer && $diagnostic === '') {
            throw new OperationParcoursImpossible('Indiquez au moins le diagnostic avant de terminer la consultation.');
        }

        // Contrôlé AVANT la transaction : le médecin garde sa saisie si c'est refusé.
        $this->verifierRetraits($consultation, (array) ($donnees['actes'] ?? []));

        $avertissements = [];

        DB::transaction(function () use ($consultation, $donnees, $diagnostic, $terminer, &$avertissements) {
            $consultation->update([
                'diagnostic' => $diagnostic ?: null,
                'observation' => $donnees['observation'] ?? null,
                'signes_cliniques' => array_values(array_filter((array) ($donnees['signes'] ?? []))),
                'statut' => $terminer ? Consultation::TERMINEE : Consultation::EN_COURS,
            ]);

            $this->rattacherActes($consultation, (array) ($donnees['actes'] ?? []));
            $this->enregistrerAntecedents($consultation, (array) ($donnees['antecedents'] ?? []));
            $this->facturer($consultation);

            if ($message = $this->planifierProchainRdv($consultation, $donnees)) {
                $avertissements[] = $message;
            }

            if ($terminer && $consultation->visite && $consultation->visite->statut->estActive()) {
                $this->accueil->terminer($consultation->visite);
            }
        });

        return ['consultation' => $consultation->fresh(['services', 'packages', 'tests', 'medicaments', 'transaction']), 'avertissements' => $avertissements];
    }

    /**
     * Un acte déjà encaissé ou déjà réclamé à l'assureur ne peut plus disparaître
     * de la facture : on le dit clairement, plutôt que de laisser la facturation
     * refuser le recalcul une fois la consultation à moitié enregistrée.
     */
    private function verifierRetraits(Consultation $consultation, array $actes): void
    {
        $transaction = $consultation->transaction()->first();

        if (! $transaction || ! $transaction->invoice) {
            return;
        }

        $verrouillee = $transaction->paiements()->exists()
            || \App\Models\InsuranceClaim::where('invoice_id', $transaction->invoice->id)->where('status', '!=', 'draft')->exists();

        if (! $verrouillee) {
            return;
        }

        $consultation->loadMissing('services', 'packages', 'tests', 'medicaments');
        $conserves = collect($actes)->map(fn ($lignes) => collect($lignes)->pluck('id')->map(fn ($id) => (int) $id)->all());

        $retires = collect([
            'services' => $consultation->services->map(fn ($a) => [$a->id, $a->name]),
            'packages' => $consultation->packages->map(fn ($a) => [$a->id, $a->name]),
            'examens' => $consultation->tests->map(fn ($a) => [$a->id, $a->name]),
            'medicaments' => $consultation->medicaments->map(fn ($a) => [$a->id, $a->nom]),
        ])->flatMap(fn ($existants, $categorie) => $existants
            ->reject(fn ($acte) => in_array($acte[0], $conserves[$categorie] ?? [], true))
            ->pluck(1));

        if ($retires->isNotEmpty()) {
            throw new OperationParcoursImpossible(
                'Cette facture est déjà encaissée ou transmise à l\'assureur : « ' . $retires->join(' », « ')
                . ' » ne peut plus en être retiré. Laissez l\'acte et ajoutez ce qui manque.'
            );
        }
    }

    /** Allergies et antécédents mis à jour depuis l'écran du médecin, sans écraser ce qui n'est pas saisi. */
    private function enregistrerAntecedents(Consultation $consultation, array $antecedents): void
    {
        $valeurs = array_filter(
            array_intersect_key($antecedents, array_flip(['allergies', 'antecedents_medicaux', 'traitements_cours'])),
            fn ($v) => $v !== null
        );

        if (! $valeurs) {
            return;
        }

        \App\Models\Antecedent::updateOrCreate(['patient_id' => $consultation->patient_id], $valeurs);
    }

    /** Actes cochés à l'écran, avec la posologie prescrite pour chaque médicament. */
    private function rattacherActes(Consultation $consultation, array $actes): void
    {
        foreach (self::MODELES as $categorie => $modele) {
            $lignes = collect($actes[$categorie] ?? [])->filter(fn ($l) => ! empty($l['id']));
            $ids = $lignes->pluck('id')->map(fn ($id) => (int) $id)->unique();

            // Global scope BelongsToEtablissement : un acte d'une autre clinique n'est pas trouvé.
            $valides = $modele::whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id);

            if ($ids->diff($valides)->isNotEmpty()) {
                throw new OperationParcoursImpossible('Un acte sélectionné n\'existe pas dans le catalogue de l\'établissement.');
            }

            $sync = [];
            foreach ($lignes as $ligne) {
                $id = (int) $ligne['id'];
                $sync[$id] = $categorie === 'medicaments' ? [
                    'quantity' => max(1, (int) ($ligne['quantite'] ?? 1)),
                    'dose' => $this->texte($ligne['dose'] ?? null, 60),
                    'frequence' => $this->texte($ligne['frequence'] ?? null, 60),
                    'duree' => $this->texte($ligne['duree'] ?? null, 60),
                    'instructions' => $this->texte($ligne['instructions'] ?? null, 255),
                ] : [];
            }

            match ($categorie) {
                'services' => $consultation->services()->sync(array_keys($sync)),
                'packages' => $consultation->packages()->sync(array_keys($sync)),
                'examens' => $consultation->tests()->sync(array_keys($sync)),
                'medicaments' => $consultation->medicaments()->sync($sync),
            };
        }
    }

    private function facturer(Consultation $consultation): void
    {
        $consultation->load(['services', 'packages', 'tests', 'medicaments', 'transaction']);
        $transaction = $consultation->transaction;

        if ($transaction) {
            $this->facturation->recalculate($transaction);

            return;
        }

        if ($consultation->services->isNotEmpty() || $consultation->packages->isNotEmpty()
            || $consultation->tests->isNotEmpty() || $consultation->medicaments->isNotEmpty()) {
            $this->facturation->createFromConsultation($consultation);
        }
    }

    /** Boutons « dans 7 jours / 1 mois… » : le rendez-vous est créé avec le motif et sa durée. */
    private function planifierProchainRdv(Consultation $consultation, array $donnees): ?string
    {
        $jours = (int) ($donnees['prochain_rdv_jours'] ?? 0);

        if ($jours < 1) {
            return null;
        }

        $motif = ! empty($donnees['prochain_rdv_motif_id']) ? MotifRdv::find($donnees['prochain_rdv_motif_id']) : null;
        $medecin = $consultation->medecin;
        $duree = $motif && $medecin ? $motif->dureePour($medecin) : DisponibiliteService::DUREE_PAR_DEFAUT;
        // Premier créneau réellement libre à partir du jour visé, au lieu d'un 9h00 souvent déjà pris.
        $debut = $this->premierCreneauLibre($medecin, $motif, now()->addDays($jours)) ?? now()->addDays($jours)->setTime(9, 0);

        try {
            $rdv = $this->rendezVous->planifierParMedecin(
                (int) $consultation->medecin_id,
                (int) $consultation->patient_id,
                $debut,
                $duree,
                'Contrôle prévu en consultation',
                $motif?->id
            );

            $consultation->update(['prochain_rdv' => $rdv->appointment_datetime ?? $debut]);

            return null;
        } catch (DomainException $e) {
            return 'Prochain rendez-vous NON créé : ' . $e->getMessage();
        }
    }

    /** Premier créneau réellement libre du médecin à partir du jour visé (14 jours explorés). */
    private function premierCreneauLibre(?Employee $medecin, ?MotifRdv $motif, Carbon $aPartirDe): ?Carbon
    {
        if (! $medecin || ! $motif) {
            return null;
        }

        $disponibilites = app(DisponibiliteService::class);

        foreach ($disponibilites->joursDisponibles($medecin, $aPartirDe->copy()->startOfDay(), 14, $motif) as $jour) {
            $creneau = $disponibilites->creneauxDisponibles($medecin, Carbon::parse($jour), $motif)->first();

            if ($creneau) {
                return $creneau['debut']->copy();
            }
        }

        return null;
    }

    private function texte(?string $valeur, int $longueur): ?string
    {
        $valeur = trim((string) $valeur);

        return $valeur === '' ? null : mb_substr($valeur, 0, $longueur);
    }
}
