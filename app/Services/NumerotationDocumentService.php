<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Numérotation atomique des pièces (transactions, paiements…) par
 * établissement.
 *
 * Remplace le calcul « dernier numéro + 1 », qui produisait des doublons
 * dès que deux caissiers encaissaient à la même seconde, et qui devenait
 * faux en multi-établissements (le « dernier » d'une clinique n'est pas
 * celui d'une autre).
 *
 * insertOrIgnore crée la ligne de compteur sans course critique, puis
 * lockForUpdate sérialise les incréments concurrents. Si la transaction
 * appelante échoue, l'incrément est annulé avec elle : pas de trou.
 */
class NumerotationDocumentService
{
    public function suivant(int $etablissementId, string $serie): int
    {
        return DB::transaction(function () use ($etablissementId, $serie) {
            DB::table('compteurs_documents')->insertOrIgnore([
                'etablissement_id' => $etablissementId,
                'serie' => $serie,
                'valeur' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $compteur = DB::table('compteurs_documents')
                ->where('etablissement_id', $etablissementId)
                ->where('serie', $serie)
                ->lockForUpdate()
                ->first();

            $valeur = (int) $compteur->valeur + 1;

            DB::table('compteurs_documents')->where('id', $compteur->id)
                ->update(['valeur' => $valeur, 'updated_at' => now()]);

            return $valeur;
        });
    }

    /** T-202600001 / P-202600001 : format historique conservé. */
    public function numero(int $etablissementId, string $prefixe, string $serie): string
    {
        $annee = now()->year;
        $sequence = $this->suivant($etablissementId, $serie . '-' . $annee);

        return sprintf('%s-%d%05d', $prefixe, $annee, $sequence);
    }
}
