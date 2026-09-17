<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 4a — laboratoire en réseau.
 *
 * Un partenariat relie UN laboratoire (propriétaire de la ligne) à UNE
 * clinique prescriptrice. Il ouvre à la clinique le catalogue du laboratoire,
 * l'envoi de demandes et la lecture des comptes rendus qu'elle a prescrits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labo_partenariats', function (Blueprint $table) {
            $table->id();
            // Le laboratoire : c'est lui qui possède la ligne (cloisonnement habituel).
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('clinique_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('statut', 20)->default('actif'); // actif | suspendu
            // patient : le labo encaisse le patient. partenaire : le labo facture la clinique.
            $table->string('mode_facturation_defaut', 20)->default('patient');
            $table->decimal('remise_pourcentage', 5, 2)->default(0);
            $table->unsignedSmallInteger('delai_paiement_jours')->nullable();
            $table->string('contact_nom')->nullable();
            $table->string('contact_telephone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['etablissement_id', 'clinique_id']);
            $table->index(['clinique_id', 'statut']);
        });

        Schema::table('labo_demandes', function (Blueprint $table) {
            $table->foreignId('partenariat_id')->nullable()->after('etablissement_prescripteur_id')
                ->constrained('labo_partenariats')->nullOnDelete();
            $table->index(['etablissement_prescripteur_id', 'created_at'], 'labo_demandes_prescripteur_index');
        });
    }

    public function down(): void
    {
        Schema::table('labo_demandes', function (Blueprint $table) {
            $table->dropIndex('labo_demandes_prescripteur_index');
            $table->dropConstrainedForeignId('partenariat_id');
        });

        Schema::dropIfExists('labo_partenariats');
    }
};
