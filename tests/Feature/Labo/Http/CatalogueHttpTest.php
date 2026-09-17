<?php

namespace Tests\Feature\Labo\Http;

use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboSection;
use Tests\Feature\Labo\LaboTestCase;

class CatalogueHttpTest extends LaboTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->creerBiologiste($this->creerEtablissement('clinique-a')));
    }

    private function formulaire(array $surcharges = []): array
    {
        return array_replace_recursive([
            'section_id' => LaboSection::where('code', 'BIOCHIMIE')->value('id'),
            'code' => 'RAPPORT', 'nom' => 'Rapport test', 'type_examen' => 'standard',
            'type_echantillon' => 'sang', 'tube' => 'jaune', 'delai_rendu_heures' => 24, 'prix' => 45000, 'actif' => '1',
            'parametres' => [
                ['code' => 'A', 'libelle' => 'Valeur A', 'type_resultat' => 'numerique', 'unite' => 'g/L', 'decimales' => 2, 'imprimable' => '1',
                 'normes' => [['min' => '1', 'max' => '2', 'critique_max' => '10']]],
                ['code' => 'B', 'libelle' => 'Valeur B', 'type_resultat' => 'numerique', 'decimales' => 2, 'imprimable' => '1'],
                ['code' => 'R', 'libelle' => 'A sur B', 'type_resultat' => 'calcule', 'formule' => 'A / B', 'decimales' => 2, 'imprimable' => '1'],
            ],
        ], $surcharges);
    }

    public function test_ecrans_du_catalogue(): void
    {
        $this->get(route('labo.catalogue.index'))->assertOk()->assertSee('NFS');
        $this->get(route('labo.catalogue.examens.create'))->assertOk();
        $this->get(route('labo.catalogue.examens.edit', LaboExamen::where('code', 'NFS')->first()))->assertOk()->assertSee('Hémoglobine');
    }

    public function test_creation_d_un_examen_avec_parametres_normes_et_formule(): void
    {
        $this->post(route('labo.catalogue.examens.store'), $this->formulaire())->assertSessionHasNoErrors()->assertRedirect();

        $examen = LaboExamen::where('code', 'RAPPORT')->firstOrFail();
        $this->assertEquals(45000, $examen->prix);
        $this->assertSame(3, $examen->parametres()->count());
        $this->assertSame(1, $examen->parametres()->where('code', 'A')->first()->valeursReference()->count());
    }

    public function test_formule_invalide_ou_variable_inconnue_refusee(): void
    {
        $this->post(route('labo.catalogue.examens.store'), $this->formulaire(['parametres' => [2 => ['formule' => 'A / XYZ']]]))
            ->assertSessionHasErrors('parametres.2.formule');

        $this->post(route('labo.catalogue.examens.store'), $this->formulaire(['parametres' => [2 => ['formule' => 'A / (B']]]))
            ->assertSessionHasErrors('parametres.2.formule');

        $this->assertNull(LaboExamen::where('code', 'RAPPORT')->first());
    }

    public function test_code_unique_dans_l_etablissement(): void
    {
        $this->post(route('labo.catalogue.examens.store'), $this->formulaire(['code' => 'NFS']))->assertSessionHasErrors('code');
    }

    public function test_modification_du_prix_et_desactivation(): void
    {
        $examen = LaboExamen::where('code', 'GLY')->with('parametres.valeursReference')->firstOrFail();
        $parametres = $examen->parametres->map(fn ($p) => [
            'id' => $p->id, 'code' => $p->code, 'libelle' => $p->libelle, 'type_resultat' => $p->type_resultat->value,
            'unite' => $p->unite, 'decimales' => $p->decimales, 'imprimable' => '1', 'obligatoire' => '1',
        ])->all();

        $this->put(route('labo.catalogue.examens.update', $examen), [
            'section_id' => $examen->section_id, 'code' => 'GLY', 'nom' => $examen->nom, 'type_examen' => 'standard',
            'type_echantillon' => 'sang', 'tube' => 'gris', 'delai_rendu_heures' => 4, 'prix' => 30000, 'actif' => '1',
            'parametres' => $parametres,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(30000, $examen->fresh()->prix);

        $this->post(route('labo.catalogue.examens.basculer', $examen))->assertSessionHas('success');
        $this->assertFalse($examen->fresh()->actif);
    }
}
