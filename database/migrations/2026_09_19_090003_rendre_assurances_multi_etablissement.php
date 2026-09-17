<?php

use App\Support\Migrations\AjoutEtablissement as A;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Assurances : chaque établissement négocie SA convention avec un assureur
 * (taux, plafonds, tarifs d'actes). « NSIA » chez Aprosafe et « NSIA » chez
 * une autre clinique sont donc deux fiches distinctes.
 */
return new class extends Migration
{
    private const TABLES = [
        'insurance_companies', 'insurance_coverages', 'patient_insurances',
        'insurance_claims', 'insurance_settlements', 'insurance_settlement_items',
    ];

    public function up(): void
    {
        $pilote = A::etablissementPilote(self::TABLES);

        foreach (self::TABLES as $table) {
            A::ajouter($table, $pilote, restreindre: true);
        }

        // Les réclamations suivent l'établissement de leur facture (déjà migrée).
        DB::statement('UPDATE insurance_claims c JOIN invoices i ON i.id = c.invoice_id
                       SET c.etablissement_id = i.etablissement_id
                       WHERE i.etablissement_id IS NOT NULL');

        A::uniqueParEtablissement('insurance_companies', 'code');
        A::uniqueParEtablissement('insurance_claims', 'claim_number');
        A::uniqueParEtablissement('insurance_settlements', 'settlement_no');

        // Reprise de la numérotation SET-AAAANNNNN là où l'ancien calcul s'était arrêté.
        $max = [];
        foreach (DB::table('insurance_settlements')->whereNotNull('settlement_no')->get(['etablissement_id', 'settlement_no']) as $l) {
            if ($l->etablissement_id && preg_match('/^SET-(\d{4})(\d{5,})$/', $l->settlement_no, $m)) {
                $cle = $l->etablissement_id . '|reglement-assurance-' . $m[1];
                $max[$cle] = max($max[$cle] ?? 0, (int) $m[2]);
            }
        }
        foreach ($max as $cle => $valeur) {
            [$etab, $serie] = explode('|', $cle);
            DB::table('compteurs_documents')->updateOrInsert(
                ['etablissement_id' => $etab, 'serie' => $serie],
                ['valeur' => $valeur, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        A::uniqueGlobal('insurance_settlements', 'settlement_no');
        A::uniqueGlobal('insurance_claims', 'claim_number');
        A::uniqueGlobal('insurance_companies', 'code');

        foreach (array_reverse(self::TABLES) as $table) {
            A::retirer($table);
        }
    }
};
