<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `patients` reste une table GLOBALE (un patient est une personne, pas
     * une ligne appartenant à une clinique). Cette table trace quels
     * établissements ont reçu quel patient et quand — c'est la base du
     * futur carnet inter-établissements (Phase 6) et ça évite qu'un même
     * patient soit dupliqué en autant de fiches que de cliniques visitées.
     */
    public function up(): void
    {
        Schema::create('etablissement_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->timestamp('premiere_visite_le')->nullable();
            $table->timestamp('derniere_visite_le')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissement_patient');
    }
};
