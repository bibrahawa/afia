<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reste dû et écart stockés sur chaque réclamation.
 *
 * L'écran des créances recalculait resteDu() réclamation par réclamation
 * (plusieurs requêtes chacune) : lent dès quelques centaines de réclamations.
 * Les deux montants sont désormais recalculés à chaque enregistrement de la
 * réclamation (InsuranceClaim::saving) et chaque nuit (assurance:recalculer-creances),
 * puis lus en une seule requête agrégée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            if (! Schema::hasColumn('insurance_claims', 'reste_du_calcule')) {
                $table->decimal('reste_du_calcule', 15, 2)->default(0);
                $table->decimal('ecart_calcule', 15, 2)->default(0);
                $table->timestamp('montants_calcules_le')->nullable();
                $table->index(['insurance_company_id', 'status'], 'claims_organisme_statut_index');
            }
        });

        // Remplissage initial : même calcul que l'application, ligne par ligne.
        \Illuminate\Support\Facades\Artisan::call('assurance:recalculer-creances');
    }

    public function down(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_claims', 'reste_du_calcule')) {
                $table->dropIndex('claims_organisme_statut_index');
                $table->dropColumn(['reste_du_calcule', 'ecart_calcule', 'montants_calcules_le']);
            }
        });
    }
};
