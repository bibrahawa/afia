<?php

namespace App\Services\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Models\Assurance\ConventionFamille;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Support\Assurance\CatalogueActes;
use App\Support\Facturation\TypesFacturables;
use Illuminate\Support\Facades\DB;

/**
 * Conventions tarifaires clinique ↔ organisme payeur.
 *
 * Deux niveaux :
 *  - RÈGLE PAR FAMILLE : tous les actes de la famille au prix catalogue, avec
 *    remise éventuelle (évite de saisir des centaines de médicaments) ;
 *  - LIGNE PAR ACTE : prix négocié, plafond, limite de fréquence, accord
 *    préalable — ou EXCLUSION d'un acte que la règle de famille couvrirait.
 *
 * Une modification ne touche pas les factures déjà émises ; elle s'applique
 * aux nouvelles factures et aux recalculs de factures non transmises.
 */
class ConventionService
{
    public function __construct(private CatalogueActes $catalogue)
    {
    }

    /** @param array<string, array> $familles valeurs saisies par famille */
    public function enregistrerFamilles(InsuranceCompany $organisme, array $familles): void
    {
        DB::transaction(function () use ($organisme, $familles) {
            foreach (FamilleActe::cases() as $famille) {
                $v = $familles[$famille->value] ?? [];

                if (empty($v['actif'])) {
                    ConventionFamille::where('insurance_company_id', $organisme->id)->where('famille_acte', $famille->value)->delete();
                    continue;
                }

                ConventionFamille::updateOrCreate(
                    ['insurance_company_id' => $organisme->id, 'famille_acte' => $famille->value],
                    [
                        'remise_pourcentage' => (float) ($v['remise_pourcentage'] ?? 0),
                        'plafond_par_acte' => ($v['plafond_par_acte'] ?? '') !== '' ? (float) $v['plafond_par_acte'] : null,
                        'accord_prealable' => ! empty($v['accord_prealable']),
                        'valid_from' => $v['valid_from'] ?? today()->toDateString(),
                        'valid_to' => ($v['valid_to'] ?? '') !== '' ? $v['valid_to'] : null,
                        'actif' => true,
                    ]
                );
            }
        });
    }

    public function ajouterActe(InsuranceCompany $organisme, string $type, int $acteId, array $donnees): InsuranceCoverage
    {
        $acte = $this->catalogue->trouver($type, $acteId);

        if (! $acte) {
            throw new OperationAssuranceImpossible('Acte introuvable dans le catalogue de l\'établissement.');
        }

        $existe = InsuranceCoverage::where('insurance_company_id', $organisme->id)
            ->pourActe($acte->getMorphClass(), $acte->id)
            ->exists();

        if ($existe) {
            throw new OperationAssuranceImpossible("« {$this->catalogue->libelle($acte)} » figure déjà dans la convention : modifiez la ligne existante.");
        }

        return InsuranceCoverage::create($this->valeursLigne($donnees) + [
            'insurance_company_id' => $organisme->id,
            'coverageable_type' => TypesFacturables::alias($acte->getMorphClass()),
            'coverageable_id' => $acte->id,
            'acte_price' => ($donnees['acte_price'] ?? '') !== '' ? (float) $donnees['acte_price'] : $this->catalogue->prixCatalogue($acte),
        ]);
    }

    public function modifierActe(InsuranceCoverage $ligne, array $donnees): InsuranceCoverage
    {
        $donnees += [
            'valid_from' => $ligne->valid_from?->toDateString(),
            'valid_to' => $ligne->valid_to?->toDateString(),
        ];
        if (($donnees['valid_from'] ?? '') === '') {
            $donnees['valid_from'] = $ligne->valid_from?->toDateString();
        }

        $ligne->update($this->valeursLigne($donnees) + ['acte_price' => (float) ($donnees['acte_price'] ?? $ligne->acte_price)]);

        return $ligne;
    }

    public function supprimerActe(InsuranceCoverage $ligne): void
    {
        $ligne->delete();
    }

    /**
     * Copie la convention d'un autre organisme. « compléter » ajoute seulement ce
     * qui manque ; « remplacer » efface d'abord la convention de la cible.
     *
     * @return array{actes: int, familles: int}
     */
    public function copier(InsuranceCompany $source, InsuranceCompany $cible, bool $remplacer): array
    {
        if ($source->is($cible)) {
            throw new OperationAssuranceImpossible('Choisissez un autre organisme comme source.');
        }

        return DB::transaction(function () use ($source, $cible, $remplacer) {
            if ($remplacer) {
                InsuranceCoverage::where('insurance_company_id', $cible->id)->delete();
                ConventionFamille::where('insurance_company_id', $cible->id)->delete();
            }

            $actes = 0;
            foreach (InsuranceCoverage::where('insurance_company_id', $source->id)->get() as $ligne) {
                $existe = InsuranceCoverage::where('insurance_company_id', $cible->id)
                    ->pourActe($ligne->coverageable_type, (int) $ligne->coverageable_id)->exists();

                if (! $existe) {
                    $copie = $ligne->replicate(['etablissement_id']);
                    $copie->insurance_company_id = $cible->id;
                    $copie->save();
                    $actes++;
                }
            }

            $familles = 0;
            foreach (ConventionFamille::where('insurance_company_id', $source->id)->get() as $regle) {
                $existe = ConventionFamille::where('insurance_company_id', $cible->id)->where('famille_acte', $regle->famille_acte->value)->exists();

                if (! $existe) {
                    $copie = $regle->replicate(['etablissement_id']);
                    $copie->insurance_company_id = $cible->id;
                    $copie->save();
                    $familles++;
                }
            }

            return ['actes' => $actes, 'familles' => $familles];
        });
    }

    private function valeursLigne(array $d): array
    {
        return [
            'coverage_amount_limit' => ($d['coverage_amount_limit'] ?? '') !== '' ? (float) $d['coverage_amount_limit'] : null,
            'max_usage_count' => ($d['max_usage_count'] ?? '') !== '' ? (int) $d['max_usage_count'] : null,
            'usage_period' => match ($d['usage_period'] ?? null) {
                'mois' => 'Mois', 'trimestre' => 'Trimestre', 'annee' => 'Annees', default => null,
            },
            'requires_preauthorization' => ! empty($d['requires_preauthorization']),
            // Exclu = la ligne existe mais l'acte n'est PAS couvert (prime sur la règle de famille).
            'status' => ! empty($d['exclu']) ? 'inactive' : 'active',
            'valid_from' => $d['valid_from'] ?? today()->toDateString(),
            'valid_to' => ($d['valid_to'] ?? '') !== '' ? $d['valid_to'] : null,
        ];
    }
}
