<?php

namespace App\Support;

/**
 * Montant en toutes lettres, en français (orthographe traditionnelle, avec traits d'union
 * entre dizaines et unités) : « cent cinquante mille francs guinéens ».
 *
 * Règles : « et » dans 21, 31… 71 ; « quatre-vingts » et « cents » prennent un « s »
 * seulement en fin de nombre ; « mille » est invariable ; « million(s) », « milliard(s) ».
 */
class MontantEnLettres
{
    private const UNITES = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix',
        'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
    private const DIZAINES = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];

    /** « … francs guinéens » (montant arrondi à l'unité, le franc guinéen n'a pas de centimes). */
    public static function gnf(float|int|string|null $montant): string
    {
        $n = (int) round(abs((float) $montant));

        return self::nombre($n) . ' ' . ($n > 1 ? 'francs guinéens' : 'franc guinéen');
    }

    public static function nombre(int $n): string
    {
        if ($n === 0) {
            return 'zéro';
        }

        $parties = [];
        foreach ([[1_000_000_000, 'milliard'], [1_000_000, 'million']] as [$valeur, $mot]) {
            if ($n >= $valeur) {
                $q = intdiv($n, $valeur);
                // « million » et « milliard » sont des noms : « quatre-vingts millions », « deux cents millions »
                // (contrairement à « mille », adjectif : « quatre-vingt mille », « deux cent mille »).
                $parties[] = self::moinsDeMille($q, true) . ' ' . $mot . ($q > 1 ? 's' : '');
                $n %= $valeur;
            }
        }
        if ($n >= 1000) {
            $q = intdiv($n, 1000);
            // « mille » invariable, jamais « un mille » ; « cent » non accordé devant « mille ».
            $parties[] = $q === 1 ? 'mille' : self::moinsDeMille($q, false) . ' mille';
            $n %= 1000;
        }
        if ($n > 0) {
            $parties[] = self::moinsDeMille($n, true);
        }

        return implode(' ', $parties);
    }

    /** @param bool $finDeNombre accord de « cents » / « quatre-vingts » seulement en fin de nombre */
    private static function moinsDeMille(int $n, bool $finDeNombre): string
    {
        $centaines = intdiv($n, 100);
        $reste = $n % 100;
        $mots = [];

        if ($centaines > 0) {
            $cent = $centaines === 1 ? 'cent' : self::UNITES[$centaines] . ' cent';
            if ($centaines > 1 && $reste === 0 && $finDeNombre) {
                $cent .= 's';
            }
            $mots[] = $cent;
        }
        if ($reste > 0) {
            $mots[] = self::moinsDeCent($reste, $finDeNombre);
        }

        return implode(' ', $mots);
    }

    private static function moinsDeCent(int $n, bool $finDeNombre): string
    {
        if ($n < 20) {
            return self::UNITES[$n];
        }

        $d = intdiv($n, 10);
        $u = $n % 10;

        if ($d === 7 || $d === 9) {            // 70-79 : soixante-dix… ; 90-99 : quatre-vingt-dix…
            $base = $d === 7 ? 'soixante' : 'quatre-vingt';
            $suite = self::UNITES[10 + $u];
            return $base . ($d === 7 && $u === 1 ? ' et ' : '-') . $suite;
        }
        if ($d === 8) {
            return $u === 0 ? 'quatre-vingt' . ($finDeNombre ? 's' : '') : 'quatre-vingt-' . self::UNITES[$u];
        }

        $dizaine = self::DIZAINES[$d];
        if ($u === 0) {
            return $dizaine;
        }

        return $dizaine . ($u === 1 ? ' et un' : '-' . self::UNITES[$u]);
    }
}
