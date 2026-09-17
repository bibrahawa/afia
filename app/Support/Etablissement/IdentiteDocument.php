<?php

namespace App\Support\Etablissement;

use App\Models\Etablissement;
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

    /** « adresse · contact · email » sans séparateurs orphelins. */
    public function coordonnees(string $separateur = ' · '): string
    {
        return implode($separateur, array_filter([$this->adresse, $this->contact, $this->email]));
    }
}
