# Lot G — Documents imprimés

À appliquer après le lot F. Aucune migration.
ConsultationController est la version du lot S3 ; HospitalisationController celle du lot A.

    unzip -o hali-lot-G.zip -d .
    php artisan view:clear

Documents : facture, reçu, ordonnance, demande d'examens (A5 et ticket 80 mm), facture
d'hospitalisation (A4). Socle commun : resources/views/documents/.

Corrigé :
  - factures et reçus PDF : mise en page en flexbox, ignorée par DomPDF (en-têtes et totaux cassés) ;
  - tickets 80 mm coupés en deux : hauteur désormais mesurée pendant le rendu (exacte) ;
  - reçu : paiements annulés comptés ; « Montant en lettres : À compléter » ;
  - facture / reçu : erreur 500 pour une consultation sans facture ;
  - nom du médecin absent de tous les documents (ordonnances comprises) ;
  - demande d'examens : téléphone vide (patient->phone) ;
  - montants au format anglais (100,000) ;
  - facture d'hospitalisation : ni nom ni logo de clinique, « Patiente » pour tous,
    dates brutes, reste à payer toujours à 0 (total_payer = total FACTURÉ).

Vues mortes (aucune route) à supprimer après vérification :
    rm -r resources/views/consultations/facture

À tester : imprimer une facture 80 mm avec beaucoup d'actes (une seule bande, sans blanc
à la fin) ; ouvrir la facture d'une consultation sans facture (message, pas d'erreur).

Commit :
    git add -A && git commit -m "feat(documents): factures, reçus, ordonnances et facture d'hospitalisation refaits pour DomPDF, tickets 80 mm à hauteur exacte"
