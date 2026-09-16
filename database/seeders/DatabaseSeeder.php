<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UsersSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(EtablissementFoundationSeeder::class);
        $this->call(UsersSeeder::class);
        $this->call(PermissionSeeder::class);
        // $this->call(MotifsRdvPermissionsSeeder::class);
        // $this->call(PilierABPermissionsSeeder::class);
        // $this->call(PermissionSeeder::class);
    }
}
