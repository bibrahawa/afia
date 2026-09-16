<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scopé par DÉPARTEMENT, pas par établissement seul : pédiatrie et
     * gynécologie n'ont pas les mêmes motifs au sein d'une même clinique.
     * `etablissement_id` est dénormalisé depuis le département (rempli
     * automatiquement à la création, voir le modèle) — défense en
     * profondeur, comme pour toutes les tables opérationnelles.
     *
     * `duree_minutes_defaut` est ce qui manquait dans l'ancien enum figé
     * `appointments.reason` : la durée n'a jamais été qu'un attribut du
     * motif, jamais du jour ou de l'établissement.
     */
    public function up(): void
    {
        Schema::create('motifs_rdv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();

            $table->string('code');
            $table->string('nom');
            $table->unsignedSmallInteger('duree_minutes_defaut')->default(15);
            $table->unsignedSmallInteger('marge_tampon_minutes')->default(5);
            $table->string('couleur', 7)->default('#3B82F6'); // affichage calendrier
            $table->boolean('actif')->default(true);
            $table->unsignedInteger('ordre_affichage')->default(0);

            // Lien optionnel vers le catalogue de facturation existant —
            // préparé pour plus tard (pré-suggestion de l'acte facturable
            // à l'issue du rdv), pas exploité tout de suite.
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();

            $table->timestamps();

            $table->unique(['department_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motifs_rdv');
    }
};
