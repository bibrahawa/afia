<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FLUX DE TRAVAIL — toutes les tables portent etablissement_id, même
     * quand il se déduit d'une jointure (défense en profondeur, cf. Pilier A :
     * un filtre de sécurité ne repose jamais sur une seule jointure).
     *
     * Aucune suppression physique n'est prévue sur ces tables : une demande
     * s'annule, un résultat se rectifie. Les cascadeOnDelete ne servent
     * qu'au démontage complet d'un établissement résilié.
     */
    public function up(): void
    {
        Schema::create('labo_compteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('cle', 40);
            $table->unsignedBigInteger('valeur')->default(0);
            $table->timestamps();

            $table->unique(['etablissement_id', 'cle']);
        });

        Schema::create('labo_demandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->string('numero', 30);
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();

            $table->string('origine', 20);
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('prescripteur_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('prescripteur_externe')->nullable();
            $table->string('prescripteur_telephone', 30)->nullable();
            // Demande électronique reçue d'un autre établissement de la
            // plateforme (V2) — le labo ne voit alors que la demande.
            $table->foreignId('etablissement_prescripteur_id')->nullable()->constrained('etablissements')->nullOnDelete();

            $table->text('renseignements_cliniques')->nullable();
            $table->boolean('grossesse')->default(false);
            $table->unsignedTinyInteger('semaines_amenorrhee')->nullable();
            $table->boolean('a_jeun_confirme')->nullable();
            $table->boolean('urgence')->default(false);

            $table->string('statut', 30)->default('enregistree');
            $table->string('mode_facturation', 20)->default('labo');
            $table->boolean('resultats_retenus_si_impaye')->default(true);

            $table->foreignId('enregistre_par')->constrained('users')->restrictOnDelete();
            $table->timestamp('annule_le')->nullable();
            $table->foreignId('annule_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motif_annulation')->nullable();
            $table->timestamp('premiere_publication_le')->nullable();
            $table->timestamps();

            $table->unique(['etablissement_id', 'numero']);
            $table->index(['etablissement_id', 'statut', 'created_at']);
            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('labo_demande_examens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('demande_id')->constrained('labo_demandes')->cascadeOnDelete();
            $table->foreignId('examen_id')->constrained('labo_examens')->restrictOnDelete();
            $table->foreignId('bilan_id')->nullable()->constrained('labo_bilans')->nullOnDelete();

            // Snapshots : le catalogue peut changer, la demande non.
            $table->string('examen_nom');
            $table->decimal('prix_applique', 12, 2)->default(0);

            $table->string('statut', 30)->default('en_attente_prelevement');

            $table->boolean('sous_traite')->default(false);
            $table->string('laboratoire_sous_traitant')->nullable();
            $table->timestamp('envoye_sous_traitant_le')->nullable();

            $table->foreignId('valide_technique_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_technique_le')->nullable();
            $table->foreignId('valide_biologique_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_biologique_le')->nullable();
            $table->text('commentaire_biologiste')->nullable();
            $table->timestamp('publie_le')->nullable();

            $table->unsignedSmallInteger('nombre_rectifications')->default(0);
            $table->text('motif_derniere_rectification')->nullable();
            $table->timestamp('annule_le')->nullable();
            $table->string('motif_annulation')->nullable();
            $table->timestamps();

            $table->unique(['demande_id', 'examen_id']);
            $table->index(['etablissement_id', 'statut']);
        });

        Schema::create('labo_echantillons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('demande_id')->constrained('labo_demandes')->cascadeOnDelete();
            $table->string('code_barres', 30);
            $table->string('type_echantillon', 40);
            $table->string('tube', 20)->nullable();
            $table->string('statut', 20)->default('attendu');

            $table->foreignId('preleve_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('preleve_le')->nullable();
            $table->foreignId('recu_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recu_le')->nullable();
            $table->foreignId('rejete_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejete_le')->nullable();
            $table->string('motif_rejet', 40)->nullable();
            $table->string('commentaire')->nullable();
            $table->foreignId('remplace_echantillon_id')->nullable()->constrained('labo_echantillons')->nullOnDelete();
            $table->timestamps();

            $table->unique(['etablissement_id', 'code_barres']);
            $table->index(['etablissement_id', 'statut']);
        });

        Schema::create('labo_echantillon_examen', function (Blueprint $table) {
            $table->foreignId('echantillon_id')->constrained('labo_echantillons')->cascadeOnDelete();
            $table->foreignId('demande_examen_id')->constrained('labo_demande_examens')->cascadeOnDelete();
            $table->primary(['echantillon_id', 'demande_examen_id']);
        });

        Schema::create('labo_resultats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('demande_examen_id')->constrained('labo_demande_examens')->cascadeOnDelete();
            $table->foreignId('parametre_id')->constrained('labo_parametres')->restrictOnDelete();

            $table->decimal('valeur_numerique', 14, 4)->nullable();
            $table->text('valeur_texte')->nullable();
            $table->string('flag', 4)->nullable();

            // Snapshot de ce qui a été appliqué AU MOMENT de la saisie.
            $table->string('libelle');
            $table->string('unite', 30)->nullable();
            $table->unsignedTinyInteger('decimales')->default(1);
            $table->decimal('norme_min', 14, 4)->nullable();
            $table->decimal('norme_max', 14, 4)->nullable();
            $table->decimal('norme_critique_min', 14, 4)->nullable();
            $table->decimal('norme_critique_max', 14, 4)->nullable();
            $table->string('norme_texte')->nullable();

            $table->string('source', 20)->default('manuel'); // manuel | calcule | sous_traitant | automate
            $table->foreignId('saisi_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('saisi_le')->nullable();
            $table->timestamps();

            $table->unique(['demande_examen_id', 'parametre_id']);
            $table->index(['etablissement_id', 'flag']);
        });

        Schema::create('labo_resultat_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resultat_id')->constrained('labo_resultats')->cascadeOnDelete();
            $table->text('ancienne_valeur')->nullable();
            $table->text('nouvelle_valeur')->nullable();
            $table->foreignId('modifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motif')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('labo_germes_isoles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('demande_examen_id')->constrained('labo_demande_examens')->cascadeOnDelete();
            $table->foreignId('germe_id')->constrained('labo_germes')->restrictOnDelete();
            $table->string('germe_nom');
            $table->string('numeration')->nullable(); // « 10^5 UFC/mL »
            $table->timestamps();

            $table->unique(['demande_examen_id', 'germe_id']);
        });

        Schema::create('labo_antibiogrammes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('germe_isole_id')->constrained('labo_germes_isoles')->cascadeOnDelete();
            $table->foreignId('antibiotique_id')->constrained('labo_antibiotiques')->restrictOnDelete();
            $table->string('antibiotique_nom');
            $table->enum('interpretation', ['S', 'I', 'R']);
            $table->string('valeur', 20)->nullable(); // diamètre (mm) ou CMI
            $table->timestamps();

            $table->unique(['germe_isole_id', 'antibiotique_id']);
        });

        Schema::create('labo_alertes_critiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('resultat_id')->constrained('labo_resultats')->cascadeOnDelete();
            $table->foreignId('signale_par')->constrained('users')->restrictOnDelete();
            $table->string('personne_contactee');
            $table->string('moyen', 20); // telephone | en_personne | sms | deja_informe
            $table->string('commentaire')->nullable();
            $table->timestamp('signale_le');
            $table->timestamps();
        });

        Schema::create('labo_comptes_rendus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->foreignId('demande_id')->constrained('labo_demandes')->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->boolean('est_partiel')->default(false);
            $table->boolean('est_rectificatif')->default(false);
            $table->text('motif_rectification')->nullable();

            // Contenu COMPLET figé du compte rendu : un PDF réimprimé dans
            // 5 ans doit être identique à celui remis au patient, même si
            // le catalogue, les normes ou le nom du biologiste ont changé.
            $table->json('contenu');
            $table->string('empreinte', 64); // sha256 du contenu
            $table->string('pdf_path')->nullable();

            $table->foreignId('publie_par')->constrained('users')->restrictOnDelete();
            $table->timestamp('publie_le');
            $table->timestamp('sms_envoye_le')->nullable();
            $table->timestamps();

            $table->unique(['demande_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labo_comptes_rendus');
        Schema::dropIfExists('labo_alertes_critiques');
        Schema::dropIfExists('labo_antibiogrammes');
        Schema::dropIfExists('labo_germes_isoles');
        Schema::dropIfExists('labo_resultat_historiques');
        Schema::dropIfExists('labo_resultats');
        Schema::dropIfExists('labo_echantillon_examen');
        Schema::dropIfExists('labo_echantillons');
        Schema::dropIfExists('labo_demande_examens');
        Schema::dropIfExists('labo_demandes');
        Schema::dropIfExists('labo_compteurs');
    }
};
