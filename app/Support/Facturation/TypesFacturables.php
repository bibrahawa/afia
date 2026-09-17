<?php

namespace App\Support\Facturation;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * Alias stables des types polymorphes de la facturation.
 *
 * POURQUOI : la base stockait des noms de classe PHP (« App\Models\Service »)
 * dans transactions.transactionable_type, invoice_items.coverage_type_type,
 * insurance_coverages.coverageable_type et accounts.owner_type. Déplacer un
 * modèle vers son dossier de module (App\Models\Consultation\…) aurait rendu
 * toutes les factures existantes illisibles. Avec ces alias, la base ne
 * connaît plus que « service », « consultation »… et les classes peuvent
 * être réorganisées librement : seule cette table de correspondance change.
 *
 * Carte NON imposée (Relation::morphMap, pas enforceMorphMap) : les autres
 * polymorphes de l'application (rôles Spatie, journal d'activité,
 * consentements) gardent leurs noms de classe et ne sont pas concernés.
 *
 * RÈGLE pour tout nouveau code : ne jamais comparer une colonne *_type à
 * `Service::class` ou à une chaîne 'App\\Models\\…'. Utiliser
 * `TypesFacturables::variantes()` dans les requêtes (tolère les deux formes
 * pendant la transition) et `TypesFacturables::classe()` pour instancier.
 */
class TypesFacturables
{
    public const CARTE = [
        // Pièces facturées (transactions.transactionable_type)
        'consultation' => \App\Models\Consultation::class,
        'hospitalisation' => \App\Models\Hospitalisation::class,
        'labo_demande' => \App\Models\Labo\LaboDemande::class,

        // Actes facturables (invoice_items, insurance_coverages)
        'service' => \App\Models\Service::class,
        'package' => \App\Models\Package::class,
        'test' => \App\Models\Test::class,
        'medicament' => \App\Models\Medicament::class,
        'chambre' => \App\Models\Chambre::class,
        'labo_examen' => \App\Models\Labo\LaboExamen::class,

        // Titulaires de compte (accounts.owner_type)
        'patient' => \App\Models\Patient::class,
    ];

    /** Libellés affichés à l'écran. */
    public const LIBELLES = [
        'consultation' => 'Consultation',
        'hospitalisation' => 'Hospitalisation',
        'labo_demande' => 'Demande de laboratoire',
        'service' => 'Service',
        'package' => 'Package',
        'test' => 'Examen',
        'medicament' => 'Médicament',
        'chambre' => 'Chambre',
        'labo_examen' => 'Examen de laboratoire',
        'patient' => 'Patient',
    ];

    /** Actes qu'une convention d'assurance peut couvrir. */
    public const ACTES_COUVRABLES = ['service', 'package', 'test', 'medicament', 'chambre', 'labo_examen'];

    public static function enregistrer(): void
    {
        Relation::morphMap(self::CARTE);
    }

    /**
     * Valeur à écrire en base pour une classe (ou un alias déjà normalisé).
     * Si la carte n'est pas enregistrée (provider absent), renvoie la classe :
     * le comportement reste celui d'avant, sans incohérence.
     */
    public static function alias(string $classeOuAlias): string
    {
        $classe = self::classe($classeOuAlias) ?? $classeOuAlias;
        $alias = array_search($classe, Relation::morphMap(), true);

        return $alias !== false ? $alias : $classe;
    }

    /** Classe PHP correspondant à un alias ou à un nom de classe ; null si inconnu. */
    public static function classe(?string $aliasOuClasse): ?string
    {
        if (! $aliasOuClasse) {
            return null;
        }

        $aliasOuClasse = ltrim($aliasOuClasse, '\\');

        if (isset(self::CARTE[$aliasOuClasse])) {
            return self::CARTE[$aliasOuClasse];
        }

        return in_array($aliasOuClasse, self::CARTE, true) ? $aliasOuClasse : null;
    }

    /**
     * Les deux formes possibles en base pour un même type : [alias, classe].
     * À utiliser dans tous les where/whereIn sur une colonne *_type, pour
     * rester juste avant, pendant et après la migration de conversion.
     *
     * @return string[]
     */
    public static function variantes(string $classeOuAlias): array
    {
        $classe = self::classe($classeOuAlias) ?? $classeOuAlias;
        $alias = array_search($classe, self::CARTE, true);

        return array_values(array_unique(array_filter([$alias ?: null, $classe])));
    }

    /** Vrai si la valeur stockée désigne bien ce type (alias ou nom de classe). */
    public static function est(?string $valeurStockee, string $classeOuAlias): bool
    {
        return $valeurStockee !== null && in_array(ltrim($valeurStockee, '\\'), self::variantes($classeOuAlias), true);
    }

    /**
     * Résout une valeur venue d'un formulaire : alias (« service »), libellé
     * (« Service », « Médicament », « examens »), ancien nom de classe.
     * Seuls les types de la carte sont acceptés — jamais une classe arbitraire.
     */
    public static function depuisSaisie(?string $saisie, ?array $autorises = null): ?string
    {
        if ($saisie === null || trim($saisie) === '') {
            return null;
        }

        $saisie = trim($saisie);
        $candidat = self::classe($saisie) ? array_search(self::classe($saisie), self::CARTE, true) : null;

        if (! $candidat) {
            $normalise = Str::of($saisie)->ascii()->lower()->replace([' ', '-'], '_')->toString();
            $synonymes = [
                'services' => 'service', 'packages' => 'package',
                'tests' => 'test', 'examen' => 'test', 'examens' => 'test',
                'medicaments' => 'medicament', 'chambres' => 'chambre',
                'examen_de_laboratoire' => 'labo_examen', 'laboexamen' => 'labo_examen',
            ];
            $normalise = $synonymes[$normalise] ?? $normalise;
            $candidat = isset(self::CARTE[$normalise]) ? $normalise : null;
        }

        if (! $candidat || ($autorises !== null && ! in_array($candidat, $autorises, true))) {
            return null;
        }

        return self::CARTE[$candidat];
    }

    public static function libelle(?string $valeurStockee): string
    {
        $classe = self::classe($valeurStockee);
        $alias = $classe ? array_search($classe, self::CARTE, true) : null;

        return $alias ? self::LIBELLES[$alias] : ($valeurStockee ? class_basename($valeurStockee) : '—');
    }
}
