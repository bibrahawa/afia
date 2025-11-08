<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController, HospitalController, UserController,
    DepartmentController, ServiceController, EmployeeController,
    PatientController, AppointmentController, PackageController,
    TestController, ReportController, AccountController, AuthController,
    ProfileController, ConsultationController, MedicamentController,
    HospitalisationController, ChambreController, InsuranceClaimController, InsuranceCompanyController,
    InsuranceCoverageController, InvoiceItemController, InvoicesController, PatientInsuranceController, 
    PaymentController, InsuranceBalanceController, SmsController, SmsReportController
};

Route::get('sms', function(){
    dd(\App\Models\Appointment::NeedingLastMinuteReminder()->get());
});

// Authentification
Route::get('/', [DashboardController::class, 'index'])->name('rdv');

Route::middleware('guest')->group(function () {
    Route::view('login', 'auth.login');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {

    // SMS - À définir selon vos besoins
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/sms/lists', [SmsController::class, 'smsLists'])->name('sms.lists');
        Route::get('/sms/send', [SmsController::class, 'newSms'])->name('sms.new');
        Route::post('/sms/send', [SmsController::class, 'send'])->name('sms.send');
        Route::post('/sms/send-bulk', [SmsController::class, 'sendBulk'])->name('sms.send-bulk');
        Route::get('/sms-report', [SmsReportController::class, 'index'])->name('admin.sms-report');
        Route::post('/sms-report/resend-failed', [SmsReportController::class, 'resendFailed']);
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

    // ============================================
    // GESTION UTILISATEURS (Admin uniquement)
    // ============================================
    Route::middleware('permission:users.view')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}/show', [UserController::class, 'show'])->name('users.show');
        
        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
        });
    });

    Route::get('index-permissions', [UserController::class, 'indexPermissions'])
            ->middleware('permission:users.permissions')
            ->name('users.index_permissions');
        
    Route::get('liste-permissions/{id}', [UserController::class, 'listePermissions'])
            ->middleware('permission:users.permissions')
            ->name('users.listePermissions');

    Route::get('users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create')
        ->name('users.create');

    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:users.create')
        ->name('users.store');
    
    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.edit')
        ->name('users.edit');
    
    Route::put('users/{user}/update', [UserController::class, 'update'])
        ->middleware('permission:users.edit')
        ->name('users.update');
    
    Route::delete('users/{user}/destroy', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');
    
    Route::post('user/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.edit')
        ->name('user.edit');
    
    Route::post('user/delete', [UserController::class, 'delete'])
        ->middleware('permission:users.delete')
        ->name('user.delete');
    
    Route::get('disable-user/{id}', [UserController::class, 'disableUser'])
        ->middleware('permission:users.disable')
        ->name('user.disable');
    
    Route::post('assign-permissions/{id}', [UserController::class, 'assignPermissions'])
        ->middleware('permission:users.permissions')
        ->name('users.store_permissions');
    
    Route::post('change/password', [UserController::class, 'changePassword'])
        ->middleware('permission:users.change_password')
        ->name('change.password');

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
    Route::middleware('permission:employee.view')->group(function () {
        Route::get('employee', [EmployeeController::class, 'index'])->name('employee.index');
        Route::get('employee/{employee}', [EmployeeController::class, 'show'])->name('employee.show');
        Route::get('employee/profile', [EmployeeController::class, 'profile'])->name('employee.profile');
    });
    
    Route::get('employee/create', [EmployeeController::class, 'create'])
        ->middleware('permission:employee.create')
        ->name('employee.create');
    
    Route::post('employee', [EmployeeController::class, 'store'])
        ->middleware('permission:employee.create')
        ->name('employee.store');
    
    Route::get('employee/{employee}/edit', [EmployeeController::class, 'edit'])
        ->middleware('permission:employee.edit')
        ->name('employee.edit');
    
    Route::put('employee/{employee}', [EmployeeController::class, 'update'])
        ->middleware('permission:employee.edit')
        ->name('employee.update');
    
    Route::delete('employee/{employee}', [EmployeeController::class, 'destroy'])
        ->middleware('permission:employee.delete')
        ->name('employee.destroy');

    // ============================================
    // MÉDECIN
    // ============================================
    Route::prefix('medecin')->name('medecin.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.medecin')
            ->name('dashboard');
        
        Route::get('appointments', [DashboardController::class, 'appointments'])
            ->middleware('permission:medecin.appointments')
            ->name('appointments');
        
        Route::post('appointments/{id}/confirm', [DashboardController::class, 'confirmAppointment'])
            ->middleware('permission:medecin.confirm_appointment')
            ->name('appointments.confirm');
        
        Route::post('appointments/{id}/complete', [DashboardController::class, 'completeAppointment'])
            ->middleware('permission:medecin.complete_appointment')
            ->name('appointments.complete');

        // Disponibilités
        Route::middleware('permission:medecin.availabilities')->group(function () {
            Route::get('availabilities', [DashboardController::class, 'availabilities'])->name('availabilities');
            Route::post('availabilities', [DashboardController::class, 'storeAvailability'])->name('availabilities.store');
            Route::put('availabilities', [DashboardController::class, 'updateAvailability'])->name('availabilities.update');
            Route::delete('availabilities/{id}', [DashboardController::class, 'destroyAvailability'])->name('availabilities.destroy');
        });

        // Congés
        Route::middleware('permission:medecin.leaves')->group(function () {
            Route::get('leaves', [DashboardController::class, 'leaves'])->name('leaves');
            Route::post('leaves', [DashboardController::class, 'storeLeave'])->name('leaves.store');
            Route::put('leaves/update', [DashboardController::class, 'updateLeave'])->name('leaves.update');
            Route::delete('leaves', [DashboardController::class, 'destroyLeaves'])->name('leaves.destroy');
        });
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
    });

    // ============================================
    // PATIENTS
    // ============================================
    Route::middleware('permission:patient.view')->group(function () {
        Route::get('patient', [PatientController::class, 'index'])->name('patient.index');
        Route::get('patient/{patient}', [PatientController::class, 'show'])->name('patient.show');
    });
    
    Route::get('patient/create', [PatientController::class, 'create'])
        ->middleware('permission:patient.create')
        ->name('patient.create');
    
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
    
    Route::post('consultations/{consultation}/facturer', [ConsultationController::class, 'facturer'])
        ->middleware('permission:consultation.facturer')
        ->name('consultations.facturer');

    // Factures de consultations
    Route::prefix('consultations/{id}/facture')->name('consultations.facture.')->group(function () {
        Route::get('/', [ConsultationController::class, 'facture'])
            ->middleware('permission:consultation.facture')
            ->name('');
        
        Route::get('/ordonnance', [ConsultationController::class, 'facture_ordonnance'])
            ->middleware('permission:consultation.ordonnance')
            ->name('ordonnance');
        
        Route::get('/medicament', [ConsultationController::class, 'facture_medicament'])
            ->middleware('permission:consultation.medicament')
            ->name('medicament');
        
        Route::get('/paiement', [ConsultationController::class, 'facture_paiement'])
            ->middleware('permission:consultation.paiement')
            ->name('paiement');
        
        Route::get('/examen', [ConsultationController::class, 'facture_examen'])
            ->middleware('permission:consultation.examen')
            ->name('examen');
    });
    
    Route::view('/facture', 'consultations.facture.facture_consultation')
        ->middleware('permission:consultation.facture')
        ->name('facture.consultation');

    // ============================================
    // RENDEZ-VOUS
    // ============================================
    Route::middleware('permission:appointment.view')->group(function () {
        Route::get('appointment', [AppointmentController::class, 'index'])->name('appointment.index');
        Route::get('appointment/{appointment}', [AppointmentController::class, 'show'])->name('appointment.show');
    });
    
    Route::get('appointment/create', [AppointmentController::class, 'create'])
        ->middleware('permission:appointment.create')
        ->name('appointment.create');
    
    Route::post('appointment', [AppointmentController::class, 'store'])
        ->middleware('permission:appointment.create')
        ->name('appointment.store');
    
    Route::get('appointment/{appointment}/edit', [AppointmentController::class, 'edit'])
        ->middleware('permission:appointment.edit')
        ->name('appointment.edit');
    
    Route::put('appointment/{appointment}', [AppointmentController::class, 'update'])
        ->middleware('permission:appointment.edit')
        ->name('appointment.update');
    
    Route::post('appointment/updated', [AppointmentController::class, 'updated'])
        ->middleware('permission:appointment.edit')
        ->name('appointment.updated');
    
    Route::delete('appointment/{appointment}', [AppointmentController::class, 'destroy'])
        ->middleware('permission:appointment.delete')
        ->name('appointment.destroy');

    // Route pour l'export PDF des rendez-vous
    Route::get('/appointments/export-pdf', [AppointmentController::class, 'exportPdf'])
        ->name('appointments.export-pdf');

    // ============================================
    // MÉDICAMENTS
    // ============================================
    Route::middleware('permission:medicament.view')->group(function () {
        Route::get('medicaments', [MedicamentController::class, 'index'])->name('medicaments.index');
        Route::get('medicaments/{medicament}', [MedicamentController::class, 'show'])->name('medicaments.show');
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
        
        Route::post('sale', [PackageController::class, 'packageSale'])
            ->middleware('permission:package.sale')
            ->name('sale');
        
        Route::get('sale/{id}', [PackageController::class, 'packageSales'])
            ->middleware('permission:package.sale')
            ->name('sales');
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
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('facture', [AccountController::class, 'factureNonPayer'])
            ->middleware('permission:account.facture')
            ->name('facture');
        
        // Route::post('facture', [AccountController::class, 'payer'])
        //     ->middleware('permission:account.payer')
        //     ->name('payer');
        
        Route::get('service', [AccountController::class, 'serviceReport'])
            ->middleware('permission:account.service_report')
            ->name('service');
        
        Route::get('opd', [AccountController::class, 'opdReport'])
            ->middleware('permission:account.opd_report')
            ->name('opd');
        
        Route::get('package', [AccountController::class, 'packageReport'])
            ->middleware('permission:account.package_report')
            ->name('package');
    });

    // ============================================
    // RAPPORTS
    // ============================================
    Route::get('report', [ReportController::class, 'index'])
        ->middleware('permission:report.view')
        ->name('reports.index');
    
    Route::post('report/actes', [ReportController::class, 'rapportActes'])
        ->middleware('permission:report.actes')
        ->name('reports.actes');
    
    Route::post('service/report', [ReportController::class, 'service'])
        ->middleware('permission:report.service')
        ->name('service.report');

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
    
    Route::get('hospitalisations/{hospitalisation}/payer', [HospitalisationController::class, 'payer'])
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
        Route::get('chambres/{chambre}', [ChambreController::class, 'show'])->name('chambres.show');
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
            Route::get('insurance-coverages', [InsuranceCoverageController::class, 'index'])->name('insurance-coverages.index');
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
        
        Route::put('insurance_patient/update', [PatientInsuranceController::class, 'update'])
            ->middleware('permission:patient_insurance.edit')
            ->name('insurance_patient.update');
        
        Route::delete('insurance_patient/delete', [PatientInsuranceController::class, 'destroy'])
            ->middleware('permission:patient_insurance.delete')
            ->name('insurance_patient.destroy');

        // FACTURES D'ASSURANCE
        Route::get('/invoice', [InvoicesController::class, 'index'])
            ->middleware('permission:invoice.view')
            ->name('invoice.index');
        
        Route::post('/invoice/add', [InvoicesController::class, 'store'])
            ->middleware('permission:invoice.create')
            ->name('invoice.add');
        
        Route::post('/invoice/update', [InvoicesController::class, 'update'])
            ->middleware('permission:invoice.edit')
            ->name('invoice.update');
        
        Route::delete('/invoice/delete/{id}', [InvoicesController::class, 'destroy'])
            ->middleware('permission:invoice.delete')
            ->name('invoice.delete');
        
        Route::get('/invoice/{id}/items', [InvoicesController::class, 'getItems'])
            ->middleware('permission:invoice.view');

        // ÉLÉMENTS DE FACTURE
        Route::get('/invoice/item', [InvoiceItemController::class, 'index'])
            ->middleware('permission:invoice_item.view')
            ->name('invoice.item.index');
        
        Route::post('/invoice/item/add', [InvoiceItemController::class, 'store'])
            ->middleware('permission:invoice_item.create')
            ->name('invoice.item.add');
        
        Route::post('/invoice/item/update', [InvoiceItemController::class, 'update'])
            ->middleware('permission:invoice_item.edit')
            ->name('invoice.item.update');
        
        Route::delete('/invoice/item/delete/{id}', [InvoiceItemController::class, 'destroy'])
            ->middleware('permission:invoice_item.delete')
            ->name('invoice.item.delete');

        // SOLDES ASSURANCE
        Route::prefix('balances')->name('insurance.balances.')->group(function() {
            Route::get('/', [InsuranceBalanceController::class, 'index'])
                ->middleware('permission:insurance_balance.view')
                ->name('index');
            
            Route::get('/{insurance}', [InsuranceBalanceController::class, 'show'])
                ->middleware('permission:insurance_balance.show')
                ->name('show');
            
            Route::post('/paiement', [InsuranceBalanceController::class, 'ProcessPaiement'])
                ->middleware('permission:insurance_balance.payment')
                ->name('payment');
            
            Route::get('/export/csv', [InsuranceBalanceController::class, 'export'])
                ->middleware('permission:insurance_balance.export')
                ->name('export');
        });
    });

    // ============================================
    // PAIEMENTS
    // ============================================
    Route::get('/payment/{patient}', [PaymentController::class, 'showPaymentPage'])
        ->middleware('permission:payment.view')
        ->name('payment.show');
    
    Route::get('/payment/{patient}/{amount}/{assurance}', [PaymentController::class, 'calculateCoverage'])
        ->middleware('permission:payment.calculate');
    
    Route::post('/payment/process', [PaymentController::class, 'processPayment'])
        ->middleware('permission:payment.process')
        ->name('account.payer');
    
    Route::post('/insurance/calculate', [PaymentController::class, 'calculateCoverage'])
        ->middleware('permission:payment.calculate')
        ->name('insurance.calculate');
    
    Route::get('/hospitalisation/{hospitalisation}/paiement', [PaymentController::class, 'paiementHospitalisation'])
        ->middleware('permission:payment.hospitalisation')
        ->name('hospitalisation.paiement');
    
    // RÉCLAMATIONS ASSURANCE
    Route::resource('insurance-claims', InsuranceClaimController::class)
        ->middleware('permission:payment.view');

});