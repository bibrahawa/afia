<?php

namespace App\Support\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Outil partagé par les migrations « rendre X multi-établissements » :
 * même colonne, même index, même rattachement des données existantes,
 * pour que toutes les tables soient cloisonnées de façon identique.
 */
class AjoutEtablissement
{
    /**
     * Établissement auquel rattacher les lignes historiques (mono-clinique) :
     * le pilote « aprosafe », ou l'unique établissement existant.
     */
    public static function etablissementPilote(array $tablesAVerifier): ?int
    {
        $id = DB::table('etablissements')->where('slug', 'aprosafe')->value('id');
        if ($id) {
            return (int) $id;
        }

        $ids = DB::table('etablissements')->pluck('id');
        $donnees = collect($tablesAVerifier)->contains(fn ($t) => Schema::hasTable($t) && DB::table($t)->exists());

        if ($ids->count() !== 1 && $donnees) {
            throw new \RuntimeException(
                'Données existantes sans établissement identifiable : lancez EtablissementFoundationSeeder (slug « aprosafe ») avant de migrer.'
            );
        }

        return $ids->first();
    }

    /**
     * @param  bool  $restreindre  true pour les tables financières ou médicales :
     *                             on refuse de supprimer un établissement qui en possède.
     */
    public static function ajouter(string $table, ?int $etablissementPilote, bool $restreindre = true): void
    {
        if (Schema::hasColumn($table, 'etablissement_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($restreindre) {
            $fk = $t->foreignId('etablissement_id')->nullable()->after('id')->constrained('etablissements');
            $restreindre ? $fk->restrictOnDelete() : $fk->cascadeOnDelete();
            $t->index(['etablissement_id', 'created_at']);
        });

        if ($etablissementPilote) {
            DB::table($table)->whereNull('etablissement_id')->update(['etablissement_id' => $etablissementPilote]);
        }
    }

    /** Remplace un index unique global par un unique par établissement. */
    public static function uniqueParEtablissement(string $table, string $colonne): void
    {
        Schema::table($table, function (Blueprint $t) use ($table, $colonne) {
            $t->dropUnique("{$table}_{$colonne}_unique");
            $t->unique(['etablissement_id', $colonne]);
        });
    }

    public static function retirer(string $table): void
    {
        if (! Schema::hasColumn($table, 'etablissement_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $t) {
            // Ordre imposé par MySQL : la clé étrangère s'appuie sur l'index composite.
            $t->dropForeign(['etablissement_id']);
            $t->dropIndex(['etablissement_id', 'created_at']);
            $t->dropColumn('etablissement_id');
        });
    }

    public static function uniqueGlobal(string $table, string $colonne): void
    {
        Schema::table($table, function (Blueprint $t) use ($colonne) {
            $t->dropUnique(['etablissement_id', $colonne]);
            $t->unique($colonne);
        });
    }
}
