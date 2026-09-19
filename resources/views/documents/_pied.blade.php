{{-- Pied de page (répété sur chaque page par DomPDF grâce à position: fixed). --}}
<div class="d-pied">
    @if($identite->messageFacture){{ $identite->messageFacture }}<br>@endif
    {{ $identite->nom }}@if($identite->numeroEnregistrement) · N° {{ $identite->numeroEnregistrement }}@endif · Document édité le {{ now()->format('d/m/Y à H:i') }}
</div>
