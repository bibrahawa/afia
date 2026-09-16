<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `duree_minutes` est figée à la prise du rendez-vous (copiée du motif
     * ou de sa surcharge médecin) — un changement ultérieur de la durée
     * par défaut d'un motif ne doit jamais modifier rétroactivement des
     * rendez-vous déjà planifiés, même principe que pour les tarifs de
     * redevance plateforme.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('motif_rdv_id')->nullable()->after('patient_id')
                ->constrained('motifs_rdv')->restrictOnDelete();
            $table->unsignedSmallInteger('duree_minutes')->nullable()->after('motif_rdv_id');
            $table->dropColumn('reason');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('motif_rdv_id');
            $table->dropColumn('duree_minutes');
            $table->string('reason')->nullable();
        });
    }
};
