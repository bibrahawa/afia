<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OPTIONNELLE par conception : l'absence de ligne pour un couple
     * (médecin, motif) signifie "l'appartenance au département suffit"
     * (comportement permissif par défaut). Cette table ne sert qu'aux
     * exceptions réelles : restreindre un motif à certains praticiens,
     * ou surcharger sa durée pour un médecin précis.
     */
    public function up(): void
    {
        Schema::create('medecin_motif', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('motif_rdv_id')->constrained('motifs_rdv')->cascadeOnDelete();
            $table->unsignedSmallInteger('duree_minutes')->nullable(); // surcharge, sinon celle du motif
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['employee_id', 'motif_rdv_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medecin_motif');
    }
};
