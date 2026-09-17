<?php

namespace App\Console\Commands\Labo;

use App\Models\Etablissement;
use App\Models\Labo\LaboExamen;
use App\Models\Module;
use App\Services\Labo\CatalogueImportService;
use Database\Seeders\Labo\LaboCatalogueModeleSeeder;
use Database\Seeders\Labo\LaboPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mise en service du laboratoire pour un établissement, en une commande
 * idempotente (relançable sans risque) :
 *
 *   php artisan labo:activer aprosafe
 *   php artisan labo:activer labo-kaloum --sans-catalogue
 *   php artisan labo:activer aprosafe --desactiver
 */
class ActiverLaboratoire extends Command
{
    protected $signature = 'labo:activer
        {etablissement : slug de l\'établissement}
        {--sans-catalogue : ne pas importer le catalogue modèle}
        {--desactiver : retirer l\'accès au module (les données sont conservées)}';

    protected $description = 'Active (ou désactive) le module Laboratoire pour un établissement et importe le catalogue modèle';

    public function handle(CatalogueImportService $import): int
    {
        $etablissement = Etablissement::where('slug', $this->argument('etablissement'))->first();
        if (! $etablissement) {
            $this->error('Établissement introuvable : ' . $this->argument('etablissement'));

            return self::FAILURE;
        }

        $module = Module::where('code', 'laboratoire')->first();
        if (! $module) {
            $this->error('Module « laboratoire » absent du catalogue : lancez EtablissementFoundationSeeder.');

            return self::FAILURE;
        }

        if ($this->option('desactiver')) {
            $etablissement->modules()->syncWithoutDetaching([$module->id => ['est_actif' => false, 'desactive_le' => now()]]);
            $this->info("Module Laboratoire désactivé pour {$etablissement->nom}. Aucune donnée supprimée.");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($etablissement, $module) {
            $etablissement->modules()->syncWithoutDetaching([
                $module->id => ['est_actif' => true, 'active_depuis' => now(), 'desactive_le' => null],
            ]);
        });
        $this->info("✔ Module activé pour {$etablissement->nom}");

        $this->call('db:seed', ['--class' => LaboPermissionsSeeder::class, '--force' => true]);
        $this->info('✔ Permissions et rôles labo à jour');

        if ($this->option('sans-catalogue')) {
            $this->warn('Catalogue non importé (--sans-catalogue).');

            return self::SUCCESS;
        }

        if (! LaboExamen::withoutGlobalScope('etablissement')->whereNull('etablissement_id')->exists()) {
            $this->call('db:seed', ['--class' => LaboCatalogueModeleSeeder::class, '--force' => true]);
            $this->info('✔ Catalogue modèle plateforme créé');
        }

        $bilan = $import->importer($etablissement->id);
        $this->table(['Élément', 'Ajoutés'], collect($bilan)->map(fn ($n, $k) => [$k, $n])->values()->all());

        $this->newLine();
        $this->warn('À faire AVANT la première demande réelle :');
        $this->line('  1. Renseigner les prix (tous à 0 après import) — menu Laboratoire › Catalogue');
        $this->line('  2. Faire valider les normes par le biologiste responsable (aucune norme pédiatrique fournie)');
        $this->line('  3. Attribuer les rôles : Accueil laboratoire, Préleveur, Technicien de laboratoire, Biologiste');

        return self::SUCCESS;
    }
}
