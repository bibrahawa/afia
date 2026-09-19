{{-- Styles propres au module laboratoire, sur les couleurs Hali (admin-theme.css). --}}
<style>
    /* En-tête et fil d'Ariane */
    .labo-entete { margin-bottom: 18px; }
    .labo-fil { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 4px; color: #9ca3af; font-size: .8rem; font-weight: 600; }
    .labo-fil a { color: var(--hali-discret, #6b7280); text-decoration: none; }
    .labo-fil a:hover { color: var(--hali-primaire, #0f766e); }

    /* Résultats : anormal en ambre, critique en rouge plein */
    .labo-flag-anormal { color: var(--hali-alerte, #b45309); font-weight: 700; }
    .labo-flag-critique { display: inline-block; padding: 0 .45rem; border-radius: 5px; background: var(--hali-danger, #b91c1c); color: #fff; font-weight: 700; }
    tr.labo-ligne-critique > td { background: var(--hali-danger-pale, #fef2f2) !important; }

    /* Chiffres clés */
    .labo-kpi { border-left: 4px solid var(--hali-primaire, #0f766e); }
    .labo-kpi .labo-kpi-valeur { font-size: 1.9rem; font-weight: 700; line-height: 1; color: var(--hali-encre, #111827); font-variant-numeric: tabular-nums; }

    /* Urgence : un filet rouge, sur une carte comme sur une ligne de tableau
       (une bordure posée sur <tr> ne s'affiche pas : on la met sur la 1re cellule). */
    .card.labo-urgent { border-left: 4px solid var(--hali-danger, #b91c1c) !important; }
    tr.labo-urgent > td { background: #fffafa; }
    tr.labo-urgent > td:first-child { box-shadow: inset 3px 0 0 var(--hali-danger, #b91c1c); }
    .labo-pastille-urgent { display: inline-flex; align-items: center; gap: 4px; padding: 1px 8px; border-radius: 999px; background: var(--hali-danger-pale, #fef2f2); color: var(--hali-danger, #b91c1c); font-size: .72rem; font-weight: 700; letter-spacing: .02em; vertical-align: 1px; }

    /* Tubes (couleur du bouchon) */
    .labo-tube { display: inline-block; width: 12px; height: 12px; margin-right: 5px; border: 1px solid rgba(0, 0, 0, .2); border-radius: 50%; vertical-align: -1px; }
    .labo-tube-violet { background: #7b3fa0; } .labo-tube-bleu { background: #3b82c4; } .labo-tube-rouge { background: #c0262d; }
    .labo-tube-jaune { background: #e8b923; } .labo-tube-vert { background: #2f8f4e; } .labo-tube-gris { background: #8a8f98; }
    .labo-tube-pot, .labo-tube-ecouvillon, .labo-tube-lame { background: #fff; }

    .labo-anteriorite { color: var(--hali-discret, #6b7280); font-size: .8rem; }
    .labo-saisie input.form-control, .labo-saisie select.form-select { max-width: 180px; }

    /* Scanner de la réception : grand, lisible, toujours prêt */
    .labo-scanner { min-height: 56px; font-size: 1.4rem; letter-spacing: .15em; font-variant-numeric: tabular-nums; border-width: 2px; }
    .labo-scanner:focus { border-color: var(--hali-primaire, #0f766e); box-shadow: 0 0 0 4px var(--hali-primaire-clair, #ccfbf1); }

    /* Codes-barres lisibles */
    .page-inner code { padding: 1px 6px; border-radius: 5px; background: #f3f4f6; color: var(--hali-encre, #111827); font-size: .82rem; }

    /* ---------------------------------------------------------------- Briques communes (lb-) */
    .lb-grille { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 16px; align-items: start; }
    .lb-grille-large { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 380px); gap: 16px; align-items: start; }
    .lb-colonne { display: grid; gap: 16px; min-width: 0; }
    @media (max-width: 1199.98px) { .lb-grille, .lb-grille-large { grid-template-columns: 1fr; } }

    .lb-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .lb-table th { padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; white-space: nowrap; }
    .lb-table td { padding: 11px 16px; border-top: 1px solid #f3f4f6; vertical-align: middle; }
    .lb-table tbody tr:first-child td { border-top: 0; }
    .lb-table tbody tr:hover > td { background: var(--hali-primaire-pale); }
    .lb-table .lb-n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .lb-table .lb-actions { text-align: right; white-space: nowrap; }
    .lb-table .lb-actions form { display: inline; margin: 0; }
    .lb-table .lb-groupe td { padding: 8px 16px; background: #f9fafb; color: var(--hali-encre); font-size: .8rem; font-weight: 700; }
    .lb-table tr.est-barre td { color: #9ca3af; text-decoration: line-through; }

    .lb-sous { display: block; color: var(--hali-discret); font-size: .78rem; font-weight: 500; }
    .lb-fort { color: var(--hali-encre); font-weight: 650; }
    .lb-petit { min-height: 32px; padding: 0 11px; font-size: .8rem; }
    .lb-risque { color: var(--hali-danger); border-color: #fecaca; }
    .lb-risque:hover { color: var(--hali-danger); border-color: var(--hali-danger); background: var(--hali-danger-pale); }
    .lb-plein-risque { background: var(--hali-danger); border-color: var(--hali-danger); color: #fff; }
    .lb-plein-risque:hover { background: #991b1b; color: #fff; }

    /* Carte d'identité du patient en tête d'écran */
    .lb-patient { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; padding: 14px 18px; }
    .lb-patient .hl-avatar { width: 46px; height: 46px; flex-basis: 46px; border-radius: 12px; font-size: .95rem; }
    .lb-patient-nom { color: var(--hali-encre); font-size: 1.1rem; font-weight: 700; }
    .lb-patient-meta { display: flex; flex-wrap: wrap; gap: 4px 14px; color: var(--hali-discret); font-size: .84rem; }
    .lb-patient .lb-droite { margin-left: auto; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }

    /* Informations en liste de définitions */
    .lb-infos { margin: 0; padding: 4px 18px 12px; }
    .lb-infos div { display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 10px; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: .86rem; }
    .lb-infos div:last-child { border-bottom: 0; }
    .lb-infos dt { color: var(--hali-discret); font-weight: 500; }
    .lb-infos dd { margin: 0; color: var(--hali-encre); font-weight: 600; overflow-wrap: anywhere; }

    /* Formulaires */
    .lb-form { display: grid; gap: 14px; padding: 16px 18px; }
    .lb-form .form-label, .lb-libelle { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .lb-deux { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 12px; }
    .lb-trois { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    @media (max-width: 767.98px) { .lb-deux, .lb-trois { grid-template-columns: 1fr; } }
    .lb-aide { margin: 4px 0 0; color: var(--hali-discret); font-size: .78rem; }

    /* Cases à cocher en tuiles (options, examens) */
    .lb-coches { display: flex; flex-wrap: wrap; gap: 8px; }
    .lb-coche { position: relative; margin: 0; }
    .lb-coche input { position: absolute; opacity: 0; pointer-events: none; }
    .lb-coche span { display: inline-flex; align-items: center; gap: 7px; min-height: 36px; padding: 0 13px; border: 1px solid var(--hali-bordure); border-radius: 999px; background: #fff; color: var(--hali-texte); font-size: .84rem; font-weight: 600; cursor: pointer; user-select: none; }
    .lb-coche input:checked + span { background: var(--hali-primaire-pale); border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); }
    .lb-coche input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    .lb-coche.est-danger input:checked + span { background: var(--hali-danger-pale); border-color: var(--hali-danger); color: var(--hali-danger); }

    /* Barre d'actions collée en bas d'un formulaire long */
    .lb-barre-bas { position: sticky; bottom: 0; z-index: 15; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 16px; padding: 12px 18px; border: 1px solid var(--hali-bordure); border-radius: var(--hali-rayon); background: rgba(255, 255, 255, .97); box-shadow: 0 -6px 16px rgba(17, 24, 39, .06); }
    .lb-barre-bas .lb-droite { margin-left: auto; display: flex; gap: 8px; }

    .lb-alerte { display: flex; gap: 10px; align-items: flex-start; margin: 0 0 14px; padding: 12px 14px; border-radius: 10px; font-size: .86rem; }
    .lb-alerte-info { background: var(--hali-info-pale); color: #1e3a8a; }
    .lb-alerte-avert { background: var(--hali-alerte-pale); color: #78350f; }
    .lb-alerte-erreur { background: var(--hali-danger-pale); color: #7f1d1d; }
    .lb-alerte ul { margin: 0; padding-left: 18px; }
</style>
