<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le graphe familial dit UNIQUEMENT "qui est lié à qui" — il ne donne
     * PAS d'accès automatique aux données de santé (sauf tutelle légale
     * d'un mineur, gérée séparément via compte_patient.role = 'tuteur').
     * Un lien "enfant" vers un patient adulte n'ouvre aucun accès sans un
     * consentement explicite dans la table `consentements`.
     *
     * Une seule ligne par relation : le sens inverse (B est "enfant" de A
     * si A est "père" de B) se calcule à la lecture via TypeRelationFamiliale
     * plutôt que d'être dupliqué — ça évite les incohérences si un seul
     * sens est modifié ou supprimé.
     *
     * `verifie_le` : une relation déclarée par un patient/staff n'a pas la
     * même valeur qu'une relation vérifiée (ex. via acte de naissance
     * présenté à l'accueil) — utile si un jour ce lien conditionne un accès
     * plus sensible que le simple affichage de l'arbre familial.
     */
    public function up(): void
    {
        Schema::create('relations_familiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('personne_liee_id')->constrained('patients')->cascadeOnDelete();
            $table->string('type_relation', 20); // voir App\Enums\TypeRelationFamiliale
            $table->timestamp('verifie_le')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'personne_liee_id', 'type_relation'], 'relation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relations_familiales');
    }
};
