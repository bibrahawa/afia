<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 3e — documents médicaux, croissance de l'enfant, rappels de CPN.
 *
 *  documents_medicaux : certificats et arrêts de travail, numérotés et conservés.
 *  normes_croissance  : tables OMS (L, M, S) importées, pour calculer les z-scores.
 *  grossesse_rappels  : trace des SMS de CPN, pour ne pas les envoyer deux fois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents_medicaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('medecin_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('numero', 50);
            $table->string('type', 30); // certificat_medical | arret_travail | certificat_grossesse | aptitude | deces
            $table->text('contenu');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->unsignedSmallInteger('jours')->nullable();
            $table->boolean('annule')->default(false);
            $table->string('motif_annulation')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['etablissement_id', 'numero']);
            $table->index(['patient_id', 'type']);
        });

        Schema::create('normes_croissance', function (Blueprint $table) {
            $table->id();
            $table->string('indicateur', 20);  // poids_age | taille_age | imc_age
            $table->string('sexe', 10);        // Homme | Femme
            $table->unsignedSmallInteger('mois');
            $table->decimal('l', 10, 6);
            $table->decimal('m', 10, 6);
            $table->decimal('s', 10, 6);
            $table->timestamps();

            $table->unique(['indicateur', 'sexe', 'mois']);
        });

        Schema::create('grossesse_rappels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('grossesse_id')->constrained('grossesses')->cascadeOnDelete();
            $table->unsignedTinyInteger('semaines');   // contact CPN concerné
            $table->timestamp('envoye_le');
            $table->string('telephone', 30)->nullable();
            $table->boolean('succes')->default(true);
            $table->timestamps();

            $table->unique(['grossesse_id', 'semaines']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grossesse_rappels');
        Schema::dropIfExists('normes_croissance');
        Schema::dropIfExists('documents_medicaux');
    }
};
