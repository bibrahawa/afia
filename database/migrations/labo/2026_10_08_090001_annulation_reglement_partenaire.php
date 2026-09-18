<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 4e — un règlement de partenaire ne se supprime pas : il s'annule, avec
 * un motif et une trace, comme un encaissement de caisse ou d'assurance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labo_reglements_partenaires', function (Blueprint $table) {
            $table->timestamp('annule_le')->nullable()->after('notes');
            $table->string('motif_annulation')->nullable()->after('annule_le');
            $table->foreignId('annule_par')->nullable()->after('motif_annulation')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labo_reglements_partenaires', function (Blueprint $table) {
            $table->dropConstrainedForeignId('annule_par');
            $table->dropColumn(['annule_le', 'motif_annulation']);
        });
    }
};
