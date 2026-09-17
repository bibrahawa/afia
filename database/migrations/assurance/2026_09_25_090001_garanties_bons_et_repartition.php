<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 2b — nouveau moteur de prise en charge.
 *
 * - assurance_formule_garanties : taux, plafond par acte, exclusion et accord
 *   préalable par FAMILLE D'ACTES (une ligne absente = règles de la formule) ;
 * - assurance_prises_en_charge : bons / accords préalables délivrés par le payeur ;
 * - assurance_pec_utilisations : montant d'un bon consommé par chaque facture ;
 * - services.famille_acte : famille d'un service du catalogue (consultation,
 *   imagerie, soins…) ; les autres actes ont une famille déduite de leur type ;
 * - invoice_items.repartition_assurance / invoices.alertes_assurance : détail
 *   lisible de la prise en charge (qui paie quoi, pourquoi un acte n'est pas couvert).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assurance_formule_garanties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('formule_id')->constrained('assurance_formules')->cascadeOnDelete();
            $table->string('famille_acte', 20);
            $table->decimal('taux', 5, 2)->nullable();
            $table->decimal('plafond_par_acte', 15, 2)->nullable();
            $table->boolean('exclu')->default(false);
            $table->boolean('accord_prealable')->default(false);
            $table->timestamps();

            $table->unique(['formule_id', 'famille_acte']);
        });

        Schema::create('assurance_prises_en_charge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('beneficiaire_id')->constrained('assurance_beneficiaires')->restrictOnDelete();
            $table->string('numero', 100);
            $table->string('famille_acte', 20)->nullable(); // null = toutes familles
            $table->decimal('montant_accorde', 15, 2)->nullable(); // null = sans plafond propre
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut', 20)->default('accorde'); // accorde | annule
            $table->text('notes')->nullable();
            $table->foreignId('enregistre_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['beneficiaire_id', 'numero']);
            $table->index(['beneficiaire_id', 'statut', 'date_fin']);
        });

        Schema::create('assurance_pec_utilisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('prise_en_charge_id')->constrained('assurance_prises_en_charge')->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('montant', 15, 2);
            $table->timestamps();

            $table->index(['prise_en_charge_id', 'invoice_id']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('famille_acte', 20)->nullable()->after('amount');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->json('repartition_assurance')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->json('alertes_assurance')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn('alertes_assurance'));
        Schema::table('invoice_items', fn (Blueprint $t) => $t->dropColumn('repartition_assurance'));
        Schema::table('services', fn (Blueprint $t) => $t->dropColumn('famille_acte'));
        Schema::dropIfExists('assurance_pec_utilisations');
        Schema::dropIfExists('assurance_prises_en_charge');
        Schema::dropIfExists('assurance_formule_garanties');
    }
};
