<?php

namespace Tests\Unit\Labo;

use App\Enums\Labo\StatutDemande;
use App\Enums\Labo\StatutExamen as E;
use PHPUnit\Framework\TestCase;

class StatutDemandeTest extends TestCase
{
    public function test_deduction_du_statut_global(): void
    {
        $this->assertSame(StatutDemande::ANNULEE, StatutDemande::deduire([E::ANNULE, E::ANNULE]));
        $this->assertSame(StatutDemande::PUBLIEE, StatutDemande::deduire([E::PUBLIE, E::ANNULE]));
        $this->assertSame(StatutDemande::PARTIELLEMENT_PUBLIEE, StatutDemande::deduire([E::PUBLIE, E::EN_COURS]));
        $this->assertSame(StatutDemande::A_VALIDER, StatutDemande::deduire([E::VALIDE_BIOLOGIQUE, E::VALIDE_TECHNIQUE]));
        $this->assertSame(StatutDemande::EN_PRELEVEMENT, StatutDemande::deduire([E::PRELEVE, E::RECU, E::ANNULE]));
    }
}
