<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 3b — consultation rapide.
 *
 *  consultation_medicament : posologie PRESCRITE (elle varie d'un patient à
 *      l'autre) ; jusqu'ici seule la posologie du catalogue existait.
 *  modeles_consultation : protocoles réutilisables (« Paludisme simple »,
 *      « CPN 1er trimestre ») que le médecin enregistre depuis une consultation
 *      réussie, puis applique en un clic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_medicament', function (Blueprint $table) {
            $table->string('dose', 60)->nullable()->after('quantity');
            $table->string('frequence', 60)->nullable()->after('dose');
            $table->string('duree', 60)->nullable()->after('frequence');
            $table->string('instructions')->nullable()->after('duree');
        });

        Schema::create('modeles_consultation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            // null = modèle partagé avec tout le département.
            $table->foreignId('medecin_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('motif_rdv_id')->nullable()->constrained('motifs_rdv')->nullOnDelete();
            $table->string('libelle');
            $table->text('diagnostic')->nullable();
            $table->json('signes_cliniques')->nullable();
            $table->text('observation')->nullable();
            $table->unsignedInteger('utilisations')->default(0);
            $table->boolean('actif')->default(true);
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['etablissement_id', 'department_id', 'actif']);
        });

        Schema::create('modele_consultation_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('modele_id')->constrained('modeles_consultation')->cascadeOnDelete();
            $table->string('type', 20); // service | package | test | medicament
            $table->unsignedBigInteger('acte_id');
            $table->unsignedInteger('quantite')->default(1);
            $table->string('dose', 60)->nullable();
            $table->string('frequence', 60)->nullable();
            $table->string('duree', 60)->nullable();
            $table->string('instructions')->nullable();
            $table->timestamps();

            $table->index(['modele_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modele_consultation_lignes');
        Schema::dropIfExists('modeles_consultation');
        Schema::table('consultation_medicament', fn (Blueprint $t) => $t->dropColumn(['dose', 'frequence', 'duree', 'instructions']));
    }
};
