<?php

namespace App\Services\Labo;

use Illuminate\Support\Facades\DB;

/**
 * Numéros séquentiels PAR établissement, sans doublon même avec plusieurs
 * guichets qui enregistrent à la même seconde.
 *
 * Le modèle Transaction existant calcule « dernier numéro + 1 » sans
 * verrou : deux enregistrements simultanés obtiennent le même numéro. Ici,
 * un compteur dédié verrouillé ligne par ligne (SELECT ... FOR UPDATE).
 */
class NumerotationService
{
    public function suivant(int $etablissementId, string $cle): int
    {
        return DB::transaction(function () use ($etablissementId, $cle) {
            // insertOrIgnore absorbe la course à la TOUTE première création
            // du compteur ; le verrou ci-dessous sérialise ensuite tout le monde.
            DB::table('labo_compteurs')->insertOrIgnore([
                'etablissement_id' => $etablissementId,
                'cle' => $cle,
                'valeur' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $ligne = DB::table('labo_compteurs')
                ->where('etablissement_id', $etablissementId)
                ->where('cle', $cle)
                ->lockForUpdate()
                ->first();

            $valeur = (int) $ligne->valeur + 1;

            DB::table('labo_compteurs')->where('id', $ligne->id)->update([
                'valeur' => $valeur,
                'updated_at' => now(),
            ]);

            return $valeur;
        });
    }

    /** LAB-2026-000123 */
    public function numeroDemande(int $etablissementId): array
    {
        $annee = now()->year;
        $sequence = $this->suivant($etablissementId, 'demande-' . $annee);

        return [
            'numero' => sprintf('LAB-%d-%06d', $annee, $sequence),
            'sequence' => $sequence,
        ];
    }

    /**
     * Code-barres numérique de 10 chiffres (Interleaved 2 of 5, lisible par
     * n'importe quelle douchette) : AA + séquence demande (6) + n° tube (2).
     */
    public static function codeBarres(string $numeroDemande, int $indexTube): string
    {
        if (! preg_match('/^LAB-(\d{4})-(\d{6})$/', $numeroDemande, $m)) {
            throw new \InvalidArgumentException("Numéro de demande inattendu : {$numeroDemande}");
        }

        return substr($m[1], 2, 2) . $m[2] . str_pad((string) $indexTube, 2, '0', STR_PAD_LEFT);
    }
}
