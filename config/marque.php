<?php

/*
| Marque de la plateforme.
|
| Un seul endroit pour le nom commercial : écrans, documents, e-mails et SMS
| le lisent ici. Le nom de CHAQUE établissement (la clinique, le laboratoire)
| reste dans la table etablissements — ce fichier ne concerne que l'éditeur.
|
| Ne jamais remplacer le nom d'un établissement par celui-ci : « Aprosafe »
| désigne la clinique pilote, pas la plateforme.
*/

return [
    'nom' => env('MARQUE_NOM', 'Hali'),

    // Repris dans les titres de page et le bas des documents.
    'signature' => env('MARQUE_SIGNATURE', 'Hali — plateforme de gestion clinique'),

    // Expéditeur des SMS : 11 caractères maximum, validé chez l'opérateur.
    'sms_expediteur' => env('MARQUE_SMS_EXPEDITEUR', env('NIMBA_SMS_SENDER', 'HALI')),

    'site' => env('MARQUE_SITE', 'https://hali.gn'),
    'support' => env('MARQUE_SUPPORT', 'support@hali.gn'),

    /*
    | Préfixe des commandes artisan. Les anciennes commandes « aprosafe:… »
    | restent acceptées en alias : elles figurent dans les crons déjà en place
    | chez les clients.
    */
    'prefixe_commandes' => 'hali',
];
