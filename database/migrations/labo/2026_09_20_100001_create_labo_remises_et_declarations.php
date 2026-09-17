<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Priorité 2 — lot 2
 *
 * 1. labo_remises : trace de chaque remise d'un compte rendu en main propre
 *    (qui a remis, à qui, quelle version, sur présentation de quoi).
 * 2. Maladies à déclaration obligatoire (MDO) : l'examen porte la maladie
 *    concernée ; un résultat positif validé ouvre une déclaration à faire.
 */
return new class extends Migration
{
    /**
     * Correspondances du catalogue MODÈLE, indicatives : la liste officielle et
     * les délais de notification sont à confirmer avec l'autorité sanitaire
     * (ANSS / direction préfectorale de la santé) avant usage réel.
     * [code examen => [maladie, notification immédiate ?]]
     */
    private const MDO_MODELE = [
        'GE' => ['Paludisme', false],
        'TDR_PALU' => ['Paludisme', false],
        'WIDAL' => ['Fièvre typhoïde', false],
        'VIH' => ['Infection à VIH', false],
        'SYPH' => ['Syphilis', false],
        'AGHBS' => ['Hépatite B', false],
        'HCV' => ['Hépatite C', false],
        'COPRO' => ['Diarrhée bactérienne (dont choléra, shigellose)', true],
    ];

    public function up(): void
    {
        Schema::table('labo_examens', function (Blueprint $table) {
            $table->string('mdo_maladie')->nullable()->after('laboratoire_sous_traitant');
            $table->boolean('mdo_immediate')->default(false)->after('mdo_maladie');
        });

        Schema::create('labo_remises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('demande_id')->constrained('labo_demandes')->restrictOnDelete();
            $table->foreignId('compte_rendu_id')->constrained('labo_comptes_rendus')->restrictOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('beneficiaire', 20);          // patient | representant | prescripteur
            $table->string('nom_beneficiaire');
            $table->string('lien_patient', 50)->nullable();   // parent, conjoint, tuteur…
            $table->string('piece_justificative', 50)->nullable(); // type de pièce présentée, jamais son numéro
            $table->boolean('avant_reglement')->default(false);
            $table->string('motif_derogation')->nullable();
            $table->foreignId('remis_par')->constrained('users')->restrictOnDelete();
            $table->timestamp('remis_le');
            $table->timestamps();

            $table->index(['etablissement_id', 'remis_le']);
        });

        Schema::create('labo_declarations_mdo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('demande_examen_id')->unique()->constrained('labo_demande_examens')->restrictOnDelete();
            $table->string('maladie');
            $table->boolean('immediate')->default(false);
            $table->string('statut', 20)->default('a_declarer'); // a_declarer | declaree | sans_objet
            $table->foreignId('declaree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('declaree_le')->nullable();
            $table->string('destinataire')->nullable();   // service ou personne notifiée
            $table->string('reference')->nullable();      // n° de fiche de notification
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'statut']);
        });

        foreach (self::MDO_MODELE as $code => [$maladie, $immediate]) {
            // Modèle plateforme, puis copies des établissements qui n'ont rien renseigné.
            $modeleId = DB::table('labo_examens')->whereNull('etablissement_id')->where('code', $code)->value('id');
            DB::table('labo_examens')->whereNull('etablissement_id')->where('code', $code)
                ->update(['mdo_maladie' => $maladie, 'mdo_immediate' => $immediate]);

            if ($modeleId) {
                DB::table('labo_examens')->where('modele_id', $modeleId)->whereNull('mdo_maladie')
                    ->update(['mdo_maladie' => $maladie, 'mdo_immediate' => $immediate]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('labo_declarations_mdo');
        Schema::dropIfExists('labo_remises');
        Schema::table('labo_examens', fn (Blueprint $table) => $table->dropColumn(['mdo_maladie', 'mdo_immediate']));
    }
};
