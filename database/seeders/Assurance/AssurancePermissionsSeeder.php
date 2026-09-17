<?php

namespace Database\Seeders\Assurance;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\Assurance\AssurancePermissionsSeeder"
 */
class AssurancePermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'assurance.referentiel.view' => 'Consulter entreprises, contrats et couvertures des patients',
        'assurance.referentiel.manage' => 'Gérer entreprises, contrats, formules, adhésions et ayants droit',
    ];

    public const ROLES_ADMINISTRATION = ['admin', 'super-admin'];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $nom) {
            Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
        }

        // Attention : PermissionSeeder fait un syncPermissions() sur « admin » —
        // le relancer retire ces permissions, relancez alors ce seeder.
        foreach (self::ROLES_ADMINISTRATION as $role) {
            Role::where('name', $role)->where('guard_name', 'web')->first()
                ?->givePermissionTo(array_keys(self::PERMISSIONS));
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
