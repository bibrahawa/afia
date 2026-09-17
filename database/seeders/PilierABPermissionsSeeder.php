<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * OBSOLÈTE — permissions intégrées à PermissionSeeder (plus appelé par
 * DatabaseSeeder). Conservé pour un lancement isolé ; guard_name ajouté.
 */
class PilierABPermissionsSeeder extends Seeder
{
    protected array $permissions = [
        'etablissement.view', 'etablissement.create', 'etablissement.edit',
        'etablissement.delete', 'etablissement.licence',
        'module.view', 'module.create', 'module.edit', 'module.delete',
        'consentement.demander', 'consentement.revoquer',
    ];

    public function run(): void
    {
        foreach ($this->permissions as $nom) {
            Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
