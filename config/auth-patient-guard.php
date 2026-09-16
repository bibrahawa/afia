<?php

/**
 * À FUSIONNER dans config/auth.php existant.
 *
 * ComptePatient (portail) et User (personnel) sont deux populations
 * d'authentification distinctes — un patient ne doit jamais pouvoir
 * s'authentifier sur le guard staff, ni inversement. Sans ce guard dédié,
 * `Auth::user()` dans le contexte patient resterait ambigu.
 */

// Dans 'guards' :
// 'patient' => [
//     'driver' => 'session',
//     'provider' => 'comptes_patients',
// ],

// // Dans 'providers' :
// 'comptes_patients' => [
//     'driver' => 'eloquent',
//     'model' => App\Models\ComptePatient::class,
// ],

/**
 * Usage dans les routes du portail :
 *   Route::middleware('auth:patient')->group(function () { ... });
 *
 * Dans les contrôleurs du portail :
 *   Auth::guard('patient')->user() // renvoie le ComptePatient connecté
 *
 * NON couvert par cette livraison : l'écran de connexion par OTP
 * lui-même (saisie du téléphone, envoi du code, vérification). Le
 * tableau de bord ci-dessous suppose un ComptePatient déjà authentifié —
 * c'est la pièce logique suivante, pas encore construite.
 */
