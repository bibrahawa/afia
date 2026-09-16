<?php

namespace App\Policies;

use App\Enums\PorteeAcces;
use App\Models\Patient;
use App\Models\User;
use App\Services\AccesDossierSanteService;

/**
 * Usage dans un contrôleur :
 *
 *   Gate::authorize('voirDossierSante', [$patient, PorteeAcces::CarnetComplet]);
 *
 * ou via le trait AuthorizesRequests :
 *
 *   $this->authorize('voirDossierSante', [$patient, PorteeAcces::Laboratoire]);
 *
 * Enregistrement dans AuthServiceProvider ou via Gate::define selon ta
 * préférence — l'important est qu'aucun contrôleur n'affiche jamais de
 * données de santé cross-établissement sans passer par ce point.
 */
class DossierSantePolicy
{
    public function __construct(protected AccesDossierSanteService $acces)
    {
    }

    public function voirDossierSante(User $utilisateur, Patient $patient, PorteeAcces $portee): bool
    {
        // Les données que l'établissement de l'utilisateur a lui-même
        // produites restent gérées par le scope normal des modèles
        // (BelongsToEtablissement) — cette policy ne couvre que l'accès
        // à l'historique produit par d'autres établissements.
        return $this->acces->peutVoir($utilisateur, $patient, $portee);
    }
}
