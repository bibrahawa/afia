<?php

/*
|--------------------------------------------------------------------------
| API — FERMÉE (lots R et R2, revue de sécurité)
|--------------------------------------------------------------------------
| Aucune application mobile ni aucun écran actif n'utilise plus cette API
| (confirmé par Ibrahim). Toutes ses routes étaient accessibles SANS connexion :
|
|  Lecture de données, toutes cliniques confondues (fermées au lot R) :
|    GET  patient/{patient}/insurances        assurances d'un patient
|    GET  transactions/{transaction}/actes    actes facturés (soins reçus)
|    GET  patient/{transactionId}/actes       idem
|    GET  balance/{insurance}                 montants dus par un assureur
|    GET  insurance-companies/active          organismes de toutes les cliniques
|    POST insurance/calculate-coverage
|
|  Création et comptes (fermées au lot R2) :
|    POST appointments, GET appointments/available-dates
|    POST check-patient, patient/find-or-create
|    POST login, register, logout, check-account
|
| La prise de rendez-vous publique, le portail patient et la connexion du
| personnel passent par routes/web.php (session, CSRF, cloisonnement par
| établissement). Pour rouvrir une API : authentification par jeton, limites
| de débit et cloisonnement par établissement sur chaque route.
*/
