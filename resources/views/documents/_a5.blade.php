{{--
    Styles des documents A5 (factures, reçus, ordonnances). Compatibles DomPDF :
    tableaux et flottants uniquement (DomPDF ignore flexbox et grid).
    DejaVu Sans : police fournie avec DomPDF, couvre tous les accents.
--}}
<style>
    @page { size: A5 portrait; margin: 9mm 9mm 16mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "DejaVu Sans", "Segoe UI", Arial, sans-serif; font-size: 9pt; line-height: 1.35; color: #1f2937; }
    table { width: 100%; border-collapse: collapse; }
    .d-entete td { vertical-align: top; }
    .d-logo { width: 16mm; padding-right: 3mm; }
    .d-logo img { max-width: 16mm; max-height: 16mm; }
    .d-initiale { width: 13mm; padding: 2.6mm 0; border-radius: 3mm; background: #0f766e; color: #fff; font-size: 16pt; font-weight: bold; text-align: center; line-height: 1; }
    .d-clinique { font-size: 12pt; font-weight: bold; color: #111827; }
    .d-coord { color: #6b7280; font-size: 7.5pt; line-height: 1.4; }
    .d-doc { text-align: right; }
    .d-type { font-size: 13pt; font-weight: bold; color: #0f766e; letter-spacing: .5pt; text-transform: uppercase; }
    .d-numero { margin-top: 1mm; font-size: 8.5pt; font-weight: bold; }
    .d-date { color: #6b7280; font-size: 7.5pt; }
    .d-filet { height: 0; margin: 3.5mm 0; border-top: 1.2pt solid #0f766e; }
    .d-personnes td { width: 50%; vertical-align: top; padding: 2.5mm 3mm; background: #f3f6f6; }
    .d-personnes td + td { border-left: 2mm solid #fff; }
    .d-etiquette { color: #6b7280; font-size: 6.8pt; text-transform: uppercase; letter-spacing: .4pt; }
    .d-nom { font-size: 10pt; font-weight: bold; color: #111827; }
    .d-petit { font-size: 7.8pt; color: #374151; }
    .d-titre-section { margin: 5mm 0 2mm; font-size: 8pt; font-weight: bold; color: #0f766e; text-transform: uppercase; letter-spacing: .4pt; }
    .d-lignes th { padding: 1.8mm 2mm; border-bottom: 1pt solid #111827; font-size: 7.3pt; font-weight: bold; text-align: left; text-transform: uppercase; color: #374151; }
    .d-lignes td { padding: 2mm; border-bottom: .5pt solid #e5e7eb; vertical-align: top; }
    .d-lignes .n, .d-totaux .n { text-align: right; white-space: nowrap; }
    .d-sous { display: block; color: #6b7280; font-size: 7.3pt; }
    .d-assurance { display: block; margin-top: .6mm; color: #047857; font-size: 7.3pt; }
    .d-totaux { width: 62%; margin: 3mm 0 0 38%; }
    .d-totaux td { padding: 1.3mm 2mm; }
    .d-totaux .d-fort td { border-top: 1pt solid #111827; font-size: 10.5pt; font-weight: bold; color: #111827; padding-top: 2mm; }
    .d-cachet { margin-top: 3mm; padding: 1.5mm 3mm; border: 1.2pt solid; border-radius: 1.5mm; font-weight: bold; font-size: 8.5pt; text-align: center; text-transform: uppercase; letter-spacing: 1pt; }
    .d-paye { color: #047857; border-color: #047857; }
    .d-partiel { color: #b45309; border-color: #b45309; }
    .d-du { color: #b91c1c; border-color: #b91c1c; }
    .d-lettres { margin-top: 3mm; padding: 2mm 3mm; border-left: 1.2pt solid #0f766e; background: #f3f6f6; font-size: 8pt; }
    .d-signature { margin-top: 8mm; width: 60%; margin-left: 40%; text-align: center; }
    .d-signature .d-ligne { height: 16mm; border-bottom: .6pt solid #9ca3af; }
    .d-pied { position: fixed; bottom: -11mm; left: 0; right: 0; padding-top: 1.5mm; border-top: .5pt solid #d1d5db; color: #6b7280; font-size: 6.6pt; text-align: center; }
    .d-valide { text-align: center; }
    .d-valide-zone { position: relative; height: 19mm; margin-top: .5mm; }
    .d-valide-cachet { position: absolute; left: 0; top: 0; width: 19mm; height: 19mm; }
    .d-valide-signature { position: relative; max-width: 38mm; max-height: 14mm; margin-top: 2.5mm; }
    .d-valide-ligne { height: 0; border-top: .6pt solid #9ca3af; margin-bottom: 1mm; }
    .d-vide { margin-top: 12mm; padding: 6mm; border: 1pt dashed #d1d5db; color: #6b7280; text-align: center; }
</style>
