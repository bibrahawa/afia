<?php

namespace Tests\Unit\Labo;

use App\Enums\Labo\FlagResultat;
use App\Support\Labo\InterpretationResultat as I;
use PHPUnit\Framework\TestCase;

class InterpretationResultatTest extends TestCase
{
    public function test_flags_numeriques(): void
    {
        $this->assertSame(FlagResultat::NORMAL, I::numerique(5, 4, 10));
        $this->assertSame(FlagResultat::BAS, I::numerique(3, 4, 10));
        $this->assertSame(FlagResultat::HAUT, I::numerique(11, 4, 10));
        $this->assertSame(FlagResultat::CRITIQUE_BAS, I::numerique(1, 4, 10, 2, 30));
        $this->assertSame(FlagResultat::CRITIQUE_HAUT, I::numerique(31, 4, 10, 2, 30));
        $this->assertSame(FlagResultat::NORMAL, I::numerique(10, null, 10), 'borne incluse');
    }

    public function test_sans_norme_pas_de_flag_sauf_seuil_critique(): void
    {
        $this->assertNull(I::numerique(5, null, null));
        $this->assertNull(I::numerique(null, 1, 2));
        $this->assertSame(FlagResultat::CRITIQUE_BAS, I::numerique(1, null, null, 2, null));
    }

    public function test_qualitatif_insensible_aux_accents(): void
    {
        $this->assertSame(FlagResultat::NORMAL, I::qualitatif('négatif', 'Negatif'));
        $this->assertSame(FlagResultat::ANORMAL, I::qualitatif('Positif', 'Négatif'));
        $this->assertNull(I::qualitatif('A+', null));
    }
}
