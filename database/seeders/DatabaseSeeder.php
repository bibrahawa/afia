<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\HospitalsTableSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\UsersSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(HospitalsTableSeeder::class);
        $this->call(PermissionSeeder::class);
        // $this->call(RolesSeeder::class);
        $this->call(UsersSeeder::class);
    }
}
