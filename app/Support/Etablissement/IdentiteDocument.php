<?php

namespace App\Support\Etablissement;

use App\Models\Etablissement;
use App\Support\Images\ImageControlee;
use App\Support\EtablissementContext;

/**
 * Identité imprimée sur les documents (factures, reçus, ordonnances,
 * bons d'examens, rapports) : celle de l'ÉTABLISSEMENT COURANT.
 *
 * Remplace `auth()->user()->hospital` (relation qui n'existe pas : la valeur
 * était toujours null) et les valeurs écrites en dur « CLINIQUE APROSAFE,
 * Kiroti, 628 16 44 22 » : chaque clinique cliente imprimait l'en-tête
 * d'Aprosafe.
 *
 * Les informations se renseignent dans la fiche établissement (nom, adresse,
 * contact, email, logo). Aucun nom de clinique n'est plus codé en dur.
 */
final class IdentiteDocument
{
    public function __construct(
        public readonly string $nom,
        public readonly ?string $adresse = null,
        public readonly ?string $contact = null,
        public readonly ?string $email = null,
        public readonly ?string $siteWeb = null,
        public readonly ?string $numeroEnregistrement = null,
        public readonly ?string $messageFacture = null,
        public readonly ?string $logo = null,
        public readonly ?string $logoDocuments = null,
        public readonly ?string $signature = null,
        public readonly ?string $cachet = null,
        public readonly ?string $signataireNom = null,
        public readonly ?string $signataireFonction = null,
    ) {
    }

    public static function courante(): self
    {
        return self::pour(EtablissementContext::current());
    }

    public static function pour(?Etablissement $etablissement): self
    {
        if (! $etablissement) {
            return new self(nom: (string) config('app.name', 'Établissement de santé'));
        }

        return new self(
            nom: $etablissement->nom,
            adresse: $etablissement->adresse,
            contact: $etablissement->contact,
            email: $etablissement->email,
            siteWeb: $etablissement->site_web,
            numeroEnregistrement: $etablissement->numero_enregistrement,
            messageFacture: $etablissement->message_facture,
            logo: $etablissement->logo,
            logoDocuments: $etablissement->logo_documents,
            signature: $etablissement->signature,
            cachet: $etablissement->cachet,
            signataireNom: $etablissement->signataire_nom,
            signataireFonction: $etablissement->signataire_fonction,
        );
    }

    /** Chemin absolu pour DomPDF, ou null si aucun logo n'est disponible. */
    public function logoPdf(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        foreach ([public_path($this->logo), storage_path('app/public/' . ltrim($this->logo, '/'))] as $chemin) {
            if (is_file($chemin)) {
                return $chemin;
            }
        }

        return null;
    }

    /** URL pour les documents imprimés depuis le navigateur, ou null. */
    public function logoWeb(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        if (is_file(public_path($this->logo))) {
            return asset($this->logo);
        }

        return is_file(storage_path('app/public/' . ltrim($this->logo, '/'))) ? asset('storage/' . ltrim($this->logo, '/')) : null;
    }

    // ------------------------------------------------------------ Documents (identité visuelle)

    /** Logo des documents pour DomPDF (chemin absolu) : logo dédié, sinon logo de l'application. */
    public function logoDocumentsPdf(): ?string
    {
        return self::pourPdf(ImageControlee::cheminAbsolu('logo_documents', $this->logoDocuments) ?? $this->logoPdf());
    }

    /** Logo des documents imprimés par le navigateur. */
    public function logoDocumentsWeb(): ?string
    {
        if ($this->logoDocuments && ($absolu = ImageControlee::cheminAbsolu('logo_documents', $this->logoDocuments))) {
            return asset('storage/' . ltrim($this->logoDocuments, '/'));
        }

        return $this->logoWeb();
    }

    /** Signature et cachet de la clinique : chemins absolus PRIVÉS pour DomPDF. */
    public function signaturePdf(): ?string
    {
        return self::pourPdf(ImageControlee::cheminAbsolu('signature', $this->signature));
    }

    public function cachetPdf(): ?string
    {
        return self::pourPdf(ImageControlee::cheminAbsolu('cachet', $this->cachet));
    }

    /** DomPDF a besoin de l'extension GD pour les PNG (transparence). */
    public static function imagesPdfPossibles(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * Sans GD, DomPDF lève une exception sur une image PNG : la facture entière planterait.
     * On omet alors l'image (le document s'imprime sans) et on le signale au journal.
     */
    private static function pourPdf(?string $chemin): ?string
    {
        if (! $chemin || self::imagesPdfPossibles()) {
            return $chemin;
        }
        static $signale = false;
        if (! $signale) {
            $signale = true;
            \Illuminate\Support\Facades\Log::warning('Extension PHP GD absente : logos, signatures et cachets sont omis des PDF.');
        }

        return null;
    }

    /** Idem en « data: URI » pour les documents imprimés par le navigateur (jamais d'adresse publique). */
    public function signatureData(): ?string
    {
        return ImageControlee::dataUri('signature', $this->signature);
    }

    public function cachetData(): ?string
    {
        return ImageControlee::dataUri('cachet', $this->cachet);
    }

    public function aSignatureOuCachet(): bool
    {
        return (bool) ($this->signaturePdf() || $this->cachetPdf());
    }

    /** Signature propre à un médecin (sur SES documents uniquement). */
    public static function signatureMedecinPdf(?\App\Models\Employee $medecin): ?string
    {
        return $medecin ? self::pourPdf(ImageControlee::cheminAbsolu('signature', $medecin->signature)) : null;
    }

    public static function signatureMedecinData(?\App\Models\Employee $medecin): ?string
    {
        return $medecin ? ImageControlee::dataUri('signature', $medecin->signature) : null;
    }

    /** « adresse · contact · email » sans séparateurs orphelins. */
    public function coordonnees(string $separateur = ' · '): string
    {
        return implode($separateur, array_filter([$this->adresse, $this->contact, $this->email]));
    }
}
