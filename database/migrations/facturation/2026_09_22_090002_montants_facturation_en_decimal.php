<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suite de 2026_09_21_090001_assainir_comptes_patients_et_montants (qui a
 * traité transactions, paiements et accounts) : tous les autres montants
 * passent en DECIMAL(15,2).
 *
 * - FLOAT / DOUBLE (tarifs du catalogue, prix de convention, remises) ne
 *   représentent pas exactement les montants : écarts d'arrondi cumulés.
 * - DECIMAL(10,2) plafonne à 99 999 999 GNF et DECIMAL(8,2) (médicaments) à
 *   999 999 GNF : trop juste pour une hospitalisation ou un traitement coûteux.
 *
 * Élargissement uniquement : aucune valeur existante n'est tronquée.
 */
return new class extends Migration
{
    private const COLONNES = [
        'services' => ['amount' => false],
        'tests' => ['amount' => false],
        'packages' => ['price' => false],
        'medicaments' => ['amount' => true],
        'chambres' => ['prix_par_jour' => false],
        'insurance_coverages' => ['acte_price' => 'zero', 'max_amount' => true, 'min_amount' => true, 'coverage_amount_limit' => true],
        'invoices' => ['total_amount' => false, 'patient_amount' => 'zero', 'insurance_amount' => 'zero'],
        'invoice_items' => ['unit_price' => false, 'total_amount' => false, 'discount' => 'zero', 'insurance_covered_amount' => 'zero', 'patient_amount' => 'zero'],
        'patient_insurances' => ['annual_limit' => true, 'used_amount' => 'zero'],
        'insurance_claims' => ['claimed_amount' => false, 'approved_amount' => true, 'paid_amount' => 'zero'],
    ];

    public function up(): void
    {
        foreach (self::COLONNES as $table => $colonnes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table, $colonnes) {
                foreach ($colonnes as $colonne => $mode) {
                    if (! Schema::hasColumn($table, $colonne)) {
                        continue;
                    }

                    $def = $t->decimal($colonne, 15, 2);
                    match ($mode) {
                        true => $def->nullable(),
                        'zero' => $def->default(0),
                        default => null,
                    };
                    $def->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Volontairement vide : revenir à FLOAT réintroduirait les erreurs
        // d'arrondi, et revenir à DECIMAL(10,2) pourrait tronquer des montants.
    }
};
