<?php

namespace App\Support\Assurance;

use App\Enums\Assurance\FamilleActe;
use App\Models\Chambre;
use App\Models\Labo\LaboExamen;
use App\Models\Medicament;
use App\Models\Package;
use App\Models\Service;
use App\Models\Test;
use App\Support\Facturation\TypesFacturables;

/**
 * Famille d'actes d'une ligne facturable.
 *  - examens (tests, laboratoire) → laboratoire ; médicaments → pharmacie ;
 *    chambres → hospitalisation ;
 *  - services et packages : famille choisie dans le catalogue (consultation /
 *    soins par défaut — un forfait accouchement se classe en maternité).
 */
class FamillesActes
{
    private array $cache = [];

    public function pour(?string $acteType, ?int $acteId): FamilleActe
    {
        $classe = TypesFacturables::classe($acteType);
        $cle = $classe . '#' . $acteId;

        return $this->cache[$cle] ??= match ($classe) {
            Test::class, LaboExamen::class => FamilleActe::Laboratoire,
            Medicament::class => FamilleActe::Pharmacie,
            Chambre::class => FamilleActe::Hospitalisation,
            Package::class => FamilleActe::tryFrom((string) Package::withoutGlobalScope('etablissement')->whereKey($acteId)->value('famille_acte'))
                ?? FamilleActe::Soins,
            Service::class => FamilleActe::tryFrom((string) Service::withoutGlobalScope('etablissement')->whereKey($acteId)->value('famille_acte'))
                ?? FamilleActe::Consultation,
            default => FamilleActe::Autre,
        };
    }
}
