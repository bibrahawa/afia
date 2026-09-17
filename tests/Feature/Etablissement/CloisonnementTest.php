<?php

namespace Tests\Feature\Etablissement;

use App\Models\Chambre;
use App\Models\ComptePatient;
use App\Models\Etablissement;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\Test as Examen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Priorité 1 — une 2e clinique ne doit rien voir ni rien modifier de la 1re.
 * Base MySQL/MariaDB de test requise (UPDATE … JOIN dans les migrations).
 */
class CloisonnementTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $a;
    private Etablissement $b;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->b = Etablissement::create(['nom' => 'Clinique B', 'slug' => 'clinique-b', 'type' => 'clinique', 'statut' => 'actif']);
        $this->userA = $this->utilisateur($this->a);
        $this->userB = $this->utilisateur($this->b);
    }

    private function utilisateur(?Etablissement $etab): User
    {
        $u = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $u->forceFill(['etablissement_id' => $etab?->id])->save();

        return $u;
    }

    private function chambre(User $u, string $numero = '101'): Chambre
    {
        $this->actingAs($u);

        return Chambre::create(['numero' => $numero, 'prix_par_jour' => 150000]);
    }

    public function test_lecture_cloisonnee(): void
    {
        $chambreA = $this->chambre($this->userA);

        $this->actingAs($this->userB);
        $this->assertSame(0, Chambre::count());
        $this->assertNull(Chambre::find($chambreA->id));
    }

    public function test_ecriture_sur_la_fiche_d_un_autre_etablissement_refusee(): void
    {
        $chambreA = $this->chambre($this->userA);

        $this->actingAs($this->userB);
        $intrus = Chambre::withoutGlobalScope('etablissement')->find($chambreA->id); // contournement volontaire du filtre

        $this->expectException(\LogicException::class);
        $intrus->update(['prix_par_jour' => 1]);
    }

    public function test_creation_forcee_pour_un_autre_etablissement_refusee(): void
    {
        $this->actingAs($this->userB);
        $this->expectException(\LogicException::class);
        (new Chambre(['numero' => '9', 'prix_par_jour' => 1]))->forceFill(['etablissement_id' => $this->a->id])->save();
    }

    public function test_meme_numero_de_chambre_et_meme_code_assurance_dans_deux_cliniques(): void
    {
        $this->chambre($this->userA, '101');
        $this->chambre($this->userB, '101');

        $this->actingAs($this->userA);
        InsuranceCompany::create(['name' => 'NSIA', 'code' => 'NSIA']);
        $this->actingAs($this->userB);
        InsuranceCompany::create(['name' => 'NSIA', 'code' => 'NSIA']);

        $this->assertSame(2, Chambre::withoutGlobalScopes()->where('numero', '101')->count());
    }

    public function test_regles_de_validation_par_etablissement(): void
    {
        $chambreA = $this->chambre($this->userA, '101');

        $this->actingAs($this->userB);
        $this->assertTrue(Validator::make(['c' => $chambreA->id], ['c' => 'exists_etablissement:chambres,id'])->fails(), 'id étranger refusé');
        $this->assertTrue(Validator::make(['n' => '101'], ['n' => 'unique_etablissement:chambres,numero'])->passes(), 'numéro libre chez B');

        $this->actingAs($this->userA);
        $this->assertTrue(Validator::make(['c' => $chambreA->id], ['c' => 'exists_etablissement:chambres,id'])->passes());
        $this->assertTrue(Validator::make(['n' => '101'], ['n' => 'unique_etablissement:chambres,numero'])->fails());
        $this->assertTrue(Validator::make(['n' => '101'], ['n' => 'unique_etablissement:chambres,numero,' . $chambreA->id])->passes(), 'ignore la fiche modifiée');
    }

    public function test_utilisateur_sans_etablissement_ne_voit_rien(): void
    {
        $this->chambre($this->userA);
        $orphelin = $this->utilisateur(null);

        $this->actingAs($orphelin);
        $this->assertSame(0, Chambre::count());
        $this->assertSame(0, Patient::suivisParEtablissement()->count());
    }

    public function test_patients_listes_uniquement_s_ils_sont_suivis(): void
    {
        $patient = Patient::create(['first_name' => 'Mariama', 'last_name' => 'Bah', 'gender' => 'Femme']);
        $this->a->patients()->attach($patient->id);

        $this->actingAs($this->userA);
        $this->assertSame(1, Patient::suivisParEtablissement()->count());

        $this->actingAs($this->userB);
        $this->assertSame(0, Patient::suivisParEtablissement()->count());
    }

    public function test_recherche_rapide_confidentielle(): void
    {
        $patient = Patient::create(['first_name' => 'Mariama', 'last_name' => 'Bah', 'gender' => 'Femme']);
        $compte = ComptePatient::create(['telephone' => '622000111', 'statut' => 'actif']);
        $compte->patients()->attach($patient->id, ['role' => 'titulaire']);
        $this->a->patients()->attach($patient->id);

        $controleur = app(\App\Http\Controllers\PatientController::class);
        $chercher = fn (string $q) => collect($controleur->rechercheRapide(request()->merge(['q' => $q]))->getData(true));

        $this->actingAs($this->userB);
        $this->assertCount(0, $chercher('Mari'), 'nom partiel : invisible pour une autre clinique');
        $this->assertCount(0, $chercher('622000'), 'numéro partiel : invisible');
        $this->assertCount(1, $chercher('622000111'), 'numéro complet donné par le patient : retrouvé');

        $this->actingAs($this->userA);
        $this->assertCount(1, $chercher('Mari'));
    }

    public function test_heritage_de_l_etablissement_hors_session(): void
    {
        $chambreA = $this->chambre($this->userA);
        auth()->logout();

        $patient = Patient::create(['first_name' => 'Ibrahima', 'last_name' => 'Camara', 'gender' => 'Homme']);
        $hospi = \App\Models\Hospitalisation::create([
            'patient_id' => $patient->id, 'chambre_id' => $chambreA->id,
            'date_entree' => now(), 'nombre_jours' => 2, 'date_sortie_prevue' => now()->addDays(2), 'statut' => 'En cours',
        ]);

        $this->assertSame($this->a->id, (int) $hospi->etablissement_id);
        $this->assertTrue($this->a->patients()->where('patient_id', $patient->id)->exists(), 'patient rattaché automatiquement');
    }

    public function test_examens_du_catalogue_propres_a_chaque_clinique(): void
    {
        $this->actingAs($this->userA);
        Examen::create(['name' => 'Glycémie', 'report_type' => 'numerique', 'amount' => 25000]);

        $this->actingAs($this->userB);
        $this->assertSame(0, Examen::count());
        $this->assertTrue(Validator::make(['name' => 'Glycémie'], ['name' => 'unique_etablissement:tests,name'])->passes());
    }
}
