<?php

namespace App\Support\Labo;

/**
 * Code-barres « Interleaved 2 of 5 » (ITF) en SVG, sans dépendance ni CDN.
 *
 * Choix d'ITF : nos codes sont numériques et de longueur paire (10 chiffres),
 * ITF est reconnu par toutes les douchettes du marché, donne un code plus
 * court que le Code 128 à largeur égale (utile sur une étiquette de tube) et
 * s'implémente en quelques lignes vérifiables — donc rien à charger depuis
 * internet sur une connexion lente.
 */
class CodeBarreItf
{
    /** N = étroit, W = large, pour chaque chiffre. */
    private const MOTIFS = [
        '0' => 'NNWWN', '1' => 'WNNNW', '2' => 'NWNNW', '3' => 'WWNNN', '4' => 'NNWNW',
        '5' => 'WNWNN', '6' => 'NWWNN', '7' => 'NNNWW', '8' => 'WNNWN', '9' => 'NWNWN',
    ];

    /**
     * Suite de modules alternant barre / espace, en commençant par une barre.
     *
     * @return int[] largeurs en modules
     */
    public static function largeurs(string $code, int $large = 3): array
    {
        if (! preg_match('/^\d+$/', $code)) {
            throw new \InvalidArgumentException('ITF : chiffres uniquement.');
        }
        if (strlen($code) % 2 !== 0) {
            $code = '0' . $code; // ITF exige un nombre pair de chiffres
        }

        $w = fn (string $c) => $c === 'W' ? $large : 1;
        $largeurs = [1, 1, 1, 1]; // start : barre, espace, barre, espace étroits

        foreach (str_split($code, 2) as $paire) {
            $barres = self::MOTIFS[$paire[0]];   // 1er chiffre → barres
            $espaces = self::MOTIFS[$paire[1]];  // 2e chiffre → espaces
            for ($i = 0; $i < 5; $i++) {
                $largeurs[] = $w($barres[$i]);
                $largeurs[] = $w($espaces[$i]);
            }
        }

        return array_merge($largeurs, [$large, 1, 1]); // stop : barre large, espace, barre
    }

    public static function svg(string $code, int $hauteur = 40, float $module = 1.5): string
    {
        $x = 10 * $module; // zone de silence
        $rects = '';
        foreach (self::largeurs($code) as $i => $l) {
            if ($i % 2 === 0) {
                $rects .= sprintf('<rect x="%.2F" y="0" width="%.2F" height="%d"/>', $x, $l * $module, $hauteur);
            }
            $x += $l * $module;
        }
        $total = $x + 10 * $module;

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%.2F" height="%d" viewBox="0 0 %.2F %d" role="img" aria-label="%s"><g fill="#000">%s</g></svg>',
            $total, $hauteur, $total, $hauteur, htmlspecialchars($code, ENT_QUOTES), $rects
        );
    }
}
