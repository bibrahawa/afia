@extends("layouts.backend")
@section('content')

<h3>Facture de consultation</h3>
<p>Patient : {{ $consultation->patient->first_name." ".$consultation->patient->last_name }}</p>
<p>Médecin : {{ $consultation->medecin->first_name." ".$consultation->patient->last_name }}</p>
<p>Date : {{ $consultation->created_at->format('d/m/Y') }}</p>

<hr>

<h4>Services & Packages</h4>
<ul>
    <li><strong>Département :</strong> {{ $consultation->department->name }}</li>
    <li><strong>Service :</strong>
        @foreach ($consultation->services as $service)
            {{ $service->name }}
        @endforeach
    </li>
    <li><strong>Package :</strong>
        @foreach ($consultation->packages as $package)
            {{ $package->name }}
        @endforeach
    </li>
</ul>

<h4>Médicaments prescrits</h4>
<ul>
@foreach($consultation->medicaments as $med)
    <li>{{ $med->nom }} - {{ $med->frequence }} - {{ $med->duree }}</li>
@endforeach
</ul>

<p class="mt-4">Signature médecin</p>

<p class="mt-4">Signature patient</p>

<p class="mt-4">Montant total : {{ $consultation->total }} GNF</p>
<p class="mt-4">Mode de paiement : {{ $consultation->mode_paiement }}</p>
<p class="mt-4">Statut : {{ $consultation->statut }}</p>
// <p class="mt-4">Date de paiement : {{ $consultation->date_paiement }}</p>
<p class="mt-4">Médecin pour le prochain RDV : {{ $consultation->prochainMedecin->first_name ?? 'Non défini' }}</p>
<p class="mt-4">Prochain RDV : {{ $consultation->prochain_rdv ? $consultation->prochain_rdv->format('d/m/Y H:i') : 'Non défini' }}</p>


@endsection
