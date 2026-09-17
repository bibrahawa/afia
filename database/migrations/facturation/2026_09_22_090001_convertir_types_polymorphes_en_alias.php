<?php

use App\Support\Facturation\TypesFacturables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remplace les noms de classe PHP stockés dans les colonnes polymorphes de la
 * facturation par des alias stables (« App\Models\Service » → « service »).
 * Prérequis pour ranger les modèles dans des dossiers de module sans casser
 * les factures, conventions et comptes existants. Voir TypesFacturables.
 *
 * Réversible : down() remet les noms de classe ACTUELS.
 */
return new class extends Migration
{
    private const COLONNES = [
        ['transactions', 'transactionable_type'],
        ['invoice_items', 'coverage_type_type'],
        ['insurance_coverages', 'coverageable_type'],
        ['accounts', 'owner_type'],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::COLONNES as [$table, $colonne]) {
                foreach (TypesFacturables::CARTE as $alias => $classe) {
                    DB::table($table)->where($colonne, $classe)->update([$colonne => $alias]);
                    // Variante avec antislash initial, déjà rencontrée sur des saisies manuelles.
                    DB::table($table)->where($colonne, '\\' . $classe)->update([$colonne => $alias]);
                }
            }

            // Anciennes conventions enregistrées avec un libellé brut par l'écran de
            // modification (« Package », « Chambre ») : normalisées aussi.
            foreach (['Service' => 'service', 'Package' => 'package', 'Test' => 'test', 'Medicament' => 'medicament', 'Chambre' => 'chambre'] as $libelle => $alias) {
                DB::table('insurance_coverages')->where('coverageable_type', $libelle)->update(['coverageable_type' => $alias]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            foreach (self::COLONNES as [$table, $colonne]) {
                foreach (TypesFacturables::CARTE as $alias => $classe) {
                    DB::table($table)->where($colonne, $alias)->update([$colonne => $classe]);
                }
            }
        });
    }
};
