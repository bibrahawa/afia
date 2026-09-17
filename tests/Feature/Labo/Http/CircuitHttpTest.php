<?php

namespace Tests\Feature\Labo\Http;

use App\Enums\Labo\StatutEchantillon;
use App\Enums\Labo\StatutExamen;
use App\Models\Labo\LaboCompteRendu;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboRemise;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Tests\Feature\Labo\LaboTestCase;

/**
 * Le circuit complet, écran par écran, par de vraies requêtes HTTP :
 * routes, middlewares, validation, contrôleurs ET rendu des vues.
 */
class CircuitHttpTest extends LaboTestCase
{
    private User $bio;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        $this->bio = $this->creerBiologiste($this->creerEtablissement('clinique-a'));
    }

    public function test_circuit_complet_de_la_demande_a_la_remise(): void
    {
        $this->actingAs($this->bio);
        $patient = $this->creerPatient();
        $gly = LaboExamen::where('code', 'GLY')->firstOrFail();

        // Écrans d'accueil
        $this->get(route('labo.tableau-bord'))->assertOk();
        $this->get(route('labo.demandes.create'))->assertOk()->assertSee('Glycémie');

        // 1. Enregistrement
        $this->post(route('labo.demandes.store'), [
            'patient_id' => $patient->id, 'origine' => 'spontanee', 'mode_facturation' => 'gratuit',
            'examens' => [$gly->id], 'urgence' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $demande = LaboDemande::latest('id')->firstOrFail();
        $this->get(route('labo.demandes.index'))->assertOk()->assertSee($demande->numero);
        $this->get(route('labo.demandes.show', $demande))->assertOk()->assertSee($demande->numero);
        $this->get(route('labo.demandes.etiquettes', $demande))->assertOk()->assertSee('<svg', false);

        // 2. Prélèvement + réception
        $echantillon = $demande->echantillons()->firstOrFail();
        $this->get(route('labo.prelevements.index'))->assertOk()->assertSee($echantillon->code_barres);
        $this->post(route('labo.echantillons.preleve', $echantillon))->assertRedirect();
        $this->assertSame(StatutEchantillon::PRELEVE, $echantillon->fresh()->statut);

        $this->get(route('labo.reception.index'))->assertOk();
        $this->post(route('labo.reception.scanner'), ['code_barres' => $echantillon->code_barres])->assertSessionHas('success');
        $this->assertSame(StatutEchantillon::RECU, $echantillon->fresh()->statut);

        // 3. Saisie + validation technique
        $ligne = $demande->examens()->firstOrFail();
        $parametre = $gly->parametres()->firstOrFail();
        $this->get(route('labo.paillasse.index'))->assertOk()->assertSee('Glycémie');
        $this->get(route('labo.paillasse.saisie', $ligne))->assertOk();
        $this->post(route('labo.paillasse.enregistrer', $ligne), [
            'valeurs' => [$parametre->id => '1,05'], 'valider_technique' => '1',
        ])->assertRedirect(route('labo.paillasse.saisie', $ligne));
        $this->assertSame(StatutExamen::VALIDE_TECHNIQUE, $ligne->fresh()->statut);

        // Correctif : le bouton reste disponible après validation technique
        $this->get(route('labo.demandes.show', $demande))->assertOk()
            ->assertSee(route('labo.paillasse.saisie', $ligne), false)->assertSee('Corriger');

        // 4. Validation biologique
        $this->get(route('labo.validation.index'))->assertOk()->assertSee($demande->patient->last_name);
        $this->post(route('labo.validation.biologique', $ligne), ['commentaire' => 'RAS'])->assertSessionHas('success');
        $this->assertSame(StatutExamen::VALIDE_BIOLOGIQUE, $ligne->fresh()->statut);

        // 5. Publication
        $this->post(route('labo.demandes.publier', $demande), ['notifier_patient' => '0'])->assertSessionHas('success');
        $compteRendu = LaboCompteRendu::where('demande_id', $demande->id)->firstOrFail();
        $this->assertSame(1, $compteRendu->version);
        $this->assertSame(StatutExamen::PUBLIE, $ligne->fresh()->statut);

        // 6. Remise tracée
        $this->post(route('labo.demandes.remettre', $demande), ['beneficiaire' => 'patient', 'piece_justificative' => 'cni'])
            ->assertSessionHas('success')->assertSessionHas('labo_imprimer_cr', $compteRendu->id);
        $this->assertSame(1, LaboRemise::count());
        $this->get(route('labo.demandes.show', $demande))->assertOk()->assertSee('Remises en main propre');
    }

    public function test_pdf_du_compte_rendu(): void
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('barryvdh/laravel-dompdf non installé');
        }

        $demande = $this->demandeValidee();
        $this->post(route('labo.demandes.publier', $demande), ['notifier_patient' => '0']);
        $cr = LaboCompteRendu::where('demande_id', $demande->id)->firstOrFail();

        $this->get(route('labo.comptes-rendus.pdf', $cr))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_rejet_d_un_echantillon_cree_un_contenant_de_remplacement(): void
    {
        $demande = $this->creerDemande($this->bio, ['GLY']);
        $echantillon = $demande->echantillons()->firstOrFail();
        $this->post(route('labo.echantillons.preleve', $echantillon));

        $this->post(route('labo.echantillons.rejeter', $echantillon), ['motif' => 'autre'])
            ->assertSessionHasErrors('commentaire');

        $this->post(route('labo.echantillons.rejeter', $echantillon), ['motif' => 'hemolyse'])->assertSessionHas('success');
        $this->assertSame(StatutEchantillon::REJETE, $echantillon->fresh()->statut);
        $this->assertSame(2, $demande->echantillons()->count());
    }

    public function test_valeur_critique_bloque_la_validation_tant_que_l_appel_n_est_pas_trace(): void
    {
        $demande = $this->creerDemande($this->bio, ['NFS']);
        foreach ($demande->echantillons as $e) {
            $this->post(route('labo.echantillons.preleve', $e), ['et_recu' => '1']);
        }
        $ligne = $demande->examens()->firstOrFail();
        $hb = $ligne->examen->parametres()->where('code', 'HB')->firstOrFail();
        // Valeurs normales pour une femme adulte, sauf l'hémoglobine critique (seuil 7 g/dL).
        $normales = ['GB' => '6', 'GR' => '4,5', 'HB' => '5,0', 'HT' => '40', 'VGM' => '90', 'TCMH' => '30', 'CCMH' => '34',
            'PLQ' => '250', 'PNN' => '60', 'LYM' => '30', 'MONO' => '6', 'PNE' => '3', 'PNB' => '1'];
        $valeurs = $ligne->examen->parametres->mapWithKeys(fn ($p) => [$p->id => $normales[$p->code] ?? '1'])->all();

        $this->post(route('labo.paillasse.enregistrer', $ligne), ['valeurs' => $valeurs, 'valider_technique' => '1'])
            ->assertSessionHas('error'); // bandeau « VALEUR(S) CRITIQUE(S) »

        $this->post(route('labo.validation.biologique', $ligne))->assertSessionHas('error');
        $this->assertSame(StatutExamen::VALIDE_TECHNIQUE, $ligne->fresh()->statut);

        $resultat = $ligne->resultats()->where('parametre_id', $hb->id)->firstOrFail();
        $this->post(route('labo.validation.alerte-critique', $resultat), ['personne_contactee' => 'Dr Barry', 'moyen' => 'telephone'])
            ->assertSessionHas('success');

        $this->post(route('labo.validation.biologique', $ligne))->assertSessionHas('success');
        $this->assertSame(StatutExamen::VALIDE_BIOLOGIQUE, $ligne->fresh()->statut);
    }

    public function test_demande_sans_examen_refusee_avec_message(): void
    {
        $this->actingAs($this->bio);

        $this->from(route('labo.demandes.create'))
            ->post(route('labo.demandes.store'), ['patient_id' => $this->creerPatient()->id, 'origine' => 'spontanee', 'mode_facturation' => 'gratuit', 'examens' => []])
            ->assertRedirect(route('labo.demandes.create'))
            ->assertSessionHas('error');

        $this->post(route('labo.demandes.store'), ['origine' => 'interne', 'mode_facturation' => 'gratuit'])
            ->assertSessionHasErrors(['patient_id', 'prescripteur_employee_id']);
    }

    public function test_annulation_d_une_demande(): void
    {
        $demande = $this->creerDemande($this->bio, ['GLY']);

        $this->post(route('labo.demandes.annuler', $demande), [])->assertSessionHasErrors('motif');
        $this->post(route('labo.demandes.annuler', $demande), ['motif' => 'Doublon'])->assertSessionHas('success');
        $this->assertTrue($demande->fresh()->estAnnulee());
    }

    private function demandeValidee(): LaboDemande
    {
        $demande = $this->creerDemande($this->bio, ['GLY']);
        foreach ($demande->echantillons as $e) {
            $this->post(route('labo.echantillons.preleve', $e), ['et_recu' => '1']);
        }
        $ligne = $demande->examens()->firstOrFail();
        $this->post(route('labo.paillasse.enregistrer', $ligne), ['valeurs' => [$ligne->examen->parametres->first()->id => '0,95']]);
        $this->post(route('labo.validation.complete', $ligne));

        return $demande->fresh();
    }
}
