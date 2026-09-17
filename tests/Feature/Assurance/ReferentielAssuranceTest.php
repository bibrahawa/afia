<?php

namespace Tests\Feature\Assurance;

use App\Enums\Assurance\LienBeneficiaire;
use App\Enums\Assurance\StatutCouverture;
use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Http\Middleware\EnsureModuleActive;
use App\Models\Assurance\Adhesion;
use App\Models\Assurance\Beneficiaire;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Entreprise;
use App\Models\Assurance\Formule;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\RelationFamiliale;
use App\Models\Service;
use App\Models\User;
use App\Services\Assurance\ReferentielAssuranceService;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Lot 2a — référentiel assurance. MySQL/MariaDB requis.
 * AssuranceServiceProvider doit être déclaré dans bootstrap/providers.php.
 */
class ReferentielAssuranceTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private User $gestionnaire;
    private InsuranceCompany $nsia;
    private Entreprise $mine;
    private Contrat $contrat;
    private Formule $cadres;
    private Patient $mamadou;
    private Patient $aissatou;
    private ReferentielAssuranceService $referentiel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-24 10:00:00'));

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->gestionnaire = $this->utilisateur($this->etab);
        $this->actingAs($this->gestionnaire);

        $this->referentiel = app(ReferentielAssuranceService::class);

        $this->nsia = InsuranceCompany::create(['name' => 'NSIA', 'code' => 'NSIA', 'type' => 'assureur']);
        $this->mine = Entreprise::create(['nom' => 'Société Minière de Boké']);
        $this->contrat = Contrat::create([
            'insurance_company_id' => $this->nsia->id, 'entreprise_id' => $this->mine->id,
            'numero_police' => 'GRP-2026-001', 'date_debut' => '2026-01-01', 'statut' => StatutCouverture::Active,
        ]);
        $this->cadres = Formule::create(['contrat_id' => $this->contrat->id, 'libelle' => 'Cadres', 'taux_prise_en_charge' => 80, 'plafond_annuel_beneficiaire' => 5000000]);

        $this->mamadou = $this->patient('Mamadou', 'Homme', '1985-03-12');
        $this->aissatou = $this->patient('Aïssatou', 'Femme', '1990-07-01');
    }

    // ------------------------------------------------------------------ Entreprise et adhésion

    public function test_un_employe_adhere_au_contrat_de_son_entreprise(): void
    {
        $emploi = $this->referentiel->rattacherEmploi($this->mamadou, $this->mine, ['matricule' => 'SMB-0042', 'poste' => 'Ingénieur']);

        $adhesion = $this->adherer($this->mamadou, ['patient_emploi_id' => $emploi->id, 'numero_carte' => 'NSIA-778']);

        $projection = PatientInsurance::where('patient_id', $this->mamadou->id)->firstOrFail();
        $this->assertSame('active', $projection->status);
        $this->assertSame($this->nsia->id, $projection->insurance_company_id);
        $this->assertSame('80.00', (string) $projection->coverage_percentage);
        $this->assertSame('NSIA-778', $projection->policy_number);
        $this->assertSame(LienBeneficiaire::Adherent, $adhesion->beneficiaires()->first()->lien);
    }

    public function test_l_emploi_doit_etre_dans_l_entreprise_souscriptrice(): void
    {
        $autre = Entreprise::create(['nom' => 'Banque X']);
        $emploi = $this->referentiel->rattacherEmploi($this->mamadou, $autre, []);

        $this->expectException(OperationAssuranceImpossible::class);
        $this->adherer($this->mamadou, ['patient_emploi_id' => $emploi->id]);
    }

    public function test_la_fin_d_emploi_clot_l_adhesion_et_les_ayants_droit(): void
    {
        $emploi = $this->referentiel->rattacherEmploi($this->mamadou, $this->mine, []);
        $adhesion = $this->adherer($this->mamadou, ['patient_emploi_id' => $emploi->id]);
        $this->referentiel->ajouterBeneficiaire($adhesion, $this->aissatou, ['lien' => 'conjoint']);

        $this->referentiel->terminerEmploi($emploi, Carbon::parse('2026-10-31'));

        foreach ([$this->mamadou, $this->aissatou] as $p) {
            $this->assertSame('2026-10-31', PatientInsurance::where('patient_id', $p->id)->firstOrFail()->end_date->toDateString());
        }
    }

    // ------------------------------------------------------------------ Conjoint et enfants

    public function test_la_conjointe_est_prise_en_charge_a_la_facturation(): void
    {
        $adhesion = $this->adherer($this->mamadou);
        $this->referentiel->ajouterBeneficiaire($adhesion, $this->aissatou, ['lien' => 'conjoint']);

        $departement = Department::create(['name' => 'Gynécologie']);
        $medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $departement->id]);
        $echo = Service::create(['name' => 'Échographie', 'amount' => 100000, 'department_id' => $departement->id]);
        InsuranceCoverage::create([
            'insurance_company_id' => $this->nsia->id, 'coverageable_type' => Service::class, 'coverageable_id' => $echo->id,
            'valid_from' => '2026-01-01', 'status' => 'active', 'acte_price' => 100000,
        ]);

        $consultation = Consultation::create(['patient_id' => $this->aissatou->id, 'medecin_id' => $medecin->id, 'department_id' => $departement->id, 'motif' => 'Suivi', 'diagnostic' => 'RAS']);
        $consultation->services()->attach($echo->id);

        $transaction = app(BillingService::class)->createFromConsultation($consultation->fresh());

        $this->assertSame('80000.00', (string) $transaction->invoice->getRawOriginal('insurance_amount'));
        $this->assertSame('20000.00', (string) $transaction->invoice->getRawOriginal('patient_amount'));
    }

    public function test_plusieurs_conjoints_peuvent_etre_declares(): void
    {
        $adhesion = $this->adherer($this->mamadou);
        $this->referentiel->ajouterBeneficiaire($adhesion, $this->aissatou, ['lien' => 'conjoint']);
        $this->referentiel->ajouterBeneficiaire($adhesion, $this->patient('Fatoumata', 'Femme', '1993-02-02'), ['lien' => 'conjoint']);

        $this->assertSame(2, $adhesion->beneficiaires()->where('lien', 'conjoint')->count());
    }

    public function test_un_enfant_perd_ses_droits_a_l_age_limite(): void
    {
        $adhesion = $this->adherer($this->mamadou);
        $enfant = $this->patient('Ibrahima', 'Homme', '2010-05-10');

        $this->referentiel->ajouterBeneficiaire($adhesion, $enfant, ['lien' => 'enfant']);

        // 21 ans le 10/05/2031 : couvert jusqu'à la veille de ses 22 ans.
        $this->assertSame('2032-05-09', PatientInsurance::where('patient_id', $enfant->id)->firstOrFail()->end_date->toDateString());
    }

    public function test_un_enfant_au_dela_de_l_age_limite_est_refuse_sauf_etudiant(): void
    {
        $adhesion = $this->adherer($this->mamadou);
        $grand = $this->patient('Alpha', 'Homme', '2003-01-15'); // 23 ans

        try {
            $this->referentiel->ajouterBeneficiaire($adhesion, $grand, ['lien' => 'enfant']);
            $this->fail('Refus attendu.');
        } catch (OperationAssuranceImpossible $e) {
            $this->assertStringContainsString('21 ans', $e->getMessage());
        }

        $b = $this->referentiel->ajouterBeneficiaire($adhesion, $grand, ['lien' => 'enfant', 'etudiant' => true]);
        $this->assertSame('active', $b->projection->status);
    }

    public function test_une_conjointe_assuree_elle_meme_garde_ses_deux_couvertures(): void
    {
        $sanlam = InsuranceCompany::create(['name' => 'Sanlam', 'code' => 'SANLAM']);
        $this->referentiel->creerContratIndividuel($this->aissatou, $sanlam, ['policy_number' => 'IND-9', 'coverage_percentage' => 50, 'start_date' => '2026-02-01']);

        $adhesion = $this->adherer($this->mamadou);
        $this->referentiel->ajouterBeneficiaire($adhesion, $this->aissatou, ['lien' => 'conjoint']);

        $this->assertSame(2, PatientInsurance::where('patient_id', $this->aissatou->id)->where('status', 'active')->count());
    }

    public function test_le_delai_de_carence_retarde_le_debut_de_couverture(): void
    {
        $this->cadres->update(['delai_carence_jours' => 30]);
        $this->adherer($this->mamadou, ['date_debut' => '2026-09-01']);

        $this->assertSame('2026-10-01', PatientInsurance::where('patient_id', $this->mamadou->id)->firstOrFail()->start_date->toDateString());
    }

    public function test_modifier_la_formule_met_a_jour_toutes_les_couvertures(): void
    {
        $adhesion = $this->adherer($this->mamadou);
        $this->referentiel->ajouterBeneficiaire($adhesion, $this->aissatou, ['lien' => 'conjoint']);

        $this->cadres->update(['taux_prise_en_charge' => 90]);

        $this->assertSame(2, PatientInsurance::where('coverage_percentage', 90)->count());
    }

    public function test_la_consommation_du_plafond_n_est_jamais_ecrasee(): void
    {
        $this->adherer($this->mamadou);
        PatientInsurance::where('patient_id', $this->mamadou->id)->update(['used_amount' => 750000]);

        $this->cadres->update(['plafond_annuel_beneficiaire' => 6000000]);

        $this->assertSame('750000.00', (string) PatientInsurance::where('patient_id', $this->mamadou->id)->value('used_amount'));
    }

    // ------------------------------------------------------------------ Écrans

    public function test_un_proche_des_liens_familiaux_s_ajoute_sans_preuve_mais_pas_un_inconnu(): void
    {
        $this->withoutMiddleware([EnsureModuleActive::class, \Illuminate\Auth\Middleware\Authorize::class]);
        $adhesion = $this->adherer($this->mamadou);

        // Aïssatou est déclarée épouse de Mamadou dans les liens familiaux.
        RelationFamiliale::create(['patient_id' => $this->mamadou->id, 'personne_liee_id' => $this->aissatou->id, 'type_relation' => 'epouse']);
        DB::table('etablissement_patient')->where('patient_id', $this->aissatou->id)->delete();

        $this->post(route('assurance.beneficiaires.store', $adhesion), ['patient_id' => $this->aissatou->id, 'lien' => 'conjoint'])
            ->assertSessionHasNoErrors();

        $inconnu = Patient::create(['first_name' => 'X', 'last_name' => 'Y', 'gender' => 'Homme']);
        DB::table('etablissement_patient')->where('patient_id', $inconnu->id)->delete();

        $this->post(route('assurance.beneficiaires.store', $adhesion), ['patient_id' => $inconnu->id, 'lien' => 'autre'])
            ->assertSessionHasErrors('patient_id');
    }

    public function test_reprise_des_anciennes_assurances(): void
    {
        $idLigne = DB::table('patient_insurances')->insertGetId([
            'etablissement_id' => $this->etab->id, 'patient_id' => $this->aissatou->id, 'insurance_company_id' => $this->nsia->id,
            'coverage_percentage' => 70, 'policy_number' => 'OLD-1', 'start_date' => '2025-06-01', 'status' => 'active',
            'annual_limit' => 2000000, 'used_amount' => 300000, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/assurance/2026_09_24_090002_reprendre_patient_insurances_dans_referentiel.php');
        $migration->up();
        $migration->up(); // idempotente

        $ligne = PatientInsurance::findOrFail($idLigne);
        $this->assertNotNull($ligne->beneficiaire_id);
        $this->assertSame('300000.00', (string) $ligne->getRawOriginal('used_amount'));
        $this->assertSame(1, Adhesion::where('reprise_patient_insurance_id', $idLigne)->count());
        $this->assertSame('Taux 70 % — plafond 2 000 000 GNF', $ligne->beneficiaire->adhesion->formule->libelle);
    }

    public function test_une_autre_clinique_ne_voit_pas_le_referentiel(): void
    {
        $this->adherer($this->mamadou);
        $autre = Etablissement::create(['nom' => 'Clinique B', 'slug' => 'clinique-b', 'type' => 'clinique', 'statut' => 'actif']);
        $this->actingAs($this->utilisateur($autre));

        $this->assertSame(0, Entreprise::count());
        $this->assertSame(0, Contrat::count());
        $this->assertSame(0, Beneficiaire::count());
    }

    // ------------------------------------------------------------------ Outils

    private function adherer(Patient $patient, array $donnees = []): Adhesion
    {
        return $this->referentiel->creerAdhesion($this->cadres, $patient, $donnees + ['date_debut' => '2026-01-01']);
    }

    private function patient(string $prenom, string $genre, string $naissance): Patient
    {
        $patient = Patient::create(['first_name' => $prenom, 'last_name' => 'Barry', 'gender' => $genre, 'birth_date' => $naissance]);
        $this->etab->patients()->syncWithoutDetaching([$patient->id]);

        return $patient;
    }

    private function utilisateur(Etablissement $etab): User
    {
        $u = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $u->forceFill(['etablissement_id' => $etab->id])->save();

        return $u;
    }
}
