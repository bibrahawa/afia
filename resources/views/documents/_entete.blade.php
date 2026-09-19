{{-- En-tête commun. $identite, $type (« Facture »…), $numero, $date (Carbon), $pdf (bool : chemin de logo pour DomPDF). --}}
@php
    $logo = ($pdf ?? true) ? $identite->logoPdf() : $identite->logoWeb();
@endphp
<table class="d-entete">
    <tr>
        <td class="d-logo">@if($logo)<img src="{{ $logo }}" alt="">@else<div class="d-initiale">{{ mb_strtoupper(mb_substr($identite->nom, 0, 1)) }}</div>@endif</td>
        <td>
            <div class="d-clinique">{{ $identite->nom }}</div>
            <div class="d-coord">{{ $identite->adresse }}@if($identite->adresse && $identite->contact)<br>@endif @if($identite->contact)Tél. {{ $identite->contact }}@endif @if($identite->email)<br>{{ $identite->email }}@endif</div>
        </td>
        <td class="d-doc">
            <div class="d-type">{{ $type }}</div>
            @if(! empty($numero))<div class="d-numero">N° {{ $numero }}</div>@endif
            <div class="d-date">{{ ($date ?? now())->format('d/m/Y') }}@if(! empty($heure)) à {{ ($date ?? now())->format('H:i') }}@endif</div>
        </td>
    </tr>
</table>
<div class="d-filet"></div>
