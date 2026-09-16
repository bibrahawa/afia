<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\Module;
use Illuminate\Database\Seeder;

class EtablissementFoundationSeeder extends Seeder
{
    /**
     * Catalogue de modules — à compléter au fil des phases (laboratoire en
     * Phase 5, pharmacie plus tard...). Les codes ne doivent jamais changer
     * une fois utilisés dans le middleware/les vues.
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

    public function run(): void
    {
        $modules = collect($this->modules)->mapWithKeys(function ($m) {
            return [$m['code'] => Module::firstOrCreate(['code' => $m['code']], $m)];
        });

        // Aprosafe = premier établissement, en phase d'essai (comme
        // convenu : on garde ses patients, le reste est réinitialisé).
        $aprosafe = Etablissement::firstOrCreate(
            ['slug' => 'aprosafe'],
            [
                'nom' => 'Clinique Médico-Chirurgicale Aprosafe',
                'type' => 'clinique',
                'statut' => 'essai',
            ]
        );

        $aprosafe->modules()->syncWithoutDetaching(
            collect(['rdv', 'consultation', 'hospitalisation', 'assurance', 'facturation_avancee'])
                ->mapWithKeys(fn ($code) => [
                    $modules[$code]->id => ['est_actif' => true, 'active_depuis' => now()],
                ])
                ->toArray()
        );
    }
}
