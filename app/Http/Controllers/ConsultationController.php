<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Service;
use App\Models\Medicament;
use App\Models\Test;
use App\Models\Package;
use App\Models\Antecedent;
use App\Models\FichierPatient;
use App\Service\TransactionService;
class ConsultationController extends Controller
{
    public function __construct()
    {
        //
    }

    public function index()
    {
        $consultations = Consultation::latest()->paginate(10);
        return view('consultations.index', compact([
            'consultations'
        ]));
    }


    public function create(Patient $patient, $id = null)
    {
        $patients = Patient::all();

        if (!auth()->user()->hasRole('medecin')) {
            return redirect()->back()->with('error', 'Vous devez être un médecin pour créer une consultation.');
        }

        $services = Service::where('department_id', auth()->user()->employee->department->id)->get();
        $packages = Package::all();
        $medicaments = Medicament::all();
        $fichiersPatients = FichierPatient::where('used_by', 'accueil')->get();
        $tests = Test::all();

        return view('consultations.new', compact([
            'patients',
            'services',
            'packages',
            'medicaments',
            'tests',
            'fichiersPatients'
        ]));
    }

    public function facture($id){
        $consultation = Consultation::findOrFail($id);

        // Génération du PDF
        return view('consultations.facture.facture_consultation', compact([
            'consultation'
        ]));
    }

    public function facture_ordonnance($id){
        $consultation = Consultation::findOrFail($id);

        // Génération du PDF
        return view('consultations.facture.facture_ordonnance', compact([
            'consultation'
        ]));
    }

    public function facture_medicament($id){
        $consultation = Consultation::findOrFail($id);

        // Génération du PDF
        return view('consultations.facture.facture_medicament', compact([
            'consultation'
        ]));
    }

    public function facture_paiement($id){
        $consultation = Consultation::findOrFail($id);

        // Génération du PDF
        return view('consultations.facture.facture_paiement', compact([
            'consultation'
        ]));
    }

    public function facture_examen($id){
        $consultation = Consultation::findOrFail($id);

        // Génération du PDF
        return view('consultations.facture.facture_examen', compact([
            'consultation'
        ]));
    }

    // Version encore plus courte avec array_map si vous préférez
    function transformSimple($originalArray, $texts) {
        $result = ['services' => [], 'examens' => [], 'packages' => []];

        foreach ($originalArray as $key => $checked) {
            [$type, $value] = explode('-', $key);
            $result[$type . 's'][] = [
                'value' => $value,
                'text' => $texts[$key] ?? ucfirst($type) . ' ' . $value,
                'checked' => $checked
            ];
        }

        return $result;
    }

    public function store(Request $request)
    {

        // Validation principale
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'motif' => 'required|string',
            'signes_cliniques' => 'nullable|string',
            'diagnostic' => 'required|string',
            'observation' => 'nullable|string',
            'prochain_rdv' => 'nullable|date'
        ]);

        // Préparation des données consultation
        $data_for_consultation = $request->only([
            'patient_id','motif', 'signes_cliniques', 'diagnostic', 'observation', 'prochain_rdv'
        ]);

        $data_for_consultation['medecin_id'] = auth()->user()->employee->id;
        $data_for_consultation['department_id'] = auth()->user()->employee->department_id;

        if ($request->signes_cliniques) {
            $data_for_consultation['signes_cliniques'] = explode(',', $request->signes_cliniques);
        }

        $consultation = Consultation::create($data_for_consultation);

        // Liaison services, packages, examens, medicaments
        $selectedItems = json_decode($request->selected_items, true);
        $billingStatus = json_decode($request->billing_status, true);
        foreach ($selectedItems as $category => $items) {
            $syncValues = [];

            foreach ($items as $item) {
                // Construire la clé de billing_status
                $prefix = $category === 'examens' ? 'examen' : rtrim($category, 's');
                $billingKey = $prefix . '-' . $item['value'];
                // Si l'item est coché dans billing_status, l'ajouter pour sync
                // Préparer les données pour sync avec pivot
                $syncValues[$item['value']] = [
                    'facturer' => $billingStatus[$billingKey] ?? false
                ];
            }

            // Synchroniser selon la catégorie
            match ($category) {
                'medicaments' => $consultation->medicaments()->sync($syncValues),
                'services'    => $consultation->services()->sync($syncValues),
                'packages'    => $consultation->packages()->sync($syncValues),
                'examens'     => $consultation->tests()->sync($syncValues),
                default       => null
            };
        }

        // Fichiers joints
        if ($fichierIds = $request->input('fichiers_enregistres')) {
            FichierPatient::whereIn('id', $fichierIds)->update(['used_by' => 'medecin']);
        }

        // Calcul montant consultation
        $consultation_amount =
            $consultation->services->sum(fn($service) => $service->pivot->facturer ? ($service->amount ?? 0) : 0) +
            $consultation->packages->sum(fn($package) => $package->pivot->facturer ? ($package->price ?? 0) : 0) +
            $consultation->tests->sum(fn($test) => $test->pivot->facturer ? ($test->amount ?? 0) : 0)+
            $consultation->medicaments->sum(fn($medicament) => $medicament->pivot->facturer ? ($medicament->amount ?? 0) : 0);

        // Mise à jour compte patient
        $accountID = TransactionService::mettreAJourCompte($request->patient_id, Patient::class, $consultation_amount, 'credit');

        // Création transaction
        $consultation->transaction()->create([
            'user_id'         => auth()->id(),
            'account_id'      => $accountID,
            'patient_id'      => $request->patient_id,
            'consultation_id' => $consultation->id,
            'description'     => $consultation->motif,
            'tax_amount'      => 0,
            'discount'        => 0,
            'sub_total'       => $consultation_amount
        ]);

        // Marque la consultation comme facturée
        $consultation->update(['est_facturee' => true]);

        /**
         * ✅ Ajout des antécédents médicaux ici
         */
        Antecedent::updateOrCreate(
            ['patient_id' => $request->patient_id], // clé de recherche
            [
                'antecedents_medicaux'           => $request->antecedents_medicaux,
                'antecedents_chirurgicaux'       => $request->antecedents_chirurgicaux,
                'antecedents_gyneco_obstetricaux'=> $request->antecedents_gyneco_obstetricaux,
                'antecedents_familiaux'          => $request->antecedents_familiaux,
                'allergies'                      => $request->allergies,
                'traitements_cours'              => $request->traitements_cours,
            ]
        );

        $updateNewVisit = Patient::find($request->patient_id);
        $updateNewVisit->first_visit = false;
        $updateNewVisit->save();


        return redirect()->route('consultation.index')->with('success', 'Consultation enregistrée.');
    }


    public function show($id){
        $consultation = Consultation::find($id);

        return view('consultations.show', compact([
            'consultation'
        ]));
    }

    public function edit($id){
        $patients = Patient::all();
        $medecins = Employee::where('type', 'doctor')->get();
        $departements = Department::all();
        $services = Service::all();
        $packages = Package::all();
        $medicaments = Medicament::all();
        $tests = Test::all();
        $consultation = Consultation::find($id);
        return view('consultations.edit', compact([
            'patients',
            'medecins',
            'departements',
            'services',
            'packages',
            'medicaments',
            'consultation',
            'tests'
        ]));
    }

    public function facturer(Consultation $consultation)
    {

        // return view('consultations.facture.facture', compact('consultation'));

        // Générer le PDF
        $pdf = Pdf::loadView('consultations.facture.facture', compact('consultation'));

        $filename = 'facture_consultation_' . $consultation->id . '.pdf';
        $path = 'factures/' . $filename;

        // Créer le répertoire s'il n'existe pas
        Storage::makeDirectory('public/factures');

        // Enregistrer dans le disque public
        Storage::disk('public')->put($path, $pdf->output());

        // Vérifier l'existence du fichier
        if (!Storage::disk('public')->exists($path)) {
            return back()->with('error', 'Erreur lors de la génération de la facture.');
        }

        // Utiliser le chemin complet du fichier pour le téléchargement
        $fullPath = storage_path('app/public/' . $path);

        // Vérifier si le fichier existe physiquement avant de le télécharger
        if (!file_exists($fullPath)) {
            return back()->with('error', 'Le fichier de facture n\'a pas été trouvé.');
        }

        return response()->download($fullPath);
    }

}
