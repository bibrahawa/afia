<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 18mm 14mm 20mm 14mm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #222; }
    .cr-entete { border-bottom: 2px solid #087f6b; padding-bottom: 6px; margin-bottom: 8px; }
    .cr-entete td { vertical-align: top; }
    .cr-titre { font-size: 14pt; color: #087f6b; font-weight: bold; }
    .cr-ident { width: 100%; border: 1px solid #ccc; border-collapse: collapse; margin-bottom: 8px; }
    .cr-ident td { padding: 5px 7px; vertical-align: top; }
    .cr-section { font-size: 10.5pt; color: #fff; background: #087f6b; padding: 3px 6px; margin: 10px 0 4px; }
    .cr-examen { margin-bottom: 8px; page-break-inside: avoid; }
    .cr-examen-titre { font-weight: bold; border-bottom: 1px solid #ddd; margin-bottom: 2px; }
    .cr-resultats { width: 100%; border-collapse: collapse; }
    .cr-resultats th { text-align: left; font-size: 8pt; color: #666; border-bottom: 1px solid #ddd; padding: 2px 4px; }
    .cr-resultats td { padding: 2px 4px; border-bottom: 1px dotted #e3e3e3; }
    .cr-groupe { font-style: italic; color: #555; }
    .cr-anormal { font-weight: bold; }
    .cr-critique { font-weight: bold; text-decoration: underline; }
    .cr-muted { color: #777; font-size: 8pt; }
    .cr-badge { font-size: 7pt; border: 1px solid #b45309; color: #b45309; padding: 0 3px; }
    .cr-rectif { border: 2px solid #b45309; padding: 5px; margin-bottom: 8px; color: #7c2d12; }
    .cr-partiel { border: 1px dashed #666; padding: 4px; margin-bottom: 8px; }
    .cr-commentaire { background: #f4f7f6; padding: 4px 6px; margin: 3px 0; }
    .cr-signature { margin-top: 18px; text-align: right; font-weight: bold; }
    .cr-pied { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7pt; color: #888; text-align: center; }
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
