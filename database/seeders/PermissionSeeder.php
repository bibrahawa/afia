<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * SOURCE DE VÉRITÉ UNIQUE des permissions par rôle.
 *
 * syncPermissions() : chaque rôle reçoit exactement la liste ci-dessous.
 * Toute permission ajoutée à la main dans l'interface et absente d'ici
 * disparaît au prochain lancement — ajoutez-la ici.
 *
 * CORRECTIONS
 * - Les permissions labo sont intégrées : relancer ce seeder ne les retire
 *   plus au rôle admin.
 * - Les permissions PLATEFORME (etablissement.*, module.*) sont réservées au
 *   rôle super-admin. Un admin de clinique qui les avait pouvait gérer tous
 *   les établissements de la plateforme.
 * - Rôles créés s'ils manquent : le seeder peut tourner seul sans planter
 *   sur un rôle null.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $plateforme = [
            'etablissement.view', 'etablissement.create', 'etablissement.edit',
            'etablissement.delete', 'etablissement.licence',
            'module.view', 'module.create', 'module.edit', 'module.delete',
        ];

        $labo = [
            'labo.tableau_bord', 'labo.catalogue.view', 'labo.catalogue.manage',
            'labo.demande.view', 'labo.demande.create', 'labo.demande.cancel',
            'labo.prelevement', 'labo.reception', 'labo.resultat.saisir',
            'labo.validation.technique', 'labo.validation.biologique',
            'labo.compte_rendu.publier', 'labo.compte_rendu.view', 'labo.facturation',
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
            'motif_rdv.view', 'motif_rdv.create', 'motif_rdv.edit', 'motif_rdv.delete',
            'consentement.demander', 'consentement.revoquer',
            'sms.journal', 'sms.renvoyer',
        ], $labo);

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
            'consentement.demander',
            // Le médecin prescrit des analyses et lit les résultats de ses patients.
            'labo.demande.view', 'labo.demande.create', 'labo.compte_rendu.view', 'labo.catalogue.view',
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
            // Encaissement des analyses et remise des résultats réglés.
            'labo.demande.view', 'labo.facturation', 'labo.catalogue.view',
        ];

        // La secrétaire prend les rdv au téléphone et enregistre les demandes d'analyses au guichet.
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
            'labo.demande.view', 'labo.demande.create', 'labo.compte_rendu.view', 'labo.catalogue.view',
            // Journal des SMS : vérifier qu'un patient a reçu son rappel, et le renvoyer.
            'sms.journal', 'sms.renvoyer',
        ];

        // Administrateur PLATEFORME (équipe Aprosafe, compte sans établissement) : tout.
        $superAdminPermissions = array_merge($adminPermissions, $plateforme);

        $toutes = array_unique(array_merge($superAdminPermissions, $medecinPermissions, $comptablePermissions, $secretairePermissions));

        foreach ($toutes as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super-admin' => $superAdminPermissions,
            'admin' => $adminPermissions,
            'medecin' => $medecinPermissions,
            'comptable' => $comptablePermissions,
            'secretaire' => $secretairePermissions,
        ];

        foreach ($roles as $nom => $permissions) {
            Role::firstOrCreate(['name' => $nom, 'guard_name' => 'web'])->syncPermissions($permissions);
        }

        // CORRIGÉ (lot Menu) — syncPermissions() ci-dessus RETIRAIT aux rôles toutes les
        // permissions des modules (accueil et parcours, assurance, caisse, rapports, réseau de
        // laboratoires), car elles ne figurent pas dans les listes ci-dessus. Lancer ce seeder
        // seul (par exemple pour ajouter une permission) privait donc le personnel de ces
        // écrans. Les seeders de modules sont désormais relancés ici : ils ne font qu'AJOUTER
        // des permissions, ce qui rend l'opération sûre quel que soit le point d'entrée.
        foreach ([
            \Database\Seeders\Labo\LaboPermissionsSeeder::class,
            \Database\Seeders\Labo\LaboReseauPermissionsSeeder::class,   // n'était appelé par aucun seeder
            \Database\Seeders\Facturation\FacturationPermissionsSeeder::class,
            \Database\Seeders\Assurance\AssurancePermissionsSeeder::class,
            \Database\Seeders\Parcours\ParcoursPermissionsSeeder::class,
            \Database\Seeders\Rapports\RapportsPermissionsSeeder::class,
        ] as $seederModule) {
            if (class_exists($seederModule)) {
                $this->call($seederModule);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Permissions synchronisées : ' . implode(', ', array_keys($roles)));
    }
}
