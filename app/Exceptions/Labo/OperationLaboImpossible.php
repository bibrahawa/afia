<?php

namespace App\Exceptions\Labo;

/**
 * Règle métier violée (transition de statut interdite, valeur critique non
 * signalée...). Le message est destiné à l'utilisateur : toujours en
 * français clair, jamais un détail technique.
 */
class OperationLaboImpossible extends \DomainException
{
}
