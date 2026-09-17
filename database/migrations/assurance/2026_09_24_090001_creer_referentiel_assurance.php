<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel assurance (lot 2a).
 *
 * Sépare ce que l'ancien modèle mélangeait dans `patient_insurances` :
 *   organisme payeur (insurance_companies + type)
 *   entreprise        → souscrit un contrat pour son personnel, ou paie en direct
 *   contrat           → police : payeur, souscripteur, période
 *   formule           → garanties d'une catégorie de personnel : taux, plafonds, carence
 *   adhésion          → assuré principal (carte), rattaché à une formule et à son emploi
 *   bénéficiaire      → qui est couvert par l'adhésion : l'adhérent, ses conjoints, ses enfants
 *
 * Tables propres à chaque établissement (comme les conventions tarifaires).
 * Aucune suppression en cascade : on clôt (date de fin, statut), on ne supprime pas
 * — les factures passées doivent garder leur payeur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            // assureur | mutuelle | entreprise (convention directe) | etat
            $table->string('type', 20)->default('assureur')->after('name');
        });

        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->string('nom');
            $table->string('nif', 50)->nullable();
            $table->string('secteur', 100)->nullable();
            $table->string('adresse')->nullable();
            $table->string('contact_nom')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('email')->nullable();
            // Renseigné quand l'entreprise règle elle-même les soins de son
            // personnel (convention directe) : elle est alors aussi un organisme payeur.
            $table->foreignId('organisme_payeur_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'nom']);
        });

        Schema::create('patient_emplois', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->string('matricule', 50)->nullable();
            $table->string('poste')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->index(['entreprise_id', 'date_fin']);
            $table->index(['patient_id', 'date_fin']);
        });

        Schema::create('assurance_contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('insurance_company_id')->constrained('insurance_companies')->restrictOnDelete();
            $table->foreignId('entreprise_id')->nullable()->constrained('entreprises')->restrictOnDelete();
            $table->string('numero_police', 100);
            $table->string('libelle')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('statut', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'insurance_company_id', 'numero_police'], 'contrat_police_unique');
        });

        Schema::create('assurance_formules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('contrat_id')->constrained('assurance_contrats')->restrictOnDelete();
            $table->string('libelle');
            $table->decimal('taux_prise_en_charge', 5, 2);
            $table->decimal('plafond_annuel_beneficiaire', 15, 2)->nullable();
            $table->decimal('plafond_annuel_famille', 15, 2)->nullable();
            $table->unsignedSmallInteger('delai_carence_jours')->default(0);
            $table->unsignedTinyInteger('age_max_enfant')->default(21);
            $table->unsignedTinyInteger('age_max_enfant_etudiant')->default(25);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['contrat_id', 'libelle']);
        });

        Schema::create('assurance_adhesions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('formule_id')->constrained('assurance_formules')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('patient_emploi_id')->nullable()->constrained('patient_emplois')->nullOnDelete();
            $table->string('numero_carte', 100)->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('statut', 20)->default('active');
            // Reprise : ligne patient_insurances d'origine.
            $table->unsignedBigInteger('reprise_patient_insurance_id')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'statut']);
        });

        Schema::create('assurance_beneficiaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('adhesion_id')->constrained('assurance_adhesions')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->string('lien', 20); // adherent | conjoint | enfant | autre
            $table->boolean('etudiant')->default(false);
            $table->string('numero_carte', 100)->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('statut', 20)->default('active');
            $table->timestamps();

            $table->unique(['adhesion_id', 'patient_id']);
            $table->index(['patient_id', 'statut']);
        });

        // Pont avec le moteur de calcul actuel (remplacé à l'étape 2b) : chaque
        // bénéficiaire est projeté en une ligne patient_insurances.
        Schema::table('patient_insurances', function (Blueprint $table) {
            $table->foreignId('beneficiaire_id')->nullable()->unique()->after('insurance_company_id')
                ->constrained('assurance_beneficiaires')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_insurances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('beneficiaire_id');
        });

        Schema::dropIfExists('assurance_beneficiaires');
        Schema::dropIfExists('assurance_adhesions');
        Schema::dropIfExists('assurance_formules');
        Schema::dropIfExists('assurance_contrats');
        Schema::dropIfExists('patient_emplois');
        Schema::dropIfExists('entreprises');

        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
