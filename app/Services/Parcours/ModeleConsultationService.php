<?php

namespace App\Services\Parcours;

use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Models\Consultation;
use App\Models\Employee;
use App\Models\Parcours\ModeleConsultation;
use App\Models\Parcours\ModeleConsultationLigne;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Modèles de consultation (protocoles).
 *
 * Aucun formulaire de création : le médecin enregistre comme modèle une
 * consultation qu'il vient de terminer, et la réapplique ensuite en un clic.
 */
class ModeleConsultationService
{
    private const TYPES = ['service' => 'services', 'package' => 'packages', 'test' => 'tests', 'medicament' => 'medicaments'];

    public function enregistrerDepuis(Consultation $consultation, array $donnees, ?User $auteur): ModeleConsultation
    {
        $libelle = trim((string) ($donnees['libelle'] ?? ''));

        if ($libelle === '') {
            throw new OperationParcoursImpossible('Donnez un nom au modèle (ex. « Paludisme simple »).');
        }

        $consultation->loadMissing('services', 'packages', 'tests', 'medicaments', 'visite');

        return DB::transaction(function () use ($consultation, $donnees, $auteur, $libelle) {
            $modele = ModeleConsultation::create([
                'department_id' => $consultation->department_id,
                'medecin_id' => empty($donnees['partager']) ? $consultation->medecin_id : null,
                'motif_rdv_id' => $consultation->visite?->motif_rdv_id,
                'libelle' => mb_substr($libelle, 0, 255),
                'diagnostic' => $consultation->diagnostic,
                'signes_cliniques' => $consultation->signes_cliniques,
                'observation' => empty($donnees['avec_observation']) ? null : $consultation->observation,
                'cree_par' => $auteur?->id,
            ]);

            foreach ($consultation->services as $service) {
                $this->ligne($modele, 'service', $service->id);
            }
            foreach ($consultation->packages as $package) {
                $this->ligne($modele, 'package', $package->id);
            }
            foreach ($consultation->tests as $test) {
                $this->ligne($modele, 'test', $test->id);
            }
            foreach ($consultation->medicaments as $medicament) {
                $this->ligne($modele, 'medicament', $medicament->id, [
                    'quantite' => max(1, (int) ($medicament->pivot->quantity ?: 1)),
                    'dose' => $medicament->pivot->dose,
                    'frequence' => $medicament->pivot->frequence,
                    'duree' => $medicament->pivot->duree,
                    'instructions' => $medicament->pivot->instructions,
                ]);
            }

            return $modele->load('lignes');
        });
    }

    /** Contenu d'un modèle, prêt à être appliqué à l'écran (aucune écriture). */
    public function contenu(ModeleConsultation $modele, Employee $medecin): array
    {
        if ($modele->medecin_id && (int) $modele->medecin_id !== (int) $medecin->id) {
            throw new OperationParcoursImpossible('Ce modèle appartient à un autre médecin.');
        }

        $modele->loadMissing('lignes');
        $catalogue = $this->catalogue($modele);

        $modele->increment('utilisations');

        return [
            'libelle' => $modele->libelle,
            'diagnostic' => $modele->diagnostic,
            'observation' => $modele->observation,
            'signes_cliniques' => $modele->signes_cliniques ?? [],
            'lignes' => $modele->lignes->map(function (ModeleConsultationLigne $ligne) use ($catalogue) {
                $acte = $catalogue[$ligne->type][$ligne->acte_id] ?? null;

                return $acte ? [
                    'type' => $ligne->type,
                    'id' => $ligne->acte_id,
                    'nom' => $acte['nom'],
                    'prix' => $acte['prix'],
                    'quantite' => max(1, (int) $ligne->quantite),
                ] + $ligne->posologie() : null;
            })->filter()->values()->all(),
        ];
    }

    public function supprimer(ModeleConsultation $modele, Employee $medecin, bool $administrateur = false): void
    {
        if (! $administrateur && $modele->medecin_id && (int) $modele->medecin_id !== (int) $medecin->id) {
            throw new OperationParcoursImpossible('Seul son auteur peut supprimer ce modèle.');
        }

        $modele->update(['actif' => false]);
    }

    /** Actes encore présents au catalogue de l'établissement (un acte supprimé disparaît du modèle). */
    private function catalogue(ModeleConsultation $modele): array
    {
        $parType = $modele->lignes->groupBy('type');

        return [
            'service' => \App\Models\Service::whereIn('id', $parType->get('service', collect())->pluck('acte_id'))->get()
                ->mapWithKeys(fn ($a) => [$a->id => ['nom' => $a->name, 'prix' => (float) $a->amount]])->all(),
            'package' => \App\Models\Package::whereIn('id', $parType->get('package', collect())->pluck('acte_id'))->get()
                ->mapWithKeys(fn ($a) => [$a->id => ['nom' => $a->name, 'prix' => (float) $a->price]])->all(),
            'test' => \App\Models\Test::whereIn('id', $parType->get('test', collect())->pluck('acte_id'))->get()
                ->mapWithKeys(fn ($a) => [$a->id => ['nom' => $a->name, 'prix' => (float) $a->amount]])->all(),
            'medicament' => \App\Models\Medicament::whereIn('id', $parType->get('medicament', collect())->pluck('acte_id'))->get()
                ->mapWithKeys(fn ($a) => [$a->id => ['nom' => $a->nom, 'prix' => (float) $a->amount]])->all(),
        ];
    }

    private function ligne(ModeleConsultation $modele, string $type, int $acteId, array $valeurs = []): void
    {
        ModeleConsultationLigne::create($valeurs + ['modele_id' => $modele->id, 'type' => $type, 'acte_id' => $acteId, 'quantite' => 1]);
    }
}
