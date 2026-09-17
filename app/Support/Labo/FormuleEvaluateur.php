<?php

namespace App\Support\Labo;

/**
 * Évalue les paramètres calculés (LDL de Friedewald, rapport CT/HDL...).
 *
 * JAMAIS eval() : une formule est saisie par un administrateur de labo,
 * donc potentiellement par n'importe quel client de la plateforme. On
 * parse nous-mêmes une grammaire volontairement minuscule :
 *   nombres (point décimal), codes de paramètres ({CT} ou CT),
 *   + - * / ^, parenthèses, moins unaire, fonctions min(a;b) max(a;b)
 *   abs(a) round(a;n) — séparateur d'arguments « ; » ou « , ».
 *
 * Retourne null (jamais 0) dès qu'une variable manque ou qu'une division
 * par zéro survient : un résultat calculé absent doit rester visiblement
 * absent, pas devenir une fausse valeur normale sur un compte rendu.
 */
class FormuleEvaluateur
{
    private const PRIORITE = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '^' => 3, 'neg' => 2.5];
    private const FONCTIONS = ['min' => 2, 'max' => 2, 'abs' => 1, 'round' => 2];

    /** Codes de paramètres utilisés par une formule (pour l'ordre de calcul et la validation). */
    public function variables(string $formule): array
    {
        $codes = [];
        foreach ($this->tokeniser($formule) as [$type, $valeur]) {
            if ($type === 'var') {
                $codes[] = $valeur;
            }
        }

        return array_values(array_unique($codes));
    }

    /** Lève InvalidArgumentException si la formule est syntaxiquement invalide. */
    public function valider(string $formule): void
    {
        $this->versRpn($this->tokeniser($formule));
    }

    /**
     * @param  array<string, float|int|null>  $valeurs  code => valeur
     */
    public function evaluer(string $formule, array $valeurs): ?float
    {
        $valeurs = array_change_key_case($valeurs, CASE_UPPER);
        $pile = [];

        foreach ($this->versRpn($this->tokeniser($formule)) as [$type, $valeur]) {
            switch ($type) {
                case 'num':
                    $pile[] = (float) $valeur;
                    break;

                case 'var':
                    if (! array_key_exists($valeur, $valeurs) || $valeurs[$valeur] === null || $valeurs[$valeur] === '') {
                        return null;
                    }
                    $pile[] = (float) $valeurs[$valeur];
                    break;

                case 'op':
                    if ($valeur === 'neg') {
                        $pile[] = -array_pop($pile);
                        break;
                    }
                    $b = array_pop($pile);
                    $a = array_pop($pile);
                    if ($a === null || $b === null) {
                        throw new \InvalidArgumentException('Formule invalide.');
                    }
                    if ($valeur === '/' && abs($b) < 1e-12) {
                        return null;
                    }
                    $pile[] = match ($valeur) {
                        '+' => $a + $b,
                        '-' => $a - $b,
                        '*' => $a * $b,
                        '/' => $a / $b,
                        '^' => $a ** $b,
                    };
                    break;

                case 'fn':
                    $arite = self::FONCTIONS[$valeur];
                    $args = array_splice($pile, -$arite);
                    if (count($args) !== $arite) {
                        throw new \InvalidArgumentException("Nombre d'arguments invalide pour {$valeur}().");
                    }
                    $pile[] = match ($valeur) {
                        'min' => min($args[0], $args[1]),
                        'max' => max($args[0], $args[1]),
                        'abs' => abs($args[0]),
                        'round' => round($args[0], (int) $args[1]),
                    };
                    break;
            }
        }

        if (count($pile) !== 1) {
            throw new \InvalidArgumentException('Formule invalide.');
        }

        $resultat = $pile[0];

        return is_finite($resultat) ? $resultat : null;
    }

    private function tokeniser(string $formule): array
    {
        $tokens = [];
        $longueur = strlen($formule);
        $i = 0;

        while ($i < $longueur) {
            $c = $formule[$i];

            if (ctype_space($c)) {
                $i++;
                continue;
            }

            if (ctype_digit($c) || $c === '.') {
                $nombre = '';
                while ($i < $longueur && (ctype_digit($formule[$i]) || $formule[$i] === '.')) {
                    $nombre .= $formule[$i++];
                }
                if (! is_numeric($nombre)) {
                    throw new \InvalidArgumentException("Nombre invalide « {$nombre} ».");
                }
                $tokens[] = ['num', $nombre];
                continue;
            }

            if ($c === '{') {
                $fin = strpos($formule, '}', $i);
                if ($fin === false) {
                    throw new \InvalidArgumentException('Accolade non fermée.');
                }
                $tokens[] = ['var', strtoupper(trim(substr($formule, $i + 1, $fin - $i - 1)))];
                $i = $fin + 1;
                continue;
            }

            if (ctype_alpha($c) || $c === '_') {
                $mot = '';
                while ($i < $longueur && (ctype_alnum($formule[$i]) || $formule[$i] === '_')) {
                    $mot .= $formule[$i++];
                }
                $minuscule = strtolower($mot);
                $tokens[] = isset(self::FONCTIONS[$minuscule]) ? ['fn', $minuscule] : ['var', strtoupper($mot)];
                continue;
            }

            if (str_contains('+-*/^', $c)) {
                $tokens[] = ['op', $c];
            } elseif ($c === '(') {
                $tokens[] = ['(', $c];
            } elseif ($c === ')') {
                $tokens[] = [')', $c];
            } elseif ($c === ';' || $c === ',') {
                $tokens[] = [',', $c];
            } else {
                throw new \InvalidArgumentException("Caractère non autorisé « {$c} ».");
            }
            $i++;
        }

        return $tokens;
    }

    /** Algorithme de Dijkstra (shunting-yard). */
    private function versRpn(array $tokens): array
    {
        $sortie = [];
        $operateurs = [];
        $precedent = null;

        foreach ($tokens as $token) {
            [$type, $valeur] = $token;

            if ($type === 'num' || $type === 'var') {
                $sortie[] = $token;
            } elseif ($type === 'fn') {
                $operateurs[] = $token;
            } elseif ($type === ',') {
                while ($operateurs && end($operateurs)[0] !== '(') {
                    $sortie[] = array_pop($operateurs);
                }
                if (! $operateurs) {
                    throw new \InvalidArgumentException('Séparateur hors fonction.');
                }
            } elseif ($type === 'op') {
                $unaire = $valeur === '-' && ($precedent === null || in_array($precedent[0], ['op', '(', ','], true));
                if ($unaire) {
                    $operateurs[] = ['op', 'neg'];
                } elseif ($valeur === '+' && ($precedent === null || in_array($precedent[0], ['op', '(', ','], true))) {
                    // plus unaire : ignoré
                } else {
                    while ($operateurs) {
                        $haut = end($operateurs);
                        if ($haut[0] !== 'op') {
                            break;
                        }
                        $pHaut = self::PRIORITE[$haut[1]];
                        $pCourant = self::PRIORITE[$valeur];
                        $associatifDroite = $valeur === '^';
                        if ($pHaut > $pCourant || ($pHaut === $pCourant && ! $associatifDroite)) {
                            $sortie[] = array_pop($operateurs);
                        } else {
                            break;
                        }
                    }
                    $operateurs[] = $token;
                }
            } elseif ($type === '(') {
                $operateurs[] = $token;
            } elseif ($type === ')') {
                while ($operateurs && end($operateurs)[0] !== '(') {
                    $sortie[] = array_pop($operateurs);
                }
                if (! $operateurs) {
                    throw new \InvalidArgumentException('Parenthèse fermante en trop.');
                }
                array_pop($operateurs);
                if ($operateurs && end($operateurs)[0] === 'fn') {
                    $sortie[] = array_pop($operateurs);
                }
            }

            $precedent = $token;
        }

        while ($operateurs) {
            $op = array_pop($operateurs);
            if ($op[0] === '(') {
                throw new \InvalidArgumentException('Parenthèse ouvrante non fermée.');
            }
            $sortie[] = $op;
        }

        if (! $sortie) {
            throw new \InvalidArgumentException('Formule vide.');
        }

        return $sortie;
    }
}
