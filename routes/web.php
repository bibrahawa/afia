<?php

/*
| ROUTES RETIRÉES le 2026-10-10 : elles pointaient vers des méthodes qui
| n'existent plus (erreur 500 à l'appel). Contrôle : scripts/inventaire-code-inutilise.sh
|
|   sms/send-bulk, users/{user}/show, index-permissions, users/{user}/edit,
|   user/edit, user/delete, change/password, patient/create,
|   medicaments/{medicament} (show), service/report, chambres/{chambre} (show)
|
| À réintroduire seulement avec la méthode correspondante dans le contrôleur.
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController, HospitalController, UserController,
    DepartmentController, ServiceController, EmployeeController,
    PatientController, AppointmentController, PackageController,
    TestController, ReportController, AccountController, AuthController,
    ProfileController, ConsultationController, MedicamentController,
    HospitalisationController, ChambreController, InsuranceCompanyController,
    InsuranceCoverageController, PatientInsuranceController,
    PaymentController,
    AppointmentExportController, DoctorAppointmentController, DoctorAvailabilityController, DoctorLeaveController, DoctorBreakController,
    // Ajoutés par la refonte multi-tenant / rdv / consentement :
    ComptePatientController, ConsentementController, DisponibiliteController,
    EtablissementController, LienCourtController, MedecinMotifController, ModuleController,
    MotifRdvController, PatientAuthController, PortailPatientController, RelationFamilialeController
};

/*
|==============================================================================
| PUBLIC — prise de rendez-vous, sans authentification
|==============================================================================
| REMPLACE l'ancienne ligne unique `Route::get('/', ...)->name('rdv')` : elle
| ne portait aucun contexte d'établissement, ce qui mélangeait les données de
| toutes les cliniques sur une page censée être unique par clinique.
*/
Route::prefix('rdv/{etablissement:slug}')
    ->middleware(['etablissement', 'throttle:60,1'])
    ->group(function () {
        

        Route::get('/', [AppointmentController::class, 'makeAppointment'])->name('rdv');
        Route::get('/l/{code}', [LienCourtController::class, 'rediriger'])->name('lien-court');

        Route::match(['get', 'post'], 'annulation/{appointment}', [AppointmentController::class, 'gererAnnulationNonReconnue'])
                ->name('rdv-public.annulation');

        Route::prefix('api')->name('rdv-public.')->group(function () {
            Route::get('motifs', [AppointmentController::class, 'getMotifs'])->name('motifs');
            Route::get('motifs/{motifRdv}/medecins', [AppointmentController::class, 'getProfessionalsForMotif'])->name('medecins');
            Route::get('dates-disponibles', [AppointmentController::class, 'getAvailableDates'])->name('dates');
            Route::get('creneaux', [DisponibiliteController::class, 'creneaux'])->name('creneaux');
            Route::get('creneaux-tous-medecins', [DisponibiliteController::class, 'creneauxTousMedecins'])->name('creneaux-tous');
            Route::post('verifier-patient', [AppointmentController::class, 'checkPatient'])->name('verifier-patient');
            Route::post('envoyer-code-rdv', [AppointmentController::class, 'envoyerCodeRdv'])->name('envoyer-code-rdv');
            Route::post('verifier-code-rdv', [AppointmentController::class, 'verifierCodeRdv'])->name('verifier-code-rdv');
            Route::post('creer-patient', [PatientController::class, 'storeRapide'])->name('creer-patient');
            Route::post('prendre', [AppointmentController::class, 'store'])->name('prendre');
        });
    });

// Connexion patient par OTP — un compte est unique sur toute la plateforme,
// pas de scoping établissement ici.
Route::prefix('mon-compte')->name('patient-auth.')->middleware('throttle:60,1')->group(function () {
    Route::get('connexion', [PatientAuthController::class, 'afficherConnexion'])->name('connexion');
    Route::post('envoyer-code', [PatientAuthController::class, 'envoyerCode'])->name('envoyer-code');
    Route::post('verifier-code', [PatientAuthController::class, 'verifierCode'])->name('verifier-code');
    Route::post('deconnexion', [PatientAuthController::class, 'deconnexion'])->name('deconnexion');
});

// Portail patient (authentifié, guard 'patient' — jamais 'auth' staff).
Route::middleware('auth:patient')->prefix('portail')->name('portail.')->group(function () {
    Route::get('/', [PortailPatientController::class, 'index'])->name('index');
    Route::get('{patient}', [PortailPatientController::class, 'dossier'])->name('dossier');
    Route::post('rendez-vous/{appointment}/annuler', [PortailPatientController::class, 'annulerRdv'])->name('rdv.annuler');
    Route::delete('consentement/{consentement}/revoquer', [PortailPatientController::class, 'revoquerAcces'])->name('consentement.revoquer');
});

Route::middleware('guest')->group(function () {
    Route::view('login', 'auth.login');
    Route::post('login', [AuthController::class, 'login'])->name('login');

    // Lot R2 : « Mot de passe oublié ? » du personnel, par code SMS
    // (le lien apparaît sur la page de connexion dès que password.request existe).
    Route::controller(\App\Http\Controllers\Auth\MotDePasseOublieController::class)->prefix('mot-de-passe-oublie')->group(function () {
        Route::get('/', 'demande')->name('password.request');
        Route::post('/', 'envoyer')->middleware('throttle:10,1')->name('password.email');
        Route::get('code', 'formulaireCode')->name('password.code');
        Route::post('code', 'reinitialiser')->middleware('throttle:20,1')->name('password.update');
    });
});


Route::middleware('auth')->group(function () {

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    /*
    | SMS — DÉSACTIVÉ (sécurité), en attente d'un vrai module de journal des SMS.
    |   - /sms/lists affichait TOUS les SMS du compte fournisseur partagé : ceux de
    |     toutes les cliniques et d'autres activités (OTP, débits de portefeuille…),
    |     à tout utilisateur connecté ;
    |   - POST /sms/send ignorait le formulaire et envoyait un SMS de test à un
    |     numéro écrit en dur ;
    |   - /sms/send appelait une route supprimée (erreur 500) et /sms-report une vue
    |     inexistante (erreur 500).
    | Les SMS métier (rappels de rendez-vous, résultats, codes du portail) ne passent
    | pas par ces routes et continuent de fonctionner.
    |
    | Route::get('/sms/lists', [SmsController::class, 'smsLists'])->name('sms.lists');
    | Route::get('/sms/send', [SmsController::class, 'newSms'])->name('sms.new');
    | Route::post('/sms/send', [SmsController::class, 'send'])->name('sms.send');
    | Route::get('/sms-report', [SmsReportController::class, 'index'])->name('admin.sms-report');
    | Route::post('/sms-report/resend-failed', [SmsReportController::class, 'resendFailed']);
    */

    // Cloche : alertes calculées (lot S4), rafraîchies par la barre du haut.
    Route::get('cloche', \App\Http\Controllers\ClocheController::class)
        ->middleware('throttle:30,1')
        ->name('cloche');

    // Journal des SMS de la clinique (lot S1) : remplace les écrans désactivés ci-dessus.
    // Affiche et cartes de prise de rendez-vous avec QR code (lot S2).
    Route::get('rendez-vous/affiche', \App\Http\Controllers\AfficheRdvController::class)
        ->middleware('permission:appointment.view')
        ->name('rdv.affiche');

    Route::get('sms/journal', [\App\Http\Controllers\Sms\JournalSmsController::class, 'index'])
        ->middleware('permission:sms.journal')
        ->name('sms.journal.index');
    Route::post('sms/journal/{journal}/renvoyer', [\App\Http\Controllers\Sms\JournalSmsController::class, 'renvoyer'])
        ->whereNumber('journal')
        ->middleware(['permission:sms.renvoyer', 'throttle:20,1'])
        ->name('sms.journal.renvoyer');

    /*
    |--------------------------------------------------------------------------
    | RENDEZ-VOUS (ADMIN / STAFF)
    |--------------------------------------------------------------------------
    | appointment.create et appointment.edit RETIRÉS : ils servaient l'ancien
    | formulaire à créneaux fixes, devenu obsolète. appointment.store pointe
    | maintenant vers storeParStaff() — la création publique (store()) exige
    | désormais une vérification OTP du patient, incompatible avec un usage
    | par le personnel.
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:appointment.view')->group(function () {
        Route::get('appointment', [AppointmentController::class, 'index'])->name('appointment.index');
        Route::get('appointment/{appointment}', [AppointmentController::class, 'show'])->name('appointment.show');
    });

    Route::post('appointment', [AppointmentController::class, 'storeParStaff'])
        ->middleware('permission:appointment.create')
        ->name('appointment.store');

    Route::put('appointment/{appointment}', [AppointmentController::class, 'update'])
        ->middleware('permission:appointment.edit')
        ->name('appointment.update');

    Route::delete('appointment/{appointment}/cancel', [AppointmentController::class, 'cancel'])
        ->middleware('permission:appointment.edit')
        ->name('appointment.cancel');

    Route::get('appointments/export-pdf', [AppointmentExportController::class, 'exportPdf'])
        ->middleware('permission:appointment.view')
        ->name('appointments.export-pdf');

    Route::put('appointment/{appointment}/reprogrammer', [AppointmentController::class, 'reprogrammer'])
        ->middleware('permission:appointment.edit')
        ->name('appointment.reprogrammer');

    // Formulaire "Nouveau rendez-vous" (réception) — mêmes données que le
    // parcours public, sans étape OTP (personnel déjà authentifié).
    Route::middleware('permission:appointment.create')->prefix('appointment-form')->name('appointment-form.')->group(function () {
        Route::get('motifs', [AppointmentController::class, 'staffMotifs'])->name('motifs');
        Route::get('motifs/{motifRdv}/medecins', [AppointmentController::class, 'staffMedecins'])->name('medecins');
        Route::get('dates-disponibles', [AppointmentController::class, 'staffDatesDisponibles'])->name('dates');
        Route::get('creneaux', [AppointmentController::class, 'staffCreneaux'])->name('creneaux');
        Route::get('patients/recherche', [PatientController::class, 'rechercheRapide'])->name('patients.recherche');
        Route::post('patients', [PatientController::class, 'storeRapideStaff'])->name('patients.store');
    });

    /*
    |--------------------------------------------------------------------------
    | MÉDECIN
    |--------------------------------------------------------------------------
    | Routes inchangées — les contrôleurs/services sous-jacents ont été
    | corrigés (scoping par médecin réactivé, autorisations réactivées,
    | bookkeeping de créneaux obsolète retiré), rien à changer ici.
    |--------------------------------------------------------------------------
    */

    Route::prefix('medecin')->name('medecin.')->group(function () {

        Route::get('appointments', [DoctorAppointmentController::class, 'index'])
            ->middleware('permission:medecin.appointments')
            ->name('appointments');

        Route::post('appointments/{appointment}/confirm', [DoctorAppointmentController::class, 'confirm'])
            ->middleware('permission:medecin.confirm_appointment')
            ->name('appointments.confirm');

        Route::post('appointments/{appointment}/complete', [DoctorAppointmentController::class, 'complete'])
            ->middleware('permission:medecin.complete_appointment')
            ->name('appointments.complete');

        Route::delete('appointments/{appointment}/cancel', [DoctorAppointmentController::class, 'cancel'])
            ->middleware('permission:medecin.confirm_appointment')
            ->name('appointments.cancel');

        Route::get('availabilities', [DoctorAvailabilityController::class, 'index'])
            ->middleware('permission:medecin.availabilities')
            ->name('availabilities.index');

        Route::post('availabilities', [DoctorAvailabilityController::class, 'store'])
            ->middleware('permission:medecin.availabilities')
            ->name('availabilities.store');

        Route::put('availabilities/{availability}', [DoctorAvailabilityController::class, 'update'])
            ->middleware('permission:medecin.availabilities')
            ->name('availabilities.update');

        Route::delete('availabilities/{availability}', [DoctorAvailabilityController::class, 'destroy'])
            ->middleware('permission:medecin.availabilities')
            ->name('availabilities.destroy');

        Route::get('leaves', [DoctorLeaveController::class, 'index'])
            ->middleware('permission:medecin.leaves')
            ->name('leaves.index');

        Route::post('leaves', [DoctorLeaveController::class, 'store'])
            ->middleware('permission:medecin.leaves')
            ->name('leaves.store');

        Route::put('leaves/{leave}', [DoctorLeaveController::class, 'update'])
            ->middleware('permission:medecin.leaves')
            ->name('leaves.update');

        Route::delete('leaves/{leave}', [DoctorLeaveController::class, 'destroy'])
            ->middleware('permission:medecin.leaves')
            ->name('leaves.destroy');

        Route::get('breaks', [DoctorBreakController::class, 'index'])
            ->middleware('permission:medecin.leaves')
            ->name('breaks.index');

        Route::post('breaks', [DoctorBreakController::class, 'store'])
            ->middleware('permission:medecin.leaves')
            ->name('breaks.store');

        Route::put('breaks/{break}', [DoctorBreakController::class, 'update'])
            ->middleware('permission:medecin.leaves')
            ->name('breaks.update');

        Route::delete('breaks/{break}', [DoctorBreakController::class, 'destroy'])
            ->middleware('permission:medecin.leaves')
            ->name('breaks.destroy');

        Route::patch('breaks/{break}/toggle', [DoctorBreakController::class, 'toggle'])
            ->middleware('permission:medecin.leaves')
            ->name('breaks.toggle');
    });

    // Dashboard
    Route::get('/home', [DashboardController::class, 'admin'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('backup', [HospitalController::class, 'backup'])
        ->middleware('permission:backup.access')
        ->name('hospital.backup');

    Route::get('setting', [HospitalController::class, 'setting'])
        ->middleware('permission:setting.access')
        ->name('hospital.setting');

    /*
    |--------------------------------------------------------------------------
    | ÉTABLISSEMENTS & LICENCES (NOUVEAU — Pilier A, multi-tenant)
    |--------------------------------------------------------------------------
    */
    Route::middleware('permission:etablissement.view')->group(function () {
        Route::get('etablissements', [EtablissementController::class, 'getIndex'])->name('etablissement.index');
        Route::get('etablissements/{etablissement}/modules', [EtablissementController::class, 'modules'])
            ->middleware('permission:etablissement.licence')
            ->name('etablissement.modules');
    });

    Route::post('etablissements', [EtablissementController::class, 'store'])
        ->middleware('permission:etablissement.create')
        ->name('etablissement.add');

    Route::put('etablissements/{etablissement}', [EtablissementController::class, 'update'])
        ->middleware('permission:etablissement.edit')
        ->name('etablissement.update');

    Route::delete('etablissements/{etablissement}', [EtablissementController::class, 'delete'])
        ->middleware('permission:etablissement.delete')
        ->name('etablissement.delete');

    Route::post('etablissements/{etablissement}/modules', [EtablissementController::class, 'syncModules'])
        ->middleware('permission:etablissement.licence')
        ->name('etablissement.modules.sync');

    Route::prefix('modules')->name('module.')->middleware('permission:module.view')->group(function () {
        Route::get('/', [ModuleController::class, 'getIndex'])->name('index');
        Route::post('/', [ModuleController::class, 'store'])->middleware('permission:module.create')->name('add');
        Route::put('{module}', [ModuleController::class, 'update'])->middleware('permission:module.edit')->name('update');
        Route::delete('{module}', [ModuleController::class, 'delete'])->middleware('permission:module.delete')->name('delete');
    });

    // ============================================
    // GESTION UTILISATEURS (Admin uniquement)
    // ============================================
    Route::middleware('permission:users.view')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');

        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
        });
    });


    Route::get('liste-permissions/{id}', [UserController::class, 'listePermissions'])
            ->middleware('permission:users.permissions')
            ->name('users.listePermissions');

    Route::get('users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create')
        ->name('users.create');

    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');


    Route::put('users/{user}/update', [UserController::class, 'update'])
        ->middleware('permission:users.edit')
        ->name('users.update');

    Route::delete('users/{user}/destroy', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');



    // Lot E1 : PATCH (jeton CSRF) au lieu d'un simple lien GET.
    Route::patch('users/{id}/statut', [UserController::class, 'disableUser'])
        ->whereNumber('id')
        ->middleware('permission:users.disable')
        ->name('user.disable');

    // Lot E1 : accès à Hali géré depuis la fiche du personnel.
    Route::prefix('employee/{employee}/acces')->whereNumber('employee')->controller(EmployeeController::class)->name('employee.acces.')->group(function () {
        Route::post('/', 'creerAcces')->middleware('permission:users.create')->name('creer');
        Route::put('/', 'modifierAcces')->middleware('permission:users.edit')->name('modifier');
        Route::post('mot-de-passe', 'reinitialiserAcces')->middleware(['permission:users.change_password', 'throttle:10,1'])->name('mot-de-passe');
        Route::patch('statut', 'basculerAcces')->middleware('permission:users.disable')->name('statut');
    });

    Route::post('assign-permissions/{id}', [UserController::class, 'assignPermissions'])
        ->middleware('permission:users.permissions')
        ->name('users.store_permissions');


    // Lot 2c : l'ancien enregistrement de règlement (facture par facture) est remplacé par
    // Assurance > Créances (règlement réclamation par réclamation, route assurance.reglements.store).

    // ============================================
    // HÔPITAL CONFIGURATION (Admin uniquement)
    // ============================================
    Route::prefix('hospital')->group(function () {
        Route::post('update/{id}', [HospitalController::class, 'updateHospital'])
            ->middleware('permission:hospital.update')
            ->name('hospital.update');

        Route::post('tax/update/{id}', [HospitalController::class, 'updateTax'])
            ->middleware('permission:tax.update')
            ->name('tax.update');

        Route::post('config/update', [HospitalController::class, 'updateConfig'])
            ->middleware('permission:config.update')
            ->name('config.update');
    });

    // ============================================
    // EMPLOYÉS
    // ============================================
    // Mon profil : ouvert à tout membre du personnel connecté (plus besoin de employee.view).
    Route::get('mon-profil', [EmployeeController::class, 'profile'])->name('employee.profile');
    Route::put('mon-profil/mot-de-passe', [EmployeeController::class, 'motDePasse'])
        ->middleware('throttle:6,1')
        ->name('employee.mot-de-passe');

    // CORRIGÉ — « employee/{employee} » était déclarée AVANT « employee/profile » et
    // « employee/create » : ces deux adresses tombaient sur la fiche d'un employé
    // nommé « profile » / « create » (erreur). Identifiant désormais numérique.
    Route::middleware('permission:employee.view')->group(function () {
        Route::get('employee', [EmployeeController::class, 'index'])->name('employee.index');
        Route::get('employee/{employee}', [EmployeeController::class, 'show'])->whereNumber('employee')->name('employee.show');
    });

    Route::get('employee/create', [EmployeeController::class, 'create'])
        ->middleware('permission:employee.create')
        ->name('employee.create');

    Route::post('employee', [EmployeeController::class, 'store'])
        ->middleware('permission:employee.create')
        ->name('employee.store');

    Route::get('employee/{employee}/edit', [EmployeeController::class, 'edit'])->whereNumber('employee')
        ->middleware('permission:employee.edit')
        ->name('employee.edit');

    Route::put('employee/{employee}', [EmployeeController::class, 'update'])->whereNumber('employee')
        ->middleware('permission:employee.edit')
        ->name('employee.update');

    Route::delete('employee/{employee}', [EmployeeController::class, 'destroy'])->whereNumber('employee')
        ->middleware('permission:employee.delete')
        ->name('employee.destroy');

    // NOUVEAU : quels motifs un médecin pratique (restriction/surcharge de durée)
    Route::middleware('permission:employee.edit')->group(function () {
        Route::get('employees/{employee}/motifs', [MedecinMotifController::class, 'gerer'])->name('employees.motifs');
        Route::post('employees/{employee}/motifs', [MedecinMotifController::class, 'synchroniser'])->name('employees.motifs.sync');
    });

    // ============================================
    // DÉPARTEMENTS
    // ============================================
    Route::prefix('department')->name('department.')->group(function () {
        Route::get('/', [DepartmentController::class, 'getIndex'])
            ->middleware('permission:department.view')
            ->name('index');

        Route::post('add', [DepartmentController::class, 'store'])
            ->middleware('permission:department.create')
            ->name('add');

        Route::post('update', [DepartmentController::class, 'update'])
            ->middleware('permission:department.edit')
            ->name('update');

        Route::delete('delete/{id?}', [DepartmentController::class, 'delete'])
            ->middleware('permission:department.delete')
            ->name('delete');
    });

    // NOUVEAU : motifs de rendez-vous — scopés par département (pédiatrie et
    // gynécologie n'ont pas les mêmes motifs, voir l'analyse).
    Route::prefix('motifs-rdv')->name('motifs-rdv.')->middleware('permission:motif_rdv.view')->group(function () {
        Route::get('/', [MotifRdvController::class, 'getIndex'])->name('index');
        Route::post('/', [MotifRdvController::class, 'store'])->middleware('permission:motif_rdv.create')->name('add');
        Route::put('{motifRdv}', [MotifRdvController::class, 'update'])->middleware('permission:motif_rdv.edit')->name('update');
        Route::patch('{motifRdv}/toggle', [MotifRdvController::class, 'toggle'])->middleware('permission:motif_rdv.edit')->name('toggle');
        Route::delete('{motifRdv}', [MotifRdvController::class, 'delete'])->middleware('permission:motif_rdv.delete')->name('delete');
    });

    // ============================================
    // SERVICES
    // ============================================
    Route::prefix('service')->name('service.')->group(function () {
        Route::get('/', [ServiceController::class, 'getIndex'])
            ->middleware('permission:service.view')
            ->name('index');

        Route::post('add', [ServiceController::class, 'store'])
            ->middleware('permission:service.create')
            ->name('add');

        Route::post('update', [ServiceController::class, 'update'])
            ->middleware('permission:service.edit')
            ->name('update');

        Route::delete('delete/{id?}', [ServiceController::class, 'delete'])
            ->middleware('permission:service.delete')
            ->name('delete');

        // Lot S3 : masquer / réafficher (garde l'historique, allège les listes).
        Route::patch('{service}/visibilite', [ServiceController::class, 'basculerVisibilite'])
            ->whereNumber('service')
            ->middleware('permission:service.edit')
            ->name('visibilite');
    });

    // Lot S3 : import du catalogue (actes, examens, médicaments) depuis Excel ou CSV.
    // Les droits de création sont vérifiés par type dans le contrôleur.
    Route::prefix('catalogue/import')->name('catalogue.import')->controller(\App\Http\Controllers\Catalogue\ImportCatalogueController::class)->group(function () {
        Route::get('/', 'index')->name('');
        Route::get('modele/{type}', 'modele')->whereIn('type', ['actes', 'examens', 'medicaments'])->name('.modele');
        Route::post('analyser', 'analyser')->middleware('throttle:20,1')->name('.analyser');
        Route::post('confirmer', 'confirmer')->name('.confirmer');
        Route::post('annuler', 'annuler')->name('.annuler');
    });

    // ============================================
    // PATIENTS
    // ============================================
    Route::middleware('permission:patient.view')->group(function () {
        Route::get('patient', [PatientController::class, 'index'])->name('patient.index');
        Route::get('patient/{patient}', [PatientController::class, 'show'])->name('patient.show');
    });


    Route::post('patient', [PatientController::class, 'store'])
        ->middleware('permission:patient.create')
        ->name('patient.store');

    Route::get('patient/{patient}/edit', [PatientController::class, 'edit'])
        ->middleware('permission:patient.edit')
        ->name('patient.edit');

    Route::put('patient/{patient}', [PatientController::class, 'update'])
        ->middleware('permission:patient.edit')
        ->name('patient.update');

    Route::delete('patient/{patient}', [PatientController::class, 'destroy'])
        ->middleware('permission:patient.delete')
        ->name('patient.destroy');

    Route::post('patient/file/{id}', [PatientController::class, 'addFile'])
        ->middleware('permission:patient.add_file')
        ->name('patient.addFile');

    /*
    |--------------------------------------------------------------------------
    | IDENTITÉ PATIENT, CONSENTEMENT, FAMILLE (NOUVEAU — Pilier B)
    |--------------------------------------------------------------------------
    */
    // Lot E2 : consulter = patient.view ; toute action qui donne ou retire un accès = patient.edit.
    Route::prefix('comptes-patients')->name('comptes-patients.')->middleware('permission:patient.view')->group(function () {
        Route::get('/', [ComptePatientController::class, 'getIndex'])->name('index');
        Route::middleware(['permission:patient.edit', 'throttle:30,1'])->group(function () {
            Route::post('/', [ComptePatientController::class, 'store'])->name('add');
            Route::put('{comptePatient}', [ComptePatientController::class, 'update'])->name('update');
            Route::delete('{comptePatient}', [ComptePatientController::class, 'delete'])->name('delete');
            Route::post('{comptePatient}/attacher', [ComptePatientController::class, 'attacherPatient'])->name('attacher');
            Route::delete('{comptePatient}/detacher/{patient}', [ComptePatientController::class, 'detacherPatient'])->name('detacher');
        });
    });

    Route::middleware('permission:patient.edit')->group(function () {
        Route::post('patient/{patient}/relations-familiales', [RelationFamilialeController::class, 'store'])->name('relations-familiales.store');
        Route::delete('relations-familiales/{relationFamiliale}', [RelationFamilialeController::class, 'delete'])->name('relations-familiales.delete');
    });

    Route::get('patient/{patient}/consentements', [ConsentementController::class, 'historique'])
        ->middleware('permission:patient.view')
        ->name('consentement.historique');

    Route::post('patient/{patient}/consentement/demander', [ConsentementController::class, 'demander'])
        ->middleware('permission:consentement.demander')
        ->name('consentement.demander');

    Route::post('patient/{patient}/consentement/confirmer-code', [ConsentementController::class, 'confirmerParCode'])
        ->middleware('permission:consentement.demander')
        ->name('consentement.confirmer-code');

    Route::delete('consentement/{consentement}/revoquer', [ConsentementController::class, 'revoquer'])
        ->middleware('permission:consentement.revoquer')
        ->name('consentement.revoquer');

    // ============================================
    // CONSULTATIONS
    // ============================================
    Route::middleware('permission:consultation.view')->group(function () {
        Route::get('consultation', [ConsultationController::class, 'index'])->name('consultation.index');
        Route::get('consultation/{consultation}/show', [ConsultationController::class, 'show'])->name('consultation.show');
    });

    Route::get('consultation/create', [ConsultationController::class, 'create'])
        ->middleware('permission:consultation.create')
        ->name('consultation.create');

    Route::post('consultation', [ConsultationController::class, 'store'])
        ->middleware('permission:consultation.create')
        ->name('consultation.store');

    Route::get('consultation/{consultation}/edit', [ConsultationController::class, 'edit'])
        ->middleware('permission:consultation.edit')
        ->name('consultation.edit');

    Route::put('consultation/{consultation}/update', [ConsultationController::class, 'update'])
        ->middleware('permission:consultation.edit')
        ->name('consultation.update');

    Route::delete('consultation/{consultation}', [ConsultationController::class, 'destroy'])
        ->middleware('permission:consultation.delete')
        ->name('consultation.destroy');

    Route::prefix('consultation')->name('consultation.')->group(function () {

        Route::get('/{consultation}/rapport/ordonnance-a80', [ConsultationController::class, 'ordonnanceA80'])->name('rapport.ordonnance.a80');
        Route::get('/{consultation}/rapport/ordonnance-a5', [ConsultationController::class, 'ordonnanceA5'])->name('rapport.ordonnance.a5');

        Route::get('/{consultation}/rapport/examens-a80', [ConsultationController::class, 'examensA80'])->name('rapport.examens.a80');
        Route::get('/{consultation}/rapport/examens-a5', [ConsultationController::class, 'examensA5'])->name('rapport.examens.a5');

        Route::get('/{consultation}/pdf/facture-a5', [ConsultationController::class, 'facturePdfA5'])->name('pdf.facture.a5');
        Route::get('/{consultation}/pdf/facture-a80', [ConsultationController::class, 'facturePdfA80'])->name('pdf.facture.a80');

        Route::get('/{consultation}/pdf/recu-a80', [ConsultationController::class, 'recuPdfA80'])->name('pdf.recu.a80');
        Route::get('/{consultation}/pdf/recu-a5', [ConsultationController::class, 'recuPdfA5'])->name('pdf.recu.a5');
   });



    // ============================================
    // MÉDICAMENTS
    // ============================================
    Route::middleware('permission:medicament.view')->group(function () {
        Route::get('medicaments', [MedicamentController::class, 'index'])->name('medicaments.index');
    });

    Route::get('medicaments/create', [MedicamentController::class, 'create'])
        ->middleware('permission:medicament.create')
        ->name('medicaments.create');

    Route::post('medicaments', [MedicamentController::class, 'store'])
        ->middleware('permission:medicament.create')
        ->name('medicaments.store');

    Route::get('medicaments/{medicament}/edit', [MedicamentController::class, 'edit'])
        ->middleware('permission:medicament.edit')
        ->name('medicaments.edit');

    Route::put('medicaments/{medicament}', [MedicamentController::class, 'update'])
        ->middleware('permission:medicament.edit')
        ->name('medicaments.update');

    Route::delete('medicaments/{medicament}', [MedicamentController::class, 'destroy'])
        ->middleware('permission:medicament.delete')
        ->name('medicaments.destroy');

    Route::patch('medicaments/{medicament}/visibilite', [MedicamentController::class, 'basculerVisibilite'])
        ->middleware('permission:medicament.edit')
        ->name('medicaments.visibilite');

    // ============================================
    // PACKAGES
    // ============================================
    Route::prefix('package')->name('package.')->group(function () {
        Route::get('/', [PackageController::class, 'getIndex'])
            ->middleware('permission:package.view')
            ->name('index');

        Route::post('/', [PackageController::class, 'store'])
            ->middleware('permission:package.create')
            ->name('store');

        Route::get('/edit/{id}', [PackageController::class, 'edit'])
            ->middleware('permission:package.edit')
            ->name('edit');

        Route::put('update/{id}', [PackageController::class, 'update'])
            ->middleware('permission:package.edit')
            ->name('update');

        Route::delete('delete/{id}', [PackageController::class, 'delete'])
            ->middleware('permission:package.delete')
            ->name('delete');

        Route::post('test/delete', [PackageController::class, 'packageTestDelete'])
            ->middleware('permission:package.edit')
            ->name('test.delete');
    });

    // ============================================
    // TESTS
    // ============================================
    Route::prefix('test')->name('test.')->group(function () {
        Route::get('/', [TestController::class, 'index'])
            ->middleware('permission:test.view')
            ->name('index');

        Route::post('add', [TestController::class, 'store'])
            ->middleware('permission:test.create')
            ->name('store');

        Route::post('edit', [TestController::class, 'edit'])
            ->middleware('permission:test.edit')
            ->name('edit');

        Route::delete('delete', [TestController::class, 'delete'])
            ->middleware('permission:test.delete')
            ->name('delete');

        Route::post('{id}/status', [TestController::class, 'statusChange'])
            ->middleware('permission:test.status')
            ->name('status');
    });

    // ============================================
    // COMPTABILITÉ
    // ============================================
    // Route::prefix('account')->name('account.')->group(function () {
    //     Route::get('facture', [AccountController::class, 'factureNonPayer'])
    //         ->middleware('permission:account.facture')
    //         ->name('facture');
    // });

    // ============================================
    // RAPPORTS
    // ============================================
    // Lot F — l'ancienne page d'accueil des rapports et le « rapport des actes » appelaient
    // des vues supprimées (erreur 500) : redirigés vers le module Rapports.
    Route::get('report', fn () => redirect()->route('rapports.index'))
        ->middleware('permission:report.view')
        ->name('reports.index');

    Route::post('report/actes', fn () => redirect()->route('rapports.index'))
        ->middleware('permission:report.actes')
        ->name('reports.actes');

    // CORRIGÉ (lot F) : aucune permission — tout membre du personnel pouvait ouvrir cet état comptable.
    Route::get('/reports/situation-par-acte', [ReportController::class, 'situationParActe'])
        ->middleware('permission:report.view')
        ->name('rapports.situation');

     Route::get('/reports/actes-par-assurance', [ReportController::class, 'actesParAssurance'])
        ->middleware('permission:report.view')
        ->name('rapports.actes.assurance');

    Route::get('/reports/actes-par-assurance-detail', [ReportController::class, 'actesParAssuranceEtParActe'])
        ->middleware('permission:report.view')
        ->name('rapports.actes.assurance.detail');

    Route::get('/reports/bordereau-assurance', [ReportController::class, 'bordereauAssurance'])
        ->middleware('permission:report.view')
        ->name('rapports.bordereau.assurance');


    // ============================================
    // HOSPITALISATIONS
    // ============================================
    Route::middleware('permission:hospitalisation.view')->group(function () {
        Route::get('hospitalisations', [HospitalisationController::class, 'index'])->name('hospitalisations.index');
        Route::get('hospitalisations/{hospitalisation}', [HospitalisationController::class, 'show'])->name('hospitalisations.show');
    });

    Route::get('hospitalisations/create', [HospitalisationController::class, 'create'])
        ->middleware('permission:hospitalisation.create')
        ->name('hospitalisations.create');

    Route::post('hospitalisations', [HospitalisationController::class, 'store'])
        ->middleware('permission:hospitalisation.create')
        ->name('hospitalisations.store');

    Route::get('hospitalisations/{hospitalisation}/edit', [HospitalisationController::class, 'edit'])
        ->middleware('permission:hospitalisation.edit')
        ->name('hospitalisations.edit');

    Route::put('hospitalisation/update', [HospitalisationController::class, 'update'])
        ->middleware('permission:hospitalisation.edit')
        ->name('hospitalisation.update');

    Route::delete('hospitalisations/{hospitalisation}', [HospitalisationController::class, 'destroy'])
        ->middleware('permission:hospitalisation.delete')
        ->name('hospitalisations.destroy');

    // Lot R : POST (clôture le séjour, libère la chambre, facture) — un GET se déclenchait par simple lien ou image.
    Route::post('hospitalisations/{hospitalisation}/payer', [HospitalisationController::class, 'payer'])
        ->middleware('permission:hospitalisation.payer')
        ->name('hospitalisations.payer');

    Route::get('hospitalisations/{hospitalisation}/facture', [HospitalisationController::class, 'facture'])
        ->middleware('permission:hospitalisation.facture')
        ->name('hospitalisations.facture');


    // ============================================
    // CHAMBRES
    // ============================================
    Route::middleware('permission:chambre.view')->group(function () {
        Route::get('chambres', [ChambreController::class, 'index'])->name('chambres.index');
    });

    Route::get('chambres/create', [ChambreController::class, 'create'])
        ->middleware('permission:chambre.create')
        ->name('chambres.create');

    Route::post('chambres', [ChambreController::class, 'store'])
        ->middleware('permission:chambre.create')
        ->name('chambres.store');

    Route::get('chambres/{chambre}/edit', [ChambreController::class, 'edit'])
        ->middleware('permission:chambre.edit')
        ->name('chambres.edit');

    Route::put('chambre/update', [ChambreController::class, 'update'])
        ->middleware('permission:chambre.edit')
        ->name('chambre.update');

    Route::delete('chambres/{chambre}', [ChambreController::class, 'destroy'])
        ->middleware('permission:chambre.delete')
        ->name('chambres.destroy');

    // ============================================
    // GESTION DES ASSURANCES
    // ============================================
    Route::prefix('insurance')->group(function () {

        // COMPAGNIES D'ASSURANCE
        Route::middleware('permission:insurance_company.view')->group(function () {
            Route::get('insurance-companies', [InsuranceCompanyController::class, 'index'])->name('insurance-companies.index');
        });

        Route::post('insurance-companies', [InsuranceCompanyController::class, 'store'])
            ->middleware('permission:insurance_company.create')
            ->name('insurance-companies.store');

        Route::put('insurance-companies/update', [InsuranceCompanyController::class, 'update'])
            ->middleware('permission:insurance_company.edit')
            ->name('insurance-companies.update');

        Route::delete('insurance-companies/delete', [InsuranceCompanyController::class, 'destroy'])
            ->middleware('permission:insurance_company.delete')
            ->name('insurance-companies.destroy');

        // COUVERTURES D'ASSURANCE
        Route::middleware('permission:insurance_coverage.view')->group(function () {
            // Lot 2d : l'écran des couvertures ligne à ligne est remplacé par Assurance > Conventions.
            Route::get('insurance-coverages', fn () => redirect()->route('assurance.conventions.index'))->name('insurance-coverages.index');
        });

        Route::post('insurance-coverages', [InsuranceCoverageController::class, 'store'])
            ->middleware('permission:insurance_coverage.create')
            ->name('insurance-coverages.store');

        Route::put('insurance-coverages/update', [InsuranceCoverageController::class, 'update'])
            ->middleware('permission:insurance_coverage.edit')
            ->name('insurance-coverages.update');

        Route::delete('insurance-coverages/delete', [InsuranceCoverageController::class, 'destroy'])
            ->middleware('permission:insurance_coverage.delete')
            ->name('insurance-coverages.destroy');

        // ASSURANCE PATIENT
        Route::middleware('permission:patient_insurance.view')->group(function () {
            Route::get('insurance_patient', [PatientInsuranceController::class, 'index'])->name('insurance_patient.index');
        });

        Route::post('insurance_patient', [PatientInsuranceController::class, 'store'])
            ->middleware('permission:patient_insurance.create')
            ->name('insurance_patient.store');

        // Lot Fix : recherche de patient au fil de la frappe pour le formulaire d'ajout
        // (même recherche confidentielle que l'assurance : partielle sur les patients de la clinique).
        Route::get('insurance_patient/patients/recherche', [PatientController::class, 'rechercheRapide'])
            ->middleware(['permission:patient_insurance.create', 'throttle:60,1'])
            ->name('insurance_patient.patients.recherche');

        Route::put('insurance_patient/update', [PatientInsuranceController::class, 'update'])
            ->middleware('permission:patient_insurance.edit')
            ->name('insurance_patient.update');

        Route::delete('insurance_patient/delete', [PatientInsuranceController::class, 'destroy'])
            ->middleware('permission:patient_insurance.delete')
            ->name('insurance_patient.destroy');

        // Lot 2c : écrans « Factures » / « Éléments de facture » retirés. Ils modifiaient
        // factures et lignes en base sans passer par la facturation (totaux, assurance,
        // encaissements, figement). Les factures se consultent depuis la consultation,
        // l'hospitalisation ou la demande de labo ; les créances assurance depuis Assurance > Créances.
        Route::get('/invoice', fn () => redirect()->route('account.facture'))->name('invoice.index');


    });

    // NOTE : le deuxième bloc `Route::prefix('insurance')->group()` qui
    // redéfinissait 'insurance-companies.*' (store/update/destroy/index) a
    // été retiré — il dupliquait exactement les routes déclarées juste
    // au-dessus. Les routes de solde/paiement d'assurance qu'il contenait
    // aussi sont conservées ci-dessous, seule la partie dupliquée disparaît.
    // Lot 2c : l'écran « Soldes » (calcul facture par facture) renvoie vers
    // Assurance > Créances. Les noms de route sont conservés pour les liens existants.
    Route::prefix('insurance')->group(function () {
        Route::get('/', fn () => redirect()->route('assurance.creances.index'))->name('insurance.balances.index');
        Route::get('/{insurance}', fn ($insurance) => redirect()->route('assurance.creances.show', $insurance))
            ->whereNumber('insurance')->name('insurance.balances.show');
    });

    // ============================================
    // PAIEMENTS
    // ============================================
    // Route::get('/payment/{patient}', [PaymentController::class, 'showPaymentPage'])
    //     ->middleware('permission:payment.view')
    //     ->name('payment.show');

    // « Paiements en attente » ouvre désormais la caisse (liens du menu, du
    // laboratoire… conservés). Retour arrière : remettre
    // [PaymentController::class, 'factureNonPayer'] à la place de la redirection.
    Route::get('facture', fn () => redirect()->route('caisse.index'))
            ->middleware('permission:account.facture')
            ->name('account.facture');

    Route::get('/payment/{patient}/{amount}/{assurance}', [PaymentController::class, 'calculateCoverage'])
        ->middleware('permission:payment.calculate');

    Route::post('/payment/process', [PaymentController::class, 'processPayment'])
        ->middleware('permission:payment.process')
        ->name('account.payer');

    // Caisse : encaissement de la part patient telle que calculée à la
    // facturation par le moteur de prise en charge (aucun recalcul côté navigateur).
    Route::controller(\App\Http\Controllers\Facturation\CaisseController::class)->group(function () {
        Route::get('caisse', 'index')->middleware('permission:account.facture')->name('caisse.index');
        Route::get('caisse/patients/{patientId}', 'show')->whereNumber('patientId')
            ->middleware('permission:account.facture')->name('caisse.show');
        Route::post('caisse/patients/{patientId}/encaisser', 'encaisser')->whereNumber('patientId')
            ->middleware('permission:payment.process')->name('caisse.encaisser');
        Route::post('caisse/factures/{transactionId}/remises', 'remises')->whereNumber('transactionId')
            ->middleware('permission:payment.process')->name('caisse.remises');
        Route::post('caisse/factures/{transactionId}/recalculer', 'recalculer')->whereNumber('transactionId')
            ->middleware('permission:payment.process')->name('caisse.recalculer');
    });

    // Annulation d'un encaissement (trace conservée, motif obligatoire).
    Route::post('/paiements/{paiement}/annuler', \App\Http\Controllers\Facturation\AnnulationPaiementController::class)
        ->middleware('permission:payment.cancel')
        ->name('paiement.annuler');

    Route::post('/insurance/calculate', [PaymentController::class, 'calculateCoverage'])
        ->middleware('permission:payment.calculate')
        ->name('insurance.calculate');

    // Lot R : POST (crée la facture du séjour).
    Route::post('/hospitalisation/{hospitalisation}/paiement', [PaymentController::class, 'paiementHospitalisation'])
        ->middleware('permission:payment.hospitalisation')
        ->name('hospitalisation.paiement');

    // RÉCLAMATIONS ASSURANCE — lot 2c : les réclamations sont créées par la facturation
    // (plus de saisie manuelle) et gérées dans Assurance > Créances.
    Route::get('insurance-claims', fn () => redirect()->route('assurance.creances.index'))->name('insurance-claims.index');

});