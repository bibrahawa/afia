<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

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
            Permission::firstOrCreate(['name' => $nom]);
        }

        // Assigne les permissions Pilier A à un rôle "super-admin" existant
        // s'il y en a un — à adapter selon la structure de tes rôles actuels.
        // \App\Models\Role::where('name', 'super-admin')->first()
        //     ?->permissions()->syncWithoutDetaching(Permission::whereIn('name', $this->permissions)->pluck('id'));
    }
}
