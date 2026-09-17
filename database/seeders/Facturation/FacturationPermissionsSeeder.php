<?php

namespace Database\Seeders\Facturation;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\Facturation\FacturationPermissionsSeeder"
 *
 * Même principe que LaboPermissionsSeeder : permissions créées ici, données
 * aux rôles d'administration existants. À attribuer ensuite au rôle de
 * responsable de caisse depuis l'écran des rôles.
 */
class FacturationPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'payment.cancel' => 'Annuler un paiement encaissé (avec motif)',
    ];

    public const ROLES_ADMINISTRATION = ['admin', 'super-admin'];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $nom) {
            Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']);
        }

        // Attention : PermissionSeeder fait un syncPermissions() sur « admin » —
        // le relancer retire cette permission, relancez alors ce seeder.
        foreach (self::ROLES_ADMINISTRATION as $role) {
            Role::where('name', $role)->where('guard_name', 'web')->first()
                ?->givePermissionTo(array_keys(self::PERMISSIONS));
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
