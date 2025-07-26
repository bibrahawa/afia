<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
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
        $adminRole  = Role::create(['name' => 'admin']);
        $medecinRole = Role::create(['name' => 'medecin']);
        $comptableRole = Role::create(['name' => 'comptable']);
        $secretaireRole = Role::create(['name' => 'secretaire']);
        $patientRole = Role::create(['name' => 'patient']);

        $userAdmin = User::create([
            'name'=> 'Admin',
            'email' => 'admin@aprosafe.com',
            'phone' => '622099672',
            'password' => 'Admin@01',
        ]);

        $userMedecin = User::create([
            'name' => 'binta',
            'phone' => '625476844',
            'email' => 'binta@aprosafe.com',
            'password' => 'binta@01',
        ]);

        $usercomptable = User::create([
            'name' => 'comptable',
            'phone' => '625000000',
            'email' => 'comptable@aprosafe.com',
            'password' => 'comptable@01',
        ]);

        $userAdmin->assignRole($adminRole);
        $userMedecin->assignRole($medecinRole);
        $usercomptable->assignRole($comptableRole);

        $department = Department::create([
            'name' => 'GYNECOLOGIE',
        ]);

        Employee::create([
            'first_name'=> 'Admin',
            'last_name'=> 'Admin',
            'user_id' => $userAdmin->id,
            'address' => 'Conakry, Guinea',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'type' => 'admin',
            'department_id' => $department->id,
        ]);

        Employee::create([
            'first_name'=> 'Fatoumata Binta',
            'last_name'=> 'Diallo',
            'user_id' => $userMedecin->id,
            'address' => 'Conakry, Guinea',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'type' => 'Docteur',
            'department_id' => $department->id,
        ]);

        Employee::create([
            'first_name'=> 'comptable',
            'last_name'=> 'Aprosafe',
            'user_id' => $usercomptable->id,
            'address' => 'Conakry, Guinea',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'type' => 'comptable',
            'department_id' => $department->id,
        ]);

    }
}
