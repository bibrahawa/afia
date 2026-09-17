<?php

namespace App\Support\Labo;

use App\Models\ActivityLog;
use App\Models\Patient;
use App\Models\User;
use App\Support\EtablissementContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ContexteLabo
{
    /** Établissement courant, obligatoire pour toute écriture du module. */
    public static function etablissementId(): int
    {
        $id = EtablissementContext::id();

        abort_unless($id, 403, 'Aucun établissement associé à cette session.');

        return (int) $id;
    }

    /**
     * Niveau 2 de défense (après le global scope) : à appeler sur toute
     * entité reçue avant une action sensible.
     */
    public static function verifierAppartenance(Model $modele): void
    {
        abort_unless((int) $modele->etablissement_id === static::etablissementId(), 404);
    }

    public static function journaliser(string $action, ?Model $sujet = null, ?string $description = null, array $proprietes = []): void
    {
        ActivityLog::create([
            'etablissement_id' => EtablissementContext::id(),
            'causer_type' => Auth::check() ? User::class : null,
            'causer_id' => Auth::id(),
            'subject_type' => $sujet ? get_class($sujet) : null,
            'subject_id' => $sujet?->getKey(),
            'action' => 'labo.' . $action,
            'description' => $description,
            'proprietes' => $proprietes ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    public static function sexePatient(Patient $patient): ?string
    {
        return match ($patient->gender) {
            'Homme' => 'M',
            'Femme' => 'F',
            default => null,
        };
    }

    /**
     * Âge en jours à une date donnée (date de la demande, pas aujourd'hui :
     * une NFS réimprimée dans 3 ans garde les normes de l'âge d'alors).
     *
     * ATTENTION : Patient::getAgeAttribute() lit une colonne `date_of_birth`
     * qui n'existe pas et renvoie donc toujours null — on lit ici la vraie
     * colonne `birth_date` (texte libre) puis, à défaut, la colonne `age`
     * brute via getRawOriginal() pour contourner cet accesseur.
     */
    public static function ageEnJours(Patient $patient, ?Carbon $aLaDate = null): ?int
    {
        $aLaDate ??= now();

        if (! empty($patient->birth_date)) {
            try {
                $naissance = Carbon::parse($patient->birth_date);
                if ($naissance->lessThanOrEqualTo($aLaDate)) {
                    return (int) $naissance->diffInDays($aLaDate);
                }
            } catch (\Throwable) {
                // texte libre illisible : on tente la colonne age
            }
        }

        $ageAnnees = $patient->getRawOriginal('age');

        return is_numeric($ageAnnees) && $ageAnnees >= 0 ? (int) $ageAnnees * 365 : null;
    }

    public static function ageTexte(?int $jours): string
    {
        if ($jours === null) {
            return 'Âge non renseigné';
        }
        if ($jours < 31) {
            return $jours . ' jour' . ($jours > 1 ? 's' : '');
        }
        if ($jours < 730) {
            return intdiv($jours, 30) . ' mois';
        }

        return intdiv($jours, 365) . ' ans';
    }
}
