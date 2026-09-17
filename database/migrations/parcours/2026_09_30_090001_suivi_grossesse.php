<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 3c — suivi de grossesse.
 *
 * Une grossesse ouverte à partir de la date des dernières règles saisie à
 * l'accueil : terme et date prévue d'accouchement calculés, consultations
 * prénatales rattachées, calendrier des CPN proposé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grossesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('medecin_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('ddr');                       // dernières règles
            $table->date('dpa');                       // date prévue d'accouchement (DDR + 280 j)
            $table->unsignedTinyInteger('gestite')->nullable();  // nombre de grossesses
            $table->unsignedTinyInteger('parite')->nullable();   // nombre d'accouchements
            $table->string('statut', 20)->default('en_cours');   // en_cours | terminee | interrompue
            $table->date('date_issue')->nullable();
            $table->string('issue', 40)->nullable();   // accouchement | fausse_couche | interruption | transfert
            $table->text('notes')->nullable();
            $table->foreignId('ouverte_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'statut']);
            $table->index(['etablissement_id', 'dpa']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('grossesse_id')->nullable()->after('appointment_id')->constrained('grossesses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultations', fn (Blueprint $t) => $t->dropConstrainedForeignId('grossesse_id'));
        Schema::dropIfExists('grossesses');
    }
};
