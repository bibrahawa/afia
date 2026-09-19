@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .pl-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .pl-table th { padding: 10px 16px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-align: left; white-space: nowrap; }
        .pl-table td { padding: 12px 16px; border-top: 1px solid #f3f4f6; vertical-align: middle; }
        .pl-table tbody tr:hover > td { background: var(--hali-primaire-pale); }
        .pl-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
        .pl-heure { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .pl-retard { color: var(--hali-danger); font-weight: 700; }
        .pl-sections { padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
        .pl-pagination { padding: 14px 18px; border-top: 1px solid var(--hali-bordure); }
        .pl-pagination nav { display: flex; justify-content: center; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Paillasse', 'fil' => [route('labo.paillasse.index') => 'Paillasse'], 'sousTitre' => 'Liste de travail : urgences et échéances les plus proches en premier.'])

    <section class="hl-bloc">
        <div class="pl-sections">
            <div class="hl-puces" role="group" aria-label="Section">
                <a href="{{ route('labo.paillasse.index') }}" class="hl-puce {{ ! $sectionId ? 'est-actif' : '' }}">Toutes les sections</a>
                @foreach($sections as $s)
                    <a href="{{ route('labo.paillasse.index', ['section' => $s->id]) }}" class="hl-puce {{ $sectionId === $s->id ? 'est-actif' : '' }}">{{ $s->nom }}</a>
                @endforeach
            </div>
        </div>

        @if($lignes->isEmpty())
            <div class="hl-vide"><i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>Aucun examen en attente sur cette paillasse.</div>
        @else
            <div class="table-responsive">
                <table class="pl-table">
                    <thead><tr><th>Examen</th><th>Patient</th><th>Reçu</th><th>Échéance</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    @foreach($lignes as $l)
                        @php
                            $echeance = $l->echeance();
                            $recu = $l->echantillons->whereNotNull('recu_le')->min('recu_le');
                        @endphp
                        <tr class="{{ $l->demande->urgence ? 'labo-urgent' : '' }}">
                            <td>
                                <strong style="color:var(--hali-encre)">{{ $l->examen_nom }}</strong>
                                @if($l->demande->urgence) <span class="labo-pastille-urgent"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>@endif
                                <span class="pl-sous">{{ $l->examen->section->nom }}</span>
                            </td>
                            <td>{{ $l->demande->patient->full_name }}<span class="pl-sous">{{ $l->demande->numero }}</span></td>
                            <td class="pl-heure">{{ $recu ? \Illuminate\Support\Carbon::parse($recu)->format('d/m H:i') : '—' }}</td>
                            <td class="pl-heure {{ $echeance?->isPast() ? 'pl-retard' : '' }}">
                                {{ $echeance?->format('d/m H:i') ?? '—' }}
                                @if($echeance?->isPast())<span class="pl-sous" style="color:var(--hali-danger)">en retard</span>@endif
                            </td>
                            <td><span class="badge badge-{{ $l->statut->couleur() }}">{{ $l->statut->libelle() }}</span></td>
                            <td style="text-align:right"><a href="{{ route('labo.paillasse.saisie', $l) }}" class="hl-bouton hl-bouton-plein" style="min-height:34px">Saisir</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($lignes->hasPages())
                <div class="pl-pagination">{{ $lignes->withQueryString()->links() }}</div>
            @endif
        @endif
    </section>
</div></div>
@endsection
