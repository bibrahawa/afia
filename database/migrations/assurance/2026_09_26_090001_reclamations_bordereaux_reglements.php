<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 2c — cycle des réclamations assurance.
 *
 *  insurance_claims            = la part d'UN payeur sur UNE facture (inchangé)
 *  insurance_claim_lignes      = détail par acte : ce qui est réclamé, ce que l'assureur accepte, pourquoi il rejette
 *  assurance_bordereaux        = envoi groupé des réclamations d'un payeur (relevé mensuel)
 *  insurance_settlement_items  = règlement imputé RÉCLAMATION par réclamation (et non plus facture par facture,
 *                                ce qui rendait la 2e assurance d'une facture impossible à régler)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assurance_bordereaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('insurance_company_id')->constrained('insurance_companies')->restrictOnDelete();
            $table->string('numero', 50);
            $table->date('periode_debut')->nullable();
            $table->date('periode_fin')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon | envoye
            $table->date('date_envoi')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['etablissement_id', 'numero']);
            $table->index(['insurance_company_id', 'statut']);
        });

        Schema::create('insurance_claim_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('insurance_claim_id')->constrained('insurance_claims')->cascadeOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
            $table->string('description');
            $table->string('famille_acte', 20)->nullable();
            $table->unsignedInteger('quantite')->default(1);
            $table->decimal('montant_acte', 15, 2)->default(0);
            $table->decimal('taux', 5, 2)->nullable();
            $table->decimal('montant_reclame', 15, 2);
            $table->decimal('montant_accepte', 15, 2)->nullable();
            $table->string('motif_rejet')->nullable();
            $table->timestamps();
        });

        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->foreignId('bordereau_id')->nullable()->after('insurance_company_id')->constrained('assurance_bordereaux')->nullOnDelete();
            // Part non acceptée par l'assureur remise à la charge du patient.
            $table->decimal('montant_transfere_patient', 15, 2)->default(0)->after('paid_amount');
            $table->index(['insurance_company_id', 'status']);
        });

        Schema::table('insurance_settlement_items', function (Blueprint $table) {
            $table->foreignId('insurance_claim_id')->nullable()->after('invoice_id')->constrained('insurance_claims')->nullOnDelete();
            // Encaissement créé par ce règlement (part payée). Null pour les règlements antérieurs au lot 2c.
            $table->foreignId('paiement_id')->nullable()->after('insurance_claim_id')->constrained('paiements')->nullOnDelete();
        });

        // Règlements antérieurs (facture par facture) : rattachés à la réclamation
        // quand la facture n'en a qu'une — cas de toutes les factures mono-assurance.
        DB::statement("
            UPDATE insurance_settlement_items i
            JOIN (SELECT invoice_id, MIN(id) AS claim_id FROM insurance_claims GROUP BY invoice_id HAVING COUNT(*) = 1) c
              ON c.invoice_id = i.invoice_id
            SET i.insurance_claim_id = c.claim_id
            WHERE i.insurance_claim_id IS NULL
        ");

        // Réclamations entièrement soldées par ces règlements : statut « paid ».
        DB::statement("
            UPDATE insurance_claims c
            JOIN (SELECT insurance_claim_id, SUM(applied_paid_amount + applied_discount_amount) AS regle, SUM(applied_paid_amount) AS paye
                  FROM insurance_settlement_items WHERE insurance_claim_id IS NOT NULL GROUP BY insurance_claim_id) x
              ON x.insurance_claim_id = c.id
            SET c.status = 'paid', c.paid_amount = x.paye
            WHERE x.regle >= c.claimed_amount - 0.01 AND c.status <> 'rejected'
        ");

        // Réclamations existantes : une ligne unique (montant global), pour que
        // l'écran de réponse fonctionne aussi sur l'historique.
        DB::statement("
            INSERT INTO insurance_claim_lignes (etablissement_id, insurance_claim_id, description, quantite, montant_acte, montant_reclame, montant_accepte, created_at, updated_at)
            SELECT c.etablissement_id, c.id, 'Prise en charge (réclamation antérieure au détail par acte)', 1, c.claimed_amount, c.claimed_amount, c.approved_amount, NOW(), NOW()
            FROM insurance_claims c
            WHERE c.etablissement_id IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM insurance_claim_lignes l WHERE l.insurance_claim_id = c.id)
        ");
    }

    public function down(): void
    {
        Schema::table('insurance_settlement_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paiement_id');
            $table->dropConstrainedForeignId('insurance_claim_id');
        });

        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->dropIndex(['insurance_company_id', 'status']);
            $table->dropConstrainedForeignId('bordereau_id');
            $table->dropColumn('montant_transfere_patient');
        });

        Schema::dropIfExists('insurance_claim_lignes');
        Schema::dropIfExists('assurance_bordereaux');
    }
};
