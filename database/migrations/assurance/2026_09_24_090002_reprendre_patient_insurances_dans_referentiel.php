<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reprise des contrats existants (patient_insurances) dans le référentiel.
 *
 * Pour chaque ligne :
 *   - contrat : un par (établissement, compagnie, numéro de police) — des
 *     patients partageant la même police sont regroupés, comme un contrat de groupe ;
 *   - formule : une par couple (taux, plafond) dans ce contrat ;
 *   - adhésion (le patient, carte = numéro de police) et bénéficiaire « adhérent » ;
 *   - la ligne d'origine est reliée à son bénéficiaire (beneficiaire_id) et devient
 *     sa projection. Taux, plafond, dates, statut et consommation sont conservés à
 *     l'identique : la facturation ne voit AUCUNE différence.
 *
 * Écriture directe en base (pas de modèles) : pas de projection recalculée pendant
 * la reprise. Idempotente : les lignes déjà reliées sont ignorées.
 */
return new class extends Migration
{
    private const STATUTS = ['active' => 'active', 'suspended' => 'suspendue', 'expired' => 'resiliee'];

    public function up(): void
    {
        $maintenant = now();

        DB::table('patient_insurances')->whereNull('beneficiaire_id')->orderBy('id')->chunkById(200, function ($lignes) use ($maintenant) {
            foreach ($lignes as $ligne) {
                DB::transaction(function () use ($ligne, $maintenant) {
                    $etablissementId = $ligne->etablissement_id
                        ?? DB::table('insurance_companies')->where('id', $ligne->insurance_company_id)->value('etablissement_id');

                    if (! $etablissementId) {
                        return; // ligne orpheline : laissée telle quelle, signalée par la commande de contrôle
                    }

                    $statut = self::STATUTS[$ligne->status] ?? 'active';
                    $police = trim((string) $ligne->policy_number) ?: 'SANS-POLICE-' . $ligne->id;

                    $contrat = DB::table('assurance_contrats')
                        ->where('etablissement_id', $etablissementId)
                        ->where('insurance_company_id', $ligne->insurance_company_id)
                        ->where('numero_police', $police)
                        ->first();

                    if (! $contrat) {
                        $contratId = DB::table('assurance_contrats')->insertGetId([
                            'etablissement_id' => $etablissementId,
                            'insurance_company_id' => $ligne->insurance_company_id,
                            'numero_police' => $police,
                            'date_debut' => $ligne->start_date,
                            'statut' => 'active',
                            'notes' => 'Repris de l\'ancien écran « Patients assurés ».',
                            'created_at' => $maintenant, 'updated_at' => $maintenant,
                        ]);
                    } else {
                        $contratId = $contrat->id;
                        if ($ligne->start_date < $contrat->date_debut) {
                            DB::table('assurance_contrats')->where('id', $contratId)->update(['date_debut' => $ligne->start_date]);
                        }
                    }

                    $taux = (float) $ligne->coverage_percentage;
                    $plafond = $ligne->annual_limit !== null ? (float) $ligne->annual_limit : null;
                    $libelle = 'Taux ' . rtrim(rtrim(number_format($taux, 2, '.', ''), '0'), '.') . ' %'
                        . ($plafond ? ' — plafond ' . number_format($plafond, 0, ',', ' ') . ' GNF' : '');

                    $formuleId = DB::table('assurance_formules')->where('contrat_id', $contratId)->where('libelle', $libelle)->value('id')
                        ?? DB::table('assurance_formules')->insertGetId([
                            'etablissement_id' => $etablissementId,
                            'contrat_id' => $contratId,
                            'libelle' => $libelle,
                            'taux_prise_en_charge' => $taux,
                            'plafond_annuel_beneficiaire' => $plafond,
                            'created_at' => $maintenant, 'updated_at' => $maintenant,
                        ]);

                    $adhesionId = DB::table('assurance_adhesions')->insertGetId([
                        'etablissement_id' => $etablissementId,
                        'formule_id' => $formuleId,
                        'patient_id' => $ligne->patient_id,
                        'numero_carte' => $ligne->policy_number,
                        'date_debut' => $ligne->start_date,
                        'date_fin' => $ligne->end_date,
                        'statut' => $statut,
                        'reprise_patient_insurance_id' => $ligne->id,
                        'notes' => $ligne->notes,
                        'created_at' => $maintenant, 'updated_at' => $maintenant,
                    ]);

                    $beneficiaireId = DB::table('assurance_beneficiaires')->insertGetId([
                        'etablissement_id' => $etablissementId,
                        'adhesion_id' => $adhesionId,
                        'patient_id' => $ligne->patient_id,
                        'lien' => 'adherent',
                        'numero_carte' => $ligne->policy_number,
                        'date_debut' => $ligne->start_date,
                        'date_fin' => $ligne->end_date,
                        'statut' => $statut,
                        'created_at' => $maintenant, 'updated_at' => $maintenant,
                    ]);

                    DB::table('patient_insurances')->where('id', $ligne->id)->update(['beneficiaire_id' => $beneficiaireId]);
                });
            }
        });
    }

    public function down(): void
    {
        $adhesions = DB::table('assurance_adhesions')->whereNotNull('reprise_patient_insurance_id')->pluck('id');
        $beneficiaires = DB::table('assurance_beneficiaires')->whereIn('adhesion_id', $adhesions)->pluck('id');

        DB::table('patient_insurances')->whereIn('beneficiaire_id', $beneficiaires)->update(['beneficiaire_id' => null]);
        DB::table('assurance_beneficiaires')->whereIn('id', $beneficiaires)->delete();
        DB::table('assurance_adhesions')->whereIn('id', $adhesions)->delete();
        // Contrats et formules repris laissés en place : ils ont pu recevoir d'autres adhésions depuis.
    }
};
