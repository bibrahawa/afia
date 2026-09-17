<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CATALOGUE — un seul jeu de tables pour deux usages :
     * - etablissement_id NULL  = catalogue MODÈLE de la plateforme (maintenu
     *   par nous, jamais visible directement par un tenant : le global scope
     *   BelongsToEtablissement l'exclut) ;
     * - etablissement_id rempli = copie propre à un établissement, importée
     *   depuis le modèle puis librement personnalisée (prix, normes adaptées
     *   à SES réactifs). `modele_id` garde la trace de l'origine.
     *
     * Aucun labo ne saisira 300 examens à la main : sans ce modèle
     * importable, la plateforme ne passe pas l'échelle de 100 clients.
     */
    public function up(): void
    {
        Schema::create('labo_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('modele_id')->nullable()->constrained('labo_sections')->nullOnDelete();
            $table->string('code', 30);
            $table->string('nom');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'code']);
        });

        Schema::create('labo_examens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('modele_id')->nullable()->constrained('labo_examens')->nullOnDelete();
            $table->foreignId('section_id')->constrained('labo_sections')->restrictOnDelete();
            // Pont avec l'ancien catalogue `tests` (utilisé par Consultation
            // et la facturation existante) — permet de convertir une
            // consultation déjà prescrite en demande labo sans double saisie.
            $table->foreignId('test_id')->nullable()->constrained('tests')->nullOnDelete();

            $table->string('code', 30);
            $table->string('nom');
            $table->string('abreviation', 30)->nullable();
            $table->string('type_examen', 20)->default('standard');
            $table->string('methode')->nullable();

            $table->string('type_echantillon', 40);   // sang, urine, selles...
            $table->string('tube', 20)->nullable();   // violet, bleu, rouge, jaune, vert, gris, pot, ecouvillon
            $table->decimal('volume_ml', 6, 2)->nullable();
            $table->boolean('a_jeun')->default(false);
            $table->text('instructions_patient')->nullable();

            $table->unsignedSmallInteger('delai_rendu_heures')->default(24);
            $table->decimal('prix', 12, 2)->default(0); // GNF
            $table->boolean('sous_traite')->default(false);
            $table->string('laboratoire_sous_traitant')->nullable();

            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'code']);
            $table->index(['etablissement_id', 'section_id', 'actif']);
        });

        Schema::create('labo_parametres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('examen_id')->constrained('labo_examens')->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('libelle');
            $table->string('groupe')->nullable();          // « Formule leucocytaire »
            $table->string('type_resultat', 20);
            $table->string('unite', 30)->nullable();
            $table->unsignedTinyInteger('decimales')->default(1);
            $table->string('formule')->nullable();
            $table->json('options')->nullable();           // choix des listes
            $table->string('valeur_defaut')->nullable();
            $table->boolean('obligatoire')->default(true);
            $table->boolean('imprimable')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();

            $table->unique(['examen_id', 'code']);
        });

        Schema::create('labo_valeurs_reference', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parametre_id')->constrained('labo_parametres')->cascadeOnDelete();
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->unsignedInteger('age_min_jours')->nullable();
            $table->unsignedInteger('age_max_jours')->nullable();
            $table->boolean('grossesse')->nullable();
            $table->decimal('min', 14, 4)->nullable();
            $table->decimal('max', 14, 4)->nullable();
            $table->decimal('critique_min', 14, 4)->nullable();
            $table->decimal('critique_max', 14, 4)->nullable();
            $table->string('valeur_attendue')->nullable(); // qualitatif : « Négatif »
            $table->string('texte_affiche')->nullable();   // « < 2,00 » plutôt que « 0 – 2,00 »
            $table->timestamps();
        });

        Schema::create('labo_bilans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('modele_id')->nullable()->constrained('labo_bilans')->nullOnDelete();
            $table->string('code', 30);
            $table->string('nom');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['etablissement_id', 'code']);
        });

        Schema::create('labo_bilan_examen', function (Blueprint $table) {
            $table->foreignId('bilan_id')->constrained('labo_bilans')->cascadeOnDelete();
            $table->foreignId('examen_id')->constrained('labo_examens')->cascadeOnDelete();
            $table->primary(['bilan_id', 'examen_id']);
        });

        Schema::create('labo_germes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('modele_id')->nullable()->constrained('labo_germes')->nullOnDelete();
            $table->string('nom');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('labo_antibiotiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('modele_id')->nullable()->constrained('labo_antibiotiques')->nullOnDelete();
            $table->string('nom');
            $table->string('famille')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labo_antibiotiques');
        Schema::dropIfExists('labo_germes');
        Schema::dropIfExists('labo_bilan_examen');
        Schema::dropIfExists('labo_bilans');
        Schema::dropIfExists('labo_valeurs_reference');
        Schema::dropIfExists('labo_parametres');
        Schema::dropIfExists('labo_examens');
        Schema::dropIfExists('labo_sections');
    }
};
