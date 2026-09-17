<?php

namespace Tests\Feature\Labo;

use App\Models\Etablissement;
use App\Models\Module;
use App\Models\Patient;
use App\Models\User;
use App\Services\Labo\CatalogueImportService;
use App\Services\Labo\DemandeService;
use Database\Seeders\Labo\LaboCatalogueModeleSeeder;
use Database\Seeders\Labo\LaboPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Base des tests du module. Nécessite une base MySQL/MariaDB de test
 * (lockForUpdate, FIELD(), TIMESTAMPDIFF) — pas SQLite.
 * phpunit.xml : DB_CONNECTION=mysql, DB_DATABASE=aprosafe_test
 */
abstract class LaboTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LaboPermissionsSeeder::class);
        $this->seed(LaboCatalogueModeleSeeder::class);
    }

    protected function creerEtablissement(string $slug): Etablissement
    {
        $etab = Etablissement::create(['nom' => 'Établissement ' . $slug, 'slug' => $slug, 'type' => 'clinique', 'statut' => 'actif']);
        $module = Module::firstOrCreate(['code' => 'laboratoire'], ['nom' => 'Laboratoire']);
        $etab->modules()->attach($module->id, ['est_actif' => true, 'active_depuis' => now()]);

        // Comme en production (commande labo:activer), l'import se fait hors session :
        // un utilisateur déjà connecté ne peut pas écrire dans le catalogue d'un autre établissement.
        $connecte = auth()->user();
        if ($connecte) {
            auth()->logout();
        }

        app(CatalogueImportService::class)->importer($etab->id);

        if ($connecte) {
            $this->actingAs($connecte);
        }

        return $etab;
    }

    protected function creerBiologiste(Etablissement $etab): User
    {
        return $this->creerUtilisateur($etab, 'Biologiste');
    }

    /** Compte du personnel rattaché à l'établissement, avec un rôle (ou aucun). */
    protected function creerUtilisateur(Etablissement $etab, ?string $role = null): User
    {
        $user = User::create([
            'name' => ($role ?? 'Sans rôle') . ' ' . $etab->slug,
            'phone' => (string) random_int(600000000, 699999999),
            'email' => Str::random(8) . '@test.gn',
            'password' => bcrypt('secret'),
        ]);
        $user->forceFill(['etablissement_id' => $etab->id])->save(); // hors $fillable, volontairement

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    protected function creerPatient(): Patient
    {
        return Patient::create(['first_name' => 'Aïssatou', 'last_name' => 'Diallo', 'gender' => 'Femme', 'age' => 30]);
    }

    /** Crée une demande « gratuite » (pas de facturation) dans le contexte de $user. */
    protected function creerDemande(User $user, array $codesExamens = ['GLY']): \App\Models\Labo\LaboDemande
    {
        $this->actingAs($user);
        $ids = \App\Models\Labo\LaboExamen::whereIn('code', $codesExamens)->pluck('id')->all();

        return app(DemandeService::class)->creer([
            'patient_id' => $this->creerPatient()->id,
            'origine' => 'spontanee',
            'mode_facturation' => 'gratuit',
            'examens' => $ids,
        ], $user);
    }
}
