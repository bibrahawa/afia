<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminRole  = Role::find(1);
        $medecinRole = Role::create(['name' => 'medecin']);
        $acceuilRole = Role::create(['name' => 'accueil']);

        $userAdmin = User::create([
            'name'=> 'Admin',
            'status' => true,
            'email' => 'admin@aprosafe.com',
            'password' => 'Admin@01',
        ]);

        // $employee = Employee::create([
        //     'first_name'=> 'Admin',
        //     'last_name'=> 'Admin',
        //     'status' => true,
        //     'email' => 'admin@aprosafe.com',
        //     'password' => 'Admin@01',
        // ]);

        $userMedecin = User::create([
            'name' => 'binta',
            'status' => true,
            'email' => 'binta@aprosafe.com',
            'password' => 'binta@01',
        ]);

        $userAcceuil = User::create([
            'name' => 'secretaire',
            'status' => true,
            'email' => 'secretaire@aprosafe.com',
            'password' => 'secretaire@01',
        ]);

        $userAdmin->assignRole($adminRole);
        $userMedecin->assignRole($medecinRole);
        $userAcceuil->assignRole($acceuilRole);

    }
}
