<?php

namespace Tests\Unit\Labo;

use App\Enums\Labo\StatutExamen as E;
use PHPUnit\Framework\TestCase;

class StatutExamenTest extends TestCase
{
    public function test_saisie_possible_jusqu_a_la_validation_technique_incluse(): void
    {
        foreach ([E::RECU, E::EN_COURS, E::VALIDE_TECHNIQUE] as $statut) {
            $this->assertTrue($statut->permetSaisie(), $statut->value);
        }
        foreach ([E::EN_ATTENTE_PRELEVEMENT, E::PRELEVE, E::VALIDE_BIOLOGIQUE, E::PUBLIE, E::ANNULE] as $statut) {
            $this->assertFalse($statut->permetSaisie(), $statut->value);
        }
    }

    public function test_verrou_apres_validation_biologique(): void
    {
        $this->assertTrue(E::VALIDE_BIOLOGIQUE->estVerrouille());
        $this->assertTrue(E::PUBLIE->estVerrouille());
        $this->assertFalse(E::VALIDE_TECHNIQUE->estVerrouille());
    }
}
