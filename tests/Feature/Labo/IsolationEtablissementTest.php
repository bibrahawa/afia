<?php

namespace Tests\Feature\Labo;

use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;

class IsolationEtablissementTest extends LaboTestCase
{
    public function test_une_clinique_ne_voit_jamais_les_demandes_d_une_autre(): void
    {
        $a = $this->creerEtablissement('clinique-a');
        $b = $this->creerEtablissement('clinique-b');
        $bioA = $this->creerBiologiste($a);
        $bioB = $this->creerBiologiste($b);

        $demandeA = $this->creerDemande($bioA);

        $this->actingAs($bioB);
        $this->assertSame(0, LaboDemande::count());
        $this->get(route('labo.demandes.show', $demandeA))->assertNotFound();
        $this->get(route('labo.comptes-rendus.pdf', 999999))->assertNotFound();
    }

    public function test_numerotation_independante_par_etablissement(): void
    {
        $a = $this->creerEtablissement('clinique-a');
        $b = $this->creerEtablissement('clinique-b');

        $d1 = $this->creerDemande($this->creerBiologiste($a));
        $d2 = $this->creerDemande($this->creerBiologiste($b));

        $this->assertStringEndsWith('-000001', $d1->numero);
        $this->assertStringEndsWith('-000001', $d2->numero);
    }

    public function test_le_catalogue_modele_est_invisible_depuis_un_etablissement(): void
    {
        $a = $this->creerEtablissement('clinique-a');
        $this->actingAs($this->creerBiologiste($a));

        $this->assertTrue(LaboExamen::exists());
        $this->assertSame(0, LaboExamen::whereNull('etablissement_id')->count());
    }

    public function test_un_code_barres_d_un_autre_etablissement_est_inconnu(): void
    {
        $a = $this->creerEtablissement('clinique-a');
        $b = $this->creerEtablissement('clinique-b');
        $code = $this->creerDemande($this->creerBiologiste($a))->echantillons->first()->code_barres;

        $this->actingAs($this->creerBiologiste($b))
            ->post(route('labo.reception.scanner'), ['code_barres' => $code])
            ->assertSessionHas('error');
    }
}
