<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 4c — assurance du patient sur les analyses envoyées, et retour des
 * résultats à la clinique.
 *
 *  labo_partenariat_actes : correspondance examen du laboratoire → acte du
 *      catalogue de la clinique, pour que le moteur d'assurance de la CLINIQUE
 *      (ses conventions, ses contrats) s'applique à ces analyses.
 *  clinique_facture_patient : la clinique facture son patient et paie ensuite
 *      le laboratoire sur relevé (lot 4b).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labo_partenariats', function (Blueprint $table) {
            $table->boolean('clinique_facture_patient')->default(false)->after('mode_facturation_defaut');
        });

        Schema::create('labo_partenariat_actes', function (Blueprint $table) {
            $table->id();
            // La correspondance appartient à la CLINIQUE : c'est son catalogue.
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('partenariat_id')->constrained('labo_partenariats')->cascadeOnDelete();
            $table->foreignId('examen_id')->constrained('labo_examens')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->boolean('cree_automatiquement')->default(false);
            $table->timestamps();

            $table->unique(['partenariat_id', 'examen_id']);
        });

        Schema::table('labo_demandes', function (Blueprint $table) {
            $table->timestamp('resultat_notifie_le')->nullable()->after('premiere_publication_le');
            $table->timestamp('resultat_vu_le')->nullable()->after('resultat_notifie_le');
            $table->foreignId('resultat_vu_par')->nullable()->after('resultat_vu_le')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labo_demandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resultat_vu_par');
            $table->dropColumn(['resultat_notifie_le', 'resultat_vu_le']);
        });

        Schema::dropIfExists('labo_partenariat_actes');
        Schema::table('labo_partenariats', fn (Blueprint $t) => $t->dropColumn('clinique_facture_patient'));
    }
};
