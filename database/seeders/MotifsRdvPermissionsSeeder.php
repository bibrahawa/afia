<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * OBSOLÈTE — permissions intégrées à PermissionSeeder (plus appelé par
 * DatabaseSeeder). Conservé pour un lancement isolé ; guard_name ajouté
 * (sans lui, Spatie peut créer la permission sur un autre guard).
 */
class MotifsRdvPermissionsSeeder extends Seeder
{
    protected array $permissions = [
        'motif_rdv.view', 'motif_rdv.create', 'motif_rdv.edit', 'motif_rdv.delete',
    ];

    public function run(): void
    {
        foreach ($this->permissions as $nom) {
            Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
