<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 4b — facturation entre établissements.
 *
 * Quand le partenariat prévoit « le laboratoire facture la clinique », chaque
 * demande produit une créance. Les créances d'une période sont regroupées dans
 * un relevé envoyé à la clinique, puis réglées.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labo_releves_partenaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('partenariat_id')->constrained('labo_partenariats')->restrictOnDelete();
            $table->string('numero', 50);
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->date('echeance')->nullable();
            $table->decimal('montant_total', 14, 2)->default(0);
            $table->string('statut', 20)->default('brouillon'); // brouillon | envoye | solde
            $table->timestamp('date_envoi')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['etablissement_id', 'numero']);
            $table->index(['partenariat_id', 'statut']);
        });

        Schema::create('labo_creances_partenaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('partenariat_id')->constrained('labo_partenariats')->restrictOnDelete();
            $table->foreignId('demande_id')->constrained('labo_demandes')->cascadeOnDelete();
            $table->foreignId('releve_id')->nullable()->constrained('labo_releves_partenaires')->nullOnDelete();
            $table->decimal('montant', 14, 2)->default(0);
            $table->decimal('montant_regle', 14, 2)->default(0);
            $table->string('statut', 20)->default('a_facturer'); // a_facturer | facturee | reglee | annulee
            $table->timestamps();

            $table->unique('demande_id');
            $table->index(['partenariat_id', 'statut']);
        });

        Schema::create('labo_reglements_partenaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('partenariat_id')->constrained('labo_partenariats')->restrictOnDelete();
            $table->decimal('montant', 14, 2);
            $table->string('mode', 30)->default('virement'); // virement | especes | cheque | mobile
            $table->string('reference')->nullable();
            $table->date('recu_le');
            $table->text('notes')->nullable();
            $table->foreignId('enregistre_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['partenariat_id', 'recu_le']);
        });

        Schema::create('labo_reglement_imputations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('reglement_id')->constrained('labo_reglements_partenaires')->cascadeOnDelete();
            $table->foreignId('creance_id')->constrained('labo_creances_partenaires')->cascadeOnDelete();
            $table->decimal('montant', 14, 2);
            $table->timestamps();

            $table->index(['creance_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labo_reglement_imputations');
        Schema::dropIfExists('labo_reglements_partenaires');
        Schema::dropIfExists('labo_creances_partenaires');
        Schema::dropIfExists('labo_releves_partenaires');
    }
};
