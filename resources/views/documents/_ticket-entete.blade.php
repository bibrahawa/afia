{{-- En-tête d'un ticket 80 mm. $identite, $type, $numero, $date, $patient --}}
<div class="t-centre">
    <div class="t-clinique">{{ $identite->nom }}</div>
    <div class="t-coord">{{ $identite->adresse }}@if($identite->contact)<br>Tél. {{ $identite->contact }}@endif</div>
</div>
<div class="t-sep"></div>
<div class="t-centre t-type">{{ $type }}</div>
<table class="t-info" style="margin-top:1.5mm">
    @if(! empty($numero))<tr><td class="t-cle">N°</td><td>{{ $numero }}</td></tr>@endif
    <tr><td class="t-cle">Date</td><td>{{ ($date ?? now())->format('d/m/Y H:i') }}</td></tr>
    <tr><td class="t-cle">Patient</td><td><strong>{{ $patient->getFullName() }}</strong></td></tr>
    @if($patient->identifiant_national_sante)<tr><td class="t-cle">ID santé</td><td>{{ $patient->identifiant_national_sante }}</td></tr>@endif
    @if(! empty($medecin))<tr><td class="t-cle">Médecin</td><td>{{ $medecin->nom_affiche }}</td></tr>@endif
</table>
<div class="t-sep"></div>
