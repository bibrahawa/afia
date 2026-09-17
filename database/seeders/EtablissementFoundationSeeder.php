<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\Module;
use Illuminate\Database\Seeder;

class EtablissementFoundationSeeder extends Seeder
{
    /**
     * Catalogue de modules. Les codes ne doivent jamais changer une fois
     * utilisés dans le middleware « module:xxx » et les vues.
     */
    protected array $modules = [
        ['code' => 'rdv', 'nom' => 'Rendez-vous'],
        ['code' => 'consultation', 'nom' => 'Consultations'],
        ['code' => 'hospitalisation', 'nom' => 'Hospitalisation'],
        ['code' => 'assurance', 'nom' => 'Gestion des assurances'],
        ['code' => 'facturation_avancee', 'nom' => 'Facturation avancée'],
        ['code' => 'laboratoire', 'nom' => 'Laboratoire'],
        ['code' => 'pharmacie', 'nom' => 'Pharmacie'],
    ];

    /** Modules actifs pour Aprosafe. CORRIGÉ : « laboratoire » manquait (menu labo invisible). */
    protected array $modulesAprosafe = ['rdv', 'consultation', 'hospitalisation', 'assurance', 'facturation_avancee', 'laboratoire'];

    public function run(): void
    {
        $modules = collect($this->modules)->mapWithKeys(fn ($m) => [
            $m['code'] => Module::firstOrCreate(['code' => $m['code']], $m),
        ]);

        $aprosafe = Etablissement::firstOrCreate(
            ['slug' => 'aprosafe'],
            ['nom' => 'Clinique Médico-Chirurgicale Aprosafe', 'type' => 'clinique', 'statut' => 'essai']
        );

        $aprosafe->modules()->syncWithoutDetaching(
            collect($this->modulesAprosafe)
                ->mapWithKeys(fn ($code) => [$modules[$code]->id => ['est_actif' => true, 'active_depuis' => now(), 'desactive_le' => null]])
                ->toArray()
        );

        $this->command?->info("Établissement Aprosafe #{$aprosafe->id} : " . implode(', ', $this->modulesAprosafe));
    }
}
