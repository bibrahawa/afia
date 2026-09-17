<?php

namespace App\Enums\Parcours;

enum TypeDocumentMedical: string
{
    case CertificatMedical = 'certificat_medical';
    case ArretTravail = 'arret_travail';
    case CertificatGrossesse = 'certificat_grossesse';
    case Aptitude = 'aptitude';

    public function libelle(): string
    {
        return match ($this) {
            self::CertificatMedical => 'Certificat médical',
            self::ArretTravail => 'Arrêt de travail',
            self::CertificatGrossesse => 'Certificat de grossesse',
            self::Aptitude => 'Certificat d\'aptitude',
        };
    }

    public function prefixe(): string
    {
        return match ($this) {
            self::ArretTravail => 'ARR',
            default => 'CRT',
        };
    }

    public function demandeDuree(): bool
    {
        return $this === self::ArretTravail;
    }

    /**
     * Texte type. Les variables entre accolades sont remplacées à la création :
     * {patient}, {age}, {sexe}, {date}, {jours}, {debut}, {fin}, {medecin}, {motif}, {terme}, {dpa}.
     */
    public function modele(): string
    {
        return match ($this) {
            self::CertificatMedical => "Je soussigné(e), {medecin}, certifie avoir examiné ce jour {patient}, {sexe}, âgé(e) de {age} ans, et avoir constaté : {motif}.\n\nCertificat établi à la demande de l'intéressé(e) et remis en main propre pour servir et valoir ce que de droit.",
            self::ArretTravail => "Je soussigné(e), {medecin}, certifie que l'état de santé de {patient}, âgé(e) de {age} ans, nécessite un arrêt de travail de {jours} jour(s), du {debut} au {fin} inclus.\n\nMotif médical : {motif}.\n\nSauf complication, la reprise est possible à l'issue de cette période.",
            self::CertificatGrossesse => "Je soussigné(e), {medecin}, certifie que {patient}, âgée de {age} ans, est enceinte. Terme évalué à {terme}, accouchement prévu vers le {dpa}.\n\nCertificat remis à l'intéressée pour servir et valoir ce que de droit.",
            self::Aptitude => "Je soussigné(e), {medecin}, certifie avoir examiné ce jour {patient}, âgé(e) de {age} ans, et n'avoir constaté aucune contre-indication apparente à : {motif}.\n\nCertificat établi à la demande de l'intéressé(e).",
        };
    }
}
