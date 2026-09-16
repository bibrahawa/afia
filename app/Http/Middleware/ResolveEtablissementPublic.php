<?php

namespace App\Http\Middleware;

use App\Models\Etablissement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CORRECTIF DÉFINITIF — la version précédente supposait que Laravel avait
 * déjà résolu `{etablissement:slug}` en modèle avant que ce middleware ne
 * s'exécute. C'est vrai UNIQUEMENT si le contrôleur appelé déclare lui-même
 * `Etablissement $etablissement` dans sa signature — sinon le binding
 * implicite ne se déclenche jamais et `$request->route('etablissement')`
 * reste la chaîne brute du slug. `checkPatient`, `envoyerCodeRdv` et
 * `verifierCodeRdv` ne déclaraient pas ce type, d'où le 404 alors que
 * `creneaux`/`getMotifs` (qui le déclarent) fonctionnaient très bien sur
 * exactement le même préfixe de route.
 *
 * Ce middleware ne dépend plus de ce détail : il résout l'établissement
 * lui-même si ce n'est pas déjà fait, puis renormalise le paramètre de
 * route pour que les contrôleurs qui déclarent le type continuent de le
 * recevoir directement. Aucun futur endpoint ne pourra retomber dans ce
 * piège, qu'il déclare le type ou non.
 */
class ResolveEtablissementPublic
{
    public function handle(Request $request, Closure $next)
    {
        $parametre = $request->route('etablissement');

        $etablissement = $parametre instanceof Etablissement
            ? $parametre
            : Etablissement::where('slug', $parametre)->first();

        if (! $etablissement || ! in_array($etablissement->statut, ['essai', 'actif'])) {
            throw new NotFoundHttpException();
        }

        app()->instance('etablissement.public', $etablissement);

        // Renormalise : un contrôleur qui type-hint Etablissement $etablissement
        // le reçoit directement, résolu, sans avoir à s'en soucier.
        $request->route()->setParameter('etablissement', $etablissement);

        return $next($request);
    }
}
