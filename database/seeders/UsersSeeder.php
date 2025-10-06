<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // IMPORTANT : Effacer le cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        echo "🧹 Nettoyage des données existantes...\n";
        
        // Nettoyer les tables dans le bon ordre
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('model_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        echo "✅ Tables nettoyées\n\n";
        
        // ==========================================
        // CRÉER LES RÔLES
        // ==========================================
        echo "📋 Création des rôles...\n";
        
        Role::create(['name' => 'patient', 'guard_name' => 'web']);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $medecinRole = Role::create(['name' => 'medecin', 'guard_name' => 'web']);
        $comptableRole = Role::create(['name' => 'comptable', 'guard_name' => 'web']);
        $secretaireRole = Role::create(['name' => 'secretaire', 'guard_name' => 'web']);
        
        echo "  ✓ Admin\n";
        echo "  ✓ Médecin\n";
        echo "  ✓ Comptable\n";
        echo "  ✓ Secrétaire\n\n";
        
        // ==========================================
        // CRÉER LES PERMISSIONS
        // ==========================================
        echo "🔑 Création des permissions...\n";
        
        $permissions = [
            'dashboard.view', 'dashboard.medecin', 'backup.access', 'setting.access',
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.disable', 
            'users.permissions', 'users.change_password',
            'hospital.update', 'tax.update', 'config.update',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete', 'employee.profile',
            'medecin.appointments', 'medecin.confirm_appointment', 'medecin.complete_appointment', 
            'medecin.availabilities', 'medecin.leaves',
            'department.view', 'department.create', 'department.edit', 'department.delete',
            'service.view', 'service.create', 'service.edit', 'service.delete',
            'patient.view', 'patient.create', 'patient.edit', 'patient.delete', 'patient.add_file',
            'consultation.view', 'consultation.create', 'consultation.edit', 'consultation.delete', 
            'consultation.facturer', 'consultation.facture', 'consultation.ordonnance', 
            'consultation.medicament', 'consultation.paiement', 'consultation.examen',
            'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete',
            'medicament.view', 'medicament.create', 'medicament.edit', 'medicament.delete',
            'package.view', 'package.create', 'package.edit', 'package.delete', 'package.sale',
            'test.view', 'test.create', 'test.edit', 'test.delete', 'test.status',
            'account.facture', 'account.payer', 'account.service_report', 'account.opd_report', 
            'account.package_report',
            'report.view', 'report.actes', 'report.service',
            'hospitalisation.view', 'hospitalisation.create', 'hospitalisation.edit', 
            'hospitalisation.delete', 'hospitalisation.payer', 'hospitalisation.facture',
            'chambre.view', 'chambre.create', 'chambre.edit', 'chambre.delete',
            'insurance_company.view', 'insurance_company.create', 'insurance_company.edit', 
            'insurance_company.delete',
            'insurance_coverage.view', 'insurance_coverage.create', 'insurance_coverage.edit', 
            'insurance_coverage.delete',
            'patient_insurance.view', 'patient_insurance.create', 'patient_insurance.edit', 
            'patient_insurance.delete',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
            'invoice_item.view', 'invoice_item.create', 'invoice_item.edit', 'invoice_item.delete',
            'payment.view', 'payment.process', 'payment.calculate', 'payment.hospitalisation',
            'insurance_balance.view', 'insurance_balance.show', 'insurance_balance.payment', 
            'insurance_balance.export',
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
        
        echo "  ✓ " . count($permissions) . " permissions créées\n\n";
        
        // ==========================================
        // ASSIGNER LES PERMISSIONS AUX RÔLES
        // ==========================================
        echo "🔗 Attribution des permissions aux rôles...\n";
        
        // ADMIN - Toutes les permissions
        $adminRole->givePermissionTo(Permission::all());
        echo "  ✓ Admin: " . Permission::count() . " permissions\n";
        
        // MÉDECIN
        $medecinPermissions = [
            'dashboard.view', 'dashboard.medecin',
            'medecin.appointments', 'medecin.confirm_appointment', 'medecin.complete_appointment', 
            'medecin.availabilities', 'medecin.leaves',
            'patient.view', 'patient.add_file',
            'consultation.view', 'consultation.create', 'consultation.edit', 'consultation.delete', 
            'consultation.facture', 'consultation.ordonnance', 'consultation.medicament', 'consultation.examen',
            'appointment.view',
            'medicament.view',
            'test.view', 'test.status',
            'hospitalisation.view', 'hospitalisation.create', 'hospitalisation.edit',
            'department.view', 'service.view',
            'chambre.view',
            'patient_insurance.view',
        ];
        $medecinRole->givePermissionTo($medecinPermissions);
        echo "  ✓ Médecin: " . count($medecinPermissions) . " permissions\n";
        
        // COMPTABLE
        $comptablePermissions = [
            'dashboard.view',
            'patient.view',
            'consultation.view', 'consultation.facturer', 'consultation.facture', 'consultation.paiement',
            'account.facture', 'account.payer', 'account.service_report', 'account.opd_report', 
            'account.package_report',
            'report.view', 'report.actes', 'report.service',
            'hospitalisation.view', 'hospitalisation.payer', 'hospitalisation.facture',
            'package.view', 'package.sale',
            'medicament.view', 'service.view', 'test.view',
            'insurance_company.view', 'insurance_coverage.view',
            'patient_insurance.view', 'patient_insurance.create', 'patient_insurance.edit',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
            'invoice_item.view', 'invoice_item.create', 'invoice_item.edit', 'invoice_item.delete',
            'payment.view', 'payment.process', 'payment.calculate', 'payment.hospitalisation',
            'insurance_balance.view', 'insurance_balance.show', 'insurance_balance.payment', 
            'insurance_balance.export',
        ];
        $comptableRole->givePermissionTo($comptablePermissions);
        echo "  ✓ Comptable: " . count($comptablePermissions) . " permissions\n";
        
        // SECRÉTAIRE
        $secretairePermissions = [
            'dashboard.view',
            'patient.view', 'patient.create', 'patient.edit', 'patient.add_file',
            'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete',
            'consultation.view', 'consultation.create',
            'department.view', 'service.view',
            'employee.view', 'employee.profile',
            'medicament.view',
            'package.view', 'package.sale',
            'test.view',
            'hospitalisation.view', 'hospitalisation.create',
            'chambre.view',
            'insurance_company.view', 'insurance_coverage.view', 'patient_insurance.view',
            'payment.view', 'payment.calculate',
        ];
        $secretaireRole->givePermissionTo($secretairePermissions);
        echo "  ✓ Secrétaire: " . count($secretairePermissions) . " permissions\n\n";
        
        // ==========================================
        // CRÉER DES UTILISATEURS DE TEST
        // ==========================================
        echo "👥 Création des utilisateurs de test...\n";
        
        $admin = User::firstOrCreate([
            'name'=> 'Admin',
            'email' => 'admin@aprosafe.com',
            'phone' => '622099672',
            'password' => 'Admin@01',
        ]);
        
        // Supprimer tous les rôles existants avant d'en assigner
        DB::table('model_has_roles')->where('model_id', $admin->id)->delete();
        $admin->assignRole('admin');
        echo "  ✓ admin (password: password)\n";
        
        // Médecin
        $medecin = User::firstOrCreate([
            'name' => 'Medecin',
            'phone' => '625476844',
            'email' => 'binta@aprosafe.com',
            'password' => 'binta@01',
        ]);

        DB::table('model_has_roles')->where('model_id', $medecin->id)->delete();
        $medecin->assignRole('medecin');
        echo "  ✓ medecin (password: password)\n";
        
        // Comptable
        $comptable = User::firstOrCreate([
            'name' => 'comptable',
            'phone' => '625000000',
            'email' => 'comptable@aprosafe.com',
            'password' => 'comptable@01',
        ]);
        
        DB::table('model_has_roles')->where('model_id', $comptable->id)->delete();
        $comptable->assignRole('comptable');
        echo "  ✓ comptable (password: password)\n";
        
        // Secrétaire
        $secretaire = User::updateOrCreate([
            'name' => 'secretaire',  
            'phone' => '626000000',
            'email' => 'secretaire@aprosafe.com',
            'password' => 'secretaire@01',
        ]);

        DB::table('model_has_roles')->where('model_id', $secretaire->id)->delete();
        $secretaire->assignRole('secretaire');


        $department = Department::updateOrCreate([
            'name' => 'GYNECOLOGIE',
        ]);

        Employee::updateOrCreate([
            'first_name'=> 'Admin',
            'last_name'=> 'Admin',
            'user_id' => $admin->id,
            'address' => 'Conakry, Guinea',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'type' => 'admin',
            'department_id' => $department->id,
        ]);

        Employee::updateOrCreate([
            'first_name'=> 'Fatoumata Binta',
            'last_name'=> 'Diallo',
            'user_id' => $medecin->id,
            'address' => 'Conakry, Guinea',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'type' => 'medecin',
            'department_id' => $department->id,
        ]);

        Employee::updateOrCreate([
            'first_name'=> 'comptable',
            'last_name'=> 'Aprosafe',
            'user_id' => $comptable->id,
            'address' => 'Conakry, Guinea',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'type' => 'comptable',
            'department_id' => $department->id,
        ]);
        
        Employee::updateOrCreate([
            'first_name'=> 'secretaire',
            'last_name'=> 'Aprosafe',
            'user_id' => $secretaire->id,
            'address' => 'Conakry, Guinea',
            'education' => 'MBA',
            'description' => 'Administrator of the system',
            'certificate' => 'Admin Certificate',
            'speciality' => 'Genycologue',
            'type' => 'secretaire',
            'department_id' => $department->id,
        ]);

    }
}
