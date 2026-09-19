<?php

namespace App\Http\Controllers\Parcours;

use App\Enums\Parcours\TypeDocumentMedical;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Parcours\DocumentMedical;
use App\Models\Patient;
use App\Services\Parcours\DocumentMedicalService;
use App\Support\Etablissement\IdentiteDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentMedicalController extends Controller
{
    public function __construct(private DocumentMedicalService $documents)
    {
    }

    /** Texte type pré-rempli, chargé dans la fenêtre sans quitter la consultation. */
    public function modele(Request $request, Consultation $consultation)
    {
        $donnees = $request->validate([
            'type' => ['required', Rule::enum(TypeDocumentMedical::class)],
            'jours' => ['nullable', 'integer', 'min:1', 'max:365'],
            'date_debut' => ['nullable', 'date'],
            'motif' => ['nullable', 'string', 'max:255'],
        ]);

        $consultation->loadMissing('patient', 'medecin');

        return response()->json([
            'contenu' => $this->documents->proposerTexte(
                TypeDocumentMedical::from($donnees['type']),
                $consultation->patient,
                $donnees + ['medecin' => $consultation->medecin?->nom_affiche]
            ),
        ]);
    }

    public function store(Request $request, Consultation $consultation)
    {
        $donnees = $request->validate([
            'type' => ['required', Rule::enum(TypeDocumentMedical::class)],
            'contenu' => ['required', 'string', 'max:5000'],
            'date_debut' => ['nullable', 'date'],
            'jours' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $consultation->loadMissing('patient');

        $document = $this->documents->creer(
            $consultation->patient,
            TypeDocumentMedical::from($donnees['type']),
            $donnees,
            $consultation,
            $request->user()
        );

        return redirect()->route('parcours.documents.imprimer', $document)
            ->with('success', $document->type->libelle() . ' n° ' . $document->numero . ' enregistré.');
    }

    public function imprimer(DocumentMedical $document)
    {
        $document->load(['patient', 'medecin']);

        return view('parcours.documents.imprimer', ['document' => $document, 'identite' => IdentiteDocument::courante()]);
    }

    public function annuler(Request $request, DocumentMedical $document)
    {
        $donnees = $request->validate(['motif_annulation' => ['required', 'string', 'max:255']]);

        $this->documents->annuler($document, $donnees['motif_annulation']);

        return back()->with('success', 'Document annulé. Il reste au dossier avec son motif.');
    }

    /** Documents d'un patient, depuis son dossier. */
    public function index(int $patientId)
    {
        $patient = Patient::suivisParEtablissement()->findOrFail($patientId);

        return view('parcours.documents.index', [
            'patient' => $patient,
            'documents' => DocumentMedical::where('patient_id', $patient->id)->with('medecin')->latest()->get(),
        ]);
    }
}
