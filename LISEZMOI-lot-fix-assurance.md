# Correctifs (paillasse, créances) + écrans d'assurance redessinés

À appliquer après le lot comptable. Aucune nouvelle migration.

    unzip -o hali-lot-fix-assurance.zip -d .
    php artisan optimize:clear

## IMPORTANT — écran des créances en erreur
Votre CreanceController est l'ANCIENNE version : le zip « hali-creances-rapides » n'a pas
été appliqué, et sa migration (colonne reste_du_calcule) n'a jamais tourné. Appliquez-le :

    unzip -o hali-creances-rapides.zip -d .
    unzip -o hali-lot-fix-assurance.zip -d .   # à réappliquer ensuite (fichiers plus récents)
    php artisan migrate
    php artisan assurance:recalculer-creances

Ce lot rend de toute façon l'écran tolérant : sans la colonne, il ne plante plus.

## Corrigé
- Laboratoire › Paillasse : « Undefined variable $groupe » et directives affichées en clair.
  Un @php(...) court avant un bloc @php … @endphp faisait avaler 50 lignes de HTML par Blade.
  Aucune autre vue du projet n'a ce défaut (vérifié sur les 207).
- Organismes payeurs : la suppression effaçait un organisme même avec des patients, des
  factures ou des réclamations → il est désormais désactivé à la place. Données de modification
  passées en JSON (une virgule dans l'adresse décalait tous les champs). Validation complète.
- Patients assurés : la liste ne charge plus tous les patients de la clinique (recherche au fil
  de la frappe) ; un patient d'une autre clinique donne un message clair au lieu d'une page 404.

## Redessiné
- Organismes payeurs : une carte par organisme (patients couverts, taux, reste dû, contacts,
  fin de convention), recherche, une seule fenêtre pour l'ajout et la modification.
- Patients assurés : type de couverture (individuelle, contrat d'entreprise, ayant droit),
  état réel (en cours, expire bientôt, terminée), plafond avec alerte quand il est atteint,
  filtres par organisme et état ; modification seulement pour les couvertures individuelles
  (les autres renvoient vers leur contrat).

Commit :
    git add -A && git commit -m "fix(labo,assurance): paillasse réparée, créances tolérantes ; organismes et patients assurés redessinés"
