<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminRole  = Role::create(['name' => 'admin']);
        $medecinRole = Role::create(['name' => 'medecin']);
        $acceuilRole = Role::create(['name' => 'accueil']);
        Role::create(['name' => 'patient']);

        $adminRole->givePermissionTo(Permission::all());

        $userAdmin = User::create([
            'name'=> 'Admin',
            'status' => true,
            'email' => 'admin@aprosafe.com',
            'password' => bcrypt('Admin@01'),
        ]);

        $userMedecin = User::create([
            'name' => 'medecin',
            'status' => true,
            'email' => 'medecin@aprosafe.com',
            'password' => bcrypt('medecin@01'),
        ]);

        $userAcceuil = User::create([
            'name' => 'acceuil',
            'status' => true,
            'email' => 'acceuil@gmail.com',
            'password' => bcrypt('acceuil@01'),
        ]);

        $userAdmin->assignRole($adminRole);
        $userMedecin->assignRole($medecinRole);
        $userAcceuil->assignRole($acceuilRole);
    }
}
