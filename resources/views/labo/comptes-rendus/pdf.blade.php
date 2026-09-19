<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    /* DomPDF : tableaux et blocs uniquement (ni flexbox ni grille). Lisible en noir et blanc. */
    @page { margin: 18mm 14mm 20mm 14mm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #1f2937; line-height: 1.35; }
    .cr-entete { border-bottom: 2px solid #0f766e; padding-bottom: 6px; margin-bottom: 10px; }
    .cr-entete td { vertical-align: top; }
    .cr-titre { font-size: 14pt; color: #0f766e; font-weight: bold; }
    .cr-muted { color: #6b7280; font-size: 8pt; }
    .cr-etiquette { display: block; margin-bottom: 2px; color: #6b7280; font-size: 7pt; font-weight: bold; letter-spacing: .5px; text-transform: uppercase; }

    .cr-rectif { border: 2px solid #b45309; padding: 6px 8px; margin-bottom: 8px; color: #7c2d12; }
    .cr-rectif-motif { font-size: 8.5pt; }
    .cr-partiel { border: 1px dashed #6b7280; padding: 5px 8px; margin-bottom: 8px; }

    .cr-ident { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .cr-ident-bloc { padding: 6px 8px; vertical-align: top; border: 1px solid #d1d5db; background: #f9fafb; }
    .cr-ident-nom { font-size: 11pt; font-weight: bold; }

    .cr-section { font-size: 10pt; color: #fff; background: #0f766e; padding: 3px 7px; margin: 12px 0 5px; }
    .cr-examen { margin-bottom: 10px; page-break-inside: avoid; }
    .cr-examen-entete { width: 100%; border-collapse: collapse; border-bottom: 1px solid #9ca3af; margin-bottom: 3px; }
    .cr-examen-titre { font-weight: bold; font-size: 9.5pt; padding: 1px 0; }
    .cr-examen-methode { text-align: right; color: #6b7280; font-size: 7.5pt; }
    .cr-badge { font-size: 7pt; border: 1px solid #b45309; color: #b45309; padding: 0 3px; margin-left: 4px; }

    .cr-resultats { width: 100%; border-collapse: collapse; }
    .cr-resultats th { text-align: left; font-size: 7.5pt; font-weight: normal; color: #6b7280; border-bottom: 1px solid #d1d5db; padding: 2px 4px; }
    .cr-resultats td { padding: 3px 4px; border-bottom: 1px dotted #e5e7eb; vertical-align: top; }
    .cr-col-param { width: 40%; }
    .cr-col-valeur { width: 20%; }
    .cr-col-unite { width: 12%; color: #4b5563; }
    .cr-col-norme { color: #6b7280; font-size: 8pt; }
    .cr-col-sir { width: 14%; text-align: center; }
    .cr-groupe { font-style: italic; color: #4b5563; padding-top: 5px !important; }
    .cr-anormal { font-weight: bold; }
    .cr-critique { font-weight: bold; border: 1.5px solid #1f2937; padding: 0 3px; }
    .cr-ligne-critique td { background: #f3f4f6; }
    .cr-resistant { font-weight: bold; text-decoration: underline; }
    .cr-germe { margin: 5px 0 2px; }
    .cr-legende-antibio { margin: 2px 0 0; }

    .cr-commentaire { border-left: 3px solid #0f766e; background: #f0fdfa; padding: 4px 7px; margin: 5px 0 3px; }
    .cr-valide { margin: 3px 0 0; color: #6b7280; font-size: 7.5pt; text-align: right; }

    .cr-fin { width: 100%; border-collapse: collapse; margin-top: 16px; border-top: 1px solid #d1d5db; }
    .cr-fin td { vertical-align: top; padding-top: 6px; }
    .cr-legende { width: 62%; color: #6b7280; font-size: 7.5pt; padding-right: 10px; }
    .cr-signature { text-align: right; font-weight: bold; }
    .cr-pied { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7pt; color: #9ca3af; text-align: center; }
</style>
</head>
<body>
    @php($logo = $c['etablissement']['logo'] ? public_path($c['etablissement']['logo']) : null)
    <table class="cr-entete" style="width:100%">
        <tr>
            <td style="width:60%">
                @if($logo && is_file($logo))<img src="{{ $logo }}" style="height:42px"><br>@endif
                <strong>{{ $c['etablissement']['nom'] }}</strong><br>
                <span class="cr-muted">{{ $c['etablissement']['adresse'] }} · {{ $c['etablissement']['contact'] }} · {{ $c['etablissement']['email'] }}
                    @if($c['etablissement']['numero_enregistrement'])<br>Agrément : {{ $c['etablissement']['numero_enregistrement'] }}@endif</span>
            </td>
            <td style="text-align:right"><div class="cr-titre">Compte rendu d'analyses</div></td>
        </tr>
    </table>

    @include('labo.comptes-rendus._corps')

    <div class="cr-pied">{{ $c['demande']['numero'] }} — v{{ $cr->version }} — empreinte {{ substr($cr->empreinte, 0, 16) }} — document confidentiel, secret médical</div>
</body>
</html>
