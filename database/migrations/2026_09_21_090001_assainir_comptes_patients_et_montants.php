<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Priorité 3 — assainissement des comptes patients et des montants.
 *
 * 1. Montants en DECIMAL(15,2) au lieu de FLOAT : un FLOAT ne représente pas
 *    exactement les montants et fait dériver les soldes à force d'additions.
 * 2. Statut « completed » (posé par erreur, absent de l'énumération) → « paid ».
 * 3. Comptes en double pour un même patient et un même établissement :
 *    fusionnés dans le plus ancien (transactions rattachées, soldes additionnés),
 *    puis index unique pour que cela ne se reproduise plus.
 * 4. ON DELETE CASCADE → RESTRICT sur transactions.account_id et
 *    paiements.transaction_id : supprimer un compte effaçait ses factures,
 *    supprimer une facture effaçait la trace de l'argent encaissé.
 *
 * Aucun solde n'est recalculé ici : lancez ensuite
 *   php artisan aprosafe:comptes            (rapport des écarts)
 *   php artisan aprosafe:comptes --corriger (après vérification)
 *
 * SAUVEGARDE COMPLÈTE OBLIGATOIRE AVANT : la fusion de comptes n'est pas réversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', fn (Blueprint $t) => $t->decimal('balance', 15, 2)->default(0)->change());
        Schema::table('paiements', fn (Blueprint $t) => $t->decimal('montant', 15, 2)->change());
        Schema::table('transactions', function (Blueprint $t) {
            $t->decimal('sub_total', 15, 2)->change();
            $t->decimal('tax_amount', 15, 2)->default(0)->change();
            $t->decimal('discount', 15, 2)->default(0)->change();
            $t->decimal('montant_payer', 15, 2)->default(0)->change();
            $t->decimal('total', 15, 2)->change();
        });

        DB::table('transactions')->where('status', 'completed')->update(['status' => 'paid']);

        DB::transaction(function () {
            $doublons = DB::table('accounts')
                ->select('etablissement_id', 'owner_type', 'owner_id', DB::raw('MIN(id) as garde'), DB::raw('COUNT(*) as nb'))
                ->groupBy('etablissement_id', 'owner_type', 'owner_id')
                ->having('nb', '>', 1)
                ->get();

            foreach ($doublons as $d) {
                $autres = DB::table('accounts')
                    ->where('owner_type', $d->owner_type)->where('owner_id', $d->owner_id)
                    ->where(fn ($q) => $d->etablissement_id === null ? $q->whereNull('etablissement_id') : $q->where('etablissement_id', $d->etablissement_id))
                    ->where('id', '!=', $d->garde)
                    ->pluck('id');

                $soldeAutres = (float) DB::table('accounts')->whereIn('id', $autres)->sum('balance');

                // D'abord rattacher les transactions (la contrainte CASCADE les supprimerait sinon).
                DB::table('transactions')->whereIn('account_id', $autres)->update(['account_id' => $d->garde]);
                DB::table('accounts')->where('id', $d->garde)->update(['balance' => DB::raw('balance + ' . number_format($soldeAutres, 2, '.', ''))]);
                DB::table('accounts')->whereIn('id', $autres)->delete();
            }
        });

        Schema::table('accounts', fn (Blueprint $t) => $t->unique(['etablissement_id', 'owner_type', 'owner_id'], 'accounts_proprietaire_unique'));

        Schema::table('transactions', function (Blueprint $t) {
            $t->dropForeign(['account_id']);
            $t->foreign('account_id')->references('id')->on('accounts')->restrictOnDelete();
        });

        Schema::table('paiements', function (Blueprint $t) {
            $t->dropForeign(['transaction_id']);
            $t->foreign('transaction_id')->references('id')->on('transactions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $t) {
            $t->dropForeign(['transaction_id']);
            $t->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
        });
        Schema::table('transactions', function (Blueprint $t) {
            $t->dropForeign(['account_id']);
            $t->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
        Schema::table('accounts', fn (Blueprint $t) => $t->dropUnique('accounts_proprietaire_unique'));
        // Les comptes fusionnés ne sont pas recréés ; les colonnes restent en DECIMAL (aucune perte à les garder).
    }
};
