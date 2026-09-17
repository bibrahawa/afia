<?php

namespace Tests\Feature\Labo\Http;

use App\Models\Labo\LaboDemande;
use App\Services\Labo\CompteRenduService;
use App\Services\Labo\PrelevementService;
use App\Services\Labo\ResultatService;
use App\Services\Labo\ValidationService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\URL;
use Tests\Feature\Labo\LaboTestCase;

/** Page ouverte par le patient depuis le lien SMS : URL signée, sans session. */
class ResultatsPublicsHttpTest extends LaboTestCase
{
    private LaboDemande $demande;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();

        $bio = $this->creerBiologiste($this->creerEtablissement('clinique-a'));
        $this->demande = $this->creerDemande($bio, ['GLY']);
        foreach ($this->demande->echantillons as $e) {
            app(PrelevementService::class)->marquerPreleveEtRecu($e, $bio);
        }
        $ligne = $this->demande->examens()->first();
        app(ResultatService::class)->enregistrer($ligne, [$ligne->examen->parametres->first()->id => '0,95'], $bio);
        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $bio);
        app(CompteRenduService::class)->publier($this->demande->fresh(), $bio, notifierPatient: false);

        auth()->logout(); // le patient n'a pas de session
    }

    private function lien(): string
    {
        return URL::temporarySignedRoute('labo.public.resultats', now()->addDays(30), ['demandeId' => $this->demande->id]);
    }

    public function test_lien_signe_affiche_les_resultats_sans_connexion(): void
    {
        $this->get($this->lien())->assertOk()
            ->assertSee('0,95')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertDatabaseHas('activity_logs', ['action' => 'labo.resultats_consultes_patient']);
    }

    public function test_lien_modifie_non_signe_ou_expire_refuse(): void
    {
        $this->get($this->lien() . 'x')->assertForbidden();
        $this->get(route('labo.public.resultats', ['demandeId' => $this->demande->id]))->assertForbidden();

        $expire = URL::temporarySignedRoute('labo.public.resultats', now()->subMinute(), ['demandeId' => $this->demande->id]);
        $this->get($expire)->assertForbidden();
    }

    public function test_telechargement_pdf_ne_casse_pas_la_signature(): void
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('barryvdh/laravel-dompdf non installé');
        }

        $this->get($this->lien() . '&pdf=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_resultats_retenus_si_impaye(): void
    {
        $this->demande->forceFill(['mode_facturation' => 'labo', 'resultats_retenus_si_impaye' => true])->saveQuietly();

        $this->get($this->lien())->assertForbidden();
    }
}
