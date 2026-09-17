<?php

namespace App\Http\Controllers\Assurance;

use App\Http\Controllers\Controller;
use App\Models\Assurance\Bordereau;
use App\Models\InsuranceClaim;
use App\Models\InsuranceCompany;
use App\Services\Assurance\BordereauService;
use App\Support\Etablissement\IdentiteDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BordereauController extends Controller
{
    public function __construct(private BordereauService $bordereaux)
    {
    }

    public function store(Request $request, InsuranceCompany $assuranceOrganisme)
    {
        $donnees = $request->validate([
            'periode_debut' => ['nullable', 'date'],
            'periode_fin' => ['nullable', 'date', 'after_or_equal:periode_debut'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $bordereau = $this->bordereaux->creer(
            $assuranceOrganisme,
            ! empty($donnees['periode_debut']) ? Carbon::parse($donnees['periode_debut']) : null,
            ! empty($donnees['periode_fin']) ? Carbon::parse($donnees['periode_fin']) : null,
            $donnees['notes'] ?? null,
            $request->user()?->id
        );

        return redirect()->route('assurance.bordereaux.show', $bordereau)
            ->with('success', 'Bordereau préparé. Vérifiez les réclamations, imprimez-le, puis marquez-le comme envoyé.');
    }

    public function show(Bordereau $assuranceBordereau)
    {
        $assuranceBordereau->load(['organisme', 'auteur', 'reclamations' => fn ($q) => $q->with(['lignes', 'pieces', 'invoice.transaction.patient', 'patientInsurance.beneficiaire.adhesion.patient'])->orderBy('id')]);

        return view('assurance.bordereaux.show', ['bordereau' => $assuranceBordereau, 'impression' => false]);
    }

    public function imprimer(Bordereau $assuranceBordereau)
    {
        $assuranceBordereau->load(['organisme', 'reclamations' => fn ($q) => $q->with(['lignes', 'pieces', 'invoice.transaction.patient', 'patientInsurance.beneficiaire.adhesion.patient'])->orderBy('id')]);

        return view('assurance.bordereaux.imprimer', ['bordereau' => $assuranceBordereau, 'identite' => IdentiteDocument::courante()]);
    }

    public function retirer(Bordereau $assuranceBordereau, InsuranceClaim $insuranceClaim)
    {
        $this->bordereaux->retirer($assuranceBordereau, $insuranceClaim);

        return back()->with('success', 'Réclamation retirée du bordereau (elle reste à envoyer).');
    }

    public function envoyer(Request $request, Bordereau $assuranceBordereau)
    {
        $donnees = $request->validate(['date_envoi' => ['required', 'date', 'before_or_equal:today']]);

        $this->bordereaux->envoyer($assuranceBordereau, Carbon::parse($donnees['date_envoi']));

        return back()->with('success', 'Bordereau envoyé : les factures concernées ne peuvent plus être modifiées.');
    }

    public function rouvrir(Bordereau $assuranceBordereau)
    {
        $this->bordereaux->rouvrir($assuranceBordereau);

        return back()->with('success', 'Bordereau rouvert : les réclamations sont de nouveau modifiables.');
    }
}
