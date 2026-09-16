<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SEULE cette table fait autorité pour "qui peut voir quoi" au-delà de
     * ce qu'un établissement a lui-même produit. `beneficiaire` est
     * polymorphe : un Etablissement entier (accès large donné à une
     * clinique), un User précis (un seul médecin nommément), ou même un
     * Patient (délégation familiale — un enfant adulte gérant le dossier
     * d'un parent). Expiration par défaut obligatoire côté application
     * (ex. 90 jours) : jamais d'autorisation ouverte indéfiniment par
     * défaut, pour éviter les accès "zombies" oubliés.
     */
    public function up(): void
    {
        Schema::create('consentements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();

            $table->morphs('beneficiaire'); // Etablissement, User ou Patient

            $table->json('portee');
            $table->enum('statut', ['actif', 'revoque', 'expire'])->default('actif');

            $table->timestamp('accorde_le');
            $table->timestamp('expire_le')->nullable();
            $table->timestamp('revoque_le')->nullable();

            $table->foreignId('demande_acces_id')->nullable()->constrained('demandes_acces')->nullOnDelete();

            $table->timestamps();

            $table->index(['patient_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentements');
    }
};
