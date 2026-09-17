<?php

namespace App\Services\Parcours;

use App\Enums\Parcours\TypeDocumentMedical;
use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Models\Consultation;
use App\Models\Parcours\DocumentMedical;
use App\Models\Parcours\Grossesse;
use App\Models\Patient;
use App\Models\User;
use App\Services\NumerotationDocumentService;
use App\Support\EtablissementContext;
use Carbon\Carbon;

/**
 * Certificats et arrêts de travail : un texte type que le médecin ajuste, un
 * numéro par établissement, et le document conservé au dossier.
 */
class DocumentMedicalService
{
    public function __construct(private NumerotationDocumentService $numerotation)
    {
    }

    /** Texte pré-rempli proposé au médecin (aucune écriture). */
    public function proposerTexte(TypeDocumentMedical $type, Patient $patient, array $donnees = []): string
    {
        $debut = ! empty($donnees['date_debut']) ? Carbon::parse($donnees['date_debut']) : today();
        $jours = max(1, (int) ($donnees['jours'] ?? 1));
        $grossesse = Grossesse::where('patient_id', $patient->id)->where('statut', Grossesse::EN_COURS)->latest('id')->first();

        $remplacements = [
            '{patient}' => $patient->full_name,
            '{age}' => $patient->age !== null ? (string) $patient->age : '—',
            '{sexe}' => $patient->gender === 'Femme' ? 'de sexe féminin' : 'de sexe masculin',
            '{date}' => today()->format('d/m/Y'),
            '{jours}' => (string) $jours,
            '{debut}' => $debut->format('d/m/Y'),
            '{fin}' => $debut->copy()->addDays($jours - 1)->format('d/m/Y'),
            '{medecin}' => $donnees['medecin'] ?? 'le médecin soussigné',
            '{motif}' => trim((string) ($donnees['motif'] ?? '')) ?: '…',
            '{terme}' => $grossesse?->termeLisible() ?? '…',
            '{dpa}' => $grossesse?->dpa->format('d/m/Y') ?? '…',
        ];

        return strtr($type->modele(), $remplacements);
    }

    public function creer(Patient $patient, TypeDocumentMedical $type, array $donnees, ?Consultation $consultation = null, ?User $auteur = null): DocumentMedical
    {
        $contenu = trim((string) ($donnees['contenu'] ?? ''));

        if ($contenu === '') {
            throw new OperationParcoursImpossible('Le texte du document est vide.');
        }

        $debut = ! empty($donnees['date_debut']) ? Carbon::parse($donnees['date_debut']) : null;
        $jours = $type->demandeDuree() ? max(1, (int) ($donnees['jours'] ?? 1)) : null;

        if ($type->demandeDuree() && ! $debut) {
            throw new OperationParcoursImpossible('Indiquez la date de début de l\'arrêt.');
        }

        $etablissementId = $consultation?->etablissement_id ?? EtablissementContext::id();

        if (! $etablissementId) {
            throw new OperationParcoursImpossible('Aucun établissement courant : impossible de numéroter le document.');
        }

        return DocumentMedical::create([
            'etablissement_id' => $etablissementId,
            'patient_id' => $patient->id,
            'consultation_id' => $consultation?->id,
            'medecin_id' => $consultation?->medecin_id ?? ($donnees['medecin_id'] ?? null),
            'numero' => $this->numerotation->numero($etablissementId, $type->prefixe(), 'document-' . $type->value),
            'type' => $type,
            'contenu' => $contenu,
            'date_debut' => $debut?->toDateString(),
            'date_fin' => $debut && $jours ? $debut->copy()->addDays($jours - 1)->toDateString() : null,
            'jours' => $jours,
            'cree_par' => $auteur?->id,
        ]);
    }

    /** Un document remis au patient ne se supprime pas : il s'annule, avec un motif. */
    public function annuler(DocumentMedical $document, string $motif): DocumentMedical
    {
        if ($document->annule) {
            return $document;
        }

        $document->update(['annule' => true, 'motif_annulation' => mb_substr(trim($motif), 0, 255) ?: 'Sans motif']);

        return $document->fresh();
    }
}
