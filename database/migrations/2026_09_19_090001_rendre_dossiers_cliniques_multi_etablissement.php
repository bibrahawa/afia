<?php

use App\Support\Migrations\AjoutEtablissement as A;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dossiers cliniques : consultations, hospitalisations, chambres,
 * antécédents, fichiers patients.
 *
 * Le patient reste GLOBAL (identité unique sur la plateforme). Ce qui est
 * cloisonné, c'est ce que chaque établissement produit à son sujet. Le
 * partage d'un établissement à l'autre passera exclusivement par le
 * consentement (DemandeAcces / AccesDossierSanteService).
 */
return new class extends Migration
{
    private const TABLES = ['consultations', 'hospitalisations', 'chambres', 'antecedents', 'fichier_patients'];

    public function up(): void
    {
        $pilote = A::etablissementPilote(self::TABLES);

        foreach (self::TABLES as $table) {
            A::ajouter($table, $pilote, restreindre: true);
        }

        // Une consultation créée dans un département déjà rattaché hérite de son établissement
        // (plus fiable que le pilote si un 2e établissement a déjà des départements).
        DB::statement('UPDATE consultations c JOIN departments d ON d.id = c.department_id
                       SET c.etablissement_id = d.etablissement_id
                       WHERE d.etablissement_id IS NOT NULL');

        DB::statement('UPDATE hospitalisations h JOIN chambres ch ON ch.id = h.chambre_id
                       SET h.etablissement_id = ch.etablissement_id
                       WHERE ch.etablissement_id IS NOT NULL');

        // Deux cliniques peuvent chacune avoir une « Chambre 101 ».
        A::uniqueParEtablissement('chambres', 'numero');
    }

    public function down(): void
    {
        A::uniqueGlobal('chambres', 'numero');
        foreach (array_reverse(self::TABLES) as $table) {
            A::retirer($table);
        }
    }
};
