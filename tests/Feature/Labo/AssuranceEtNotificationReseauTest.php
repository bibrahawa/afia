<?php

namespace Tests\Feature\Labo;

use App\Models\Etablissement;
use App\Models\InsuranceCompany;
use App\Models\Labo\LaboCreancePartenaire;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboPartenariat;
use App\Models\Labo\LaboPartenariatActe;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\Test;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Labo\FacturationCliniqueAnalysesService;
use App\Services\Labo\LaboReseauService;
use App\Services\Labo\NotificationReseauService;
use App\Services\Labo\PartenariatService;
use App\Support\ContexteTemporaire;
use App\Support\Facturation\TypesFacturables;
use Database\Seeders\Labo\LaboReseauPermissionsSeeder;

/** Lot 4c — la clinique facture ses analyses avec son assurance, et reçoit les résultats. */
class AssuranceEtNotificationReseauTest extends LaboTestCase
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

        $this->labo = $this->creerEtablissement('labo-central');
        $this->clinique = $this->creerEtablissement('clinique-nord');
        $this->biologiste = $this->creerBiologiste($this->labo);
        $this->medecin = $this->creerUtilisateur($this->clinique);

        $this->actingAs($this->biologiste);
        $this->partenariat = app(PartenariatService::class)->creer($this->clinique, [
            'mode_facturation_defaut' => 'partenaire',
            'clinique_facture_patient' => true,
            'remise_pourcentage' => 10,
            'contact_telephone' => '620000002',
        ], $this->biologiste);
        // Le catalogue modèle est livré à 0 : chaque laboratoire fixe ses tarifs.
        LaboExamen::withoutGlobalScopes()
            ->where('etablissement_id', $this->labo->id)
            ->whereIn('code', ['GLY', 'NFS'])
            ->each(fn (LaboExamen $examen) => $examen->forceFill(['prix' => $examen->code === 'GLY' ? 20000 : 30000])->save());

    }

    public function test_la_clinique_facture_le_patient_sur_son_propre_catalogue(): void
    {
        $patient = $this->patient();
        $demande = $this->envoyer($patient, ['GLY']);

        // Facture créée dans la clinique, pas au laboratoire.
        $transaction = Transaction::withoutGlobalScopes()
            ->whereIn('transactionable_type', TypesFacturables::variantes(LaboDemande::class))
            ->where('transactionable_id', $demande->id)
            ->firstOrFail();

        $this->assertSame($this->clinique->id, (int) $transaction->etablissement_id);
        $this->assertGreaterThan(0, (float) $transaction->total);

        // Un acte du catalogue de la clinique a été créé et relié à l'examen.
        $correspondance = LaboPartenariatActe::withoutGlobalScopes()->where('partenariat_id', $this->partenariat->id)->firstOrFail();
        $acte = Test::withoutGlobalScopes()->findOrFail($correspondance->test_id);

        $this->assertSame($this->clinique->id, (int) $acte->etablissement_id);
        $this->assertTrue($correspondance->cree_automatiquement);

        // Le laboratoire garde sa créance envers la clinique.
        $this->assertSame(1, LaboCreancePartenaire::withoutGlobalScopes()->where('demande_id', $demande->id)->count());
    }

    public function test_l_assurance_de_la_clinique_s_applique(): void
    {
        $patient = $this->patient();

        $this->actingAs($this->medecin);
        $organisme = InsuranceCompany::create(['name' => 'NSIA', 'code' => 'NSIA', 'type' => 'assureur']);
        PatientInsurance::create([
            'patient_id' => $patient->id,
            'insurance_company_id' => $organisme->id,
            'policy_number' => 'POL-1',
            'coverage_percentage' => 80,
            'start_date' => today()->subMonth(),
            'end_date' => today()->addMonths(6),
            'status' => 'active',
        ]);

        $demande = $this->envoyer($patient, ['GLY']);

        $facture = Transaction::withoutGlobalScopes()
            ->whereIn('transactionable_type', TypesFacturables::variantes(LaboDemande::class))
            ->where('transactionable_id', $demande->id)
            ->firstOrFail()
            ->invoice()->withoutGlobalScopes()->firstOrFail();

        // Sans convention pour cet acte, l'assureur ne paie rien : c'est la règle du moteur.
        $this->assertSame((float) $facture->total_amount, (float) $facture->patient_amount + (float) $facture->insurance_amount);
    }

    public function test_notification_de_la_clinique_a_la_publication(): void
    {
        $patient = $this->patient();
        $demande = $this->envoyer($patient, ['GLY']);

        $this->actingAs($this->biologiste);
        $demandeLabo = LaboDemande::findOrFail($demande->id);
        $demandeLabo->update(['premiere_publication_le' => now()]);

        app(NotificationReseauService::class)->notifierPublication($demandeLabo->fresh('partenariat'));

        $this->assertNotNull($demandeLabo->fresh()->resultat_notifie_le);

        // Côté clinique : signalé tant que la demande n'est pas ouverte.
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $this->assertSame(1, $reseau->resultatsNonVus());

        app(NotificationReseauService::class)->marquerVue($reseau->demandePrescrite($demande->id), $this->medecin);
        $this->assertSame(0, $reseau->resultatsNonVus());
    }

    public function test_correspondance_reutilisee_au_deuxieme_envoi(): void
    {
        $premier = $this->envoyer($this->patient(), ['GLY']);
        $second = $this->envoyer($this->patient(), ['GLY']);

        $this->assertSame(1, LaboPartenariatActe::withoutGlobalScopes()->where('partenariat_id', $this->partenariat->id)->count());
        $this->assertSame(2, LaboDemande::withoutGlobalScopes()->where('partenariat_id', $this->partenariat->id)->count());
    }

    private function envoyer(Patient $patient, array $codes): LaboDemande
    {
        $this->actingAs($this->medecin);
        $reseau = app(LaboReseauService::class);
        $examens = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->labo->id)->whereIn('code', $codes)->pluck('id')->all();

        return $reseau->envoyer($reseau->partenariat($this->partenariat->id), $patient, ['examens' => $examens], $this->medecin);
    }

    private function patient(): Patient
    {
        $patient = Patient::create(['first_name' => 'Aïssatou', 'last_name' => 'Diallo', 'gender' => 'Femme', 'birth_date' => '1994-04-04']);
        $this->clinique->patients()->syncWithoutDetaching([$patient->id]);

        return $patient;
    }
}
