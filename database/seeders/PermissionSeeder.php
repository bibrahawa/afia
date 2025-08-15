<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;

class PermissionSeeder extends Seeder
{
    /**
     * Seed permissions from defined named routes.
     *
     * @return void
     */
    public function run()
    {
    
    // Recuperation des rôles
    $adminRole = Role::where('name', 'admin')->first();
    $medecinRole = Role::where('name', 'medecin')->first();
    $comptableRole = Role::where('name', 'comptable')->first();
    $secretaireRole = Role::where('name', 'secretaire')->first();

    // ADMIN - Toutes les permissions
    $adminPermissions = [
        // Dashboard & Configuration
        'dashboard.view', 'dashboard.medecin', 'backup.access', 'setting.access',
        
        // Utilisateurs (Admin uniquement)
        'users.view', 'users.create', 'users.edit', 'users.delete', 'users.disable', 'users.permissions', 'users.change_password',
        
        // Hôpital (Admin uniquement)
        'hospital.update', 'tax.update', 'config.update',
        
        // Employés
        'employee.view', 'employee.create', 'employee.edit', 'employee.delete', 'employee.profile',
        
        // Médecin
        'medecin.appointments', 'medecin.confirm_appointment', 'medecin.complete_appointment', 'medecin.availabilities', 'medecin.leaves',
        
        // Départements
        'department.view', 'department.create', 'department.edit', 'department.delete',
        
        // Services
        'service.view', 'service.create', 'service.edit', 'service.delete',
        
        // Patients
        'patient.view', 'patient.create', 'patient.edit', 'patient.delete', 'patient.add_file',
        
        // Consultations
        'consultation.view', 'consultation.create', 'consultation.edit', 'consultation.delete', 'consultation.facturer', 
        'consultation.facture', 'consultation.ordonnance', 'consultation.medicament', 'consultation.paiement', 'consultation.examen',
        
        // Rendez-vous
        'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete',
        
        // Médicaments
        'medicament.view', 'medicament.create', 'medicament.edit', 'medicament.delete',
        
        // Packages
        'package.view', 'package.create', 'package.edit', 'package.delete', 'package.sale',
        
        // Tests
        'test.view', 'test.create', 'test.edit', 'test.delete', 'test.status',
        
        // Comptabilité
        'account.facture', 'account.payer', 'account.service_report', 'account.opd_report', 'account.package_report',
        
        // Rapports
        'report.view', 'report.actes', 'report.service',
        
        // Hospitalisations
        'hospitalisation.view', 'hospitalisation.create', 'hospitalisation.edit', 'hospitalisation.delete', 'hospitalisation.payer', 'hospitalisation.facture',
        
        // Chambres
        'chambre.view', 'chambre.create', 'chambre.edit', 'chambre.delete',
        
        // Assurances
        'insurance_company.view', 'insurance_company.create', 'insurance_company.edit', 'insurance_company.delete',
        'insurance_coverage.view', 'insurance_coverage.create', 'insurance_coverage.edit', 'insurance_coverage.delete',
        'patient_insurance.view', 'patient_insurance.create', 'patient_insurance.edit', 'patient_insurance.delete',
        
        // Factures
        'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
        'invoice_item.view', 'invoice_item.create', 'invoice_item.edit', 'invoice_item.delete',
        
        // Paiements
        'payment.view', 'payment.process', 'payment.calculate', 'payment.hospitalisation',
        
        // Soldes Assurance
        'insurance_balance.view', 'insurance_balance.show', 'insurance_balance.payment', 'insurance_balance.export',
    ];

    // MEDECIN - Permissions liées aux soins et consultations
    $medecinPermissions = [
        // Dashboard médecin
        'dashboard.view', 'dashboard.medecin',
        
        // Gestion de ses activités médicales
        'medecin.appointments', 'medecin.confirm_appointment', 'medecin.complete_appointment', 
        'medecin.availabilities', 'medecin.leaves',
        
        // Patients - Lecture et ajout de fichiers
        'patient.view', 'patient.add_file',
        
        // Consultations - Toutes les actions médicales
        'consultation.view', 'consultation.create', 'consultation.edit', 'consultation.delete', 
        'consultation.facture', 'consultation.ordonnance', 'consultation.medicament', 'consultation.examen',
        
        // Rendez-vous - Consulter uniquement
        'appointment.view',
        
        // Médicaments - Consulter pour prescriptions
        'medicament.view',
        
        // Tests - Prescrire et voir résultats
        'test.view', 'test.status',
        
        // Hospitalisations - Médical uniquement
        'hospitalisation.view', 'hospitalisation.create', 'hospitalisation.edit',
        
        // Départements et Services - Lecture
        'department.view', 'service.view',
        
        // Chambres - Consulter disponibilité
        'chambre.view',
        
        // Assurances patients - Lecture
        'patient_insurance.view',
    ];

    // COMPTABLE - Permissions financières et rapports
    $comptablePermissions = [
        // Dashboard
        'dashboard.view',
        
        // Patients - Consultation pour facturation
        'patient.view',
        
        // Consultations - Facturation uniquement
        'consultation.view', 'consultation.facturer', 'consultation.facture', 'consultation.paiement',
        
        // Comptabilité - Toutes les actions financières
        'account.facture', 'account.payer', 'account.service_report', 'account.opd_report', 'account.package_report',
        
        // Rapports - Tous les rapports
        'report.view', 'report.actes', 'report.service',
        
        // Hospitalisations - Facturation
        'hospitalisation.view', 'hospitalisation.payer', 'hospitalisation.facture',
        
        // Packages - Vente
        'package.view', 'package.sale',
        
        // Médicaments - Consultation prix
        'medicament.view',
        
        // Services - Consultation prix
        'service.view',
        
        // Tests - Consultation prix
        'test.view',
        
        // Assurances complètes - Gestion financière
        'insurance_company.view', 'insurance_coverage.view',
        'patient_insurance.view', 'patient_insurance.create', 'patient_insurance.edit',
        
        // Factures assurance - Toutes les actions
        'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
        'invoice_item.view', 'invoice_item.create', 'invoice_item.edit', 'invoice_item.delete',
        
        // Paiements - Toutes les actions
        'payment.view', 'payment.process', 'payment.calculate', 'payment.hospitalisation',
        
        // Soldes Assurance - Toutes les actions
        'insurance_balance.view', 'insurance_balance.show', 'insurance_balance.payment', 'insurance_balance.export',
    ];

    // SECRETAIRE - Permissions administratives et accueil
    $secretairePermissions = [
        // Dashboard
        'dashboard.view',
        
        // Patients - Gestion complète (accueil)
        'patient.view', 'patient.create', 'patient.edit', 'patient.add_file',
        
        // Rendez-vous - Gestion complète
        'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete',
        
        // Consultations - Programmation uniquement
        'consultation.view', 'consultation.create',
        
        // Départements et Services - Lecture
        'department.view', 'service.view',
        
        // Employés - Consultation
        'employee.view', 'employee.profile',
        
        // Médicaments - Consultation
        'medicament.view',
        
        // Packages - Consultation et vente
        'package.view', 'package.sale',
        
        // Tests - Programmation
        'test.view',
        
        // Hospitalisations - Admission
        'hospitalisation.view', 'hospitalisation.create',
        
        // Chambres - Gestion disponibilité
        'chambre.view',
        
        // Assurances patients - Vérification couverture
        'insurance_company.view', 'insurance_coverage.view', 'patient_insurance.view',
        
        // Paiements - Consultation uniquement
        'payment.view', 'payment.calculate',
    ];

    foreach ($adminPermissions as $key => $permission) {
        Permission::create(['name'=>$permission]);
    }

    

    // Attribution des permissions aux rôles
    $adminRole->syncPermissions($adminPermissions);
    $medecinRole->syncPermissions($medecinPermissions);
    $comptableRole->syncPermissions($comptablePermissions);
    $secretaireRole->syncPermissions($secretairePermissions);

    // Fonction helper pour assigner un rôle à un utilisateur
    // function assignRoleToUser($userId, $roleName) {
    //     $user = User::find($userId);
    //     $user->assignRole($roleName);
    // }

    // Exemples d'utilisation dans les contrôleurs

    // Pour vérifier une permission
    // if (auth()->user()->can('patient.create')) { ... }

    // Dans les routes avec middleware
    // Route::get('/patients', [PatientController::class, 'index'])->middleware('can:patient.view');

    // Dans les vues Blade
    // @can('patient.create')
    //     <a href="{{ route('patient.create') }}">Nouveau Patient</a>
    // @endcan

        /*
        RÉSUMÉ DES RÔLES:

        ADMIN (89 permissions)
        - Accès total à toutes les fonctionnalités
        - Gestion des utilisateurs et configuration
        - Supervision complète

        MEDECIN (28 permissions)
        - Focus sur les activités médicales
        - Consultations, diagnostics, prescriptions
        - Gestion de son planning

        COMPTABLE (35 permissions)  
        - Focus sur les aspects financiers
        - Facturation, paiements, rapports
        - Gestion des assurances

        SECRETAIRE (23 permissions)
        - Focus sur l'accueil et administration
        - Gestion des patients et RDV
        - Support administratif
        */
    }

    /**
      * Vérifie si une route utilise le middleware d'authentification.
      *
      * @param mixed $middleware Le middleware ou tableau de middlewares
      * @return bool
      */

}
