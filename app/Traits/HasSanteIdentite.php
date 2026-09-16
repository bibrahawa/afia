<?php

namespace App\Traits;

use App\Models\Consentement;
use App\Models\DemandeAcces;
use App\Models\RelationFamiliale;
use Carbon\Carbon;

trait HasSanteIdentite
{
    protected static function bootHasSanteIdentite(): void
    {
        static::creating(function ($patient) {
            if (empty($patient->identifiant_national_sante)) {
                $patient->identifiant_national_sante = static::genererIdentifiant();
            }
        });
    }

    protected static function genererIdentifiant(): string
    {
        do {
            $candidat = 'GN' . now()->format('y') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (static::where('identifiant_national_sante', $candidat)->exists());

        return $candidat;
    }

    public function comptesPatients()
    {
        return $this->belongsToMany(\App\Models\ComptePatient::class, 'compte_patient')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * NOUVEAU — point unique de vérité pour le téléphone d'un patient.
     * L'ancien lien `patient->user->phone` traîne encore dans une bonne
     * dizaine de vues et contrôleurs jamais migrés (Consultations,
     * Assurances, listes...) — plutôt que de corriger chaque appel un par
     * un (et d'en oublier), on centralise ici. `$patient->telephone`
     * remplace `$patient->user->phone` PARTOUT désormais.
     */
    public function getTelephoneAttribute(): ?string
    {
        return $this->comptesPatients->first()?->telephone;
    }

    public function relationsFamiliales()
    {
        return $this->hasMany(RelationFamiliale::class, 'patient_id');
    }

    public function consentementsAccordes()
    {
        return $this->hasMany(Consentement::class);
    }

    public function demandesAcces()
    {
        return $this->hasMany(DemandeAcces::class);
    }

    /**
     * NOUVEAU. `birth_date` reste un champ texte libre dans le schéma
     * actuel (jamais assaini en vraie date) — on essaie de le parser
     * prudemment plutôt que d'exiger une migration destructive sur des
     * données de production existantes. Retourne `null` si indéterminable
     * : à traiter comme "ne pas supposer mineur" par l'appelant, jamais
     * comme "considérer majeur" — le doute doit profiter à la prudence,
     * pas à l'accès élargi.
     */
    public function estMineur(): ?bool
    {
        if (empty($this->birth_date)) {
            return null;
        }

        try {
            return Carbon::parse($this->birth_date)->age < 18;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
