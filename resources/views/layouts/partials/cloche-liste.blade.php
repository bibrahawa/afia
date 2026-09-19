{{-- Contenu de la cloche (rendu aussi par ClocheController pour le rafraîchissement). --}}
@forelse($alertes as $a)
    @php $balise = $a['lien'] ? 'a' : 'div'; @endphp
    <{{ $balise }} @if($a['lien']) href="{{ $a['lien'] }}" @endif class="hl-cl-item est-{{ $a['niveau'] }}">
        <span class="hl-cl-icone" aria-hidden="true"><i class="fas {{ $a['icone'] }}"></i></span>
        <span class="hl-cl-texte">
            <strong>{{ $a['titre'] }}</strong>
            <span>{{ $a['detail'] }}</span>
        </span>
        @if($a['quand'])<time class="hl-cl-quand" datetime="{{ $a['quand']->toIso8601String() }}">{{ $a['quand']->isToday() ? $a['quand']->format('H:i') : $a['quand']->translatedFormat('d M') }}</time>@endif
    </{{ $balise }}>
@empty
    <div class="hl-cl-vide">
        <i class="fas fa-check-circle" aria-hidden="true"></i>
        <strong>Tout est en ordre</strong>
        <span>Aucune valeur critique, aucune longue attente, aucune réclamation rejetée à traiter.</span>
    </div>
@endforelse
