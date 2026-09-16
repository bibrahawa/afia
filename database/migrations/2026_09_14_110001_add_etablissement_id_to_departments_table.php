<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `departments` était un reliquat mono-tenant, comme `hospitals` à
     * l'origine — aucune notion de tenant. C'est un prérequis silencieux
     * du module Rdv : les motifs sont scopés par département, donc le
     * département doit d'abord être scopé par établissement.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('etablissement_id')->nullable()->after('id')
                ->constrained('etablissements')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etablissement_id');
        });
    }
};
