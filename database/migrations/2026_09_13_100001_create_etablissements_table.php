<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un "établissement" = un client de la plateforme (clinique, laboratoire,
     * pharmacie, cabinet...). C'est le remplaçant de l'ancienne table
     * `hospitals`, qui n'était qu'une ligne de config unique.
     *
     * `statut` distingue explicitement un client en phase d'essai (comme
     * Aprosafe aujourd'hui) d'un client payant actif, suspendu ou résilié —
     * indispensable pour la facturation plateforme (Phase 2).
     */
    public function up(): void
    {
        Schema::create('etablissements', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->enum('type', ['clinique', 'laboratoire', 'pharmacie', 'cabinet']);
            $table->enum('statut', ['essai', 'actif', 'suspendu', 'resilie'])->default('essai');

            $table->string('logo')->nullable();
            $table->string('adresse')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('site_web')->nullable();
            $table->longText('description')->nullable();

            // Repris de l'ancienne table hospitals
            $table->string('prefixe_facture')->default('FAC-');
            $table->string('prefixe_patient')->default('PAT-');
            $table->string('type_taxe')->nullable();
            $table->integer('taux_taxe')->nullable();
            $table->string('message_facture')->nullable();
            $table->string('numero_pan')->nullable();
            $table->string('numero_enregistrement')->nullable();

            $table->date('date_debut_contrat')->nullable();
            $table->date('date_fin_contrat')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissements');
    }
};
