<?php

namespace App\Services\Labo;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Etablissement;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboPartenariat;
use App\Models\User;
use App\Support\Labo\ContexteLabo;

/**
 * Partenariats, côté LABORATOIRE : c'est le laboratoire qui ouvre l'accès à
 * son catalogue et qui peut le suspendre.
 */
class PartenariatService
{
    public function creer(Etablissement $clinique, array $donnees, User $auteur): LaboPartenariat
    {
        $laboratoireId = ContexteLabo::etablissementId();

        if ((int) $clinique->id === $laboratoireId) {
            throw new OperationLaboImpossible('Un établissement ne peut pas être son propre partenaire.');
        }

        if (LaboPartenariat::where('clinique_id', $clinique->id)->exists()) {
            throw new OperationLaboImpossible("Un partenariat existe déjà avec {$clinique->nom}.");
        }

        $partenariat = LaboPartenariat::create([
            'etablissement_id' => $laboratoireId,
            'clinique_id' => $clinique->id,
            'statut' => LaboPartenariat::ACTIF,
            'mode_facturation_defaut' => in_array($donnees['mode_facturation_defaut'] ?? null, ['patient', 'partenaire'], true)
                ? $donnees['mode_facturation_defaut']
                : 'patient',
            'clinique_facture_patient' => (bool) ($donnees['clinique_facture_patient'] ?? false),
            'remise_pourcentage' => (float) ($donnees['remise_pourcentage'] ?? 0),
            'delai_paiement_jours' => $donnees['delai_paiement_jours'] ?? null,
            'contact_nom' => $donnees['contact_nom'] ?? null,
            'contact_telephone' => $donnees['contact_telephone'] ?? null,
            'notes' => $donnees['notes'] ?? null,
            'cree_par' => $auteur->id,
        ]);

        ContexteLabo::journaliser('partenariat_cree', $partenariat, "Partenariat avec {$clinique->nom}");

        return $partenariat;
    }

    public function modifier(LaboPartenariat $partenariat, array $donnees): LaboPartenariat
    {
        ContexteLabo::verifierAppartenance($partenariat);

        $partenariat->update([
            'mode_facturation_defaut' => in_array($donnees['mode_facturation_defaut'] ?? null, ['patient', 'partenaire'], true)
                ? $donnees['mode_facturation_defaut']
                : $partenariat->mode_facturation_defaut,
            'clinique_facture_patient' => (bool) ($donnees['clinique_facture_patient'] ?? $partenariat->clinique_facture_patient),
            'remise_pourcentage' => (float) ($donnees['remise_pourcentage'] ?? $partenariat->remise_pourcentage),
            'delai_paiement_jours' => $donnees['delai_paiement_jours'] ?? $partenariat->delai_paiement_jours,
            'contact_nom' => $donnees['contact_nom'] ?? $partenariat->contact_nom,
            'contact_telephone' => $donnees['contact_telephone'] ?? $partenariat->contact_telephone,
            'notes' => $donnees['notes'] ?? $partenariat->notes,
        ]);

        return $partenariat->fresh();
    }

    /** Suspendre ferme l'envoi de nouvelles demandes ; les demandes en cours se terminent normalement. */
    public function basculerStatut(LaboPartenariat $partenariat): LaboPartenariat
    {
        ContexteLabo::verifierAppartenance($partenariat);

        $partenariat->update([
            'statut' => $partenariat->estActif() ? LaboPartenariat::SUSPENDU : LaboPartenariat::ACTIF,
        ]);

        ContexteLabo::journaliser('partenariat_statut', $partenariat, 'Partenariat ' . ($partenariat->estActif() ? 'réactivé' : 'suspendu'));

        return $partenariat->fresh();
    }

    /** Activité d'un partenariat : demandes reçues et leur avancement. */
    public function activite(LaboPartenariat $partenariat): array
    {
        ContexteLabo::verifierAppartenance($partenariat);

        $demandes = LaboDemande::where('partenariat_id', $partenariat->id)->get(['id', 'statut', 'created_at']);

        return [
            'total' => $demandes->count(),
            'en_cours' => $demandes->filter(fn (LaboDemande $d) => ! in_array($d->statut->value, ['publiee', 'annulee'], true))->count(),
            'ce_mois' => $demandes->filter(fn (LaboDemande $d) => $d->created_at->isSameMonth(now()))->count(),
            'derniere' => $demandes->max('created_at'),
        ];
    }

    /** Établissements candidats : cliniques et cabinets pas encore partenaires. */
    public function candidats(): \Illuminate\Support\Collection
    {
        $laboratoireId = ContexteLabo::etablissementId();
        $dejaPartenaires = LaboPartenariat::pluck('clinique_id');

        return Etablissement::withoutGlobalScopes()
            ->whereIn('type', ['clinique', 'cabinet'])
            ->where('id', '!=', $laboratoireId)
            ->whereNotIn('id', $dejaPartenaires)
            ->orderBy('nom')
            ->get(['id', 'nom', 'type']);
    }
}
