<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Chambre;
use App\Models\Consultation;
use App\Models\Hospitalisation;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\Medicament;
use App\Models\Patient;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function admin()
    {
        $rdv_today = Appointment::whereDate('appointment_date', today())->count();
        $hospitalisations_active = Hospitalisation::where('statut', 'active')->count();
        $chambres_libres = Chambre::where('statut', 'libre')->count();
        $patients_assures = Patient::count();
        $factures_impayees = Invoice::whereIn('insurance_status', ['pending', 'approved'])->count();
        $montant_impaye = Invoice::whereIn('insurance_status', ['pending', 'approved'])->sum('insurance_amount');
        $medicaments_stock_faible = Medicament::count();
        $reclamations_en_attente = InsuranceClaim::where('status', 'draft')->count();

        $rdv_aujourdhui = Appointment::with(['patient.user', 'employee'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get();

        $total_patient = Patient::count();
        $patientes = Patient::latest()->limit(5)->get();
        $consultations = Consultation::latest()->limit(10)->get();
        $transactions = Transaction::latest()->limit(10)->get();

        return view('dashboard', compact(
            'total_patient',
            'consultations',
            'transactions',
            'patientes',
            'rdv_aujourdhui',
            'rdv_today',
            'hospitalisations_active',
            'chambres_libres',
            'patients_assures',
            'factures_impayees',
            'montant_impaye',
            'medicaments_stock_faible',
            'reclamations_en_attente'
        ));
    }

    public function indexProfessionel()
    {
        $professional = auth()->user();

        return view('professional.dashboard', compact('professional'));
    }
}