{{-- Bloc patient / médecin. $patient, $medecin (Employee|null), $service (string|null) --}}
@php
    $age = $patient->age;
    $sexe = ['Homme' => 'Homme', 'Femme' => 'Femme', 'M' => 'Homme', 'F' => 'Femme', 'male' => 'Homme', 'female' => 'Femme'][$patient->gender] ?? null;
@endphp
<table class="d-personnes">
    <tr>
        <td>
            <div class="d-etiquette">Patient</div>
            <div class="d-nom">{{ $patient->getFullName() }}</div>
            <div class="d-petit">{{ collect([$sexe, $age !== null ? $age . ' an' . ($age > 1 ? 's' : '') : null])->filter()->implode(' · ') }}</div>
            @if($patient->identifiant_national_sante)<div class="d-petit">ID santé {{ $patient->identifiant_national_sante }}</div>@endif
        </td>
        <td>
            @if($medecin)
                <div class="d-etiquette">Médecin</div>
                <div class="d-nom">{{ $medecin->nom_affiche }}</div>
                @if($medecin->speciality)<div class="d-petit">{{ $medecin->speciality }}</div>@endif
            @endif
            @if(! empty($service))<div class="d-petit">{{ $service }}</div>@endif
        </td>
    </tr>
</table>
