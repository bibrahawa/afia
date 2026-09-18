<?php

namespace Tests\Feature\Labo;

use App\Exceptions\Labo\OperationLaboImpossible;
use App\Models\Etablissement;
use App\Models\Labo\LaboCreancePartenaire;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Labo\LaboRelevePartenaire;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use App\Services\Labo\FacturationPartenaireService;
use App\Services\Labo\LaboReseauService;
use App\Services\Labo\NotificationReseauService;
use App\Services\Labo\PartenariatService;
use App\Services\Parcours\DossierPatientService;
use App\Services\SmsService;
use Carbon\Carbon;
use Database\Seeders\Labo\LaboReseauPermissionsSeeder;
use Mockery;

/** Lot 4e — corrections issues de l'audit : confidentialité, annulation de règlement, dossier, marge, prix à zéro. */
class ReseauAuditTest extends LaboTestCase
{
    private Etablissement $labo;
    private Etablissement $clinique;
    private User $biologiste;
    private User $medecin;
    private LaboPartenariat $partenariat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LaboReseauPermissionsSeeder::class);
        $this->travelTo(Carbon::parse('2026-10-08 09:00:00'));

        $this->labo = $this->creerEtablissement('labo-central');
        $this->clinique = $this->creerEtablissement('clinique-nord');
        $this->biologiste = $this->creerBiologiste($this->labo);
        $this->medecin = $this->creerUtilisateur($this->clinique);

        $this->actingAs($this->biologiste);
        $this->partenariat = app(PartenariatService::class)->creer($this->clinique, [
            'mode_facturation_defaut' => 'partenaire',
            'clinique_facture_patient' => true,
            'remise_pourcentage' => 10,
            'delai_paiement_jours' => 30,
            'contact_telephone' => '620000003',
        ], $this->biologiste);

        LaboExamen::withoutGlobalScopes()
            ->where('etablissement_id', $this->labo->id)
            ->whereIn('code', ['GLY', 'NFS'])
            ->each(fn (LaboExamen $examen) => $examen->forceFill(['prix' => $examen->code === 'GLY' ? 20000 : 30000])->save());

        // La clinique doit accepter la proposition avant tout envoi (lot 4f).
        $this->actingAs($this->medecin);
        $this->partenariat = app(LaboReseauService::class)->accepterProposition($this->partenariat->id, $this->medecin);

    }

    public function test_le_sms_de_resultats_ne_contient_pas_le_nom_du_patient(): void
    {
        $patient = $this->patient('Aïssatou');
        $demande = $this->envoyer($patient, ['GLY']);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('sendSms')
            ->once()
            ->withArgs(function ($telephone, $message) use ($patient, $demande) {
                return $telephone === '620000003'
                    && str_contains($message, $demande->numero)
                    && ! str_contains($message, $patient->first_name)
                    && ! str_contains($message, $patient->last_name);
            })
            ->andReturn(['success' => true]);
        $this->app->instance(SmsService::class, $sms);

        $this->actingAs($this->biologiste);
        $demandeLabo = LaboDemande::findOrFail($demande->id);
        $demandeLabo->update(['premiere_publication_le' => now()]);

        app(NotificationReseauService::class)->notifierPublication($demandeLabo->fresh('partenariat'));
    }

    public function test_annulation_d_un_reglement_rouvre_les_creances(): void
    {
        $this->envoyer($this->patient('Aïssatou'), ['GLY']);

        $this->actingAs($this->biologiste);
        $facturation = app(FacturationPartenaireService::class);
        $releve = $facturation->preparerReleve($this->partenariat, today()->startOfMonth(), today()->endOfMonth(), $this->biologiste);
        $facturation->envoyerReleve($releve);

        $montant = (float) $releve->fresh()->montant_total;
        $reglement = $facturation->enregistrerReglement(
            $this->partenariat,
            ['montant' => $montant, 'mode' => 'virement', 'recu_le' => today()->toDateString()],
            [],
            $this->biologiste
        );

        $this->assertSame(LaboRelevePartenaire::SOLDE, $releve->fresh()->statut);

        $facturation->annulerReglement($reglement, 'Virement rejeté par la banque', $this->biologiste);

        $this->assertNotNull($reglement->fresh()->annule_le);
        $this->assertSame(LaboRelevePartenaire::ENVOYE, $releve->fresh()->statut);
        $this->assertSame($montant, (float) $facturation->resume($this->partenariat)['reste_du']);

        $creance = LaboCreancePartenaire::where('partenariat_id', $this->partenariat->id)->firstOrFail();
        $this->assertSame(0.0, (float) $creance->montant_regle);
        $this->assertSame(LaboCreancePartenaire::FACTUREE, $creance->statut);
        $this->assertSame(0, $reglement->imputations()->count());
    }

    public function test_les_analyses_envoyees_apparaissent_dans_le_dossier(): void
    {
        $patient = $this->patient('Aïssatou');
        $demande = $this->envoyer($patient, ['GLY']);

        $this->actingAs($this->medecin);
        $frise = app(DossierPatientService::class)->frise($patient, ['types' => ['laboratoire']]);

        $this->assertSame(1, $frise['total']);
        $evenement = $frise['evenements']->first();
        $this->assertStringContainsString($demande->numero, $evenement['titre']);
        $this->assertSame($this->labo->nom, $evenement['details']['Laboratoire']);
    }

    public function test_marge_et_alerte_de_facturation(): void
    {
        $this->envoyer($this->patient('Aïssatou'), ['GLY']);

        $this->actingAs($this->biologiste);
        $facturation = app(FacturationPartenaireService::class);
        $releve = $facturation->preparerReleve($this->partenariat, today()->startOfMonth(), today()->endOfMonth(), $this->biologiste);
        $facturation->envoyerReleve($releve);

        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $rapprochement = $reseau->margeSurReleve($reseau->releveRecu($releve->id));

        // La clinique facture son patient : le rapprochement doit le voir.
        $this->assertFalse($rapprochement['facturation_absente']);
        $this->assertSame(18000.0, $rapprochement['du_au_laboratoire']);
        $this->assertSame($rapprochement['facture_au_patient'] - 18000.0, (float) $rapprochement['marge']);
    }

    public function test_envoi_refuse_si_le_laboratoire_n_a_pas_tarife(): void
    {
        LaboExamen::withoutGlobalScopes()
            ->where('etablissement_id', $this->labo->id)
            ->where('code', 'GLY')
            ->update(['prix' => 0]);

        $this->expectException(OperationLaboImpossible::class);
        $this->envoyer($this->patient('Aïssatou'), ['GLY']);
    }

    public function test_correspondance_modifiable_par_la_clinique(): void
    {
        $demande = $this->envoyer($this->patient('Aïssatou'), ['GLY']);

        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $partenariat = $reseau->partenariat($this->partenariat->id);

        $autreActe = Test::create(['name' => 'Glycémie (tarif clinique)', 'report_type' => 'numerique', 'amount' => 35000]);
        $examenId = (int) LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->where('code', 'GLY')->value('id');

        $reseau->changerCorrespondance($partenariat, $examenId, $autreActe->id);

        $ligne = $reseau->correspondances($partenariat)->firstWhere('lien.examen_id', $examenId);

        $this->assertSame($autreActe->id, $ligne['acte']->id);
        $this->assertFalse($ligne['lien']->cree_automatiquement);
        $this->assertSame(18000.0, (float) $ligne['prix_negocie']);
    }

    private function envoyer(Patient $patient, array $codes): LaboDemande
    {
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $examens = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->whereIn('code', $codes)->pluck('id')->all();

        return $reseau->envoyer($reseau->partenariat($this->partenariat->id), $patient, ['examens' => $examens], $this->medecin);
    }

    private function patient(string $prenom): Patient
    {
        $patient = Patient::create(['first_name' => $prenom, 'last_name' => 'Diallo', 'gender' => 'Femme', 'birth_date' => '1994-04-04']);
        $this->clinique->patients()->syncWithoutDetaching([$patient->id]);

        return $patient;
    }
}
