<?php

namespace Tests\Feature\Labo;

use App\Enums\Labo\StatutExamen;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Services\Labo\PrelevementService;
use App\Services\Labo\ResultatService;
use App\Services\Labo\ValidationService;

class VerrouValidationTest extends LaboTestCase
{
    public function test_un_resultat_valide_par_le_biologiste_est_fige_jusqu_a_rectification(): void
    {
        $etab = $this->creerEtablissement('clinique-a');
        $bio = $this->creerBiologiste($etab);
        $demande = $this->creerDemande($bio, ['GLY']);

        foreach ($demande->echantillons as $e) {
            app(PrelevementService::class)->marquerPreleveEtRecu($e, $bio);
        }

        $ligne = $demande->examens()->first();
        $parametre = $ligne->examen->parametres->first();
        app(ResultatService::class)->enregistrer($ligne, [$parametre->id => '0,95'], $bio);
        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $bio);

        $ligne->refresh();
        $this->assertSame(StatutExamen::VALIDE_BIOLOGIQUE, $ligne->statut);

        // Verrou service
        try {
            app(ResultatService::class)->enregistrer($ligne, [$parametre->id => '1,20'], $bio);
            $this->fail('La saisie aurait dû être refusée');
        } catch (OperationLaboImpossible) {
        }

        // Verrou modèle : même un accès direct est bloqué
        $this->expectException(\LogicException::class);
        $ligne->resultats()->first()->update(['valeur_numerique' => 1.2]);
    }

    public function test_rectification_trace_et_repasse_en_saisie(): void
    {
        $etab = $this->creerEtablissement('clinique-a');
        $bio = $this->creerBiologiste($etab);
        $demande = $this->creerDemande($bio, ['GLY']);
        foreach ($demande->echantillons as $e) {
            app(PrelevementService::class)->marquerPreleveEtRecu($e, $bio);
        }
        $ligne = $demande->examens()->first();
        $p = $ligne->examen->parametres->first();
        app(ResultatService::class)->enregistrer($ligne, [$p->id => '0,95'], $bio);
        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $bio);

        app(ValidationService::class)->rouvrirPourRectification($ligne->fresh(), 'Erreur de saisie : 1,95', $bio);

        $ligne->refresh();
        $this->assertTrue($ligne->statut->permetSaisie());
        $this->assertSame(1, $ligne->nombre_rectifications);
        app(ResultatService::class)->enregistrer($ligne, [$p->id => '1,95'], $bio);
        $this->assertEquals(1.95, $ligne->resultats()->first()->valeur_numerique);
    }
}
