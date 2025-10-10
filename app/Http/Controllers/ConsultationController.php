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
use App\Models\Invoice;
use App\Models\Hospital;
use App\Models\Appointment;
use App\Models\FichierPatient;
use App\Models\AppointmentSlot;
use App\Services\TransactionService;
use App\Services\ConsultationService;
use App\Services\InsuranceCalculationService;
use Carbon\Carbon;
use DB;
class ConsultationController extends Controller
{
    protected $insuranceCalculation;
    protected $consultationService;

    public function __construct(InsuranceCalculationService $insuranceCalculation, ConsultationService $consultationService)
    {
        $this->insuranceCalculation = $insuranceCalculation;
        $this->consultationService = $consultationService;
    }

    public function index()
    {
        $consultations = Consultation::latest()->paginate(10);

        return view('consultations.index', compact([
            'consultations'
        ]));
    }

    public function create()
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'motif' => 'required|string',
            'signes_cliniques' => 'nullable|string',
            'diagnostic' => 'required|string',
            'observation' => 'nullable|string',
            'prochain_rdv' => 'nullable|date'
        ]);


        DB::beginTransaction();
        
        try {
                $consultation = Consultation::create([
                    ...$validated, // Utilisez $validated au lieu de $request->only()
                    'medecin_id' => auth()->user()->employee->id,
                    'department_id' => auth()->user()->employee->department_id,
                    'signes_cliniques' => explode(',', $request->signes_cliniques)
                ]);

                ConsultationService::attachItems($consultation, json_decode($request->selected_items, true));

                // Fichiers joints
                // if ($fichierIds = $request->input('fichiers_enregistres')) {
                //     FichierPatient::whereIn('id', $fichierIds)->update(['used_by' => 'medecin']);
                //     $consultation->fichiers()->sync($fichierIds);
                // }

                $consultation->load(['services', 'packages', 'tests', 'medicaments']);

                $items  = $this->consultationService->calculateAmountAndReturnItems($consultation);

                $amount = $items['total_amount'];

                $accountID = ConsultationService::mettreAJourCompte($request->patient_id, Patient::class, $amount, 'credit');
            
                $transaction = $consultation->transaction()->create([
                    'user_id'    => auth()->id(),
                    'account_id' => $accountID,
                    'patient_id' => $validated['patient_id'],
                    'description'=> $consultation->motif,
                    'tax_amount' => 0,
                    'discount'   => 0,
                    'sub_total'  => $amount,
                    'total'      => $amount
                ]);

                $consultation->update(['est_facturee' => true]);

                $this->insuranceCalculation->createInvoiceForConsulation($request->patient_id, $transaction->id, $consultation, $items);

                $patient = Patient::find($request->patient_id);

                $patient->first_visit = false;
                $patient->save();

                if(!$patient->antecedant()->exists()){
                    
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
                }

                // Créer le rendez-vous
                $appointment = Appointment::create([
                    'employee_id' => auth()->user()->employee->id,
                    'patient_id' => $request->patient_id,
                    'appointment_date' => Carbon::parse($request->prochain_rdv)->format('Y-m-d'),
                    'appointment_time' => Carbon::parse($request->prochain_rdv)->format('H:i:s'),
                    'reason' => "autre",
                    'description' => "Reservation de rendez vous pris direction avec le medecin",
                    'status' => 'confirmed'
                ]);

                // Marquer le slot comme indisponible
                AppointmentSlot::where('employee_id', auth()->user()->employee->id)
                    ->where('date', Carbon::parse($request->prochain_rdv)->format('Y-m-d'))
                    ->where('time', Carbon::parse($request->prochain_rdv)->format('H:i:s'))
                    ->update(['is_available' => false]);
                    

                DB::commit();
                
                return redirect()->route('consultation.index')->with('success', 'Consultation enregistrée.');
            
            } catch (\Exception $e) {
                DB::rollback();
                return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
            }
        
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
        $hopital = Hopital::first();

        return view('consultations.facture.new_ordonnance', compact([
            'consultation', 'hopital'
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

    public function store_old(Request $request)
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

        $patient = Patient::find($request->patient_id);
        $patient->first_visit = false;
        $patient->save();

        if(!$patient->antecedant->exists()){
            
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
        }


        return redirect()->route('consultation.index')->with('success', 'Consultation enregistrée.');
    }

    public function show($id){
        $consultation = Consultation::find($id);
        $hopital = Hospital::first();
        return view('consultations.show', compact([
            'consultation', 'hopital'
        ]));
    }

    public function edit($id)
    {
        $consultation = Consultation::with(['patient', 'services', 'tests', 'packages', 'medicaments'])->findOrFail($id);
        $patients = Patient::all();
        $services = Service::all();
        $tests = Test::all();
        $packages = Package::all();
        $medicaments = Medicament::all();

        return view('consultations.edit', compact('consultation', 'patients', 'services', 'tests', 'packages', 'medicaments'));
    }

    /**
     * Synchroniser les médicaments avec les quantités
     */
    public function syncMedicamentsWithQuantities(array $medicaments, array $quantities = [])
    {
        $syncData = [];
        
        foreach ($medicaments as $medicamentId) {
            $medicament = Medicament::find($medicamentId);
            if ($medicament) {
                $quantity = $quantities[$medicamentId] ?? 1;
                $unitPrice = $medicament->amount;
                $totalPrice = $unitPrice * $quantity;

                $syncData[$medicamentId] = [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        $this->medicaments()->sync($syncData);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'motif' => 'required|string',
            'signes_cliniques' => 'nullable|string',
            'diagnostic' => 'required|string',
            'observation' => 'nullable|string',
            'prochain_rdv' => 'nullable|date'
        ]);


        DB::beginTransaction();
        
        try {

            $consultation = Consultation::findOrFail($id);

            ConsultationService::updateProchainRdvAppoitments($consultation, $request->prochain_rdv);

            $consultation->update([
                ...$request->only(['patient_id', 'motif', 'diagnostic', 'observation', 'prochain_rdv']),
                'medecin_id' => auth()->user()->employee->id,
                'department_id' => auth()->user()->employee->department_id,
                'signes_cliniques' => explode(',', $request->signes_cliniques)
            ]);

            $syncData = [];

            $quantities = $request->medicament_quantities ?? [];
        
            foreach ($request->medicaments as $medicamentId) {
                if ($medicamentId) {
                    $quantity = $quantities[$medicamentId] ?? 1;
                    $syncData[$medicamentId] = [
                        'quantity' => $quantity,
                    ];
                }
            }

            // Mise à jour des liaisons
            $consultation->medicaments()->sync($syncData);
            $consultation->services()->sync($request->services);
            $consultation->packages()->sync($request->packages);
            $consultation->tests()->sync($request->tests);

            // Fichiers joints
            // if ($fichierIds = $request->input('fichiers_enregistres')) {
            //     FichierPatient::whereIn('id', $fichierIds)->update(['used_by' => 'medecin']);
            //     $consultation->fichiers()->sync($fichierIds);
            // }

            $consultation->load(['services', 'packages', 'tests', 'medicaments']);
            
            $items  = $this->consultationService->calculateAmountAndReturnItems($consultation);

            $newAmount = $items['total_amount'];

            // Calculer la différence AVANT updateOrCreate
            $oldTransaction = $consultation->transaction;
            $oldAmount = $oldTransaction?->sub_total ?? 0;
            $diffAmount = $newAmount - $oldAmount;
            
            $patient = Patient::find($request->patient_id);
            
            // Gérer la différence seulement si elle existe
            $accountID = null;
            if ($diffAmount != 0) {
                // Mettre à jour le compte avec la différence
                $accountID = TransactionService::mettreAJourCompte(
                    $request->patient_id,
                    Patient::class,
                    abs($diffAmount),
                    $diffAmount >= 0 ? 'credit' : 'debit'
                );
            } else {
                // Garder l'ancien account_id si pas de changement
                $accountID = $oldTransaction?->account_id;
            }

            // ✅ CORRECTION 4: Mise à jour ou création de la transaction
            $oldTransaction->update([
                    'account_id' => $accountID,
                    'patient_id' => $request->patient_id,
                    'description'=> $consultation->motif,
                    'sub_total'  => $newAmount,
                    'total'      => $newAmount
            ]);

            $transaction = $oldTransaction;


            // ✅ CORRECTION 5: Vérifier que la facture existe avant de la mettre à jour
            $this->insuranceCalculation->updateInvoiceForConsultation(
                $request->patient_id, 
                $transaction->invoice->id, 
                $consultation,
                $items
            );

            $consultation->update(['est_facturee' => true]);

            /**
             * ✅ Modification de la partie des antécédents médicaux
             */
            Antecedent::updateOrCreate(
                ['patient_id' => $request->patient_id], // Condition de recherche
                [
                    'antecedents_medicaux'           => $request->antecedents_medicaux,
                    'antecedents_chirurgicaux'       => $request->antecedents_chirurgicaux,
                    'antecedents_gyneco_obstetricaux'=> $request->antecedents_gyneco_obstetricaux,
                    'antecedents_familiaux'          => $request->antecedents_familiaux,
                    'allergies'                      => $request->allergies,
                    'traitements_cours'              => $request->traitements_cours
                ] // Données à mettre à jour/créer
            );

            DB::commit();
            
            return redirect()->route('consultation.index')->with('success', 'Consultation modifiée avec succès.');
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur lors de la mise à jour de consultation: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
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

    public function destroy($id)
    {
        $consultation = Consultation::with(['transaction', 'services', 'tests', 'packages', 'medicaments'])->findOrFail($id);

        // Récupérer la transaction associée
        $transaction = $consultation->transaction;

        if ($transaction) {
            // ✅ Récupérer et ajuster le solde du compte
            $account = $transaction->account;
            if ($account) {
                $account->balance -= $transaction->sub_total;
                $account->save();
            }

            // ❌ Supprimer la transaction
            $transaction->delete();
        }

        // 🔗 Détacher toutes les relations
        $consultation->services()->detach();
        $consultation->tests()->detach();
        $consultation->packages()->detach();
        $consultation->medicaments()->detach();

        // 🗑️ Supprimer la consultation
        $consultation->delete();

        return redirect()->route('consultation.index')->with('success', 'Consultation supprimée avec succès.');
    }


    public function store_with_old(Request $request)
    {

        DB::beginTransaction();
        
        // try {
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
            $invoiceItems = []; // Pour stocker les éléments à facturer
            
            foreach ($selectedItems as $category => $items) {
                $syncValues = [];

                foreach ($items as $item) {
                    // Construire la clé de billing_status
                    $prefix = $category === 'examens' ? 'examen' : rtrim($category, 's');
                    $billingKey = $prefix . '-' . $item['value'];
                    $shouldBill = $billingStatus[$billingKey] ?? false;
                    
                    // Préparer les données pour sync avec pivot
                    $syncValues[$item['value']] = [
                        'facturer' => $shouldBill
                    ];

                    // Si l'item doit être facturé, l'ajouter aux éléments de facture
                    if ($shouldBill) {
                        $invoiceItems[] = [
                            'category' => $category,
                            'item_id' => $item['value'],
                            'description' => $item['label'] ?? '',
                            'unit_price' => 0, // Sera mis à jour après la sync
                            'quantity' => 1
                        ];
                    }
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

            // Calcul montant consultation et mise à jour des prix dans invoiceItems
            $consultation_amount = 0;
            
            foreach ($invoiceItems as &$invoiceItem) {
               
                $model = null;
                $amount = 0;
                
                switch ($invoiceItem['category']) {
                    case 'services':
                        $model = $consultation->services()->where('services.id', $invoiceItem['item_id'])->first();
                        $amount = $model?->amount ?? 0;
                        break;
                    case 'packages':
                        $model = $consultation->packages()->where('packages.id', $invoiceItem['item_id'])->first();
                        $amount = $model?->price ?? 0;
                        break;
                    case 'examens':
                        $model = $consultation->tests()->where('tests.id', $invoiceItem['item_id'])->first();
                        $amount = $model?->amount ?? 0;
                        break;
                    case 'medicaments':
                        $model = $consultation->medicaments()->where('medicaments.id', $invoiceItem['item_id'])->first();
                        $amount = $model?->amount ?? 0;
                        break;
                }
                
                $invoiceItem['unit_price'] = $amount;
                $invoiceItem['total_amount'] = $amount * $invoiceItem['quantity'];
                $consultation_amount += $invoiceItem['total_amount'];
            }

            // Récupérer les informations d'assurance du patient
            $patient = Patient::with('activeInsurance.insuranceCompany')->find($request->patient_id);
            $patientInsurance = $patient->activeInsurance;
            // Calcul des montants assurance/patient
            $insurance_amount = 0;
            $patient_amount = $consultation_amount;
            
            if ($patientInsurance && $patientInsurance->status) {
                $coverage_percentage = $patientInsurance->coverage_percentage ?? 0;
                $insurance_amount = ($consultation_amount * $coverage_percentage) / 100;
                $patient_amount = $consultation_amount - $insurance_amount;
            }

            // Mise à jour compte patient (seulement la part patient)
            $accountID = TransactionService::mettreAJourCompte($request->patient_id, Patient::class, $patient_amount, 'credit');

            // Création transaction
            $transaction = $consultation->transaction()->create([
                'user_id'         => auth()->id(),
                'account_id'      => $accountID,
                'patient_id'      => $request->patient_id,
                'consultation_id' => $consultation->id,
                'description'     => $consultation->motif,
                'tax_amount'      => 0,
                'discount'        => 0,
                'sub_total'       => $consultation_amount
            ]);

            // ✅ Création de la facture
            $invoice = $transaction->invoice()->create([
                'transaction_id' => $transaction->id,
                'insurance_company_id' => $patientInsurance?->insurance_company_id,
                'patient_insurance_id' => $patientInsurance?->id,
                // 'total_amount' => $consultation_amount,
                'patient_amount' => $patient_amount,
                'insurance_amount' => $insurance_amount,
                'insurance_status' => $insurance_amount > 0 ? 'pending' : null,
                'patient_amount_status' => $patient_amount > 0 ? 'pending' : null,
            ]);


            // ✅ Création des éléments de facture détaillés
            foreach ($invoiceItems as $item) {
                $coverage_percentage_applied = null;
                $insurance_covered_amount = 0;
                $item_patient_amount = $item['total_amount'];
                
                if ($patientInsurance && $patientInsurance->status) {
                    $coverage_percentage_applied = $patientInsurance->coverage_percentage ?? 0;
                    $insurance_covered_amount = ($item['total_amount'] * $coverage_percentage_applied) / 100;
                    $item_patient_amount = $item['total_amount'] - $insurance_covered_amount;
                }

                $invoice->items()->create([
                    'coverage_type_type' => $this->getCoverageTypeModel($item['category']),
                    'coverage_type_id' => $item['item_id'],
                    'description' => $item['description'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'total_amount' => $item['total_amount'],
                    'insurance_covered_amount' => $insurance_covered_amount,
                    'patient_amount' => $item_patient_amount,
                    'coverage_percentage_applied' => $coverage_percentage_applied,
                ]);
            }

            // Marque la consultation comme facturée
            $consultation->update(['est_facturee' => true]);

            // Ajout des antécédents médicaux
            Antecedent::updateOrCreate(
                ['patient_id' => $request->patient_id],
                [
                    'antecedents_medicaux'           => $request->antecedents_medicaux,
                    'antecedents_chirurgicaux'       => $request->antecedents_chirurgicaux,
                    'antecedents_gyneco_obstetricaux'=> $request->antecedents_gyneco_obstetricaux,
                    'antecedents_familiaux'          => $request->antecedents_familiaux,
                    'allergies'                      => $request->allergies,
                    'traitements_cours'              => $request->traitements_cours,
                ]
            );

            // Mise à jour du statut de première visite
            $updateNewVisit = Patient::find($request->patient_id);
            $updateNewVisit->first_visit = false;
            $updateNewVisit->save();

            DB::commit();
            
            return redirect()->route('consultation.index')->with('success', 'Consultation et facture enregistrées avec succès.');
            
        // } catch (\Exception $e) {
        //     DB::rollBack();
            
        //     return redirect()->back()
        //         ->withInput()
        //         ->withErrors(['error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()]);
        // }
    }

    /**
     * Retourne le nom du modèle selon la catégorie pour la relation polymorphe
     */
    private function getCoverageTypeModel($category)
    {
        return match ($category) {
            'services' => 'App\\Models\\Service',
            'packages' => 'App\\Models\\Package', // Ajustez selon votre namespace
            'examens' => 'App\\Models\\Test',
            'medicaments' => 'App\\Models\\Medicament',
            default => null
        };
    }

}
