<?php

use App\Support\Migrations\AjoutEtablissement as A;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Référentiels tarifaires : services, tests, packages, médicaments.
 * Chaque établissement fixe ses propres actes et ses propres prix.
 *
 * Les tables pivots (package_services, package_tests, consultation_*) ne
 * reçoivent pas de colonne : elles héritent de l'établissement de leur
 * parent, et Eloquent les remplit via attach() sans passer par un modèle.
 */
return new class extends Migration
{
    private const TABLES = ['services', 'tests', 'packages', 'medicaments'];

    public function up(): void
    {
        $pilote = A::etablissementPilote(self::TABLES);

        foreach (self::TABLES as $table) {
            // cascade : supprimer un établissement résilié supprime son catalogue ;
            // les actes déjà facturés restent dans invoice_items (restrict).
            A::ajouter($table, $pilote, restreindre: false);
        }

        foreach (['services', 'packages'] as $table) {
            DB::statement("UPDATE {$table} x JOIN departments d ON d.id = x.department_id
                           SET x.etablissement_id = d.etablissement_id
                           WHERE d.etablissement_id IS NOT NULL");
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            A::retirer($table);
        }
    }
};
