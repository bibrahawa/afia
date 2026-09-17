<?php

namespace Database\Seeders;

use Database\Seeders\Parcours\ParcoursPermissionsSeeder;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed
 *
 * Rejouable sans risque : aucun seeder ne vide de table, les rôles et
 * utilisateurs existants sont conservés (seuls les comptes de test sont
 * remis dans leur état de référence).
 *
 * L'ordre compte :
 *  1. établissement et modules      (tout le reste s'y rattache)
 *  2. informations de l'établissement
 *  3. rôles + comptes de test        (les rôles doivent exister avant assignRole)
 *  4. permissions des rôles          (syncPermissions : source de vérité unique)
 *  5. catalogue labo modèle + rôles labo
 *  6. import du catalogue labo pour Aprosafe
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(EtablissementFoundationSeeder::class);
        $this->call(HospitalsTableSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(PermissionSeeder::class);

        // Casse exacte « Labo » : sur un serveur Linux, « labo\ » ne trouve pas le fichier.
        if (class_exists(Labo\LaboCatalogueModeleSeeder::class)) {
            $this->call(Labo\LaboCatalogueModeleSeeder::class);
            $this->call(Labo\LaboPermissionsSeeder::class);

            $aprosafe = \App\Models\Etablissement::where('slug', 'aprosafe')->first();
            if ($aprosafe) {
                app(\App\Services\Labo\CatalogueImportService::class)->importer($aprosafe->id);
                $this->command?->info('Catalogue labo importé pour Aprosafe (prix à 0, normes à valider).');
            }
        }
        // MotifsRdvPermissionsSeeder et PilierABPermissionsSeeder ne sont plus
        // appelés : leurs permissions sont intégrées à PermissionSeeder.

        if (class_exists(Facturation\FacturationPermissionsSeeder::class)) {
            $this->call(Facturation\FacturationPermissionsSeeder::class);
        }

        if (class_exists(Assurance\AssurancePermissionsSeeder::class)) {
            $this->call(Assurance\AssurancePermissionsSeeder::class);
        }

        if (class_exists(ParcoursPermissionsSeeder::class)) {
            $this->call(ParcoursPermissionsSeeder::class);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
