<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 4f — accord de la clinique au partenariat, et trace de l'information
 * donnée au patient sur l'envoi de ses analyses à un autre établissement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labo_partenariats', function (Blueprint $table) {
            $table->timestamp('propose_le')->nullable()->after('statut');
            $table->timestamp('accepte_le')->nullable()->after('propose_le');
            $table->foreignId('accepte_par')->nullable()->after('accepte_le')->constrained('users')->nullOnDelete();
            $table->string('motif_refus')->nullable()->after('accepte_par');
        });

        Schema::table('labo_demandes', function (Blueprint $table) {
            $table->timestamp('consentement_partage_le')->nullable()->after('resultat_vu_par');
            $table->foreignId('consentement_recueilli_par')->nullable()->after('consentement_partage_le')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labo_demandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consentement_recueilli_par');
            $table->dropColumn('consentement_partage_le');
        });

        Schema::table('labo_partenariats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accepte_par');
            $table->dropColumn(['propose_le', 'accepte_le', 'motif_refus']);
        });
    }
};
