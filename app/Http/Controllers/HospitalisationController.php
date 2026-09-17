<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use App\Models\Hospitalisation;
use App\Models\Patient;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HospitalisationController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {
    }

    public function index()
    {
        $hospitalisations = Hospitalisation::with('patient', 'chambre')->latest()->get();

        $patients = Patient::suivisParEtablissement()->orderBy('last_name')->get();

        $chambres = Chambre::where('statut', 'Libre')
            ->orWhereIn('id', function ($query) {
                $query->select('chambre_id')
                    ->from('hospitalisations')
                    ->where('statut', '!=', 'En cours');
            })
            ->get();

        return view('hospitalisations.index', compact('hospitalisations', 'patients', 'chambres'));
    }

    public function create()
    {
        $patients = Patient::suivisParEtablissement()->orderBy('last_name')->get();
        $chambres = Chambre::where('statut', 'Libre')->get();

        return view('hospitalisations.create', compact('patients', 'chambres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'chambre_id' => 'required|exists_etablissement:chambres,id',
            'date_entree' => 'required|date',
            'nombre_jours' => 'required|integer|min:1',
            'observation' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $dateSortie = Carbon::parse($request->date_entree)->addDays((int) $request->nombre_jours);

            $hospitalisation = Hospitalisation::create([
                'patient_id' => $request->patient_id,
                'chambre_id' => $request->chambre_id,
                'date_entree' => $request->date_entree,
                'nombre_jours' => $request->nombre_jours,
                'date_sortie_prevue' => $dateSortie,
                'observation' => $request->observation,
                'statut' => 'En cours',
            ]);

            Chambre::findOrFail($request->chambre_id)->update([
                'statut' => 'Occupée'
            ]);

            DB::commit();

            return redirect()
                ->route('hospitalisations.index')
                ->with('success', 'Hospitalisation enregistrée.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de l’enregistrement: ' . $e->getMessage());
        }
    }

    public function show(Hospitalisation $hospitalisation)
    {
        $hospitalisation->load('patient', 'chambre', 'transaction.invoice.items', 'transaction.paiements');

        return view('hospitalisations.show', compact('hospitalisation'));
    }

    public function edit(Hospitalisation $hospitalisation)
    {
        $patients = Patient::suivisParEtablissement()->orderBy('last_name')->get();

        $chambres = Chambre::where('statut', 'Libre')
            ->orWhere('id', $hospitalisation->chambre_id)
            ->get();

        return view('hospitalisations.edit', compact('hospitalisation', 'patients', 'chambres'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists_etablissement:hospitalisations,id',
            'patient_id' => 'required|exists:patients,id',
            'chambre_id' => 'required|exists_etablissement:chambres,id',
            'date_entree' => 'required|date',
            'nombre_jours' => 'required|integer|min:1',
            'observation' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $hospitalisation = Hospitalisation::with('chambre', 'transaction')
                ->findOrFail($request->id);

            $ancienneChambreId = $hospitalisation->chambre_id;
            $nouvelleChambreId = (int) $request->chambre_id;

            if ($ancienneChambreId !== $nouvelleChambreId) {
                $nouvelleChambre = Chambre::findOrFail($nouvelleChambreId);

                if ($nouvelleChambre->statut !== 'Libre') {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'La chambre sélectionnée est déjà occupée.');
                }

                Chambre::findOrFail($ancienneChambreId)->update(['statut' => 'Libre']);
                $nouvelleChambre->update(['statut' => 'Occupée']);
            }

            $dateSortie = Carbon::parse($request->date_entree)->addDays((int) $request->nombre_jours);

            $hospitalisation->update([
                'patient_id' => $request->patient_id,
                'chambre_id' => $nouvelleChambreId,
                'date_entree' => $request->date_entree,
                'nombre_jours' => $request->nombre_jours,
                'date_sortie_prevue' => $dateSortie,
                'observation' => $request->observation,
            ]);

            if ($hospitalisation->transaction) {
                $this->billingService->recalculate($hospitalisation->transaction);
            }

            DB::commit();

            return redirect()
                ->route('hospitalisations.index')
                ->with('success', 'Hospitalisation mise à jour.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    public function destroy(Hospitalisation $hospitalisation)
    {
        DB::beginTransaction();

        try {
            $hospitalisation->load('chambre', 'transaction.invoice.items', 'transaction.paiements', 'transaction.account');

            if ($hospitalisation->chambre) {
                $hospitalisation->chambre->update(['statut' => 'Libre']);
            }

            $transaction = $hospitalisation->transaction;

            if ($transaction) {
                // CORRIGÉ : supprimer une pièce déjà encaissée effaçait la trace de l'argent reçu
                // (paiements supprimés) et faussait le solde (on retirait le total, pas le reste dû).
                // Paiements annulés compris : ils restent en base pour la traçabilité,
                // la pièce ne peut donc plus être supprimée (contrainte RESTRICT).
                if ($transaction->paiements()->avecAnnules()->exists()) {
                    DB::rollBack();

                    return redirect()->back()->with('error', 'Cette facture a un historique de paiements (même annulés) : elle ne peut pas être supprimée, pour garder la trace des encaissements.');
                }

                app(\App\Services\PatientAccountService::class)->retirerTransaction($transaction);

                if ($transaction->invoice) {
                    $transaction->invoice->items()->delete();
                    $transaction->invoice->delete();
                }

                $transaction->paiements()->delete();
                $transaction->delete();
            }

            $hospitalisation->delete();

            DB::commit();

            return redirect()
                ->route('hospitalisations.index')
                ->with('success', 'Hospitalisation supprimée.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    public function payer(Hospitalisation $hospitalisation)
    {
        DB::beginTransaction();

        try {
            
            $hospitalisation->load('patient', 'chambre', 'transaction');

            $dateDebut = Carbon::parse($hospitalisation->date_entree);
            $dateFin = $hospitalisation->date_sortie_effective ?? now();
            $nombreJours = max(1, (int) ceil($dateDebut->diffInDays($dateFin) ?: 1));

            if ($hospitalisation->statut !== 'Terminé') {
                $hospitalisation->date_sortie_effective = now();
                $hospitalisation->nombre_jours = $nombreJours;
                $hospitalisation->statut = 'Terminé';
                $hospitalisation->save();

                if ($hospitalisation->chambre) {
                    $hospitalisation->chambre->update(['statut' => 'Libre']);
                }
            }

            if ($hospitalisation->transaction) {
                $transaction = $this->billingService->recalculate($hospitalisation->transaction);
            } else {
                $transaction = $this->billingService->createFromHospitalisation($hospitalisation);
            }

            $hospitalisation->refresh();
            $hospitalisation->total_payer = (int) round((float) $transaction->total);
            $hospitalisation->save();

            DB::commit();

            return redirect()
                ->route('hospitalisations.facture', $hospitalisation->id)
                ->with('success', 'Hospitalisation clôturée et facturée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }
    }

    public function facture(Hospitalisation $hospitalisation)
    {
        $hospitalisation->load('patient', 'chambre', 'transaction.invoice.items');

        $dateDebut = Carbon::parse($hospitalisation->date_entree);
        $dateFin = $hospitalisation->date_sortie_effective ?? now();
        $nombreJours = max(1, (int) ceil($dateDebut->diffInDays($dateFin) ?: 1));

        $prixJour = (float) $hospitalisation->chambre->prix_par_jour;
        $total = $hospitalisation->transaction?->total ?? ($prixJour * $nombreJours);

        $factureNo = $hospitalisation->transaction?->invoice_no
            ?? ('HOSP-' . now()->format('Ym') . str_pad($hospitalisation->id, 3, '0', STR_PAD_LEFT));

        $pdf = Pdf::loadView('hospitalisations.facture-pdf', [
            'hospitalisation' => $hospitalisation,
            'nombreJours' => $nombreJours,
            'total' => $total,
            'factureNo' => $factureNo,
        ]);

        return $pdf->stream("Facture-{$factureNo}.pdf");
    }
}