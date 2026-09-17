<?php

namespace App\Http\Controllers;

use App\Models\Antecedent;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\FichierPatient;
use App\Models\Hospital;
use App\Models\Medicament;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Test;
use App\Services\BillingService;
use App\Services\ConsultationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ConsultationController extends Controller
{
    public function __construct(
        private ConsultationService $consultationService,
        private BillingService $billingService
    ) {
    }

    public function show(Consultation $consultation)
    {
        $consultation->load([
            'patient.antecedant',
            'medecin',
            // 'prochainMedecin',
            'department',
            'packages',
            'services',
            'tests',
            'medicaments',
            'fichiers',
            'transaction.invoice.items',
            'transaction.paiements',
        ]);

        $hopital = auth()->user()->hospital ?? null;

        return view('consultations.show', compact('consultation', 'hopital'));
        
    }

    public function ordonnanceA80(Consultation $consultation)
    {
        $consultation->load(['patient', 'medecin', 'department', 'medicaments']);
        $hopital = auth()->user()->hospital ?? null;

        return view('consultations.rapport.ordonnance-a80', compact('consultation', 'hopital'));
    }

    public function ordonnanceA5(Consultation $consultation)
    {
        $consultation->load(['patient', 'medecin', 'department', 'medicaments']);
        $hopital = auth()->user()->hospital ?? null;

        return view('consultations.rapport.ordonnance-a5', compact('consultation', 'hopital'));
    }

    public function examensA80(Consultation $consultation)
    {
        $consultation->load(['patient', 'medecin', 'department', 'tests']);
        $hopital = auth()->user()->hospital ?? null;

        return view('consultations.rapport.examens-a80', compact('consultation', 'hopital'));
    }

    public function examensA5(Consultation $consultation)
    {
        $consultation->load(['patient', 'medecin', 'department', 'tests']);
        $hopital = auth()->user()->hospital ?? null;

        return view('consultations.rapport.examens-a5', compact('consultation', 'hopital'));
    }

    public function facturePdfA5(Consultation $consultation)
    {
        $consultation->load([
            'patient',
            'medecin',
            'department',
            'transaction.invoice.items'
        ]);

        $hopital = auth()->user()->hospital ?? null;

        $invoiceData = $this->prepareInvoiceData($consultation);


        $pdf = Pdf::loadView('consultations.rapport.facture-a5', [
            'consultation' => $consultation,
            'hopital' => $hopital,
            'invoiceData' => $invoiceData
        ]);

        return $pdf
            ->setPaper('A5', 'portrait')
            ->stream('ticket-'.$consultation->id.'.pdf');
    }


    public function facturePdfA80(Consultation $consultation)
    {
        $consultation->load([
            'patient',
            'transaction.invoice.items'
        ]);

        $hopital = auth()->user()->hospital ?? null;
        $invoiceData = $this->prepareInvoiceData($consultation);

        $itemCount = optional($invoiceData['invoice'])->items->count() ?? 1;

        $height = 200 + ($itemCount * 35);

        $pdf = Pdf::loadView('consultations.rapport.facture-a80', compact(
            'consultation',
            'hopital',
            'invoiceData'
        ));

        return $pdf
            ->setPaper([0, 0, 226.77, $height], 'portrait')
            ->stream('ticket-'.$consultation->id.'.pdf');
    }

    public function recuPdfA80(Consultation $consultation)
    {
        $consultation->load([
            'patient',
            'transaction.invoice',
            'transaction.paiements'
        ]);

        $hopital = auth()->user()->hospital ?? null;

        $invoiceData = $this->prepareInvoiceData($consultation);

        $pdf = Pdf::loadView('consultations.rapport.paiement-a80', compact(
            'consultation','hopital','invoiceData'
        ));

        return $pdf
            ->setPaper([0, 0, 226.77, 600], 'portrait') // A80
            ->stream('recu-'.$consultation->id.'.pdf');
    }

     public function recuPdfA5(Consultation $consultation)
    {
        $consultation->load([
            'patient',
            'transaction.invoice',
            'transaction.paiements'
        ]);

        $hopital = auth()->user()->hospital ?? null;

        $invoiceData = $this->prepareInvoiceData($consultation);

        $pdf = Pdf::loadView('consultations.rapport.paiement-a5', compact(
            'consultation','hopital','invoiceData'
        ));

        return $pdf
            ->setPaper('A5', 'portrait') // A5
            ->stream('recu-'.$consultation->id.'.pdf');
    }

    private function prepareInvoiceData($consultation)
    {
        $invoice = optional($consultation->transaction)->invoice;

        if (!$invoice) {
            return null;
        }

        // 🧠 PART PATIENT = montant réel à payer
        $patientAmount = $invoice->patient_amount ?? 0;

        // 💰 MONTANT PAYÉ
        $paidAmount = $consultation->transaction->montant_payer ?? 0;

        // 📊 RESTE À PAYER (UNIQUEMENT PATIENT)
        $remaining = max($patientAmount - $paidAmount, 0);

        // ✅ STATUT
        $status = $remaining <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

        return [
            'invoice' => $invoice,
            'patient_amount' => $patientAmount,
            'paid_amount' => $paidAmount,
            'remaining' => $remaining,
            'status' => $status,
        ];
    }


    public function index()
    {
        $consultations = Consultation::latest()->get();

        return view('consultations.index', compact('consultations'));
    }

    public function create()
    {
        // $employeeDepartmentId = auth()->user()->employee->department_id;

        return view('consultations.new', [
            'patients' => Patient::suivisParEtablissement()->orderBy('first_name')->get(),

            'services' => Service::select('id', 'name', 'amount')
                // ->where('department_id', $employeeDepartmentId)
                ->orderBy('name')
                ->get(),

            'packages' => Package::select('id', 'name', 'price')
                ->orderBy('name')
                ->get(),

            'medicaments' => Medicament::select('id', 'nom', 'amount')
                ->orderBy('nom')
                ->get(),

            'tests' => Test::select('id', 'name', 'amount')
                ->orderBy('name')
                ->get(),

            'fichiersPatients' => FichierPatient::select('id', 'nom_fichier', 'patient_id')
                ->where('used_by', 'accueil')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $isMedecin = auth()->user()->hasRole('medecin');

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'motif' => $isMedecin ? 'required|string' : 'nullable|string',
            'signes_cliniques' => 'nullable|string',
            'diagnostic' => $isMedecin ? 'required|string' : 'nullable|string',
            'observation' => 'nullable|string',
            'prochain_rdv' => 'nullable|date',
            'selected_items' => 'nullable|string',
        ];

        $validated = $request->validate($rules);

        if (!$isMedecin) {
            $validated['motif'] = $validated['motif'] ?? 'N/A';
            $validated['diagnostic'] = $validated['diagnostic'] ?? 'N/A';
        }

        DB::beginTransaction();

        try {
            
            $consultationData = [
                ...$validated,
                'department_id' => auth()->user()->employee->department_id,
                'signes_cliniques' => $request->filled('signes_cliniques')
                    ? explode(',', $request->signes_cliniques)
                    : [],
            ];

            if (auth()->user()->employee) {
                $consultationData['medecin_id'] = auth()->user()->employee->id;
            }

            $consultation = Consultation::create($consultationData);

            $selectedItems = json_decode($request->selected_items ?? '[]', true);
            if (!empty($selectedItems)) {
                $this->consultationService->attachItems($consultation, $selectedItems);
            }

            $consultation->load(['patient', 'services', 'packages', 'tests', 'medicaments']);

            $this->billingService->createFromConsultation($consultation);

            $patient = $consultation->patient;
            if ($patient) {
                $patient->first_visit = false;
                $patient->save();
            }

            if ($isMedecin && $patient && !$patient->antecedant()->exists()) {
                Antecedent::updateOrCreate(
                    ['patient_id' => $patient->id],
                    [
                        'antecedents_medicaux' => $request->antecedents_medicaux,
                        'antecedents_chirurgicaux' => $request->antecedents_chirurgicaux,
                        'antecedents_gyneco_obstetricaux' => $request->antecedents_gyneco_obstetricaux,
                        'antecedents_familiaux' => $request->antecedents_familiaux,
                        'allergies' => $request->allergies,
                        'traitements_cours' => $request->traitements_cours,
                    ]
                );
            }

            if ($isMedecin && $request->filled('prochain_rdv')) {
                $this->consultationService->createNextAppointment($consultation, $request->prochain_rdv);
            }

            DB::commit();

            return redirect()->route('consultation.index')
                ->with('success', 'Consultation enregistrée.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $consultation = Consultation::with(['patient', 'services', 'tests', 'packages', 'medicaments'])
            ->findOrFail($id);

        $patients = Patient::suivisParEtablissement()->orderBy('last_name')->get();
        $services = Service::all();
        $tests = Test::all();
        $packages = Package::all();
        $medicaments = Medicament::all();

        return view('consultations.edit', compact(
            'consultation',
            'patients',
            'services',
            'tests',
            'packages',
            'medicaments'
        ));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'motif' => 'required|string',
            'signes_cliniques' => 'nullable|string',
            'diagnostic' => 'required|string',
            'observation' => 'nullable|string',
            'prochain_rdv' => 'nullable|date',
            'services' => 'nullable|array',
            'packages' => 'nullable|array',
            'tests' => 'nullable|array',
            'medicaments' => 'nullable|array',
            'medicament_quantities' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {

            $consultation = Consultation::with(['transaction', 'patient'])->findOrFail($id);

            $this->consultationService->updateNextAppointment(
                $consultation,
                $request->prochain_rdv
            );

            $consultation->update([
                ...$request->only(['patient_id', 'motif', 'diagnostic', 'observation', 'prochain_rdv']),
                'medecin_id' => auth()->user()->employee->id,
                'department_id' => auth()->user()->employee->department_id,
                'signes_cliniques' => $request->filled('signes_cliniques')
                    ? explode(',', $request->signes_cliniques)
                    : [],
            ]);

            $syncMedicaments = [];
            $quantities = $request->medicament_quantities ?? [];

            foreach (($request->medicaments ?? []) as $medicamentId) {
                if ($medicamentId) {
                    $syncMedicaments[$medicamentId] = [
                        'quantity' => $quantities[$medicamentId] ?? 1,
                    ];
                }
            }

            $consultation->medicaments()->sync($syncMedicaments);
            $consultation->services()->sync($request->services ?? []);
            $consultation->packages()->sync($request->packages ?? []);
            $consultation->tests()->sync($request->tests ?? []);

            $consultation->load(['patient', 'services', 'packages', 'tests', 'medicaments', 'transaction']);

            if ($consultation->transaction) {
                $this->billingService->recalculate($consultation->transaction);
            } else {
                $this->billingService->createFromConsultation($consultation);
            }

            Antecedent::updateOrCreate(
                ['patient_id' => $request->patient_id],
                [
                    'antecedents_medicaux' => $request->antecedents_medicaux,
                    'antecedents_chirurgicaux' => $request->antecedents_chirurgicaux,
                    'antecedents_gyneco_obstetricaux' => $request->antecedents_gyneco_obstetricaux,
                    'antecedents_familiaux' => $request->antecedents_familiaux,
                    'allergies' => $request->allergies,
                    'traitements_cours' => $request->traitements_cours,
                ]
            );

            DB::commit();

            return redirect()->route('consultation.index')
                ->with('success', 'Consultation modifiée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la mise à jour de consultation: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $consultation = Consultation::with([
                'transaction.invoice.items',
                'transaction.account',
                'patient',
                'services',
                'tests',
                'packages',
                'medicaments'
            ])->findOrFail($id);

            $transaction = $consultation->transaction;

            if ($transaction) {
                $account = $transaction->account;
                if ($account) {
                    $account->balance -= (float) $transaction->sub_total;
                    $account->save();
                }

                if ($transaction->invoice) {
                    $transaction->invoice->items()->delete();
                    $transaction->invoice->delete();
                }

                $transaction->paiements()->delete();
                $transaction->delete();
            }

            $consultation->services()->detach();
            $consultation->tests()->detach();
            $consultation->packages()->detach();
            $consultation->medicaments()->detach();

            $consultation->delete();

            DB::commit();

            return redirect()->route('consultation.index')
                ->with('success', 'Consultation supprimée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }
}