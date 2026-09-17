<?php

namespace App\Exceptions\Assurance;

/**
 * Règle du référentiel assurance violée (enfant au-delà de l'âge limite,
 * adhérent déjà inscrit, emploi d'une autre entreprise…). Message destiné à
 * l'utilisateur, en français clair. Rendu par AssuranceServiceProvider.
 */
class OperationAssuranceImpossible extends \DomainException
{
}
