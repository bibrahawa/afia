{{-- Affichage compact des constantes. @include('parcours.partials.constantes', ['c' => $constante]) --}}
@if($c)
    @php $alertes = $c->alertes(); $terme = $c->terme(); @endphp
    <span class="small">
        @if($c->temperature !== null)<span class="{{ in_array('température', $alertes) ? 'text-danger fw-bold' : '' }}">{{ rtrim(rtrim(number_format((float) $c->temperature, 1, ',', ''), '0'), ',') }} °C</span> · @endif
        @if($c->tension_systolique)<span class="{{ in_array('tension', $alertes) ? 'text-danger fw-bold' : '' }}">TA {{ $c->tension_systolique }}/{{ $c->tension_diastolique }}</span> · @endif
        @if($c->pouls)<span class="{{ in_array('pouls', $alertes) ? 'text-danger fw-bold' : '' }}">{{ $c->pouls }} bpm</span> · @endif
        @if($c->saturation_o2)<span class="{{ in_array('saturation', $alertes) ? 'text-danger fw-bold' : '' }}">SpO₂ {{ $c->saturation_o2 }} %</span> · @endif
        @if($c->poids_kg)<span>{{ rtrim(rtrim(number_format((float) $c->poids_kg, 2, ',', ''), '0'), ',') }} kg</span>@if($c->imc()) (IMC {{ number_format($c->imc(), 1, ',', '') }})@endif · @endif
        @if($c->glycemie !== null)<span class="{{ in_array('glycémie', $alertes) ? 'text-danger fw-bold' : '' }}">Glyc. {{ number_format((float) $c->glycemie, 2, ',', '') }} g/L</span> · @endif
        @if($terme)<span class="text-primary">{{ $terme[0] }} SA {{ $terme[1] }} j (DPA {{ $c->datePrevueAccouchement()->format('d/m/Y') }})</span>@endif
    </span>
@else
    <span class="small text-muted">Constantes non prises</span>
@endif
