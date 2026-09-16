<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class MotifsRdvPermissionsSeeder extends Seeder
{
    protected array $permissions = [
        'motif_rdv.view', 'motif_rdv.create', 'motif_rdv.edit', 'motif_rdv.delete',
    ];

    public function run(): void
    {
        foreach ($this->permissions as $nom) {
            Permission::firstOrCreate(['name' => $nom]);
        }
    }
}
