<?php

namespace Database\Seeders\Labo;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\Labo\LaboPermissionsSeeder"
 *
 * Les rôles Spatie sont globaux (pas d'option « teams ») : un rôle
 * « Biologiste » est partagé par tous les établissements, ce qui convient
 * tant que les rôles décrivent un MÉTIER et non un périmètre. Le périmètre,
 * lui, est garanti par etablissement_id.
 */
class LaboPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'labo.tableau_bord' => 'Tableau de bord du laboratoire',
        'labo.catalogue.view' => 'Consulter le catalogue d\'examens',
        'labo.catalogue.manage' => 'Gérer le catalogue (examens, prix, normes)',
        'labo.demande.view' => 'Consulter les demandes',
        'labo.demande.create' => 'Enregistrer une demande',
        'labo.demande.cancel' => 'Annuler une demande ou un examen',
        'labo.prelevement' => 'Prélever et imprimer les étiquettes',
        'labo.reception' => 'Réceptionner / rejeter les échantillons',
        'labo.resultat.saisir' => 'Saisir les résultats',
        'labo.validation.technique' => 'Validation technique',
        'labo.validation.biologique' => 'Validation biologique et rectification',
        'labo.compte_rendu.publier' => 'Publier les comptes rendus',
        'labo.compte_rendu.view' => 'Consulter / imprimer les comptes rendus',
        'labo.facturation' => 'Facturer et remettre des résultats non réglés',
    ];

    public const ROLES_ADMINISTRATION = ['admin', 'super-admin'];

    public const ROLES = [
        'Accueil laboratoire' => ['labo.demande.view', 'labo.demande.create', 'labo.compte_rendu.view', 'labo.catalogue.view'],
        'Préleveur' => ['labo.demande.view', 'labo.prelevement', 'labo.catalogue.view'],
        'Technicien de laboratoire' => [
            'labo.tableau_bord', 'labo.demande.view', 'labo.prelevement', 'labo.reception',
            'labo.resultat.saisir', 'labo.validation.technique', 'labo.catalogue.view', 'labo.compte_rendu.view',
        ],
        'Biologiste' => [
            'labo.tableau_bord', 'labo.demande.view', 'labo.demande.create', 'labo.demande.cancel',
            'labo.prelevement', 'labo.reception', 'labo.resultat.saisir', 'labo.validation.technique',
            'labo.validation.biologique', 'labo.compte_rendu.publier', 'labo.compte_rendu.view',
            'labo.catalogue.view', 'labo.catalogue.manage', 'labo.facturation',
        ],
    ];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $nom) {
            Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])->givePermissionTo($permissions);
        }

        // Rôles d'administration EXISTANTS : toutes les permissions labo.
        // (Jamais créés ici. Attention : PermissionSeeder fait un syncPermissions()
        // sur « admin » — le relancer retire ces permissions, relancez alors ce seeder.)
        foreach (self::ROLES_ADMINISTRATION as $role) {
            Role::where('name', $role)->where('guard_name', 'web')->first()
                ?->givePermissionTo(array_keys(self::PERMISSIONS));
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
