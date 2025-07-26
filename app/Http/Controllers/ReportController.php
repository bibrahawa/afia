<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;
use App\Models\Service;
use App\Models\Department;
use App\Models\Consultation;

class ReportController extends Controller
{
	public function index()
	{
        $services = Service::all();
        $departments = Department::all();
        $examens = Test::all();
		return view('reports.tools.index', compact('services', 'departments', 'examens'));

    }

    public function service(Request $request)
    {
        // Validation des données
        $request->validate([
            'department_id' => 'required',
            'services' => 'required|array',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ], [
            'to.after_or_equal' => 'La date de fin doit être supérieure ou égale à la date de début',
            'services.required' => 'Veuillez sélectionner au moins un service',
        ]);

        $departmentId = $request->get('department_id');
        $serviceIds = $request->get('services');
        $from = $request->get('from');
        $to = $request->get('to');
        $rapports = [];

        // Conversion des dates
        $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $to)->endOfDay();
        // Construction de la requête pour les services
        $consultationQuery = Consultation::whereBetween('created_at', [$fromDate, $toDate]);
        // Filtrer par département si spécifié
        if ($departmentId != 'all') {
            $consultationQuery->where('department_id', $departmentId);
        }

        $consultationQuery = $consultationQuery->get();
        $i = 0;
        $consultationQuery->map(function($consultation) use ($serviceIds, &$rapports, $i) {
            $total = 0;
            $rapports[$i] = [
                'department' => $consultation->department->name,
                'patiente' => $consultation->patient->first_name . ' ' . $consultation->patient->last_name,
                'consultation' => $consultation,
            ];
            // Récupération des services associés à la consultation
            foreach ($consultation->services as $key=>$service) {
                // Vérifier si le service est dans la liste des services sélectionnés
                if (in_array($service->id, $serviceIds) || in_array('all', $serviceIds)) {
                    $rapports[$i]['services'][$key] = [
                        'service' => $service->name,
                        'amount' => $service->amount,
                    ];
                    $total += $service->amount;
                }
            }

            $rapports[$i]['total'] = $total;

        });

        $i++;

        return view('reports.tools.rapport_service', compact(
            'rapports',
            'from',
            'to',
        ));
    }

}
