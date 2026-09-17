<?php

namespace Database\Seeders\Parcours;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\Parcours\ParcoursPermissionsSeeder"
 *
 * Attention : PermissionSeeder fait un syncPermissions() sur les rôles —
 * le relancer retire ces permissions, relancez alors ce seeder.
 */
class ParcoursPermissionsSeeder extends Seeder
{
    public const PERMISSIONS = [
        'parcours.accueil' => 'Accueillir les patients (arrivées, file du jour, transferts)',
        'parcours.constantes' => 'Saisir les constantes avant consultation',
        'parcours.file' => 'Voir sa file d\'attente et appeler les patients',
        'parcours.dossier' => 'Consulter le dossier du patient en frise et les suivis de grossesse',
        'parcours.grossesse' => 'Ouvrir, corriger et clôturer un suivi de grossesse',
        'parcours.statistiques' => 'Consulter les statistiques de consultation',
    ];

    public const ROLES = [
        'secretaire' => ['parcours.accueil', 'parcours.constantes', 'parcours.dossier', 'parcours.grossesse'],
        'medecin' => ['parcours.file', 'parcours.constantes', 'parcours.dossier', 'parcours.grossesse', 'parcours.statistiques'],
        'admin' => ['parcours.accueil', 'parcours.constantes', 'parcours.file', 'parcours.dossier', 'parcours.grossesse', 'parcours.statistiques'],
        'super-admin' => ['parcours.accueil', 'parcours.constantes', 'parcours.file', 'parcours.dossier', 'parcours.grossesse', 'parcours.statistiques'],
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
