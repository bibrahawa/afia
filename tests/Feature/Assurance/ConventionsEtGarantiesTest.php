<?php

namespace Tests\Feature\Assurance;

use App\Enums\Assurance\StatutCouverture;
use App\Http\Middleware\EnsureModuleActive;
use App\Models\Assurance\ConventionFamille;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Formule;
use App\Models\Assurance\PieceJustificative;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Hospitalisation;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Models\InsuranceCoverage;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Assurance\BordereauService;
use App\Services\Assurance\ConventionService;
use App\Services\Assurance\ReclamationService;
use App\Services\Assurance\ReferentielAssuranceService;
use App\Services\Assurance\ReglementAssuranceService;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Lot 2d — conventions par famille, maternité, fréquences, arrondi, feuille de
 * soins, pièces justificatives. MySQL/MariaDB requis.
 *
 * NSIA, formule 80 % depuis le 01/01/2026. Consultation 100 000 GNF,
 * échographie 50 000 GNF (imagerie), CPN 30 000 GNF (maternité).
 */
class ConventionsEtGarantiesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $departement;
    private Employee $medecin;
    private InsuranceCompany $nsia;
    private Formule $formule;
    private Service $consultation;
    private Service $echographie;
    private Service $cpn;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-09-26 09:00:00'));

        $etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->user = User::create(['name' => 'Gestionnaire', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->user->forceFill(['etablissement_id' => $etab->id])->save();
        $this->actingAs($this->user);

        $this->departement = Department::create(['name' => 'Gynécologie']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id]);
        $this->consultation = Service::create(['name' => 'Consultation', 'amount' => 100000, 'department_id' => $this->departement->id, 'famille_acte' => 'consultation']);
        $this->echographie = Service::create(['name' => 'Échographie', 'amount' => 50000, 'department_id' => $this->departement->id, 'famille_acte' => 'imagerie']);
        $this->cpn = Service::create(['name' => 'Consultation prénatale', 'amount' => 30000, 'department_id' => $this->departement->id, 'famille_acte' => 'maternite']);

        $this->nsia = InsuranceCompany::create(['name' => 'NSIA', 'code' => 'NSIA', 'type' => 'assureur']);
        InsuranceCoverage::create(['insurance_company_id' => $this->nsia->id, 'coverageable_type' => Service::class, 'coverageable_id' => $this->consultation->id,
            'valid_from' => '2026-01-01', 'status' => 'active', 'acte_price' => 100000]);

        $contrat = Contrat::create(['insurance_company_id' => $this->nsia->id, 'numero_police' => 'GRP-1', 'date_debut' => '2026-01-01', 'statut' => StatutCouverture::Active]);
        $this->formule = Formule::create(['contrat_id' => $contrat->id, 'libelle' => 'Standard', 'taux_prise_en_charge' => 80]);

        $this->patient = Patient::create(['first_name' => 'Hawa', 'last_name' => 'Keita', 'gender' => 'Femme', 'birth_date' => '1994-01-01']);
        $etab->patients()->syncWithoutDetaching([$this->patient->id]);
        app(ReferentielAssuranceService::class)->creerAdhesion($this->formule, $this->patient, ['date_debut' => '2026-01-01']);
    }

    // ------------------------------------------------------------------ Conventions

    public function test_regle_par_famille_couvre_sans_ligne_par_acte(): void
    {
        $this->regleFamille('imagerie', 10);

        $ligne = $this->facturer([$this->echographie])->invoice->items->first();

        // 50 000 − 10 % = 45 000 ; 80 % = 36 000.
        $this->assertSame('45000.00', (string) $ligne->getRawOriginal('total_amount'));
        $this->assertSame('36000.00', (string) $ligne->getRawOriginal('insurance_covered_amount'));
    }

    public function test_une_ligne_exclue_prime_sur_la_regle_de_famille(): void
    {
        $this->regleFamille('imagerie');
        $ligne = app(ConventionService::class)->ajouterActe($this->nsia, 'service', $this->echographie->id, ['exclu' => true]);

        $item = $this->facturer([$this->echographie])->invoice->items->first();
        $this->assertSame('0.00', (string) $item->getRawOriginal('insurance_covered_amount'));
        $this->assertSame('acte exclu de la convention', $item->repartition_assurance[0]['motif']);

        // Supprimer la ligne : la règle de famille s'applique de nouveau.
        app(ConventionService::class)->supprimerActe($ligne);
        $this->assertSame('40000.00', (string) $this->facturer([$this->echographie])->invoice->items->first()->getRawOriginal('insurance_covered_amount'));
    }

    public function test_modifier_le_prix_d_une_ligne(): void
    {
        $ligne = InsuranceCoverage::where('coverageable_id', $this->consultation->id)->firstOrFail();
        app(ConventionService::class)->modifierActe($ligne, ['acte_price' => 90000]);

        $this->assertSame('72000.00', (string) $this->facturer([$this->consultation])->invoice->getRawOriginal('insurance_amount'));
    }

    public function test_copier_une_convention(): void
    {
        $this->regleFamille('imagerie');
        $sanlam = InsuranceCompany::create(['name' => 'Sanlam', 'code' => 'SANLAM', 'type' => 'assureur']);

        $resultat = app(ConventionService::class)->copier($this->nsia, $sanlam, false);

        $this->assertSame(['actes' => 1, 'familles' => 1], $resultat);
        $this->assertSame(1, ConventionFamille::where('insurance_company_id', $sanlam->id)->where('famille_acte', 'imagerie')->count());
    }

    // ------------------------------------------------------------------ Garanties

    public function test_carence_maternite(): void
    {
        $this->regleFamille('maternite');
        $this->formule->garanties()->create(['famille_acte' => 'maternite', 'delai_carence_jours' => 270]);

        // 01/01/2026 + 270 jours = 28/09/2026.
        $avant = $this->facturer([$this->cpn]);
        $this->assertSame('0.00', (string) $avant->invoice->getRawOriginal('insurance_amount'));
        $this->assertStringContainsString('carence', $avant->invoice->alertes_assurance[0]);

        $this->travelTo(Carbon::parse('2026-09-29 09:00:00'));
        $this->assertSame('24000.00', (string) $this->facturer([$this->cpn])->invoice->getRawOriginal('insurance_amount'));
    }

    public function test_limite_de_frequence(): void
    {
        $this->regleFamille('imagerie');
        $this->formule->garanties()->create(['famille_acte' => 'imagerie', 'nombre_max' => 2, 'periode' => 'annee']);

        $this->facturer([$this->echographie]);
        $this->facturer([$this->echographie]);
        $troisieme = $this->facturer([$this->echographie]);

        $this->assertSame('0.00', (string) $troisieme->invoice->getRawOriginal('insurance_amount'));
        $this->assertStringContainsString('limite de 2', $troisieme->invoice->items->first()->repartition_assurance[0]['motif']);
    }

    public function test_montants_arrondis_au_franc(): void
    {
        $this->formule->update(['taux_prise_en_charge' => 33.33]);

        $t = $this->facturer([$this->consultation]);

        $this->assertSame('33330.00', (string) $t->invoice->getRawOriginal('insurance_amount'));
        $this->assertSame('66670.00', (string) $t->invoice->getRawOriginal('patient_amount'));
    }

    public function test_hospitalisation_evaluee_a_l_admission(): void
    {
        $hospitalisation = new Hospitalisation(['date_entree' => '2026-09-10']);

        $this->assertSame('2026-09-10', app(BillingService::class)->dateSoin($hospitalisation)->toDateString());
    }

    // ------------------------------------------------------------------ Réclamations

    public function test_une_reclamation_rejetee_reste_visible_dans_les_creances(): void
    {
        $t = $this->facturer([$this->consultation]);
        $bordereaux = app(BordereauService::class);
        $bordereaux->envoyer($bordereaux->creer($this->nsia, null, null), today());
        $reclamation = InsuranceClaim::with('lignes')->where('invoice_id', $t->invoice->id)->firstOrFail();

        app(ReclamationService::class)->enregistrerReponse($reclamation, [$reclamation->lignes->first()->id => ['montant_accepte' => 0, 'motif_rejet' => 'Hors garanties']]);

        $this->assertSame('rejected', $reclamation->fresh()->status);
        $this->assertTrue(app(ReglementAssuranceService::class)->reclamationsOuvertes($this->nsia)->contains('id', $reclamation->id));
    }

    public function test_feuille_de_soins_et_piece_jointe(): void
    {
        $this->withoutMiddleware([EnsureModuleActive::class, \Illuminate\Auth\Middleware\Authorize::class]);
        Storage::fake(PieceJustificative::DISQUE);

        $t = $this->facturer([$this->consultation]);
        $reclamation = InsuranceClaim::where('invoice_id', $t->invoice->id)->firstOrFail();

        $this->get(route('assurance.feuilles-de-soins.show', $t->id))
            ->assertOk()
            ->assertSee('Feuille de soins')
            ->assertSee('NSIA')
            ->assertSee('80 000');

        $this->post(route('assurance.pieces.store', $reclamation), [
            'type' => 'feuille_soins',
            'fichier' => UploadedFile::fake()->create('feuille.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $piece = PieceJustificative::firstOrFail();
        Storage::disk(PieceJustificative::DISQUE)->assertExists($piece->chemin);
        $this->get(route('assurance.pieces.telecharger', $piece))->assertOk();
    }

    // ------------------------------------------------------------------ Outils

    private function regleFamille(string $famille, float $remise = 0): void
    {
        ConventionFamille::create(['insurance_company_id' => $this->nsia->id, 'famille_acte' => $famille, 'remise_pourcentage' => $remise, 'valid_from' => '2026-01-01', 'actif' => true]);
    }

    private function facturer(array $services): Transaction
    {
        $c = Consultation::create(['patient_id' => $this->patient->id, 'medecin_id' => $this->medecin->id, 'department_id' => $this->departement->id, 'motif' => 'Suivi', 'diagnostic' => 'RAS']);
        $c->services()->attach(collect($services)->pluck('id')->all());

        return app(BillingService::class)->createFromConsultation($c->fresh())->fresh(['invoice.items']);
    }
}
