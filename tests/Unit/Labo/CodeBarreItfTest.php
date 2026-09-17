<?php

namespace Tests\Unit\Labo;

use App\Support\Labo\CodeBarreItf;
use PHPUnit\Framework\TestCase;

class CodeBarreItfTest extends TestCase
{
    public function test_structure_start_stop_et_longueur(): void
    {
        $l = CodeBarreItf::largeurs('2600012301');
        $this->assertSame([1, 1, 1, 1], array_slice($l, 0, 4), 'start');
        $this->assertSame([3, 1, 1], array_slice($l, -3), 'stop');
        $this->assertCount(4 + 5 * 10 + 3, $l); // 5 paires × 10 éléments
    }

    public function test_chaque_chiffre_a_deux_elements_larges(): void
    {
        $l = CodeBarreItf::largeurs('0123456789');
        $corps = array_slice($l, 4, -3);
        foreach (array_chunk($corps, 10) as $paire) {
            $barres = [$paire[0], $paire[2], $paire[4], $paire[6], $paire[8]];
            $espaces = [$paire[1], $paire[3], $paire[5], $paire[7], $paire[9]];
            $this->assertSame(2, count(array_filter($barres, fn ($x) => $x === 3)));
            $this->assertSame(2, count(array_filter($espaces, fn ($x) => $x === 3)));
        }
    }

    public function test_longueur_impaire_completee_et_non_numerique_refuse(): void
    {
        $this->assertSame(CodeBarreItf::largeurs('0123'), CodeBarreItf::largeurs('123'));
        $this->expectException(\InvalidArgumentException::class);
        CodeBarreItf::largeurs('12A4');
    }

    public function test_svg(): void
    {
        $svg = CodeBarreItf::svg('2600012301');
        $this->assertStringStartsWith('<svg', $svg);
        $this->assertSame(2 + 5 * 5 + 2, substr_count($svg, '<rect')); // barres : start + 5 paires + stop
    }
}
