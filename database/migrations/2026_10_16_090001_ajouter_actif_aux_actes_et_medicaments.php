<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot S3 — « Masquer » plutôt que « Supprimer ».
 * Un acte ou un médicament masqué n'est plus proposé (accueil, consultation,
 * forfaits, motifs, modèles), mais reste lisible partout où il a déjà servi
 * (consultations, ordonnances, factures).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['services', 'medicaments'] as $table) {
            if (! Schema::hasColumn($table, 'actif')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->boolean('actif')->default(true)->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['services', 'medicaments'] as $table) {
            if (Schema::hasColumn($table, 'actif')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('actif'));
            }
        }
    }
};
