<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 3a — parcours du patient dans la clinique.
 *
 *  visites     : un passage du patient (arrivée → file d'attente → consultation → sortie),
 *                avec ou sans rendez-vous. C'est l'accueil qui la crée : le médecin trouve
 *                le patient dans sa file avec motif, constantes et acte déjà facturé.
 *  constantes  : poids, taille, température, tension, pouls, SpO2, glycémie, DDR — prises à
 *                l'accueil ou par l'infirmier, jamais ressaisies par le médecin.
 *  consultations : liées à la visite et au rendez-vous ; statut « en_cours » tant que le
 *                médecin n'a pas conclu ; diagnostic facultatif à l'ouverture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained('appointments')->nullOnDelete();
            $table->foreignId('medecin_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('motif_rdv_id')->nullable()->constrained('motifs_rdv')->nullOnDelete();
            $table->string('motif');
            $table->string('statut', 20)->default('en_attente'); // en_attente | en_consultation | terminee | partie | annulee
            $table->boolean('urgence')->default(false);
            $table->timestamp('arrivee_le');
            $table->timestamp('appele_le')->nullable();
            $table->timestamp('terminee_le')->nullable();
            $table->text('notes_accueil')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['etablissement_id', 'arrivee_le']);
            $table->index(['medecin_id', 'statut', 'arrivee_le']);
            $table->index(['patient_id', 'arrivee_le']);
        });

        Schema::create('constantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('visite_id')->nullable()->constrained('visites')->nullOnDelete();
            $table->decimal('poids_kg', 5, 2)->nullable();
            $table->decimal('taille_cm', 5, 1)->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('tension_systolique')->nullable();
            $table->unsignedSmallInteger('tension_diastolique')->nullable();
            $table->unsignedSmallInteger('pouls')->nullable();
            $table->unsignedSmallInteger('frequence_respiratoire')->nullable();
            $table->unsignedTinyInteger('saturation_o2')->nullable();
            $table->decimal('glycemie', 4, 2)->nullable(); // g/L
            $table->date('ddr')->nullable(); // date des dernières règles
            $table->string('notes')->nullable();
            $table->foreignId('mesure_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mesure_le');
            $table->timestamps();

            $table->index(['patient_id', 'mesure_le']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('visite_id')->nullable()->unique()->after('id')->constrained('visites')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->after('visite_id')->constrained('appointments')->nullOnDelete();
            // Consultations existantes : terminées.
            $table->string('statut', 20)->default('terminee')->after('est_facturee');
            $table->text('diagnostic')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_id');
            $table->dropConstrainedForeignId('visite_id');
            $table->dropColumn('statut');
        });

        Schema::dropIfExists('constantes');
        Schema::dropIfExists('visites');
    }
};
