<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use App\Models\Invoices;
use App\Models\InvoiceItem;
use App\Models\PatientInsurance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InvoicesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = Invoices::with(['items', 'insuranceCompany', 'patientInsurance', 'transaction'])->get();
        $transactions = Transaction::all();
        $insuranceCompanies = InsuranceCompany::all();
        $patientInsurances = PatientInsurance::all();
        return view('invoices.index', compact('invoices', 'transactions', 'insuranceCompanies', 'patientInsurances'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Tu peux passer ici les listes de companies d'assurance, patients, transactions, etc.
        $insuranceCompanies = \App\Models\InsuranceCompany::all();
        $transactions = \App\Models\Transaction::all();
        $patientInsurances = \App\Models\PatientInsurance::all();
        
        // Pour les types de couverture polymorphes (ex: Services, Products)
        $coverageTypes = [
            'services' => \App\Models\Service::all(), // Assure-toi d'avoir ces modèles
            'examens' => \App\Models\Test::all(), // Assure-toi d'avoir ces modèles
            'medicaments' => \App\Models\Medicament::all(), // Assure-toi d'avoir ces modèles
            // Ajoute d'autres types si nécessaire
        ];

        return view('invoices.create', compact('insuranceCompanies', 'transactions', 'patientInsurances', 'coverageTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'insurance_company_id' => 'nullable|exists:insurance_companies,id',
            'patient_insurance_id' => 'nullable|exists:patient_insurances,id',
            'total_amount' => 'required|numeric|min:0',
            'patient_amount' => 'required|numeric|min:0',
            'insurance_amount' => 'required|numeric|min:0',
            'insurance_status' => ['nullable', Rule::in(['pending', 'submitted', 'approved', 'rejected', 'paid'])],
            'patient_amount_status' => ['nullable', Rule::in(['pending', 'rejected', 'paid'])],
            'insurance_submission_date' => 'nullable|date',
            'insurance_payment_date' => 'nullable|date',
            'insurance_claim_number' => 'nullable|string|max:255',
            'insurance_notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.total_amount' => 'required|numeric|min:0',
            'items.*.insurance_covered_amount' => 'required|numeric|min:0',
            'items.*.patient_amount' => 'required|numeric|min:0',
            'items.*.coverage_percentage_applied' => 'nullable|numeric|min:0|max:100',
            'items.*.coverage_type_type' => 'required|string', // Ex: App\Models\Service
            'items.*.coverage_type_id' => 'required|integer',
        ]);

        DB::transaction(function () use ($request) {
            $invoice = Invoices::create($request->except('items'));

            foreach ($request->input('items') as $itemData) {
                // Assurez-vous que les modèles polymorphes existent et sont valides
                $modelClass = $itemData['coverage_type_type'];
                if (!class_exists($modelClass) || !is_a($modelClass, \Illuminate\Database\Eloquent\Model::class, true)) {
                    throw new \Exception("Invalid coverage_type_type: {$modelClass}");
                }
                
                // Vérifie si l'ID existe pour le type de modèle donné
                $relatedModel = $modelClass::find($itemData['coverage_type_id']);
                if (!$relatedModel) {
                    throw new \Exception("Related model with ID {$itemData['coverage_type_id']} not found for type {$modelClass}");
                }

                $invoice->items()->create([
                    'coverage_type_type' => $modelClass,
                    'coverage_type_id' => $itemData['coverage_type_id'],
                    'description' => $itemData['description'],
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'total_amount' => $itemData['total_amount'],
                    'insurance_covered_amount' => $itemData['insurance_covered_amount'],
                    'patient_amount' => $itemData['patient_amount'],
                    'coverage_percentage_applied' => $itemData['coverage_percentage_applied'],
                ]);
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Facture créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoices $invoice)
    {
        $invoice->load(['items', 'insuranceCompany', 'patientInsurance', 'transaction']);
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoices $invoice)
    {
        $invoice->load('items'); // Charger les items pour l'édition
        
        $insuranceCompanies = \App\Models\InsuranceCompany::all();
        $transactions = \App\Models\Transaction::all();
        $patientInsurances = \App\Models\PatientInsurance::all();
        
        $coverageTypes = [
            'services' => \App\Models\Service::all(), // Assure-toi d'avoir ces modèles
            'examens' => \App\Models\Test::all(), // Assure-toi d'avoir ces modèles
            'medicaments' => \App\Models\Medicament::all(), // Assure-toi d'avoir ces modèles
            
        ];

        return view('invoices.edit', compact('invoice', 'insuranceCompanies', 'transactions', 'patientInsurances', 'coverageTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoices $invoice)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'insurance_company_id' => 'nullable|exists:insurance_companies,id',
            'patient_insurance_id' => 'nullable|exists:patient_insurances,id',
            'total_amount' => 'required|numeric|min:0',
            'patient_amount' => 'required|numeric|min:0',
            'insurance_amount' => 'required|numeric|min:0',
            'insurance_status' => ['nullable', Rule::in(['pending', 'submitted', 'approved', 'rejected', 'paid'])],
            'patient_amount_status' => ['nullable', Rule::in(['pending', 'rejected', 'paid'])],
            'insurance_submission_date' => 'nullable|date',
            'insurance_payment_date' => 'nullable|date',
            'insurance_claim_number' => 'nullable|string|max:255',
            'insurance_notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.id' => 'nullable|exists:invoice_items,id', // Pour les items existants à mettre à jour
            'items.*.description' => 'required|string|max:255',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.total_amount' => 'required|numeric|min:0',
            'items.*.insurance_covered_amount' => 'required|numeric|min:0',
            'items.*.patient_amount' => 'required|numeric|min:0',
            'items.*.coverage_percentage_applied' => 'nullable|numeric|min:0|max:100',
            'items.*.coverage_type_type' => 'required|string',
            'items.*.coverage_type_id' => 'required|integer',
        ]);

        DB::transaction(function () use ($request, $invoice) {
            $invoice->update($request->except('items'));

            $existingItemIds = $invoice->items()->pluck('id')->toArray();
            $itemsToKeep = [];

            foreach ($request->input('items') as $itemData) {
                $modelClass = $itemData['coverage_type_type'];
                if (!class_exists($modelClass) || !is_a($modelClass, \Illuminate\Database\Eloquent\Model::class, true)) {
                    throw new \Exception("Invalid coverage_type_type: {$modelClass}");
                }
                $relatedModel = $modelClass::find($itemData['coverage_type_id']);
                if (!$relatedModel) {
                    throw new \Exception("Related model with ID {$itemData['coverage_type_id']} not found for type {$modelClass}");
                }

                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = InvoiceItem::find($itemData['id']);
                    if ($item && $item->invoice_id === $invoice->id) {
                        $item->update([
                            'coverage_type_type' => $modelClass,
                            'coverage_type_id' => $itemData['coverage_type_id'],
                            'description' => $itemData['description'],
                            'unit_price' => $itemData['unit_price'],
                            'quantity' => $itemData['quantity'],
                            'total_amount' => $itemData['total_amount'],
                            'insurance_covered_amount' => $itemData['insurance_covered_amount'],
                            'patient_amount' => $itemData['patient_amount'],
                            'coverage_percentage_applied' => $itemData['coverage_percentage_applied'],
                        ]);
                        $itemsToKeep[] = $item->id;
                    }
                } else {
                    // Create new item
                    $newItem = $invoice->items()->create([
                        'coverage_type_type' => $modelClass,
                        'coverage_type_id' => $itemData['coverage_type_id'],
                        'description' => $itemData['description'],
                        'unit_price' => $itemData['unit_price'],
                        'quantity' => $itemData['quantity'],
                        'total_amount' => $itemData['total_amount'],
                        'insurance_covered_amount' => $itemData['insurance_covered_amount'],
                        'patient_amount' => $itemData['patient_amount'],
                        'coverage_percentage_applied' => $itemData['coverage_percentage_applied'],
                    ]);
                    $itemsToKeep[] = $newItem->id;
                }
            }

            // Supprimer les items qui ne sont plus dans la requête
            InvoiceItem::where('invoice_id', $invoice->id)
                ->whereNotIn('id', $itemsToKeep)
                ->delete();
        });

        return redirect()->route('invoices.index')->with('success', 'Facture mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoices $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Facture supprimée avec succès.');
    }

    public function getItems($id)
    {
        $invoice = Invoices::with(['items', 'transaction.patient'])->findOrFail($id);
        
        return view('partials.invoice-items', compact('invoice'));
    }


    public function consulter(Request $request)
    {
        DB::beginTransaction();
        
        try {
            // Validation
            $validated = $this->validateConsultationRequest($request);
            
            // Création de la consultation
            $consultation = $this->createConsultation($request);
            
            // Gestion des items sélectionnés et facturation
            $invoiceItems = $this->processSelectedItems($request, $consultation);
            
            // Gestion des fichiers
            $this->handleAttachedFiles($request);
            
            // Calcul des montants et création de la facture
            $consultationAmount = $this->calculateConsultationAmount($invoiceItems);
            $transaction = $this->createTransaction($request, $consultation, $consultationAmount);
            $this->createInvoiceWithItems($transaction, $consultation, $invoiceItems, $consultationAmount);
            
            // Finalisation
            $this->finalizeConsultation($request, $consultation);
            
            DB::commit();
            
            return redirect()->route('consultation.index')->with('success', 'Consultation et facture enregistrées avec succès.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()]);
        }
    }

    /**
     * Validation des données de la consultation
     */
    private function validateConsultationRequest(Request $request)
    {
        return $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'motif' => 'required|string',
            'signes_cliniques' => 'nullable|string',
            'diagnostic' => 'required|string',
            'observation' => 'nullable|string',
            'prochain_rdv' => 'nullable|date'
        ]);
    }

    /**
     * Création de la consultation
     */
    private function createConsultation(Request $request)
    {
        $data = $request->only([
            'patient_id','motif', 'signes_cliniques', 'diagnostic', 'observation', 'prochain_rdv'
        ]);

        $data['medecin_id'] = auth()->user()->employee->id;
        $data['department_id'] = auth()->user()->employee->department_id;

        if ($request->signes_cliniques) {
            $data['signes_cliniques'] = explode(',', $request->signes_cliniques);
        }

        return Consultation::create($data);
    }

    /**
     * Traitement des items sélectionnés et liaison avec la consultation
     */
    private function processSelectedItems(Request $request, Consultation $consultation)
    {
        $selectedItems = json_decode($request->selected_items, true);
        $billingStatus = json_decode($request->billing_status, true);
        $invoiceItems = [];
        
        foreach ($selectedItems as $category => $items) {
            $syncValues = [];

            foreach ($items as $item) {
                $prefix = $category === 'examens' ? 'examen' : rtrim($category, 's');
                $billingKey = $prefix . '-' . $item['value'];
                $shouldBill = $billingStatus[$billingKey] ?? false;
                
                $syncValues[$item['value']] = ['facturer' => $shouldBill];

                if ($shouldBill) {
                    $invoiceItems[] = [
                        'category' => $category,
                        'item_id' => $item['value'],
                        'description' => $item['label'] ?? '',
                        'quantity' => 1
                    ];
                }
            }

            $this->syncItemsByCategory($consultation, $category, $syncValues);
        }

        return $this->enrichInvoiceItemsWithPrices($consultation, $invoiceItems);
    }

    /**
     * Synchronisation des items par catégorie
     */
    private function syncItemsByCategory(Consultation $consultation, string $category, array $syncValues)
    {
        match ($category) {
            'medicaments' => $consultation->medicaments()->sync($syncValues),
            'services'    => $consultation->services()->sync($syncValues),
            'packages'    => $consultation->packages()->sync($syncValues),
            'examens'     => $consultation->tests()->sync($syncValues),
            default       => null
        };
    }

    /**
     * Enrichissement des items avec les prix
     */
    private function enrichInvoiceItemsWithPrices(Consultation $consultation, array $invoiceItems)
    {
        foreach ($invoiceItems as &$item) {
            $amount = $this->getItemAmount($consultation, $item['category'], $item['item_id']);
            $item['unit_price'] = $amount;
            $item['total_amount'] = $amount * $item['quantity'];
        }

        return $invoiceItems;
    }

    /**
     * Récupération du montant d'un item selon sa catégorie
     */
    private function getItemAmount(Consultation $consultation, string $category, int $itemId)
    {
        $model = match ($category) {
            'services' => $consultation->services()->where('services.id', $itemId)->first(),
            'packages' => $consultation->packages()->where('packages.id', $itemId)->first(),
            'examens' => $consultation->tests()->where('tests.id', $itemId)->first(),
            'medicaments' => $consultation->medicaments()->where('medicaments.id', $itemId)->first(),
            default => null
        };

        return match ($category) {
            'services', 'examens', 'medicaments' => $model?->amount ?? 0,
            'packages' => $model?->price ?? 0,
            default => 0
        };
    }

    /**
     * Gestion des fichiers joints
     */
    private function handleAttachedFiles(Request $request)
    {
        if ($fichierIds = $request->input('fichiers_enregistres')) {
            FichierPatient::whereIn('id', $fichierIds)->update(['used_by' => 'medecin']);
        }
    }

    /**
     * Calcul du montant total de la consultation
     */
    private function calculateConsultationAmount(array $invoiceItems)
    {
        return array_sum(array_column($invoiceItems, 'total_amount'));
    }

    /**
     * Création de la transaction
     */
    private function createTransaction(Request $request, Consultation $consultation, float $consultationAmount)
    {
        $patient = Patient::with('activeInsurance')->find($request->patient_id);
        $patientAmount = $this->calculatePatientAmount($patient, $consultationAmount);
        
        $accountID = TransactionService::mettreAJourCompte($request->patient_id, Patient::class, $patientAmount, 'credit');

        return $consultation->transaction()->create([
            'user_id'         => auth()->id(),
            'account_id'      => $accountID,
            'patient_id'      => $request->patient_id,
            'consultation_id' => $consultation->id,
            'description'     => $consultation->motif,
            'tax_amount'      => 0,
            'discount'        => 0,
            'sub_total'       => $consultationAmount
        ]);
    }

    /**
     * Calcul de la part patient
     */
    private function calculatePatientAmount(Patient $patient, float $totalAmount)
    {
        $patientInsurance = $patient->activeInsurance;
        
        if (!$patientInsurance || !$patientInsurance->is_active) {
            return $totalAmount;
        }

        $coveragePercentage = $patientInsurance->coverage_percentage ?? 0;
        $insuranceAmount = ($totalAmount * $coveragePercentage) / 100;
        
        return $totalAmount - $insuranceAmount;
    }

    /**
     * Création de la facture et de ses éléments
     */
    private function createInvoiceWithItems($transaction, Consultation $consultation, array $invoiceItems, float $consultationAmount)
    {
        $patient = Patient::with('activeInsurance.company')->find($consultation->patient_id);
        $patientInsurance = $patient->activeInsurance;
        
        $insuranceAmount = 0;
        $patientAmount = $consultationAmount;
        
        if ($patientInsurance && $patientInsurance->is_active) {
            $coveragePercentage = $patientInsurance->coverage_percentage ?? 0;
            $insuranceAmount = ($consultationAmount * $coveragePercentage) / 100;
            $patientAmount = $consultationAmount - $insuranceAmount;
        }

        // Création de la facture
        $invoice = $transaction->invoice()->create([
            'transaction_id' => $transaction->id,
            'insurance_company_id' => $patientInsurance?->company?->id,
            'patient_insurance_id' => $patientInsurance?->id,
            'total_amount' => $consultationAmount,
            'patient_amount' => $patientAmount,
            'insurance_amount' => $insuranceAmount,
            'insurance_status' => $insuranceAmount > 0 ? 'pending' : null,
            'patient_amount_status' => $patientAmount > 0 ? 'pending' : null,
        ]);

        // Création des éléments de facture
        $this->createInvoiceItems($invoice, $invoiceItems, $patientInsurance);
    }

    /**
     * Création des éléments détaillés de la facture
     */
    private function createInvoiceItems($invoice, array $invoiceItems, $patientInsurance)
    {
        foreach ($invoiceItems as $item) {
            $coveragePercentageApplied = null;
            $insuranceCoveredAmount = 0;
            $itemPatientAmount = $item['total_amount'];
            
            if ($patientInsurance && $patientInsurance->is_active) {
                $coveragePercentageApplied = $patientInsurance->coverage_percentage ?? 0;
                $insuranceCoveredAmount = ($item['total_amount'] * $coveragePercentageApplied) / 100;
                $itemPatientAmount = $item['total_amount'] - $insuranceCoveredAmount;
            }

            $invoice->items()->create([
                'coverage_type_type' => $this->getCoverageTypeModel($item['category']),
                'coverage_type_id' => $item['item_id'],
                'description' => $item['description'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'total_amount' => $item['total_amount'],
                'insurance_covered_amount' => $insuranceCoveredAmount,
                'patient_amount' => $itemPatientAmount,
                'coverage_percentage_applied' => $coveragePercentageApplied,
            ]);
        }
    }

    /**
     * Finalisation de la consultation
     */
    private function finalizeConsultation(Request $request, Consultation $consultation)
    {
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
        Patient::find($request->patient_id)->update(['first_visit' => false]);
    }

    /**
     * Retourne le nom du modèle selon la catégorie pour la relation polymorphe
     */
    private function getCoverageTypeModel($category)
    {
        return match ($category) {
            'services' => 'App\\Models\\Service',
            'packages' => 'App\\Models\\Package',
            'examens' => 'App\\Models\\Test',
            'medicaments' => 'App\\Models\\Medicament',
            default => null
        };
    }

}