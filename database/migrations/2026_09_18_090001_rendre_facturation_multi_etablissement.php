<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Facturation multi-établissements : transactions, invoices, invoice_items,
 * paiements et accounts reçoivent un etablissement_id.
 *
 * - Les lignes existantes sont rattachées à l'établissement pilote (slug
 *   « aprosafe »), ou au seul établissement existant.
 * - restrictOnDelete (et non cascade) : supprimer un établissement ne doit
 *   JAMAIS effacer silencieusement des pièces comptables.
 * - Les numéros (T-2026xxxxx, P-2026xxxxx) deviennent uniques PAR
 *   établissement : deux cliniques peuvent chacune avoir leur T-202600001.
 * - accounts : le solde d'un patient est désormais propre à chaque
 *   établissement (le patient, lui, reste global).
 *
 * À lancer AVANT tout second établissement en production, et après une
 * sauvegarde complète de la base.
 */
return new class extends Migration
{
    private const TABLES = ['transactions', 'invoices', 'invoice_items', 'paiements', 'accounts'];

    public function up(): void
    {
        $etablissementId = $this->etablissementPilote();

        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'etablissement_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('etablissement_id')->nullable()->after('id')
                    ->constrained('etablissements')->restrictOnDelete();
                $t->index(['etablissement_id', 'created_at']);
            });

            if ($etablissementId) {
                DB::table($table)->whereNull('etablissement_id')->update(['etablissement_id' => $etablissementId]);
            }
        }

        Schema::table('transactions', function (Blueprint $t) {
            $t->dropUnique('transactions_invoice_no_unique');
            $t->unique(['etablissement_id', 'invoice_no']);
        });

        Schema::table('paiements', function (Blueprint $t) {
            $t->dropUnique('paiements_paiement_no_unique');
            $t->unique(['etablissement_id', 'paiement_no']);
        });

        Schema::table('accounts', function (Blueprint $t) {
            $t->index(['etablissement_id', 'owner_type', 'owner_id']);
        });

        // Compteurs atomiques de numérotation, par établissement et par série.
        Schema::create('compteurs_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $t->string('serie', 50);          // ex. « transaction-2026 », « paiement-2026 »
            $t->unsignedBigInteger('valeur')->default(0);
            $t->timestamps();
            $t->unique(['etablissement_id', 'serie']);
        });

        $this->initialiserCompteurs();
    }

    public function down(): void
    {
        Schema::dropIfExists('compteurs_documents');

        Schema::table('accounts', fn (Blueprint $t) => $t->dropIndex(['etablissement_id', 'owner_type', 'owner_id']));
        Schema::table('paiements', function (Blueprint $t) {
            $t->dropUnique(['etablissement_id', 'paiement_no']);
            $t->unique('paiement_no');
        });
        Schema::table('transactions', function (Blueprint $t) {
            $t->dropUnique(['etablissement_id', 'invoice_no']);
            $t->unique('invoice_no');
        });

        foreach (array_reverse(self::TABLES) as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['etablissement_id', 'created_at']);
                $t->dropConstrainedForeignId('etablissement_id');
            });
        }
    }

    private function etablissementPilote(): ?int
    {
        $id = DB::table('etablissements')->where('slug', 'aprosafe')->value('id');
        if ($id) {
            return (int) $id;
        }

        $ids = DB::table('etablissements')->pluck('id');
        $lignesExistantes = collect(self::TABLES)->contains(fn ($t) => DB::table($t)->exists());

        if ($ids->count() !== 1 && $lignesExistantes) {
            throw new RuntimeException(
                'Impossible de déterminer l\'établissement des factures existantes : lancez d\'abord EtablissementFoundationSeeder (slug « aprosafe »).'
            );
        }

        return $ids->first();
    }

    /** Reprend la numérotation là où l'ancien système « dernier + 1 » s'était arrêté. */
    private function initialiserCompteurs(): void
    {
        $series = [
            ['transactions', 'invoice_no', 'transaction'],
            ['paiements', 'paiement_no', 'paiement'],
        ];

        foreach ($series as [$table, $colonne, $prefixe]) {
            $lignes = DB::table($table)
                ->whereNotNull('etablissement_id')->whereNotNull($colonne)
                ->select('etablissement_id', $colonne)->get();

            $max = [];
            foreach ($lignes as $l) {
                // Format T-AAAANNNNN / P-AAAANNNNN
                if (preg_match('/^[A-Z]-(\d{4})(\d{5,})$/', $l->{$colonne}, $m)) {
                    $cle = $l->etablissement_id . '|' . $prefixe . '-' . $m[1];
                    $max[$cle] = max($max[$cle] ?? 0, (int) $m[2]);
                }
            }

            foreach ($max as $cle => $valeur) {
                [$etab, $serie] = explode('|', $cle);
                DB::table('compteurs_documents')->insert([
                    'etablissement_id' => $etab, 'serie' => $serie, 'valeur' => $valeur,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }
};
