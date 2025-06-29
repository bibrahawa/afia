<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\OpdSales;
use App\Models\Test;
use App\Models\Consultation;
use App\Models\Transaction;
use Auth;


class DashboardController extends Controller
{
    //define constructor with middleware

    public function __construct()
    {

    }

    public function index()
    {

    	// $user = Auth::user()->id;
      	// $invoices = Invoice::where('user_id', $user)->whereDate('created_at', '=', date('Y-m-d'))->get();
        // $patients = Patient::get();
        // $appointments = Appointment::whereDate('appointment_date', '=', date('Y-m-d'))->get();
        // $opds = OpdSales::whereDate('created_at' , '=', date('Y-m-d'))->get();
      	// //return $invoices;
      	// $total['sub_total'] = $invoices->sum('sub_total');
      	// $total['discount'] = $invoices->sum('discount');
     	//   $total['tax_amount'] = $invoices->sum('tax_amount');
      	// $total['total_amount'] = $invoices->sum('total_amount');
      	// // Appointment
        // $pending['appointment'] = Appointment::where('status', 0)->count();
        // $total_doctor = Employee::where('type', '==', 'medecin')->get()->count();
        // $total_test = Test::get()->count();

        $list_patient = Patient::orderBy('id', 'desc');
        $consultations = Consultation::orderBy('id', 'desc')->get();
        $transactions = Transaction::orderBy('id', 'desc')->get();

        $total_patient = $list_patient->get()->count();
        $patientes = $list_patient->paginate(5);

    	return view('dashboard' , compact('total_patient','consultations', 'transactions', 'patientes'));

    }
}
