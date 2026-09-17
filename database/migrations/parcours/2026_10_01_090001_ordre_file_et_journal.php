<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 3d — ordre de la file décidé par la clinique.
 *
 *  etablissements.ordre_file : règle par défaut (arrivée, ou rendez-vous d'abord).
 *  visites.rang              : ordre imposé à la main par l'accueil, prioritaire
 *                              sur la règle par défaut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->string('ordre_file', 20)->default('arrivee')->after('statut'); // arrivee | rendez_vous
        });

        Schema::table('visites', function (Blueprint $table) {
            $table->unsignedInteger('rang')->nullable()->after('urgence');
            $table->index(['etablissement_id', 'rang']);
        });
    }

    public function down(): void
    {
        Schema::table('visites', function (Blueprint $table) {
            $table->dropIndex(['etablissement_id', 'rang']);
            $table->dropColumn('rang');
        });

        Schema::table('etablissements', fn (Blueprint $t) => $t->dropColumn('ordre_file'));
    }
};
