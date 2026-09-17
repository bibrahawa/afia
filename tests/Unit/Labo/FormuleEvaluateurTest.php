<?php

namespace Tests\Unit\Labo;

use App\Support\Labo\FormuleEvaluateur;
use PHPUnit\Framework\TestCase;

class FormuleEvaluateurTest extends TestCase
{
    private FormuleEvaluateur $f;

    protected function setUp(): void
    {
        $this->f = new FormuleEvaluateur();
    }

    public function test_ldl_friedewald(): void
    {
        $this->assertEqualsWithDelta(1.4, $this->f->evaluer('CT - HDL - TG/5', ['CT' => 2.2, 'HDL' => 0.5, 'TG' => 1.5]), 1e-9);
    }

    public function test_variables_entre_accolades_insensibles_a_la_casse(): void
    {
        $this->assertEqualsWithDelta(4.0, $this->f->evaluer('{ct}/{hdl}', ['CT' => 2, 'HDL' => 0.5]), 1e-9);
    }

    public function test_variable_manquante_donne_null_et_jamais_zero(): void
    {
        $this->assertNull($this->f->evaluer('CT - HDL', ['CT' => 2]));
    }

    public function test_division_par_zero_donne_null(): void
    {
        $this->assertNull($this->f->evaluer('A/B', ['A' => 1, 'B' => 0]));
    }

    public function test_priorites_et_associativite(): void
    {
        $this->assertEquals(-4.0, $this->f->evaluer('-2^2', []));
        $this->assertEquals(512.0, $this->f->evaluer('2^3^2', []));
        $this->assertEquals(9.0, $this->f->evaluer('(1+2)*3', []));
        $this->assertEquals(-6.0, $this->f->evaluer('2*-3', []));
    }

    public function test_fonctions(): void
    {
        $this->assertEquals(6.0, $this->f->evaluer('max(A; B) + min(1,2)', ['A' => 3, 'B' => 5]));
        $this->assertEquals(3.33, $this->f->evaluer('round(10/3; 2)', []));
    }

    public function test_liste_des_variables(): void
    {
        $this->assertSame(['CT', 'HDL', 'TG'], $this->f->variables('CT - HDL - TG/5 + max(CT;1)'));
    }

    public function test_code_arbitraire_et_syntaxe_invalide_refuses(): void
    {
        foreach (['1+', '(1+2', '1+2)', 'system("x")', 'A $ B', 'max(1)'] as $mauvaise) {
            try {
                $this->f->evaluer($mauvaise, ['A' => 1, 'B' => 1]);
                $this->fail("Aurait dû être refusée : {$mauvaise}");
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }
}
