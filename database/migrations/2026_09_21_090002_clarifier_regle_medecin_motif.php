<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nouvelle règle (voir Employee::peutPratiquerMotif) : un médecin du
 * département pratique un motif SAUF s'il existe une ligne `medecin_motif`
 * avec actif = false pour lui. La surcharge de durée ne restreint plus rien.
 *
 * Ancienne règle : dès qu'UN médecin avait une association active sur un
 * motif, tous les autres médecins sans ligne étaient exclus (liste blanche
 * implicite). Pour que la bascule ne change AUCUN comportement en
 * production, on matérialise ces exclusions implicites en lignes
 * explicites actif = false. La clinique peut ensuite les rouvrir à l'écran.
 */
return new class extends Migration
{
    public function up(): void
    {
        $maintenant = now();

        $motifsEnListeBlanche = DB::table('medecin_motif')
            ->where('actif', true)
            ->distinct()
            ->pluck('motif_rdv_id');

        foreach ($motifsEnListeBlanche as $motifId) {
            $motif = DB::table('motifs_rdv')->where('id', $motifId)->first();
            if (! $motif) {
                continue;
            }

            $dejaAssocies = DB::table('medecin_motif')->where('motif_rdv_id', $motifId)->pluck('employee_id');

            $exclus = DB::table('employees')
                ->where('department_id', $motif->department_id)
                ->whereNotIn('id', $dejaAssocies)
                ->pluck('id');

            DB::table('medecin_motif')->insert($exclus->map(fn ($employeeId) => [
                'employee_id' => $employeeId,
                'motif_rdv_id' => $motifId,
                'duree_minutes' => null,
                'actif' => false,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ])->all());
        }
    }

    public function down(): void
    {
        // Irréversible sans perte d'information : les lignes insérées sont
        // indiscernables d'exclusions saisies à l'écran. Rien à défaire —
        // l'ancienne règle donne le même résultat avec ces lignes présentes.
    }
};
