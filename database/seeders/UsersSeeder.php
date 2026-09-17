<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Rôles + comptes de référence.
 *
 * CORRECTIONS
 * - Ne vide PLUS les tables de rôles et permissions. Avant, un simple
 *   `php artisan db:seed` retirait les rôles de TOUS les utilisateurs réels.
 * - Chaque compte du personnel est rattaché à l'établissement
 *   (users.etablissement_id). Avant, seul l'employé l'était : compte admin
 *   sans établissement → menu labo invisible, écrans vides après la
 *   Priorité 1.
 * - Ajout des comptes manquants : administrateur plateforme et les 4 rôles
 *   du laboratoire.
 * - Rôles tous créés ici, avant assignRole (les rôles labo n'existaient
 *   qu'après LaboPermissionsSeeder → erreur RoleDoesNotExist).
 *
 * Comptes EXISTANTS : rôle, établissement et employé remis en état ; le mot
 * de passe n'est jamais écrasé. Pour le changer :
 *   php artisan aprosafe:compte <téléphone> --mot-de-passe="..."
 *
 * ATTENTION : syncRoles() — un compte de référence qui avait reçu un rôle
 * supplémentaire à la main le perd (ex. admin + Biologiste → admin, qui a
 * de toute façon toutes les permissions labo).
 *
 * ⚠️ Mots de passe de référence : à changer dès la première connexion en production.
 */
class UsersSeeder extends Seeder
{
    public const ROLES = [
        'super-admin', 'admin', 'medecin', 'comptable', 'secretaire', 'patient',
        'Accueil laboratoire', 'Préleveur', 'Technicien de laboratoire', 'Biologiste',
    ];

    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
        $this->command?->info('Rôles présents : ' . implode(', ', self::ROLES));

        $etablissement = Etablissement::where('slug', 'aprosafe')->first();
        if (! $etablissement) {
            $this->command?->error("Établissement « aprosafe » absent : lancez EtablissementFoundationSeeder d'abord.");

            return;
        }

        $gyneco = Department::withoutGlobalScopes()->updateOrCreate(
            ['name' => 'GYNECOLOGIE', 'etablissement_id' => $etablissement->id], []
        );
        $labo = Department::withoutGlobalScopes()->updateOrCreate(
            ['name' => 'LABORATOIRE', 'etablissement_id' => $etablissement->id], []
        );

        // [email, nom, téléphone, mot de passe, rôle, type employé, prénom, nom employé, spécialité, département, rattaché ?]
        $comptes = [
            // Administrateur PLATEFORME : volontairement SANS établissement (voit toutes les cliniques).
            ['superadmin@aprosafe.com', 'Super Admin', '620000000', 'SuperAdmin@01', 'super-admin', 'Admin', 'Super', 'Admin', 'Plateforme', $gyneco, false],

            ['admin@aprosafe.com', 'Admin', '622099672', 'Admin@01', 'admin', 'Admin', 'Admin', 'Admin', 'Administration', $gyneco, true],
            ['binta@aprosafe.com', 'Medecin', '625476844', 'binta@01', 'medecin', 'Doctor', 'Fatoumata Binta', 'Diallo', 'GYNECOLOGIE', $gyneco, true],
            ['comptable@aprosafe.com', 'comptable', '625000000', 'comptable@01', 'comptable', 'Accountant', 'Comptable', 'Aprosafe', 'Comptabilité', $gyneco, true],
            ['secretaire@aprosafe.com', 'secretaire', '626000000', 'secretaire@01', 'secretaire', 'Secretary', 'Secrétaire', 'Aprosafe', 'Accueil', $gyneco, true],

            // Laboratoire
            ['accueil.labo@aprosafe.com', 'Accueil Labo', '627000001', 'AccueilLabo@01', 'Accueil laboratoire', 'Reception', 'Accueil', 'Laboratoire', 'Accueil laboratoire', $labo, true],
            ['preleveur@aprosafe.com', 'Préleveur', '627000002', 'Preleveur@01', 'Préleveur', 'Nurse', 'Préleveur', 'Aprosafe', 'Prélèvements', $labo, true],
            ['technicien@aprosafe.com', 'Technicien Labo', '627000003', 'Technicien@01', 'Technicien de laboratoire', 'Laboratory', 'Technicien', 'Laboratoire', 'Analyses', $labo, true],
            ['biologiste@aprosafe.com', 'Biologiste', '627000004', 'Biologiste@01', 'Biologiste', 'Laboratory', 'Biologiste', 'Aprosafe', 'Biologie médicale', $labo, true],
        ];

        $lignes = [];

        foreach ($comptes as [$email, $nom, $telephone, $motDePasse, $role, $type, $prenomEmp, $nomEmp, $specialite, $departement, $rattache]) {
            $user = User::where('email', $email)->orWhere('phone', $telephone)->first();
            $cree = ! $user;

            // Le cast « hashed » du modèle User chiffre le mot de passe.
            $user ??= User::create(['email' => $email, 'name' => $nom, 'phone' => $telephone, 'password' => $motDePasse]);

            $user->forceFill([
                'etablissement_id' => $rattache ? $etablissement->id : null,
                'login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            $user->syncRoles([$role]);

            // Pas de fiche employé pour l'administrateur plateforme : il n'appartient à aucune clinique.
            if ($rattache) Employee::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'etablissement_id' => $etablissement->id,
                    'first_name' => $prenomEmp, 'last_name' => $nomEmp,
                    'address' => 'Conakry, Guinée', 'description' => $specialite,
                    'speciality' => $specialite, 'type' => $type,
                    'department_id' => $departement->id, 'is_active' => true,
                ]
            );

            $lignes[] = [$role, $user->phone, $user->email, $cree ? $motDePasse : '(inchangé)', $rattache ? $etablissement->slug : 'AUCUN (plateforme)'];
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->table(['Rôle', 'Téléphone (connexion)', 'E-mail', 'Mot de passe', 'Établissement'], $lignes);
    }
}
