{{--
    Styles des tickets 80 mm (imprimantes thermiques). Compatibles DomPDF.
    Largeur utile ≈ 72 mm ; noir uniquement (les imprimantes thermiques n'impriment pas les couleurs).
--}}
<style>
    @page { margin: 3mm 3.5mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "DejaVu Sans", Arial, sans-serif; font-size: 7.6pt; line-height: 1.35; color: #000; }
    table { width: 100%; border-collapse: collapse; }
    .t-centre { text-align: center; }
    .t-clinique { font-size: 10.5pt; font-weight: bold; }
    .t-coord { font-size: 6.8pt; }
    .t-sep { height: 0; margin: 2mm 0; border-top: .7pt dashed #000; }
    .t-sep-plein { height: 0; margin: 2mm 0; border-top: 1pt solid #000; }
    .t-type { font-size: 10pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase; }
    .t-info td { padding: .3mm 0; vertical-align: top; }
    .t-info td.t-cle { width: 38%; }
    .t-lignes td { padding: .8mm 0; vertical-align: top; }
    .t-n { text-align: right; white-space: nowrap; }
    .t-sous { display: block; font-size: 6.6pt; }
    .t-fort td { padding-top: 1.2mm; font-size: 9pt; font-weight: bold; }
    .t-cadre { margin-top: 2mm; padding: 1.2mm; border: 1pt solid #000; font-weight: bold; text-align: center; letter-spacing: 1pt; text-transform: uppercase; }
    .t-merci { margin-top: 2.5mm; font-size: 6.8pt; text-align: center; }
</style>
