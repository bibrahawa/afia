<?php

namespace App\Services\Labo;

use App\Models\Labo\LaboAntibiotique;
use App\Models\Labo\LaboBilan;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboGerme;
use App\Models\Labo\LaboParametre;
use App\Models\Labo\LaboSection;
use Illuminate\Support\Facades\DB;

/**
 * Copie le catalogue MODÈLE de la plateforme dans un établissement.
 *
 * Idempotent : un élément déjà importé (même modele_id) n'est jamais
 * recopié ni écrasé — les personnalisations du labo (prix, normes) sont
 * sacrées. Relancer l'import après un enrichissement du modèle ajoute
 * seulement les nouveautés.
 */
class CatalogueImportService
{
    /** @return array<string,int> nombre d'éléments ajoutés par type */
    public function importer(int $etablissementId): array
    {
        // Importer pour un AUTRE établissement que celui de la session serait refusé
        // ligne par ligne par BelongsToEtablissement : on le dit clairement d'emblée.
        $courant = \App\Support\EtablissementContext::id();
        if ($courant !== null && (int) $courant !== $etablissementId) {
            throw new \App\Exceptions\Labo\OperationLaboImpossible(
                'Import du catalogue pour un autre établissement : utilisez « php artisan labo:activer <slug> » (hors session).'
            );
        }

        return DB::transaction(function () use ($etablissementId) {
            $compte = ['sections' => 0, 'examens' => 0, 'bilans' => 0, 'germes' => 0, 'antibiotiques' => 0];

            $sections = $this->copierSimple(LaboSection::class, $etablissementId, ['code', 'nom', 'ordre', 'actif'], $compte['sections']);

            $examensModele = LaboExamen::withoutGlobalScope('etablissement')
                ->whereNull('etablissement_id')
                ->with(['parametres' => fn ($q) => $q->withoutGlobalScope('etablissement'), 'parametres.valeursReference'])
                ->get();

            $dejaImportes = $this->correspondances(LaboExamen::class, $etablissementId);
            $codesExistants = LaboExamen::withoutGlobalScope('etablissement')
                ->where('etablissement_id', $etablissementId)->pluck('id', 'code')->all();
            $examens = $dejaImportes;

            foreach ($examensModele as $modele) {
                if (isset($dejaImportes[$modele->id])) {
                    continue;
                }

                // Le labo a déjà créé lui-même un examen avec ce code : on le
                // garde tel quel (jamais écrasé) et on se contente de le relier.
                if (isset($codesExistants[$modele->code])) {
                    LaboExamen::withoutGlobalScope('etablissement')->whereKey($codesExistants[$modele->code])->update(['modele_id' => $modele->id]);
                    $examens[$modele->id] = $codesExistants[$modele->code];
                    continue;
                }

                $copie = $modele->replicate(['etablissement_id', 'modele_id', 'section_id', 'test_id']);
                $copie->etablissement_id = $etablissementId;
                $copie->modele_id = $modele->id;
                $copie->section_id = $sections[$modele->section_id];
                $copie->save();

                foreach ($modele->parametres as $parametreModele) {
                    $parametre = $parametreModele->replicate(['etablissement_id', 'examen_id']);
                    $parametre->etablissement_id = $etablissementId;
                    $parametre->examen_id = $copie->id;
                    $parametre->save();

                    foreach ($parametreModele->valeursReference as $valeur) {
                        $copieValeur = $valeur->replicate(['parametre_id']);
                        $copieValeur->parametre_id = $parametre->id;
                        $copieValeur->save();
                    }
                }

                $examens[$modele->id] = $copie->id;
                $compte['examens']++;
            }

            $bilansModele = LaboBilan::withoutGlobalScope('etablissement')->whereNull('etablissement_id')->with('examens')->get();
            $bilansImportes = $this->correspondances(LaboBilan::class, $etablissementId);
            foreach ($bilansModele as $modele) {
                if (isset($bilansImportes[$modele->id])) {
                    continue;
                }
                if (LaboBilan::withoutGlobalScope('etablissement')->where('etablissement_id', $etablissementId)->where('code', $modele->code)->exists()) {
                    continue; // bilan homonyme créé par le labo : on n'y touche pas
                }
                $bilan = LaboBilan::create([
                    'etablissement_id' => $etablissementId, 'modele_id' => $modele->id,
                    'code' => $modele->code, 'nom' => $modele->nom, 'actif' => $modele->actif,
                ]);
                $bilan->examens()->attach($modele->examens->pluck('id')->map(fn ($id) => $examens[$id] ?? null)->filter()->all());
                $compte['bilans']++;
            }

            $this->copierSimple(LaboGerme::class, $etablissementId, ['nom', 'actif'], $compte['germes']);
            $this->copierSimple(LaboAntibiotique::class, $etablissementId, ['nom', 'famille', 'ordre', 'actif'], $compte['antibiotiques']);

            return $compte;
        });
    }

    /** @return array<int,int> id modèle => id copie */
    private function copierSimple(string $classe, int $etablissementId, array $champs, int &$compteur): array
    {
        $map = $this->correspondances($classe, $etablissementId);

        foreach ($classe::withoutGlobalScope('etablissement')->whereNull('etablissement_id')->get() as $modele) {
            if (isset($map[$modele->id])) {
                continue;
            }

            $existant = in_array('code', $champs, true)
                ? $classe::withoutGlobalScope('etablissement')->where('etablissement_id', $etablissementId)->where('code', $modele->code)->first()
                : null;
            if ($existant) {
                $existant->update(['modele_id' => $modele->id]);
                $map[$modele->id] = $existant->id;
                continue;
            }

            $copie = $classe::create(array_merge($modele->only($champs), [
                'etablissement_id' => $etablissementId,
                'modele_id' => $modele->id,
            ]));
            $map[$modele->id] = $copie->id;
            $compteur++;
        }

        return $map;
    }

    private function correspondances(string $classe, int $etablissementId): array
    {
        return $classe::withoutGlobalScope('etablissement')
            ->where('etablissement_id', $etablissementId)
            ->whereNotNull('modele_id')
            ->pluck('id', 'modele_id')
            ->all();
    }
}
