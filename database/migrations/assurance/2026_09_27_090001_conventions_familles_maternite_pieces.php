<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 2d — ajustements terrain.
 *
 *  - L'employeur ne paie jamais les soins en Guinée : le type d'organisme
 *    « entreprise » et la « convention directe » disparaissent. Les organismes
 *    concernés redeviennent des assureurs (rien n'est supprimé).
 *  - Conventions par FAMILLE d'actes : tous les actes d'une famille couverts au
 *    prix catalogue (avec remise éventuelle) sans saisir une ligne par acte. Les
 *    lignes par acte restent pour les exceptions (prix négocié, exclusion).
 *  - Garanties : carence par famille (maternité) et nombre d'actes par période.
 *  - invoice_items.famille_acte : famille figée sur la ligne (limites de fréquence, rapports).
 *  - Pièces justificatives jointes aux réclamations.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('insurance_companies')->where('type', 'entreprise')->update(['type' => 'assureur']);

        if (Schema::hasColumn('entreprises', 'organisme_payeur_id')) {
            Schema::table('entreprises', function (Blueprint $table) {
                $table->dropConstrainedForeignId('organisme_payeur_id');
            });
        }

        Schema::create('assurance_convention_familles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('insurance_company_id')->constrained('insurance_companies')->cascadeOnDelete();
            $table->string('famille_acte', 20);
            $table->decimal('remise_pourcentage', 5, 2)->default(0);
            $table->decimal('plafond_par_acte', 15, 2)->nullable();
            $table->boolean('accord_prealable')->default(false);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['insurance_company_id', 'famille_acte'], 'convention_famille_unique');
        });

        Schema::table('assurance_formule_garanties', function (Blueprint $table) {
            $table->unsignedSmallInteger('delai_carence_jours')->nullable()->after('accord_prealable');
            $table->unsignedSmallInteger('nombre_max')->nullable()->after('delai_carence_jours');
            $table->string('periode', 20)->nullable()->after('nombre_max'); // mois | trimestre | annee
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('famille_acte', 20)->nullable()->after('coverage_type_id');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->string('famille_acte', 20)->nullable()->after('price');
        });

        Schema::create('assurance_pieces_justificatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('insurance_claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->string('type', 30); // carte | bon | ordonnance | compte_rendu | feuille_soins | autre
            $table->string('libelle')->nullable();
            $table->string('chemin');
            $table->string('nom_original');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('taille')->default(0);
            $table->foreignId('ajoute_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assurance_pieces_justificatives');
        Schema::table('packages', fn (Blueprint $t) => $t->dropColumn('famille_acte'));
        Schema::table('invoice_items', fn (Blueprint $t) => $t->dropColumn('famille_acte'));
        Schema::table('assurance_formule_garanties', fn (Blueprint $t) => $t->dropColumn(['delai_carence_jours', 'nombre_max', 'periode']));
        Schema::dropIfExists('assurance_convention_familles');
        Schema::table('entreprises', function (Blueprint $table) {
            $table->foreignId('organisme_payeur_id')->nullable()->constrained('insurance_companies')->nullOnDelete();
        });
    }
};
