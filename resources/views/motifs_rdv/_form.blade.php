{{-- Champs d'un motif. $services : actes du catalogue ; $departementId : département du motif (null pour la modification). --}}
@php
    $departementId = $departementId ?? null;
    $gnfM = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $actesDuDep = $departementId ? $services->where('department_id', $departementId) : collect();
    $autresActes = $departementId ? $services->where('department_id', '!=', $departementId) : $services;
@endphp
<div><label class="cat-l">Nom du motif</label>
    <input type="text" name="nom" class="form-control" placeholder="Consultation, contrôle, échographie…" required></div>
<div class="cat-deux">
    <div><label class="cat-l">Durée</label>
        <div class="mt-duree">
            @foreach([5, 10, 15, 20, 30, 45, 60] as $m)<button type="button" data-duree="{{ $m }}">{{ $m }}</button>@endforeach
        </div>
        <div class="cat-montant"><input type="number" name="duree_minutes_defaut" class="form-control" min="1" max="240" value="15" required><span>min</span></div>
        <p class="cat-aide">5 min pour un résultat, 15 à 20 min pour une consultation.</p></div>
    <div><label class="cat-l">Marge après</label>
        <div class="cat-montant"><input type="number" name="marge_tampon_minutes" class="form-control" min="0" max="60" value="5" required><span>min</span></div>
        <p class="cat-aide">Temps laissé libre avant le rendez-vous suivant.</p></div>
</div>
<div><label class="cat-l">Acte facturé à l'arrivée</label>
    <select name="service_id" class="form-control">
        <option value="">Aucun (le médecin choisit pendant la consultation)</option>
        @if($actesDuDep->isNotEmpty())
            <optgroup label="Ce département">@foreach($actesDuDep as $s)<option value="{{ $s->id }}">{{ $s->name }} — {{ $gnfM($s->amount) }} GNF</option>@endforeach</optgroup>
            <optgroup label="Autres départements">@foreach($autresActes as $s)<option value="{{ $s->id }}">{{ $s->name }} — {{ $gnfM($s->amount) }} GNF</option>@endforeach</optgroup>
        @else
            @foreach($autresActes as $s)<option value="{{ $s->id }}">{{ $s->name }} — {{ $gnfM($s->amount) }} GNF</option>@endforeach
        @endif
    </select>
    <p class="cat-aide">Proposé automatiquement à l'accueil quand le patient arrive pour ce motif.</p></div>
<div><label class="cat-l">Couleur dans l'agenda</label>
    <div class="mt-couleurs">
        @foreach(['#0f766e', '#2563eb', '#7c3aed', '#db2777', '#dc2626', '#ea580c', '#ca8a04', '#16a34a', '#475569'] as $c)
            <button type="button" data-couleur="{{ $c }}" style="background: {{ $c }}" aria-label="Couleur {{ $c }}"></button>
        @endforeach
        <input type="color" name="couleur" class="form-control form-control-color" value="#0f766e" aria-label="Couleur personnalisée">
    </div></div>
