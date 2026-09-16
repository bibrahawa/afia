<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La licence réelle d'un établissement : quels modules il a le droit
     * d'utiliser. C'est cette table, et non `etablissements.type`, qui fait
     * autorité pour l'affichage des menus et le middleware `module:xxx`.
     *
     * On garde une trace de l'activation/désactivation plutôt que de
     * supprimer la ligne : utile pour l'historique commercial et pour
     * comprendre "depuis quand ce client a accès à quoi".
     */
    public function up(): void
    {
        Schema::create('etablissement_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('est_actif')->default(true);
            $table->timestamp('active_depuis')->nullable();
            $table->timestamp('desactive_le')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissement_module');
    }
};
