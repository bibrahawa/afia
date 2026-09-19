# Lot E2 — Comptes du portail (faille corrigée), établissements, licence

À appliquer APRÈS le lot E1 (routes/web.php en est la dernière version). Aucune migration.

    unzip -o hali-lot-E2.zip -d .
    php artisan optimize:clear

## Faille de confidentialité corrigée (comptes du portail patient)
Avant : tout soignant voyait les comptes de TOUTE la plateforme, pouvait rattacher le dossier
de n'importe quel patient (autre clinique comprise) à son propre téléphone, puis lire ce dossier
dans le portail. Désormais :
  - seuls les comptes liés aux patients de la clinique sont visibles et modifiables ;
  - seuls les patients de la clinique peuvent être rattachés ;
  - « J'ai vérifié l'identité » obligatoire pour rattacher un dossier ou changer un téléphone ;
  - modifications réservées à la permission patient.edit (et plus patient.view) ;
  - chaque action inscrite au journal d'activité (activity_logs, action compte_patient.*).

À vérifier en base après installation (rattachements suspects faits avant la correction) :
    SELECT action, description, created_at FROM activity_logs WHERE action LIKE 'compte_patient.%';
    -- et, pour l'historique antérieur : comptes dont les dossiers relèvent de cliniques différentes
    SELECT cp.compte_patient_id, COUNT(DISTINCT ep.etablissement_id) AS cliniques
      FROM compte_patient cp JOIN etablissement_patient ep ON ep.patient_id = cp.patient_id
     GROUP BY cp.compte_patient_id HAVING cliniques > 1;

Commit :
    git add -A && git commit -m "fix(securite): comptes du portail cloisonnés par clinique ; établissements et licence redessinés"
