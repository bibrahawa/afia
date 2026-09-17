<?php

namespace App\Services\Labo;

use App\Enums\Labo\ModeFacturation;
use App\Enums\Labo\OrigineDemande;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Etablissement;
use App\Models\Labo\LaboCompteRendu;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Patient;
use App\Models\User;
use App\Support\ContexteTemporaire;
use App\Support\EtablissementContext;
use Illuminate\Support\Collection;

/**
 * Laboratoire en réseau, côté CLINIQUE prescriptrice.
 *
 * Une clinique sans laboratoire envoie ses demandes à un laboratoire
 * partenaire : elle voit son catalogue, lui envoie la demande, suit son
 * avancement et récupère le compte rendu.
 *
 * Toutes les lectures se font hors cloisonnement, mais TOUJOURS filtrées sur
 * `etablissement_prescripteur_id` = la clinique courante : une clinique ne
 * voit que ce qu'elle a elle-même prescrit.
 */
class LaboReseauService
{
    public function __construct(
        private DemandeService $demandes,
        private FacturationCliniqueAnalysesService $facturationClinique,
    ) {
    }

    /** Laboratoires partenaires de la clinique courante. */
    public function partenaires(?int $cliniqueId = null): Collection
    {
        $cliniqueId ??= $this->cliniqueCourante();

        return LaboPartenariat::withoutGlobalScopes()
            ->where('clinique_id', $cliniqueId)
            ->actifs()
            // Etablissement est cloisonné : sans withoutGlobalScopes, le nom du
            // laboratoire revenait vide dans toutes les listes de la clinique.
            ->with(['laboratoire' => fn ($q) => $q->withoutGlobalScopes()])
            ->get();
    }

    public function partenariat(int $partenariatId, ?int $cliniqueId = null): LaboPartenariat
    {
        $cliniqueId ??= $this->cliniqueCourante();

        $partenariat = LaboPartenariat::withoutGlobalScopes()
            ->where('clinique_id', $cliniqueId)
            ->with(['laboratoire' => fn ($q) => $q->withoutGlobalScopes()])
            ->find($partenariatId);

        if (! $partenariat) {
            throw new OperationLaboImpossible('Ce laboratoire n\'est pas partenaire de votre établissement.');
        }

        if (! $partenariat->estActif()) {
            throw new OperationLaboImpossible('Le partenariat avec ' . $partenariat->laboratoire?->nom . ' est suspendu.');
        }

        return $partenariat;
    }

    /** Catalogue du laboratoire partenaire, aux prix négociés. */
    public function catalogue(LaboPartenariat $partenariat, ?string $recherche = null): Collection
    {
        return LaboExamen::withoutGlobalScopes()
            ->where('etablissement_id', $partenariat->etablissement_id)
            ->where('actif', true)
            ->when($recherche, fn ($q) => $q->where(fn ($w) => $w->where('nom', 'like', "%{$recherche}%")->orWhere('code', 'like', "%{$recherche}%")))
            ->orderBy('nom')
            ->get()
            ->map(fn (LaboExamen $e) => [
                'id' => $e->id,
                'code' => $e->code,
                'nom' => $e->nom,
                'type_echantillon' => $e->type_echantillon,
                'a_jeun' => (bool) $e->a_jeun,
                'delai_heures' => $e->delai_rendu_heures,
                'prix' => $partenariat->prixNegocie((float) $e->prix),
            ]);
    }

    /**
     * Envoi d'une demande au laboratoire partenaire. La demande est créée
     * CHEZ LE LABORATOIRE (numérotation, examens, échantillons), avec la
     * clinique comme prescripteur.
     */
    public function envoyer(LaboPartenariat $partenariat, Patient $patient, array $donnees, User $auteur): LaboDemande
    {
        $examens = collect($donnees['examens'] ?? [])->map(fn ($id) => (int) $id)->unique();

        if ($examens->isEmpty()) {
            throw new OperationLaboImpossible('Sélectionnez au moins un examen.');
        }

        $inconnus = $examens->diff(
            LaboExamen::withoutGlobalScopes()
                ->where('etablissement_id', $partenariat->etablissement_id)
                ->where('actif', true)
                ->whereIn('id', $examens)
                ->pluck('id')
        );

        if ($inconnus->isNotEmpty()) {
            throw new OperationLaboImpossible('Un examen sélectionné n\'existe plus au catalogue du laboratoire.');
        }

        $cliniqueId = $this->cliniqueCourante();
        $clinique = Etablissement::withoutGlobalScopes()->find($cliniqueId);

        // Le laboratoire ne voit pas les employés de la clinique : on lui laisse
        // aussi le nom du prescripteur en clair sur la demande.
        $medecin = ! empty($donnees['prescripteur_employee_id'])
            ? \App\Models\Employee::find($donnees['prescripteur_employee_id'])
            : null;

        $prescripteurLisible = $donnees['prescripteur_externe']
            ?? trim(($medecin ? 'Dr ' . $medecin->full_name : 'Médecin') . ' — ' . ($clinique?->nom ?? 'clinique partenaire'));

        $demande = ContexteTemporaire::pour($partenariat->etablissement_id, fn () => $this->demandes->creer([
            'patient_id' => $patient->id,
            'origine' => OrigineDemande::EXTERNE->value,
            'examens' => $examens->all(),
            'etablissement_prescripteur_id' => $cliniqueId,
            'partenariat_id' => $partenariat->id,
            'consultation_id' => $donnees['consultation_id'] ?? null,
            'prescripteur_employee_id' => $donnees['prescripteur_employee_id'] ?? null,
            'prescripteur_externe' => $prescripteurLisible,
            'prescripteur_telephone' => $donnees['prescripteur_telephone'] ?? null,
            'renseignements_cliniques' => $donnees['renseignements_cliniques'] ?? null,
            'grossesse' => (bool) ($donnees['grossesse'] ?? false),
            'semaines_amenorrhee' => $donnees['semaines_amenorrhee'] ?? null,
            'urgence' => (bool) ($donnees['urgence'] ?? false),
            'mode_facturation' => $partenariat->modeFacturation()->value,
            // Le laboratoire encaisse le patient seulement si c'est le mode convenu.
            'facturer_maintenant' => $partenariat->modeFacturation() === ModeFacturation::LABO,
        ], $auteur));

        // La clinique facture son patient avec SES conventions d'assurance (lot 4c).
        if ($partenariat->cliniqueFacturePatient()) {
            try {
                $this->facturationClinique->facturer($demande, $partenariat, $auteur);
            } catch (\Throwable $e) {
                // Les deux écritures vivent dans deux établissements : pas de
                // transaction commune. Si la facture échoue, la demande envoyée
                // est annulée pour ne pas laisser une analyse non facturée.
                ContexteTemporaire::pour($partenariat->etablissement_id, fn () => $this->demandes->annuler(
                    LaboDemande::findOrFail($demande->id),
                    'Facturation impossible dans la clinique prescriptrice',
                    $auteur
                ));

                throw $e;
            }
        }

        return $demande;
    }

    /**
     * La clinique retire une demande envoyée par erreur. Possible tant que le
     * laboratoire n'a rien prélevé ; ensuite, c'est à lui de l'annuler.
     */
    public function annulerEnvoi(LaboDemande $demande, string $motif, User $auteur): void
    {
        $demande = $this->demandePrescrite($demande->id);

        if ($demande->statut !== \App\Enums\Labo\StatutDemande::ENREGISTREE) {
            throw new OperationLaboImpossible('Le laboratoire a déjà commencé le traitement : demandez-lui l\'annulation.');
        }

        ContexteTemporaire::pour($demande->etablissement_id, fn () => $this->demandes->annuler(
            LaboDemande::findOrFail($demande->id),
            trim($motif) ?: 'Annulée par la clinique prescriptrice',
            $auteur
        ));
    }

    /** Demandes publiées que la clinique n'a pas encore ouvertes. */
    public function resultatsNonVus(?int $cliniqueId = null): int
    {
        $cliniqueId ??= $this->cliniqueCourante();

        return LaboDemande::withoutGlobalScopes()
            ->where('etablissement_prescripteur_id', $cliniqueId)
            ->whereNotNull('premiere_publication_le')
            ->whereNull('resultat_vu_le')
            ->count();
    }

    /** Demandes envoyées par la clinique courante à ses partenaires. */
    public function demandesEnvoyees(array $filtres = [], ?int $cliniqueId = null)
    {
        $cliniqueId ??= $this->cliniqueCourante();

        return LaboDemande::withoutGlobalScopes()
            ->where('etablissement_prescripteur_id', $cliniqueId)
            ->with([
                'patient',
                'examens' => fn ($q) => $q->withoutGlobalScopes(),
                'etablissement' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->when($filtres['partenariat_id'] ?? null, fn ($q, $id) => $q->where('partenariat_id', $id))
            ->when($filtres['statut'] ?? null, fn ($q, $statut) => $q->where('statut', $statut))
            ->when($filtres['patient'] ?? null, fn ($q, $terme) => $q->whereHas('patient', fn ($p) => $p
                ->where('first_name', 'like', "%{$terme}%")->orWhere('last_name', 'like', "%{$terme}%")))
            ->latest('id');
    }

    /** Une demande prescrite par la clinique courante, ou 404. */
    public function demandePrescrite(int $demandeId, ?int $cliniqueId = null): LaboDemande
    {
        $cliniqueId ??= $this->cliniqueCourante();

        $demande = LaboDemande::withoutGlobalScopes()
            ->where('etablissement_prescripteur_id', $cliniqueId)
            ->with([
                'patient',
                'examens' => fn ($q) => $q->withoutGlobalScopes()->with(['examen' => fn ($e) => $e->withoutGlobalScopes()]),
                'etablissement' => fn ($q) => $q->withoutGlobalScopes(),
                'partenariat' => fn ($q) => $q->withoutGlobalScopes()->with(['laboratoire' => fn ($l) => $l->withoutGlobalScopes()]),
                'prescripteur' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->find($demandeId);

        abort_unless($demande, 404, 'Demande introuvable ou prescrite par un autre établissement.');

        return $demande;
    }

    /** Dernier compte rendu publié d'une demande prescrite par la clinique. */
    public function compteRendu(LaboDemande $demande): ?LaboCompteRendu
    {
        return LaboCompteRendu::withoutGlobalScopes()
            ->where('demande_id', $demande->id)
            ->whereNotNull('publie_le')
            ->orderByDesc('version')
            ->first();
    }

    /** Relevés reçus des laboratoires partenaires (lot 4b), côté clinique. */
    public function relevesRecus(?int $cliniqueId = null)
    {
        $cliniqueId ??= $this->cliniqueCourante();

        return \App\Models\Labo\LaboRelevePartenaire::withoutGlobalScopes()
            ->whereIn('partenariat_id', $this->idsPartenariats($cliniqueId))
            ->whereIn('statut', [\App\Models\Labo\LaboRelevePartenaire::ENVOYE, \App\Models\Labo\LaboRelevePartenaire::SOLDE])
            ->with(['partenariat' => fn ($q) => $q->withoutGlobalScopes()->with(['laboratoire' => fn ($l) => $l->withoutGlobalScopes()])])
            ->latest('id');
    }

    public function releveRecu(int $releveId, ?int $cliniqueId = null): \App\Models\Labo\LaboRelevePartenaire
    {
        $cliniqueId ??= $this->cliniqueCourante();

        $releve = \App\Models\Labo\LaboRelevePartenaire::withoutGlobalScopes()
            ->whereIn('partenariat_id', $this->idsPartenariats($cliniqueId))
            ->whereIn('statut', [\App\Models\Labo\LaboRelevePartenaire::ENVOYE, \App\Models\Labo\LaboRelevePartenaire::SOLDE])
            ->with(['partenariat' => fn ($q) => $q->withoutGlobalScopes()->with(['laboratoire' => fn ($l) => $l->withoutGlobalScopes()])])
            ->find($releveId);

        abort_unless($releve, 404, 'Relevé introuvable ou destiné à un autre établissement.');

        // Les créances et leurs demandes, hors cloisonnement mais bornées à ce relevé.
        $releve->setRelation('creances', \App\Models\Labo\LaboCreancePartenaire::withoutGlobalScopes()
            ->where('releve_id', $releve->id)
            ->with(['demande' => fn ($q) => $q->withoutGlobalScopes()->with(['patient', 'examens' => fn ($e) => $e->withoutGlobalScopes()])])
            ->get());

        return $releve;
    }

    /** Total encore dû par la clinique à ses laboratoires partenaires. */
    public function resteDuPartenaires(?int $cliniqueId = null): float
    {
        return (float) $this->relevesRecus($cliniqueId)->get()->sum(fn ($releve) => $releve->resteDu());
    }

    private function idsPartenariats(int $cliniqueId): array
    {
        return LaboPartenariat::withoutGlobalScopes()->where('clinique_id', $cliniqueId)->pluck('id')->all();
    }

    private function cliniqueCourante(): int
    {
        $id = EtablissementContext::id();

        abort_unless($id, 403, 'Aucun établissement associé à cette session.');

        return (int) $id;
    }
}
