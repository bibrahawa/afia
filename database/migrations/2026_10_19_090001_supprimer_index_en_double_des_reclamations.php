<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot R2 — La migration des créances instantanées a créé « claims_organisme_statut_index »
 * sur (insurance_company_id, status), alors qu'un index identique existait déjà.
 * Un index en double ne sert à rien et ralentit chaque écriture : on le retire,
 * seulement si l'autre index est bien présent.
 */
return new class extends Migration
{
    public function up(): void
    {
        $index = collect(Schema::getIndexes('insurance_claims'));
        $doublon = $index->firstWhere('name', 'claims_organisme_statut_index');
        $autre = $index->first(fn ($i) => $i['name'] !== 'claims_organisme_statut_index'
            && $i['columns'] === ['insurance_company_id', 'status']);

        if ($doublon && $autre) {
            Schema::table('insurance_claims', fn (Blueprint $t) => $t->dropIndex('claims_organisme_statut_index'));
        }
    }

    public function down(): void
    {
        // Rien : l'index restant couvre les mêmes colonnes.
    }
};
