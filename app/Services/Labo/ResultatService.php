<?php

namespace App\Services\Labo;

use App\Models\User;
use App\Enums\Labo\FlagResultat;
use App\Enums\Labo\StatutExamen;
use App\Enums\Labo\TypeResultat;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Labo\LaboAntibiogramme;
use App\Models\Labo\LaboAntibiotique;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboGerme;
use App\Models\Labo\LaboGermeIsole;
use App\Models\Labo\LaboParametre;
use App\Models\Labo\LaboResultat;
use App\Support\Labo\ContexteLabo;
use App\Support\Labo\FormuleEvaluateur;
use App\Support\Labo\InterpretationResultat;
use App\Support\Labo\SelecteurValeurReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultatService
{
    public function __construct(
        private FormuleEvaluateur $formules,
        private DemandeService $demandes,
    ) {
    }

    /**
     * Enregistre (ou corrige avant validation biologique) les résultats d'un examen.
     *
     * @param  array<int|string, string|null>  $saisies  parametre_id => valeur brute saisie
     * @return Collection<LaboResultat> résultats critiques après enregistrement
     */
    public function enregistrer(LaboDemandeExamen $ligne, array $saisies, User $auteur, string $source = 'manuel'): Collection
    {
        ContexteLabo::verifierAppartenance($ligne);

        $autorises = [StatutExamen::RECU, StatutExamen::EN_COURS, StatutExamen::VALIDE_TECHNIQUE];
        if (! in_array($ligne->statut, $autorises, true)) {
            throw new OperationLaboImpossible(match (true) {
                $ligne->statut->estVerrouille() => 'Résultats validés par le biologiste : rouvrez l\'examen pour rectification.',
                $ligne->statut === StatutExamen::ANNULE => 'Cet examen est annulé.',
                default => 'L\'échantillon n\'a pas encore été réceptionné au laboratoire.',
            });
        }

        $ligne->loadMissing(['demande.patient', 'examen.parametres.valeursReference', 'resultats']);
        $demande = $ligne->demande;
        $parametres = $ligne->examen->parametres;

        // 1. Contrôle et normalisation de TOUTES les saisies avant la moindre écriture.
        $valeurs = $this->normaliserSaisies($parametres, $saisies);

        // 2. Paramètres calculés, à partir des valeurs numériques disponibles.
        $valeurs = $this->calculer($parametres, $valeurs, $ligne->resultats);

        $sexe = ContexteLabo::sexePatient($demande->patient);
        $ageJours = ContexteLabo::ageEnJours($demande->patient, $demande->created_at);

        return DB::transaction(function () use ($ligne, $parametres, $valeurs, $auteur, $source, $sexe, $ageJours, $demande) {
            $aChange = false;

            foreach ($parametres as $parametre) {
                if (! array_key_exists($parametre->id, $valeurs)) {
                    continue; // paramètre non transmis : on ne touche pas à l'existant
                }

                [$numerique, $texte] = $valeurs[$parametre->id];
                $plage = SelecteurValeurReference::choisir($parametre->valeursReference, $sexe, $ageJours, (bool) $demande->grossesse);
                $flag = $this->flag($parametre, $numerique, $texte, $plage);

                $resultat = $ligne->resultats->firstWhere('parametre_id', $parametre->id)
                    ?? new LaboResultat([
                        'etablissement_id' => $ligne->etablissement_id,
                        'demande_examen_id' => $ligne->id,
                        'parametre_id' => $parametre->id,
                    ]);

                $ancienne = $resultat->exists ? $resultat->valeurBrute() : null;

                $resultat->fill([
                    'valeur_numerique' => $numerique,
                    'valeur_texte' => $texte,
                    'flag' => $flag,
                    'libelle' => $parametre->libelle,
                    'unite' => $parametre->unite,
                    'decimales' => $parametre->decimales,
                    'norme_min' => $plage?->min,
                    'norme_max' => $plage?->max,
                    'norme_critique_min' => $plage?->critique_min,
                    'norme_critique_max' => $plage?->critique_max,
                    'norme_texte' => $plage?->texte_affiche ?: ($parametre->type_resultat->estNumerique() ? null : $plage?->valeur_attendue),
                ]);

                if (! $resultat->isDirty()) {
                    continue;
                }

                $valeurChangee = ! $resultat->exists || $resultat->isDirty(['valeur_numerique', 'valeur_texte']);
                if ($valeurChangee) {
                    $resultat->fill([
                        'saisi_par' => $auteur->id,
                        'saisi_le' => now(),
                        'source' => $parametre->type_resultat === TypeResultat::CALCULE ? 'calcule' : $source,
                    ]);
                }

                $resultat->save();

                if ($valeurChangee && $ancienne !== null) {
                    $resultat->historiques()->create([
                        'ancienne_valeur' => $ancienne,
                        'nouvelle_valeur' => $resultat->valeurBrute(),
                        'modifie_par' => $auteur->id,
                    ]);
                }

                $aChange = $aChange || $valeurChangee;
            }

            if ($aChange || $ligne->statut === StatutExamen::RECU) {
                // Toute modification après validation technique annule
                // cette validation : le technicien doit re-valider ce qu'il
                // a réellement vu.
                $ligne->update([
                    'statut' => StatutExamen::EN_COURS,
                    'valide_technique_par' => null,
                    'valide_technique_le' => null,
                ]);
                $this->demandes->rafraichirStatut($demande);
            }

            return $ligne->resultats()->get()->filter(fn (LaboResultat $r) => $r->flag?->estCritique());
        });
    }

    /**
     * Bactériologie : germes isolés + antibiogramme de chacun.
     *
     * @param  array<int, array{germe_id:int, numeration?:string, antibiogramme?: array<int, array{interpretation?:string, valeur?:string}>}>  $germes
     */
    public function enregistrerBacteriologie(LaboDemandeExamen $ligne, array $germes, User $auteur): void
    {
        ContexteLabo::verifierAppartenance($ligne);

        if (! in_array($ligne->statut, [StatutExamen::RECU, StatutExamen::EN_COURS, StatutExamen::VALIDE_TECHNIQUE], true)) {
            throw new OperationLaboImpossible('Saisie impossible pour cet examen dans son état actuel.');
        }

        DB::transaction(function () use ($ligne, $germes, $auteur) {
            $conserves = [];

            foreach ($germes as $saisie) {
                if (empty($saisie['germe_id'])) {
                    continue;
                }

                $germe = LaboGerme::findOrFail($saisie['germe_id']); // scopé : un germe d'un autre établissement → 404

                $isole = LaboGermeIsole::updateOrCreate(
                    ['demande_examen_id' => $ligne->id, 'germe_id' => $germe->id],
                    ['etablissement_id' => $ligne->etablissement_id, 'germe_nom' => $germe->nom, 'numeration' => $saisie['numeration'] ?? null]
                );
                $conserves[] = $isole->id;

                $antibiotiquesConserves = [];
                foreach ($saisie['antibiogramme'] ?? [] as $antibiotiqueId => $ab) {
                    $interpretation = strtoupper(trim($ab['interpretation'] ?? ''));
                    if (! array_key_exists($interpretation, LaboAntibiogramme::INTERPRETATIONS)) {
                        continue; // non testé
                    }

                    $antibiotique = LaboAntibiotique::findOrFail($antibiotiqueId);
                    LaboAntibiogramme::updateOrCreate(
                        ['germe_isole_id' => $isole->id, 'antibiotique_id' => $antibiotique->id],
                        [
                            'etablissement_id' => $ligne->etablissement_id,
                            'antibiotique_nom' => $antibiotique->nom,
                            'interpretation' => $interpretation,
                            'valeur' => $ab['valeur'] ?? null,
                        ]
                    );
                    $antibiotiquesConserves[] = $antibiotique->id;
                }

                $isole->antibiogramme()->whereNotIn('antibiotique_id', $antibiotiquesConserves)->delete();
            }

            $ligne->germesIsoles()->whereNotIn('id', $conserves)->delete();

            $ligne->update(['statut' => StatutExamen::EN_COURS, 'valide_technique_par' => null, 'valide_technique_le' => null]);
            $this->demandes->rafraichirStatut($ligne->demande);

            ContexteLabo::journaliser('antibiogramme_saisi', $ligne, null, ['germes' => count($conserves), 'par' => $auteur->id]);
        });
    }

    /**
     * Derniers résultats du même patient pour les mêmes paramètres (même code),
     * dans CET établissement — l'historique d'autres établissements relève
     * du consentement (AccesDossierSanteService), pas d'un accès implicite.
     *
     * @return Collection<string, Collection> code paramètre => résultats antérieurs
     */
    public function anteriorites(LaboDemandeExamen $ligne, int $limite = 3): Collection
    {
        $ligne->loadMissing('demande', 'examen.parametres');
        $codes = $ligne->examen->parametres->pluck('code');

        return LaboResultat::query()
            ->join('labo_demande_examens as lde', 'lde.id', '=', 'labo_resultats.demande_examen_id')
            ->join('labo_demandes as ld', 'ld.id', '=', 'lde.demande_id')
            ->join('labo_parametres as lp', 'lp.id', '=', 'labo_resultats.parametre_id')
            ->where('ld.patient_id', $ligne->demande->patient_id)
            ->where('ld.id', '!=', $ligne->demande_id)
            ->whereIn('lp.code', $codes)
            ->whereIn('lde.statut', [StatutExamen::VALIDE_BIOLOGIQUE->value, StatutExamen::PUBLIE->value])
            ->orderByDesc('ld.created_at')
            ->select('labo_resultats.*', 'lp.code as parametre_code', 'ld.created_at as date_demande', 'ld.numero as numero_demande')
            ->get()
            ->groupBy('parametre_code')
            ->map(fn ($groupe) => $groupe->take($limite));
    }

    /** @return array<int, array{0: ?float, 1: ?string}> */
    private function normaliserSaisies(Collection $parametres, array $saisies): array
    {
        $erreurs = [];
        $valeurs = [];

        foreach ($parametres as $parametre) {
            if ($parametre->type_resultat === TypeResultat::CALCULE || ! array_key_exists($parametre->id, $saisies)) {
                continue;
            }

            $brut = is_string($saisies[$parametre->id]) ? trim($saisies[$parametre->id]) : $saisies[$parametre->id];

            if ($brut === null || $brut === '') {
                $valeurs[$parametre->id] = [null, null];
                continue;
            }

            if ($parametre->type_resultat === TypeResultat::NUMERIQUE) {
                // Accepte « 1,25 », « 1.25 », « < 0,10 », « >= 1000 ».
                if (! preg_match('/^(<=|>=|<|>)?\s*(-?\d+(?:[.,]\d+)?)$/u', str_replace(' ', '', (string) $brut), $m)) {
                    $erreurs["valeurs.{$parametre->id}"] = "« {$parametre->libelle} » : valeur numérique attendue.";
                    continue;
                }
                $nombre = (float) str_replace(',', '.', $m[2]);
                $texte = $m[1] !== '' ? $m[1] . ' ' . str_replace('.', ',', $m[2]) : null;
                $valeurs[$parametre->id] = [$nombre, $texte];
                continue;
            }

            if ($parametre->type_resultat->utiliseOptions() && $parametre->options) {
                if (! in_array($brut, $parametre->options, true)) {
                    $erreurs["valeurs.{$parametre->id}"] = "« {$parametre->libelle} » : choix non autorisé.";
                    continue;
                }
            }

            $valeurs[$parametre->id] = [null, mb_substr((string) $brut, 0, 5000)];
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }

        return $valeurs;
    }

    private function calculer(Collection $parametres, array $valeurs, Collection $existants): array
    {
        $calcules = $parametres->filter(fn (LaboParametre $p) => $p->type_resultat === TypeResultat::CALCULE && $p->formule);
        if ($calcules->isEmpty()) {
            return $valeurs;
        }

        // Valeurs soumises en priorité, sinon valeurs déjà enregistrées : une
        // saisie partielle ne doit pas effacer un calcul encore valable.
        $numeriques = [];
        foreach ($parametres as $p) {
            if (array_key_exists($p->id, $valeurs)) {
                $numeriques[$p->code] = $valeurs[$p->id][0];
            } elseif ($existant = $existants->firstWhere('parametre_id', $p->id)) {
                $numeriques[$p->code] = $existant->valeur_numerique;
            }
        }

        // Plusieurs passes : un calculé peut dépendre d'un autre calculé.
        for ($passe = 0; $passe < $calcules->count(); $passe++) {
            foreach ($calcules as $p) {
                try {
                    $resultat = $this->formules->evaluer($p->formule, $numeriques);
                } catch (\InvalidArgumentException) {
                    $resultat = null; // formule cassée : ne jamais bloquer la saisie, le champ reste vide
                }
                $numeriques[$p->code] = $resultat === null ? null : round($resultat, $p->decimales);
                $valeurs[$p->id] = [$numeriques[$p->code], null];
            }
        }

        return $valeurs;
    }

    private function flag(LaboParametre $parametre, ?float $numerique, ?string $texte, $plage): ?FlagResultat
    {
        if (! $plage) {
            return null;
        }

        if ($parametre->type_resultat->estNumerique()) {
            return InterpretationResultat::numerique($numerique, $plage->min, $plage->max, $plage->critique_min, $plage->critique_max);
        }

        return InterpretationResultat::qualitatif($texte, $plage->valeur_attendue);
    }
}
