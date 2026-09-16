<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Etablissement;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * PRÉREQUIS : EtablissementFoundationSeeder doit avoir tourné avant
     * celui-ci — on a besoin d'un Etablissement existant pour rattacher
     * explicitement département et employés. Un seeder tourne en console,
     * sans requête HTTP : EtablissementContext (qui remplit
     * etablissement_id automatiquement ailleurs dans l'app) n'a aucun
     * contexte à résoudre ici, il faut donc le faire à la main.
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        echo "🧹 Nettoyage des données existantes...\n";

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        echo "✅ Tables nettoyées\n\n";

        $etablissement = Etablissement::where('slug', 'aprosafe')->first();

        if (! $etablissement) {
            echo "⚠️  Aucun établissement 'aprosafe' trouvé — lance d'abord EtablissementFoundationSeeder.\n";
            echo "    Les employés créés ici resteraient sans etablissement_id (invisibles partout).\n";
        }

        // ==========================================
        // RÔLES
        // ==========================================
        echo "📋 Création des rôles...\n";

        Role::create(['name' => 'patient', 'guard_name' => 'web']);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $medecinRole = Role::create(['name' => 'medecin', 'guard_name' => 'web']);
        $comptableRole = Role::create(['name' => 'comptable', 'guard_name' => 'web']);
        $secretaireRole = Role::create(['name' => 'secretaire', 'guard_name' => 'web']);

        echo "  ✓ Admin\n  ✓ Médecin\n  ✓ Comptable\n  ✓ Secrétaire\n\n";

        // ==========================================
        // PERMISSIONS
        // ==========================================
        // Liste déplacée et complétée dans PermissionSeeder — inclut
        // désormais etablissement.*, module.*, motif_rdv.*, consentement.*
        // qui manquaient totalement ici. On ne la duplique plus dans ce
        // fichier pour éviter les deux sources de vérité qui divergent.
        echo "🔑 Les permissions sont créées par PermissionSeeder — lance-le juste après celui-ci.\n\n";

        // ==========================================
        // UTILISATEURS DE TEST
        // ==========================================
        echo "👥 Création des utilisateurs de test...\n";

        $admin = User::firstOrCreate(
            ['email' => 'admin@aprosafe.com'],
            ['name' => 'Admin', 'phone' => '622099672', 'password' => 'Admin@01']
        );
        DB::table('model_has_roles')->where('model_id', $admin->id)->delete();
        $admin->assignRole('admin');
        echo "  ✓ admin\n";

        $medecin = User::firstOrCreate(
            ['email' => 'binta@aprosafe.com'],
            ['name' => 'Medecin', 'phone' => '625476844', 'password' => 'binta@01']
        );
        DB::table('model_has_roles')->where('model_id', $medecin->id)->delete();
        $medecin->assignRole('medecin');
        echo "  ✓ medecin\n";

        $comptable = User::firstOrCreate(
            ['email' => 'comptable@aprosafe.com'],
            ['name' => 'comptable', 'phone' => '625000000', 'password' => 'comptable@01']
        );
        DB::table('model_has_roles')->where('model_id', $comptable->id)->delete();
        $comptable->assignRole('comptable');
        echo "  ✓ comptable\n";

        $secretaire = User::firstOrCreate(
            ['email' => 'secretaire@aprosafe.com'],
            ['name' => 'secretaire', 'phone' => '626000000', 'password' => 'secretaire@01']
        );
        DB::table('model_has_roles')->where('model_id', $secretaire->id)->delete();
        $secretaire->assignRole('secretaire');
        echo "  ✓ secretaire\n\n";

        // ==========================================
        // DÉPARTEMENT ET EMPLOYÉS — etablissement_id EXPLICITE
        // ==========================================
        $department = Department::updateOrCreate(
            ['name' => 'GYNECOLOGIE', 'etablissement_id' => $etablissement?->id],
            []
        );

        // 'type' aligné sur l'énumération réelle d'employees.type — voir
        // la migration qui l'élargit (Admin, Secretary ajoutés).
        Employee::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'etablissement_id' => $etablissement?->id,
                'first_name' => 'Admin', 'last_name' => 'Admin',
                'address' => 'Conakry, Guinea', 'education' => 'MBA',
                'description' => 'Administrateur du système',
                'certificate' => 'Admin Certificate', 'speciality' => 'Administration',
                'type' => 'Admin',
                'department_id' => $department->id,
            ]
        );

        Employee::updateOrCreate(
            ['user_id' => $medecin->id],
            [
                'etablissement_id' => $etablissement?->id,
                'first_name' => 'Fatoumata Binta', 'last_name' => 'Diallo',
                'address' => 'Conakry, Guinea', 'education' => 'MBA',
                'description' => 'Médecin gynécologue',
                'certificate' => 'Certificat médical', 'speciality' => 'GYNECOLOGIE',
                'type' => 'Doctor',
                'department_id' => $department->id,
            ]
        );

        Employee::updateOrCreate(
            ['user_id' => $comptable->id],
            [
                'etablissement_id' => $etablissement?->id,
                'first_name' => 'Comptable', 'last_name' => 'Aprosafe',
                'address' => 'Conakry, Guinea', 'education' => 'MBA',
                'description' => 'Comptable', 'certificate' => 'Certificat', 'speciality' => 'Comptabilité',
                'type' => 'Accountant',
                'department_id' => $department->id,
            ]
        );

        Employee::updateOrCreate(
            ['user_id' => $secretaire->id],
            [
                'etablissement_id' => $etablissement?->id,
                'first_name' => 'Secrétaire', 'last_name' => 'Aprosafe',
                'address' => 'Conakry, Guinea', 'education' => 'MBA',
                'description' => 'Secrétaire d\'accueil', 'certificate' => 'Certificat', 'speciality' => 'Accueil',
                'type' => 'Secretary',
                'department_id' => $department->id,
            ]
        );

        echo "✅ Département et employés créés" . ($etablissement ? " pour {$etablissement->nom}\n" : ", SANS établissement (à corriger)\n");
    }
}
