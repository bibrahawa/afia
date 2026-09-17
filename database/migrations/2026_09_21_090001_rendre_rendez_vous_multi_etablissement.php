<?php

use App\Support\Migrations\AjoutEtablissement as A;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rendez-vous : cloisonnement DIRECT par établissement.
 *
 * Jusqu'ici `appointments` n'avait pas de colonne `etablissement_id` : on
 * comptait sur le global scope d'Employee pour filtrer indirectement. Ça ne
 * protège pas la liaison de route `{appointment}` (show / cancel /
 * reprogrammer) ni l'export PDF, qui voyaient les rendez-vous de TOUTES les
 * cliniques. Même traitement que les dossiers cliniques (restrict : on ne
 * supprime pas un établissement qui a un historique de rendez-vous).
 */
return new class extends Migration
{
    public function up(): void
    {
        $pilote = A::etablissementPilote(['appointments']);

        A::ajouter('appointments', $pilote, restreindre: true);

        // L'établissement réel est celui du médecin — plus fiable que le pilote.
        DB::statement('UPDATE appointments a JOIN employees e ON e.id = a.employee_id
                       SET a.etablissement_id = e.etablissement_id
                       WHERE e.etablissement_id IS NOT NULL');

        Schema::table('appointments', function (Blueprint $table) {
            // Requêtes du moteur de disponibilité et de la vue réception.
            $table->index(['employee_id', 'appointment_date'], 'idx_rdv_medecin_date');
            $table->index(['etablissement_id', 'appointment_date'], 'idx_rdv_etablissement_date');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_rdv_etablissement_date');
            $table->dropIndex('idx_rdv_medecin_date');
        });

        A::retirer('appointments');
    }
};
