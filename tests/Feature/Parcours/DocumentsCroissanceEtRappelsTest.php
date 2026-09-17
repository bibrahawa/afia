<?php

namespace Tests\Feature\Parcours;

use App\Enums\Parcours\TypeDocumentMedical;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureModuleActive;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Parcours\Constante;
use App\Models\Parcours\DocumentMedical;
use App\Models\Parcours\Grossesse;
use App\Models\Parcours\GrossesseRappel;
use App\Models\Parcours\NormeCroissance;
use App\Models\Patient;
use App\Models\RelationFamiliale;
use App\Models\Service;
use App\Models\User;
use App\Services\Parcours\AccueilService;
use App\Services\Parcours\CroissanceService;
use App\Services\Parcours\DocumentMedicalService;
use App\Services\Parcours\GrossesseService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/** Lot 3e — certificats, croissance, nouveau-né, rappels de CPN. */
class DocumentsCroissanceEtRappelsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private User $user;
    private Employee $medecin;
    private Department $departement;
    private Service $acteMaternite;
    private Patient $patiente;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->travelTo(Carbon::parse('2026-10-02 09:00:00'));
        $this->withoutMiddleware([CheckPermission::class, Authorize::class, EnsureModuleActive::class]);

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->user = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $this->user->forceFill(['etablissement_id' => $this->etab->id])->save();
        $this->actingAs($this->user);

        $this->departement = Department::create(['name' => 'Gynécologie']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id, 'user_id' => $this->user->id]);
        $this->acteMaternite = Service::create(['name' => 'Consultation prénatale', 'amount' => 80000, 'department_id' => $this->departement->id, 'famille_acte' => 'maternite']);
        $this->patiente = $this->patient('Aminata', 'Femme', '1995-05-05');
    }

    public function test_arret_de_travail_numerote_et_imprimable(): void
    {
        $visite = app(AccueilService::class)->arriveeSansRendezVous($this->patiente, $this->medecin, ['service_id' => $this->acteMaternite->id], $this->user);

        $this->post(route('parcours.documents.store', $visite->consultation), [
            'type' => TypeDocumentMedical::ArretTravail->value,
            'contenu' => 'Arrêt de travail de 3 jours pour paludisme simple.',
            'date_debut' => '2026-10-02',
            'jours' => 3,
        ])->assertRedirect();

        $document = DocumentMedical::firstOrFail();
        $this->assertSame(TypeDocumentMedical::ArretTravail, $document->type);
        $this->assertStringStartsWith('ARR-', $document->numero);
        $this->assertSame('2026-10-04', $document->date_fin->toDateString());

        $this->get(route('parcours.documents.imprimer', $document))
            ->assertOk()
            ->assertSee('Arrêt de travail')
            ->assertSee($this->patiente->full_name);
    }

    public function test_texte_type_avec_variables_remplacees(): void
    {
        $texte = app(DocumentMedicalService::class)->proposerTexte(
            TypeDocumentMedical::ArretTravail,
            $this->patiente,
            ['jours' => 5, 'date_debut' => '2026-10-02', 'motif' => 'paludisme', 'medecin' => 'Dr Awa Diallo']
        );

        $this->assertStringContainsString('Dr Awa Diallo', $texte);
        $this->assertStringContainsString('5 jour(s)', $texte);
        $this->assertStringContainsString('du 02/10/2026 au 06/10/2026', $texte);
        $this->assertStringNotContainsString('{', $texte);
    }

    public function test_document_annule_reste_au_dossier(): void
    {
        $visite = app(AccueilService::class)->arriveeSansRendezVous($this->patiente, $this->medecin, [], $this->user);
        $document = app(DocumentMedicalService::class)->creer(
            $this->patiente, TypeDocumentMedical::CertificatMedical, ['contenu' => 'Certificat.'], $visite->consultation, $this->user
        );

        app(DocumentMedicalService::class)->annuler($document, 'Erreur de date');

        $this->assertTrue($document->fresh()->annule);
        $this->assertSame('Erreur de date', $document->fresh()->motif_annulation);
        $this->assertSame(1, DocumentMedical::count());
    }

    public function test_z_score_calcule_avec_les_normes_importees(): void
    {
        $bebe = $this->patient('Ibrahima', 'Homme', today()->subMonths(6)->toDateString());

        // Valeurs de démonstration : dans la vraie vie, la table vient du fichier OMS.
        NormeCroissance::create(['indicateur' => NormeCroissance::POIDS_AGE, 'sexe' => 'Homme', 'mois' => 6, 'l' => 0.2, 'm' => 7.9, 's' => 0.11]);

        $croissance = app(CroissanceService::class);
        $this->assertTrue($croissance->normesDisponibles());

        // À la médiane exactement, le z-score vaut 0.
        $this->assertSame(0.0, $croissance->zScore(NormeCroissance::POIDS_AGE, $bebe, 6, 7.9));
        $this->assertLessThan(0, $croissance->zScore(NormeCroissance::POIDS_AGE, $bebe, 6, 6.0));
        $this->assertSame('Dans la norme', $croissance->interpretation(0.0)['libelle']);
    }

    public function test_mesures_affichees_sans_normes(): void
    {
        $bebe = $this->patient('Ibrahima', 'Homme', today()->subMonths(3)->toDateString());
        Constante::create(['patient_id' => $bebe->id, 'poids_kg' => 6.2, 'taille_cm' => 60, 'mesure_le' => now(), 'etablissement_id' => $this->etab->id]);

        $mesures = app(CroissanceService::class)->mesures($bebe);

        $this->assertCount(1, $mesures);
        $this->assertSame(3, $mesures->first()['mois']);
        $this->assertNull($mesures->first()['z']['poids']);
    }

    public function test_nouveau_ne_cree_a_la_cloture(): void
    {
        $grossesse = app(GrossesseService::class)->ouvrir($this->patiente, Carbon::parse('2026-01-05'), [], $this->user);

        app(GrossesseService::class)->cloturer(
            $grossesse, 'accouchement', today(), null,
            ['prenom' => 'Mariama', 'sexe' => 'Femme', 'poids_kg' => 3.2], $this->user
        );

        $bebe = Patient::where('first_name', 'Mariama')->firstOrFail();
        $this->assertSame($this->patiente->last_name, $bebe->last_name);
        $this->assertSame(today()->toDateString(), Carbon::parse($bebe->birth_date)->toDateString());
        $this->assertSame('3.20', (string) Constante::where('patient_id', $bebe->id)->firstOrFail()->getRawOriginal('poids_kg'));
        $this->assertSame(1, RelationFamiliale::where('patient_id', $bebe->id)->where('personne_liee_id', $this->patiente->id)->count());
    }

    public function test_rappel_cpn_envoye_une_seule_fois(): void
    {
        $compte = \App\Models\ComptePatient::create(['telephone' => '620000001', 'statut' => 'actif']);
        $this->patiente->comptesPatients()->attach($compte->id, ['role' => 'titulaire']);
        // DDR telle que le contact de 40 SA tombe dans trois jours.
        $grossesse = app(GrossesseService::class)->ouvrir($this->patiente, today()->subWeeks(40)->addDays(3), [], $this->user);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('sendSms')->once()->andReturn(['success' => true]);
        $this->app->instance(SmsService::class, $sms);

        $this->artisan('aprosafe:rappels-cpn', ['--jours' => 3])->assertSuccessful();
        $this->artisan('aprosafe:rappels-cpn', ['--jours' => 3])->assertSuccessful();

        $this->assertSame(1, GrossesseRappel::withoutGlobalScopes()->where('grossesse_id', $grossesse->id)->count());
    }

    public function test_ecran_salle_attente(): void
    {
        $visite = app(AccueilService::class)->arriveeSansRendezVous($this->patiente, $this->medecin, [], $this->user);

        $this->get(route('parcours.salle-attente.index'))
            ->assertOk()
            ->assertSee('Salle d\'attente', false)
            ->assertSee($this->patiente->first_name)
            ->assertDontSee($this->patiente->last_name);
    }

    // ------------------------------------------------------------------ Outils

    private function patient(string $prenom, string $sexe, string $naissance): Patient
    {
        $p = Patient::create(['first_name' => $prenom, 'last_name' => 'Barry', 'gender' => $sexe, 'birth_date' => $naissance]);
        $this->etab->patients()->syncWithoutDetaching([$p->id]);

        return $p;
    }
}
