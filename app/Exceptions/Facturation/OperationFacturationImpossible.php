<?php

namespace App\Exceptions\Facturation;

/**
 * Règle de facturation violée (facture déjà encaissée, remise trop élevée,
 * paiement déjà annulé…). Le message est destiné à l'utilisateur : français
 * clair, jamais de détail technique. Rendu par FacturationServiceProvider
 * (retour à la page précédente avec le message).
 *
 * Hérite de DomainException : les try/catch existants la traitent déjà.
 */
class OperationFacturationImpossible extends \DomainException
{
}
