<?php

namespace Database\Seeders\Labo;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\Labo\LaboReseauPermissionsSeeder"
 *
 * Attention : PermissionSeeder fait un syncPermissions() sur les rôles ;
 * le relancer retire ces permissions, relancez alors ce seeder.
 */
class LaboReseauPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'labo.partenariat.gerer' => 'Ouvrir et suspendre les partenariats du laboratoire',
        'labo.reseau.view' => 'Suivre les analyses envoyées à un laboratoire partenaire',
        'labo.reseau.demander' => 'Envoyer une demande d\'analyses à un laboratoire partenaire',
        'labo.partenariat.facturer' => 'Facturer les cliniques partenaires : relevés et règlements',
        'labo.reseau.factures' => 'Consulter les relevés reçus des laboratoires partenaires',
    ];

    public const ROLES = [
        'admin' => ['labo.partenariat.gerer', 'labo.partenariat.facturer', 'labo.reseau.view', 'labo.reseau.demander', 'labo.reseau.factures'],
        'super-admin' => ['labo.partenariat.gerer', 'labo.partenariat.facturer', 'labo.reseau.view', 'labo.reseau.demander', 'labo.reseau.factures'],
        'medecin' => ['labo.reseau.view', 'labo.reseau.demander'],
        'secretaire' => ['labo.reseau.view', 'labo.reseau.demander'],
        'comptable' => ['labo.partenariat.facturer', 'labo.reseau.view', 'labo.reseau.factures'],
    ];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $nom) {
            Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::where('name', $role)->where('guard_name', 'web')->first()?->givePermissionTo($permissions);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
