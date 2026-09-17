<?php

namespace Tests\Unit\Labo;

use App\Support\Labo\SelecteurValeurReference as S;
use PHPUnit\Framework\TestCase;

class SelecteurValeurReferenceTest extends TestCase
{
    private array $plages = [
        ['id' => 1, 'sexe' => null, 'age_min_jours' => null, 'age_max_jours' => null, 'grossesse' => null],
        ['id' => 2, 'sexe' => 'F', 'age_min_jours' => 5844, 'age_max_jours' => null, 'grossesse' => null],
        ['id' => 3, 'sexe' => 'F', 'age_min_jours' => 5844, 'age_max_jours' => null, 'grossesse' => true],
        ['id' => 4, 'sexe' => null, 'age_min_jours' => 0, 'age_max_jours' => 30, 'grossesse' => null],
    ];

    public function test_plage_la_plus_specifique(): void
    {
        $this->assertSame(1, S::choisir($this->plages, 'M', 10000)['id']);
        $this->assertSame(2, S::choisir($this->plages, 'F', 10000)['id']);
        $this->assertSame(3, S::choisir($this->plages, 'F', 10000, true)['id']);
        $this->assertSame(4, S::choisir($this->plages, 'M', 10)['id']);
    }

    public function test_age_inconnu_ne_retient_que_les_plages_sans_age(): void
    {
        $this->assertSame(1, S::choisir($this->plages, 'F', null)['id']);
    }

    public function test_aucune_plage_applicable(): void
    {
        $this->assertNull(S::choisir([$this->plages[2]], 'M', 10000));
    }

    public function test_accepte_des_objets(): void
    {
        $o = (object) ['id' => 9, 'sexe' => 'M', 'age_min_jours' => null, 'age_max_jours' => null, 'grossesse' => null];
        $this->assertSame(9, S::choisir([$o], 'M', null)->id);
    }
}
