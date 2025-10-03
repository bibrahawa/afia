<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\HospitalsTableSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UsersSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UsersSeeder::class);
        $this->call(HospitalsTableSeeder::class);
        // $this->call(PermissionSeeder::class);
    }
}
