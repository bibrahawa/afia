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
        $adminRole  = Role::find(1);
        $medecinRole = Role::create(['name' => 'medecin']);
        $patientRole = Role::create(['name' => 'patiente']);
        $acceuilRole = Role::create(['name' => 'accueil']);

        $userAdmin = User::create([
            'name'=> 'Admin',
            'status' => true,
            'email' => 'admin@aprosafe.com',
            'password' => 'Admin@01',
        ]);

        Department::create([
            'name' => 'GYNECOLOGIE',
        ]);

        Employee::create([
            'first_name'=> 'Admin',
            'last_name'=> 'Admin',
            'user_id' => $userAdmin->id,
            'address' => 'Conakry, Guinea',
            'phone' => '+224625476844',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'working_day' => 'Lundi,Mardi,Mercredi,Jeudi,Vendredi',
            'in_time' => '08:00',
            'out_time' => '20:00',
            'type' => 'Docteur',
            'department_id' => 1,
        ]);

        $userMedecin = User::create([
            'name' => 'binta',
            'status' => true,
            'email' => 'binta@aprosafe.com',
            'password' => 'binta@01',
        ]);

        Employee::create([
            'first_name'=> 'Fatoumata Binta',
            'last_name'=> 'Diallo',
            'user_id' => $userMedecin->id,
            'address' => 'Conakry, Guinea',
            'phone' => '+224625476844',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'working_day' => 'Lundi,Mardi,Mercredi,Jeudi,Vendredi',
            'in_time' => '08:00',
            'out_time' => '20:00',
            'type' => 'Docteur',
            'department_id' => 1,
        ]);

        $userAcceuil = User::create([
            'name' => 'secretaire',
            'status' => true,
            'email' => 'secretaire@aprosafe.com',
            'password' => 'secretaire@01',
        ]);

        Employee::create([
            'first_name'=> 'Secretaire',
            'last_name'=> 'Aprosafe',
            'user_id' => $userAcceuil->id,
            'address' => 'Conakry, Guinea',
            'phone' => '+224625476844',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'working_day' => 'Lundi,Mardi,Mercredi,Jeudi,Vendredi, Samedi',
            'in_time' => '08:00',
            'out_time' => '20:00',
            'type' => 'Secretaire',
            'department_id' => 1,
        ]);

        $userAdmin->assignRole($adminRole);
        $userMedecin->assignRole($medecinRole);
        $userAcceuil->assignRole($acceuilRole);

    }
}
