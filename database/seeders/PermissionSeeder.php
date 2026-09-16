<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::where('name', 'admin')->first();
        $medecinRole = Role::where('name', 'medecin')->first();
        $comptableRole = Role::where('name', 'comptable')->first();
        $secretaireRole = Role::where('name', 'secretaire')->first();

        // NOUVEAU — manquait entièrement : sans ces permissions, même
        // l'admin n'avait accès à aucune des pages construites pendant la
        // refonte (établissements, modules, motifs de rdv, consentement).
        $nouvellesPermissions = [
            'etablissement.view', 'etablissement.create', 'etablissement.edit',
            'etablissement.delete', 'etablissement.licence',
            'module.view', 'module.create', 'module.edit', 'module.delete',
            'motif_rdv.view', 'motif_rdv.create', 'motif_rdv.edit', 'motif_rdv.delete',
            'consentement.demander', 'consentement.revoquer',
        ];

        $adminPermissions = array_merge([
            'dashboard.view', 'dashboard.medecin', 'backup.access', 'setting.access',
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.disable', 'users.permissions', 'users.change_password',
            'hospital.update', 'tax.update', 'config.update',
            'employee.view', 'employee.create', 'employee.edit', 'employee.delete', 'employee.profile',
            'medecin.appointments', 'medecin.confirm_appointment', 'medecin.complete_appointment', 'medecin.availabilities', 'medecin.leaves',
            'department.view', 'department.create', 'department.edit', 'department.delete',
            'service.view', 'service.create', 'service.edit', 'service.delete',
            'patient.view', 'patient.create', 'patient.edit', 'patient.delete', 'patient.add_file',
            'consultation.view', 'consultation.create', 'consultation.edit', 'consultation.delete', 'consultation.facturer',
            'consultation.facture', 'consultation.ordonnance', 'consultation.medicament', 'consultation.paiement', 'consultation.examen',
            'appointment.view', 'appointment.create', 'appointment.edit', 'appointment.delete',
            'medicament.view', 'medicament.create', 'medicament.edit', 'medicament.delete',
            'package.view', 'package.create', 'package.edit', 'package.delete', 'package.sale',
            'test.view', 'test.create', 'test.edit', 'test.delete', 'test.status',
            'account.facture', 'account.payer', 'account.service_report', 'account.opd_report', 'account.package_report',
            'report.view', 'report.actes', 'report.service',
            'hospitalisation.view', 'hospitalisation.create', 'hospitalisation.edit', 'hospitalisation.delete', 'hospitalisation.payer', 'hospitalisation.facture',
            'chambre.view', 'chambre.create', 'chambre.edit', 'chambre.delete',
            'insurance_company.view', 'insurance_company.create', 'insurance_company.edit', 'insurance_company.delete',
            'insurance_coverage.view', 'insurance_coverage.create', 'insurance_coverage.edit', 'insurance_coverage.delete',
            'patient_insurance.view', 'patient_insurance.create', 'patient_insurance.edit', 'patient_insurance.delete',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
            'invoice_item.view', 'invoice_item.create', 'invoice_item.edit', 'invoice_item.delete',
            'payment.view', 'payment.process', 'payment.calculate', 'payment.hospitalisation',
            'insurance_balance.view', 'insurance_balance.show', 'insurance_balance.payment', 'insurance_balance.export',
        ], $nouvellesPermissions);

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

        $comptablePermissions = [
            'dashboard.view',
            'patient.view',
            'consultation.view', 'consultation.facturer', 'consultation.facture', 'consultation.paiement',
            'account.facture', 'account.payer', 'account.service_report', 'account.opd_report', 'account.package_report',
            'report.view', 'report.actes', 'report.service',
            'hospitalisation.view', 'hospitalisation.payer', 'hospitalisation.facture',
            'package.view', 'package.sale',
            'medicament.view', 'service.view', 'test.view',
            'insurance_company.view', 'insurance_coverage.view',
            'patient_insurance.view', 'patient_insurance.create', 'patient_insurance.edit',
            'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
            'invoice_item.view', 'invoice_item.create', 'invoice_item.edit', 'invoice_item.delete',
            'payment.view', 'payment.process', 'payment.calculate', 'payment.hospitalisation',
            'insurance_balance.view', 'insurance_balance.show', 'insurance_balance.payment', 'insurance_balance.export',
        ];

        // NOUVEAU : la secrétaire est celle qui prend les rdv pour les
        // patients au téléphone (storeParStaff) — sans motif_rdv.view et
        // employee.edit, le formulaire "Nouveau rendez-vous" resterait
        // inutilisable pour elle (motifs jamais chargés).
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
            'motif_rdv.view',
        ];

        // Union de toutes les listes : garantit qu'aucune permission
        // utilisée par syncPermissions() plus bas ne manque à l'appel (ce
        // qui ferait échouer syncPermissions avec une exception
        // PermissionDoesNotExist côté Spatie). firstOrCreate plutôt que
        // create : rejouer ce seeder ne doit jamais planter sur une
        // contrainte d'unicité si les permissions existent déjà.
        $toutesLesPermissions = array_unique(array_merge(
            $adminPermissions, $medecinPermissions, $comptablePermissions, $secretairePermissions
        ));

        foreach ($toutesLesPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole->syncPermissions($adminPermissions);
        $medecinRole->syncPermissions($medecinPermissions);
        $comptableRole->syncPermissions($comptablePermissions);
        $secretaireRole->syncPermissions($secretairePermissions);
    }
}
