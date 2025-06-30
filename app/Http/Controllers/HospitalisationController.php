<?php

namespace App\Http\Controllers;

use App\Models\Hospitalisation;
use App\Models\Patient;
use App\Models\Chambre;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Service\TransactionService;

class HospitalisationController extends Controller
{
    public function index()
    {
        $hospitalisations = Hospitalisation::with('patient', 'chambre')->latest()->get();
        $patients = Patient::all();
        $chambres = Chambre::where('statut', 'Libre')->orWhereIn('id', function($query) {
            $query->select('chambre_id')->from('hospitalisations')->where('statut', '!=', 'En cours');
        })->get();

        return view('hospitalisations.index', compact('hospitalisations', 'patients', 'chambres'));
    }


    public function create()
    {
        $patients = Patient::all();
        $chambres = Chambre::where('statut', 'Libre')->get();
        return view('hospitalisations.create', compact('patients', 'chambres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'chambre_id' => 'required|exists:chambres,id',
            'date_entree' => 'required|date',
            'nombre_jours' => 'required|integer|min:1',
            'observation' => 'nullable|string',
        ]);

        $date_sortie = Carbon::parse($request->date_entree)->addDays((int)$request->nombre_jours);
        Hospitalisation::create([
            'patient_id' => $request->patient_id,
            'chambre_id' => $request->chambre_id,
            'date_entree' => $request->date_entree,
            'nombre_jours' => $request->nombre_jours,
            'date_sortie_prevue' => $date_sortie,
            'observation' => $request->observation,
        ]);

        // Marquer la chambre comme occupée
        Chambre::find($request->chambre_id)->update(['statut' => 'Occupée']);

        return redirect()->route('hospitalisations.index')->with('success', 'Hospitalisation enregistrée.');
    }

    public function show(Hospitalisation $hospitalisation)
    {
        return view('hospitalisations.show', compact('hospitalisation'));
    }

    public function edit(Hospitalisation $hospitalisation)
    {
        $patients = Patient::all();
        $chambres = Chambre::where('statut', 'Libre')->orWhere('id', $hospitalisation->chambre_id)->get();
        return view('hospitalisations.edit', compact('hospitalisation', 'patients', 'chambres'));
    }

    public function update(Request $request, Hospitalisation $hospitalisation)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'chambre_id' => 'required|exists:chambres,id',
            'date_entree' => 'required|date',
            'nombre_jours' => 'required|integer|min:1',
            'statut' => 'required|in:En cours,Terminé,Annulé',
            'observation' => 'nullable|string',
        ]);

        $date_sortie = Carbon::parse($request->date_entree)->addDays((int)$request->nombre_jours);

        $hospitalisation->update([
            'patient_id' => $request->patient_id,
            'chambre_id' => $request->chambre_id,
            'date_entree' => $request->date_entree,
            'nombre_jours' => $request->nombre_jours,
            'date_sortie_prevue' => $date_sortie,
            'statut' => $request->statut,
            'observation' => $request->observation,
        ]);

        return redirect()->route('hospitalisations.index')->with('success', 'Hospitalisation mise à jour.');
    }

    public function destroy(Hospitalisation $hospitalisation)
    {
        $hospitalisation->delete();
        return redirect()->route('hospitalisations.index')->with('success', 'Hospitalisation supprimée.');
    }


    public function facture(Hospitalisation $hospitalisation)
    {


        // Durée réelle
        $dateDebut = \Carbon\Carbon::parse($hospitalisation->date_entree);
        $dateFin = $hospitalisation->date_sortie_effective ?? now();
        $nombreJours = ceil($dateDebut->diffInDays($dateFin) ?: 1);

        $prixJour = $hospitalisation->chambre->prix_par_jour;
        $total = $prixJour * $nombreJours;

        \DB::transaction(function () use (&$hospitalisation, $total) {
            $hospitalisation->load('patient', 'chambre');
            // Marquer comme libéré si pas déjà fait
            if (!$hospitalisation->date_sortie_effective) {
                $hospitalisation->date_sortie_effective = now();
                $hospitalisation->statut = 'Terminé';
                $hospitalisation->save();

                // Libérer la chambre
                $hospitalisation->chambre->update(['statut' => 'Libre']);

                // Mise à jour compte patient
                $accountID = TransactionService::mettreAJourCompte($hospitalisation->patient->id, Patient::class, $total, 'credit');

                // Création transaction
                $hospitalisation->transaction()->create([
                    'user_id'         => auth()->id(),
                    'account_id'      => $accountID,
                    'patient_id'      => $hospitalisation->patient->id,
                    'consultation_id' => $hospitalisation->id,
                    'description'     => $hospitalisation->observation,
                    'tax_amount'      => 0,
                    'discount'        => 0,
                    'sub_total'       => $total,
                ]);
            }
        });


        $factureNo = 'HOSP-' . now()->format('Ym') . str_pad($hospitalisation->id, 3, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('hospitalisations.facture-pdf', [
            'hospitalisation' => $hospitalisation,
            'nombreJours' => $nombreJours,
            'total' => $total,
            'factureNo' => $factureNo,
        ]);

        return $pdf->stream("Facture-{$factureNo}.pdf");
    }


}
