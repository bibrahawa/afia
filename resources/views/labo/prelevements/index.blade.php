@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .pv-carte { margin-bottom: 14px; }
        .pv-carte.labo-urgent { border-left: 4px solid var(--hali-danger) !important; }
        .pv-haut { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
        .pv-haut .hl-avatar { width: 38px; height: 38px; flex-basis: 38px; }
        .pv-nom { color: var(--hali-encre); font-size: 1rem; font-weight: 700; }
        .pv-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
        .pv-liens { display: flex; gap: 8px; margin-left: auto; }
        .pv-tube { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
        .pv-tube:first-child { border-top: 0; }
        .pv-tube form { display: flex; gap: 8px; margin: 0 0 0 auto; }
        .pv-pagination nav { display: flex; justify-content: center; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Prélèvements en attente', 'fil' => [route('labo.prelevements.index') => 'Prélèvements'], 'sousTitre' => 'Un bloc par patient, un bouton par tube.'])

    @forelse($demandes as $d)
        @php($initiales = mb_strtoupper(mb_substr((string) $d->patient->first_name, 0, 1) . mb_substr((string) $d->patient->last_name, 0, 1)))
        <section class="hl-bloc pv-carte {{ $d->urgence ? 'labo-urgent' : '' }}">
            <div class="pv-haut">
                <span class="hl-avatar {{ $d->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initiales }}</span>
                <div>
                    <span class="pv-nom">{{ $d->patient->full_name }}</span>
                    @if($d->urgence) <span class="labo-pastille-urgent"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>@endif
                    @if($d->a_jeun_confirme) <span class="hl-statut hl-s-info">À jeun</span>@endif
                    <span class="pv-sous">{{ $d->numero }} · demandée {{ $d->created_at->diffForHumans() }} ({{ $d->created_at->format('d/m à H:i') }})</span>
                </div>
                <div class="pv-liens">
                    <a href="{{ route('labo.demandes.etiquettes', $d) }}" target="_blank" class="hl-bouton"><i class="fa fa-barcode" aria-hidden="true"></i> Étiquettes</a>
                    <a href="{{ route('labo.demandes.show', $d) }}" class="hl-bouton">Fiche</a>
                </div>
            </div>
            <div>
                @foreach($d->echantillons as $e)
                    <div class="pv-tube">
                        <div>
                            @if($e->tube)<span class="labo-tube labo-tube-{{ $e->tube }}"></span>@endif
                            <strong style="color:var(--hali-encre)">{{ $e->libelleContenant() }}</strong> <code>{{ $e->code_barres }}</code>
                            @if($e->remplace_echantillon_id) <span class="hl-statut hl-s-alerte">Re-prélèvement</span>@endif
                            <span class="pv-sous">{{ $e->examens->pluck('examen_nom')->implode(', ') }}</span>
                        </div>
                        <form method="POST" action="{{ route('labo.echantillons.preleve', $e) }}">@csrf
                            <button class="hl-bouton">Prélevé</button>
                            @can('labo.reception')<button name="et_recu" value="1" class="hl-bouton hl-bouton-plein" title="Prélevé et déposé au laboratoire">Prélevé et reçu</button>@endcan
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <section class="hl-bloc"><div class="hl-vide"><i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>Aucun prélèvement en attente.</div></section>
    @endforelse

    @if($demandes->hasPages())
        <div class="pv-pagination">{{ $demandes->withQueryString()->links() }}</div>
    @endif
</div></div>
@endsection
