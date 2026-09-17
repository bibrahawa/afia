<?php

namespace Tests\Feature\Labo\Http;

use App\Models\Etablissement;
use App\Support\PageAccueil;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Labo\LaboTestCase;

/** Chaque rôle n'atteint que ses écrans ; hors module ou hors établissement : refus. */
class PermissionsHttpTest extends LaboTestCase
{
    private Etablissement $etab;

    protected function setUp(): void
    {
        parent::setUp();
        $this->etab = $this->creerEtablissement('clinique-a');
    }

    /** @return array<string, array{0: string, 1: array<string, int>}> */
    public static function matrice(): array
    {
        return [
            'Accueil laboratoire' => ['Accueil laboratoire', [
                'labo.demandes.index' => 200, 'labo.demandes.create' => 200, 'labo.catalogue.index' => 200,
                'labo.prelevements.index' => 403, 'labo.reception.index' => 403, 'labo.paillasse.index' => 403,
                'labo.validation.index' => 403, 'labo.tableau-bord' => 403, 'labo.declarations.index' => 403,
            ]],
            'Préleveur' => ['Préleveur', [
                'labo.prelevements.index' => 200, 'labo.demandes.index' => 200,
                'labo.demandes.create' => 403, 'labo.reception.index' => 403, 'labo.paillasse.index' => 403, 'labo.validation.index' => 403,
            ]],
            'Technicien' => ['Technicien de laboratoire', [
                'labo.tableau-bord' => 200, 'labo.reception.index' => 200, 'labo.paillasse.index' => 200, 'labo.validation.index' => 200,
                'labo.declarations.index' => 403, 'labo.catalogue.examens.create' => 403,
            ]],
            'Biologiste' => ['Biologiste', [
                'labo.tableau-bord' => 200, 'labo.validation.index' => 200, 'labo.declarations.index' => 200, 'labo.catalogue.examens.create' => 200,
            ]],
        ];
    }

    #[DataProvider('matrice')]
    public function test_acces_par_role(string $role, array $attendus): void
    {
        $this->actingAs($this->creerUtilisateur($this->etab, $role));

        foreach ($attendus as $route => $code) {
            $this->get(route($route))->assertStatus($code);
        }
    }

    public function test_actions_sensibles_refusees_au_technicien(): void
    {
        $demande = $this->creerDemande($this->creerBiologiste($this->etab), ['GLY']);
        $ligne = $demande->examens()->firstOrFail();

        $this->actingAs($this->creerUtilisateur($this->etab, 'Technicien de laboratoire'));

        $this->post(route('labo.validation.biologique', $ligne))->assertForbidden();
        $this->post(route('labo.validation.rouvrir', $ligne), ['motif' => 'Erreur de saisie'])->assertForbidden();
        $this->post(route('labo.demandes.publier', $demande))->assertForbidden();
        $this->post(route('labo.demandes.annuler', $demande), ['motif' => 'test'])->assertForbidden();
        $this->post(route('labo.catalogue.importer'))->assertForbidden();
    }

    public function test_sans_role_labo_tout_est_refuse(): void
    {
        $this->actingAs($this->creerUtilisateur($this->etab));

        $this->get(route('labo.demandes.index'))->assertForbidden();
        $this->get(route('labo.tableau-bord'))->assertForbidden();
    }

    public function test_module_desactive_refuse_meme_au_biologiste(): void
    {
        $bio = $this->creerBiologiste($this->etab);
        $this->etab->modules()->updateExistingPivot(\App\Models\Module::where('code', 'laboratoire')->value('id'), ['est_actif' => false]);

        $this->actingAs($bio)->get(route('labo.tableau-bord'))->assertForbidden();
    }

    public function test_invite_redirige_vers_la_connexion(): void
    {
        $this->get(route('labo.demandes.index'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_une_autre_clinique_obtient_404_sur_chaque_ecran_de_la_demande(): void
    {
        $demande = $this->creerDemande($this->creerBiologiste($this->etab), ['GLY']);
        $ligne = $demande->examens()->firstOrFail();
        $echantillon = $demande->echantillons()->firstOrFail();

        $this->actingAs($this->creerBiologiste($this->creerEtablissement('clinique-b')));

        $this->get(route('labo.demandes.show', $demande))->assertNotFound();
        $this->get(route('labo.demandes.etiquettes', $demande))->assertNotFound();
        $this->get(route('labo.paillasse.saisie', $ligne))->assertNotFound();
        $this->post(route('labo.echantillons.preleve', $echantillon))->assertNotFound();
        $this->post(route('labo.validation.biologique', $ligne))->assertNotFound();
        $this->post(route('labo.demandes.annuler', $demande), ['motif' => 'intrusion'])->assertNotFound();
    }

    public function test_page_d_accueil_selon_le_role(): void
    {
        $this->assertSame(route('labo.prelevements.index'), PageAccueil::url($this->creerUtilisateur($this->etab, 'Préleveur')));
        $this->assertSame(route('labo.demandes.index'), PageAccueil::url($this->creerUtilisateur($this->etab, 'Accueil laboratoire')));
        $this->assertSame(route('labo.tableau-bord'), PageAccueil::url($this->creerBiologiste($this->etab)));
    }
}
