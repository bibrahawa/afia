<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nullable volontairement : un super-admin plateforme (toi, ton équipe
     * support) n'appartient à aucun établissement et doit pouvoir switcher
     * de contexte. Un utilisateur "métier" (médecin, secrétaire, biologiste)
     * a systématiquement un etablissement_id renseigné — c'est cette valeur
     * qui alimente le global scope sur toutes les tables opérationnelles.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('etablissement_id')
                ->nullable()
                ->after('id')
                ->constrained('etablissements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etablissement_id');
        });
    }
};
