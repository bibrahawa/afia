<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Correctif Patient::getAgeAttribute (lisait une colonne inexistante). Sans base de données. */
class PatientAgeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function patient(array $attributs): Patient
    {
        return (new Patient())->forceFill($attributs);
    }

    public function test_age_calcule_depuis_la_date_de_naissance(): void
    {
        Carbon::setTestNow('2026-09-17');

        $this->assertSame(32, $this->patient(['birth_date' => '1994-03-10', 'age' => 99])->age, 'la date prime sur la colonne age');
        $this->assertSame('32 ans', $this->patient(['birth_date' => '1994-03-10'])->age_texte);
    }

    public function test_repli_sur_la_colonne_age(): void
    {
        $this->assertSame(45, $this->patient(['birth_date' => null, 'age' => 45])->age);
        $this->assertSame(45, $this->patient(['birth_date' => 'né vers 1980', 'age' => 45])->age, 'texte libre illisible');
        $this->assertNull($this->patient(['birth_date' => null, 'age' => null])->age);
        $this->assertSame('Âge non renseigné', $this->patient([])->age_texte);
    }

    public function test_nourrissons_en_mois_et_en_jours(): void
    {
        Carbon::setTestNow('2026-09-17');

        $this->assertSame('8 mois', $this->patient(['birth_date' => '2026-01-10'])->age_texte);
        $this->assertSame('12 jours', $this->patient(['birth_date' => '2026-09-05'])->age_texte);
        $this->assertSame(0, $this->patient(['birth_date' => '2026-09-05'])->age);
    }

    public function test_date_future_ignoree(): void
    {
        Carbon::setTestNow('2026-09-17');

        $this->assertSame(30, $this->patient(['birth_date' => '2030-01-01', 'age' => 30])->age);
    }
}
