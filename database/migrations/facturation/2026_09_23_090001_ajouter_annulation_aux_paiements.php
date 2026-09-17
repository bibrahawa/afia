<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annulation d'un paiement SANS suppression.
 *
 * Un encaissement erroné (mauvais montant, mauvaise facture, double saisie)
 * n'avait qu'une issue : supprimer la ligne en base, ce qui efface la trace
 * de l'argent. Désormais on l'annule : la ligne reste, avec qui l'a annulée,
 * quand et pourquoi. Les paiements annulés sont exclus de tous les calculs
 * (global scope « valides » du modèle Paiement).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->timestamp('annule_le')->nullable()->after('montant');
            $table->foreignId('annule_par')->nullable()->after('annule_le')->constrained('users')->nullOnDelete();
            $table->string('motif_annulation', 255)->nullable()->after('annule_par');

            $table->index(['transaction_id', 'annule_le'], 'idx_paiements_transaction_valides');
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropIndex('idx_paiements_transaction_valides');
            $table->dropConstrainedForeignId('annule_par');
            $table->dropColumn(['annule_le', 'motif_annulation']);
        });
    }
};
