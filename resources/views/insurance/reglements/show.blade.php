@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); $r = $reglement; @endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('assurance.partials.entete', [
        'titre' => 'Règlement ' . $r->settlement_no,
        'fil' => [route('assurance.creances.index') => 'Créances', route('assurance.creances.show', $r->insuranceCompany) => $r->insuranceCompany->name, 0 => $r->settlement_no],
    ])

    <div class="card">
        <div class="card-body small">
            <div class="row">
                <div class="col-md-3"><strong>Organisme :</strong> {{ $r->insuranceCompany->name }}</div>
                <div class="col-md-3"><strong>Date :</strong> {{ $r->payment_date?->format('d/m/Y') }}</div>
                <div class="col-md-3"><strong>Mode :</strong> {{ $r->payment_method ?? '—' }}</div>
                <div class="col-md-3"><strong>Référence :</strong> {{ $r->payment_reference ?? '—' }}</div>
                <div class="col-md-3 mt-2"><strong>Montant reçu :</strong> {{ $gnf($r->paid_amount) }} GNF</div>
                <div class="col-md-3 mt-2"><strong>Écarts passés en perte :</strong> {{ $gnf($r->discount_amount) }} GNF</div>
                @if($r->notes)<div class="col-md-6 mt-2 text-muted">{{ $r->notes }}</div>@endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4 class="card-title">Imputation</h4></div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle small">
                <thead><tr><th>Réclamation</th><th>Patient</th><th class="text-end">Reste avant</th><th class="text-end">Payé</th><th class="text-end">Écart</th><th class="text-end">Reste après</th><th>Encaissement</th></tr></thead>
                <tbody>
                @foreach($r->items as $item)
                    <tr>
                        <td>@if($item->reclamation)<a href="{{ route('assurance.reclamations.show', $item->reclamation) }}">{{ $item->reclamation->claim_number }}</a>@else facture #{{ $item->invoice_id }} @endif</td>
                        <td>{{ $item->reclamation?->invoice?->transaction?->patient?->full_name }}</td>
                        <td class="text-end">{{ $gnf($item->remaining_before) }}</td>
                        <td class="text-end">{{ $gnf($item->applied_paid_amount) }}</td>
                        <td class="text-end">{{ $gnf($item->applied_discount_amount) }}</td>
                        <td class="text-end">{{ $gnf($item->remaining_after) }}</td>
                        <td>{{ $item->paiement?->paiement_no ?? '—' }} @if($item->paiement?->estAnnule())<span class="badge badge-danger">annulé</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div></div>
@endsection
