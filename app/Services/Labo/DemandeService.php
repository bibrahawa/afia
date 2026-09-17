<?php

namespace App\Services\Labo;

use App\Models\Consultation;
use App\Models\Department;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\User;
use App\Enums\Labo\ModeFacturation;
use App\Enums\Labo\OrigineDemande;
use App\Enums\Labo\StatutDemande;
use App\Enums\Labo\StatutEchantillon;
use App\Enums\Labo\StatutExamen;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboBilan;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboEchantillon;
use App\Models\Labo\LaboExamen;
use App\Support\Labo\ContexteLabo;
use App\Support\EtablissementContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemandeService
{
    public function __construct(
        private NumerotationService $numerotation,
        private FacturationLaboService $facturation,
    ) {
    }

    /**
     * @param  array{patient_id:int, origine:string, examens?:int[], bilans?:int[], ...}  $donnees
     */
    public function creer(array $donnees, User $auteur): LaboDemande
    {
        $etablissementId = ContexteLabo::etablissementId();

        return DB::transaction(function () use ($donnees, $auteur, $etablissementId) {
            $patient = Patient::findOrFail($donnees['patient_id']);
            [$examens, $bilanParExamen] = $this->resoudreExamens($donnees['examens'] ?? [], $donnees['bilans'] ?? []);

            if ($examens->isEmpty()) {
                throw new OperationLaboImpossible('Sélectionnez au moins un examen.');
            }

            $numero = $this->numerotation->numeroDemande($etablissementId)['numero'];

            $demande = LaboDemande::create([
                'etablissement_id' => $etablissementId,
                'numero' => $numero,
                'patient_id' => $patient->id,
                'origine' => $donnees['origine'],
                'consultation_id' => $donnees['consultation_id'] ?? null,
                'prescripteur_employee_id' => $donnees['origine'] === OrigineDemande::INTERNE->value ? ($donnees['prescripteur_employee_id'] ?? null) : null,
                'prescripteur_externe' => $donnees['origine'] === OrigineDemande::EXTERNE->value ? ($donnees['prescripteur_externe'] ?? null) : null,
                'prescripteur_telephone' => $donnees['prescripteur_telephone'] ?? null,
                'renseignements_cliniques' => $donnees['renseignements_cliniques'] ?? null,
                'grossesse' => (bool) ($donnees['grossesse'] ?? false),
                'semaines_amenorrhee' => $donnees['semaines_amenorrhee'] ?? null,
                'a_jeun_confirme' => isset($donnees['a_jeun_confirme']) ? (bool) $donnees['a_jeun_confirme'] : null,
                'urgence' => (bool) ($donnees['urgence'] ?? false),
                'statut' => StatutDemande::ENREGISTREE,
                'mode_facturation' => $donnees['mode_facturation'] ?? ModeFacturation::LABO->value,
                'resultats_retenus_si_impaye' => (bool) ($donnees['resultats_retenus_si_impaye'] ?? true),
                'enregistre_par' => $auteur->id,
            ]);

            $this->ajouterExamens($demande, $examens, $bilanParExamen);
            $this->rattacherPatientEtablissement($patient);

            ContexteLabo::journaliser('demande_creee', $demande, "Demande {$numero}", [
                'examens' => $examens->pluck('code')->all(),
            ]);

            if ($demande->mode_facturation === ModeFacturation::LABO && ($donnees['facturer_maintenant'] ?? true)) {
                $this->facturation->facturer($demande);
            }

            return $demande->fresh(['examens', 'echantillons']);
        });
    }

    /**
     * Convertit les tests déjà prescrits dans une consultation (ancien
     * catalogue `tests`) en demande labo. Mode de facturation CONSULTATION :
     * la consultation facture déjà ces tests, les refacturer ici créerait
     * une double facture au patient.
     *
     * @return array{demande: LaboDemande, non_convertis: string[]}
     */
    public function creerDepuisConsultation(Consultation $consultation, User $auteur): array
    {
        // Consultation n'est pas encore multi-tenant : on vérifie
        // l'appartenance via son département, qui l'est.
        abort_unless(Department::whereKey($consultation->department_id)->exists(), 404);

        $existante = LaboDemande::where('consultation_id', $consultation->id)
            ->where('statut', '!=', StatutDemande::ANNULEE->value)
            ->first();
        if ($existante) {
            return ['demande' => $existante, 'non_convertis' => []];
        }

        $consultation->loadMissing('tests');
        $examens = LaboExamen::where('actif', true)->whereIn('test_id', $consultation->tests->pluck('id'))->get();
        $nonConvertis = $consultation->tests->whereNotIn('id', $examens->pluck('test_id'))->pluck('name')->all();

        if ($examens->isEmpty()) {
            throw new OperationLaboImpossible(
                'Aucun test de cette consultation n\'est relié à un examen du catalogue labo. '
                . 'Renseignez la correspondance « Ancien test » dans le catalogue.'
            );
        }

        $demande = $this->creer([
            'patient_id' => $consultation->patient_id,
            'origine' => OrigineDemande::INTERNE->value,
            'consultation_id' => $consultation->id,
            'prescripteur_employee_id' => $consultation->medecin_id,
            'renseignements_cliniques' => trim(($consultation->motif ?? '') . "\n" . ($consultation->diagnostic ?? '')) ?: null,
            'examens' => $examens->pluck('id')->all(),
            'mode_facturation' => ModeFacturation::CONSULTATION->value,
            'facturer_maintenant' => false,
        ], $auteur);

        return ['demande' => $demande, 'non_convertis' => $nonConvertis];
    }

    public function annuler(LaboDemande $demande, string $motif, User $auteur): void
    {
        ContexteLabo::verifierAppartenance($demande);

        DB::transaction(function () use ($demande, $motif, $auteur) {
            $demande->load('examens', 'transaction');

            if ($demande->examens->contains(fn ($e) => $e->statut->rang() >= StatutExamen::VALIDE_TECHNIQUE->rang() && $e->statut !== StatutExamen::ANNULE)) {
                throw new OperationLaboImpossible('Des résultats sont déjà validés : annulez uniquement les examens concernés.');
            }

            if ($demande->transaction && Paiement::where('transaction_id', $demande->transaction->id)->exists()) {
                throw new OperationLaboImpossible('Un paiement a déjà été encaissé pour cette demande : traitez d\'abord le remboursement en caisse.');
            }

            foreach ($demande->examens as $examen) {
                $examen->update(['statut' => StatutExamen::ANNULE, 'annule_le' => now(), 'motif_annulation' => $motif]);
            }

            $demande->update([
                'statut' => StatutDemande::ANNULEE,
                'annule_le' => now(),
                'annule_par' => $auteur->id,
                'motif_annulation' => $motif,
            ]);

            if ($demande->transaction) {
                $demande->transaction->update(['status' => 'cancel']);
            }

            ContexteLabo::journaliser('demande_annulee', $demande, $motif);
        });
    }

    public function annulerExamen(LaboDemandeExamen $examen, string $motif): void
    {
        ContexteLabo::verifierAppartenance($examen);

        DB::transaction(function () use ($examen, $motif) {
            if ($examen->statut->rang() >= StatutExamen::VALIDE_TECHNIQUE->rang() && $examen->statut !== StatutExamen::ANNULE) {
                throw new OperationLaboImpossible('Cet examen est déjà validé : il ne peut plus être annulé.');
            }

            $examen->update(['statut' => StatutExamen::ANNULE, 'annule_le' => now(), 'motif_annulation' => $motif]);
            $demande = $examen->demande;

            $this->rafraichirStatut($demande);
            $this->facturation->recalculerSiFacturee($demande);

            ContexteLabo::journaliser('examen_annule', $examen, $motif);
        });
    }

    /**
     * Crée les lignes d'examens puis regroupe en contenants physiques : une
     * NFS et une VS partagent le même tube violet, pas deux piqûres.
     */
    public function ajouterExamens(LaboDemande $demande, Collection $examens, array $bilanParExamen = []): void
    {
        $lignes = collect();

        foreach ($examens as $examen) {
            if ($demande->examens()->where('examen_id', $examen->id)->exists()) {
                continue;
            }

            $lignes->push(LaboDemandeExamen::create([
                'etablissement_id' => $demande->etablissement_id,
                'demande_id' => $demande->id,
                'examen_id' => $examen->id,
                'bilan_id' => $bilanParExamen[$examen->id] ?? null,
                'examen_nom' => $examen->nom,
                'prix_applique' => $examen->prix,
                'statut' => StatutExamen::EN_ATTENTE_PRELEVEMENT,
                'sous_traite' => $examen->sous_traite,
                'laboratoire_sous_traitant' => $examen->laboratoire_sous_traitant,
            ])->setRelation('examen', $examen));
        }

        $index = $demande->echantillons()->count();

        foreach ($lignes->groupBy(fn ($ligne) => $ligne->examen->cleContenant()) as $groupe) {
            $modele = $groupe->first()->examen;
            $index++;

            $echantillon = LaboEchantillon::create([
                'etablissement_id' => $demande->etablissement_id,
                'demande_id' => $demande->id,
                'code_barres' => NumerotationService::codeBarres($demande->numero, $index),
                'type_echantillon' => $modele->type_echantillon,
                'tube' => $modele->tube,
                'statut' => StatutEchantillon::ATTENDU,
            ]);

            $echantillon->examens()->attach($groupe->pluck('id'));
        }

        $this->rafraichirStatut($demande);
    }

    /** Recalcule le statut global à partir des examens (jamais saisi à la main). */
    public function rafraichirStatut(LaboDemande $demande): StatutDemande
    {
        $statuts = $demande->examens()->pluck('statut')
            ->map(fn ($s) => $s instanceof StatutExamen ? $s : StatutExamen::from($s))
            ->all();

        $statut = StatutDemande::deduire($statuts);

        if ($demande->statut !== $statut) {
            $demande->update(['statut' => $statut]);
        }

        return $statut;
    }

    /** @return array{0: Collection, 1: array<int,int>} examens + examen_id => bilan_id */
    private function resoudreExamens(array $examenIds, array $bilanIds): array
    {
        $ids = collect($examenIds)->map(fn ($id) => (int) $id);
        $bilanParExamen = [];

        if ($bilanIds) {
            foreach (LaboBilan::with('examens')->whereIn('id', $bilanIds)->get() as $bilan) {
                foreach ($bilan->examens as $examen) {
                    $ids->push($examen->id);
                    $bilanParExamen[$examen->id] ??= $bilan->id;
                }
            }
        }

        // Le global scope garantit qu'un id d'examen d'un autre établissement
        // (formulaire trafiqué) est simplement ignoré.
        $examens = LaboExamen::where('actif', true)->whereIn('id', $ids->unique()->values())->with('section')->get();

        return [$examens, $bilanParExamen];
    }

    private function rattacherPatientEtablissement(Patient $patient): void
    {
        $etablissement = EtablissementContext::current();
        if (! $etablissement) {
            return;
        }

        if ($etablissement->patients()->where('patient_id', $patient->id)->exists()) {
            $etablissement->patients()->updateExistingPivot($patient->id, ['derniere_visite_le' => now()]);
        } else {
            $etablissement->patients()->attach($patient->id, ['premiere_visite_le' => now(), 'derniere_visite_le' => now()]);
        }
    }
}
