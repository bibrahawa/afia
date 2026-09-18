<?php

namespace Database\Seeders\Rapports;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/** php artisan db:seed --class="Database\Seeders\Rapports\RapportsPermissionsSeeder" */
class RapportsPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = ['rapports.view' => 'Consulter les rapports de l\'établissement'];

    public const ROLES = ['admin', 'super-admin', 'comptable', 'medecin'];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $nom) {
            Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $role) {
            Role::where('name', $role)->where('guard_name', 'web')->first()?->givePermissionTo(array_keys(self::PERMISSIONS));
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
