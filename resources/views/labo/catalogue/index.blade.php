@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .ct-outils { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
        .ct-outils form { margin: 0; }
        .ct-filtre { position: relative; flex: 1 1 260px; }
        .ct-filtre i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .ct-filtre input { padding-left: 40px; min-height: 42px; }
        .ct-section { margin-bottom: 16px; }
        .ct-prix-zero { color: var(--hali-danger); font-weight: 700; }
        tr.est-inactif td { color: #9ca3af; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Catalogue des examens', 'fil' => [route('labo.catalogue.index') => 'Catalogue'], 'sousTitre' => 'Prix, délais, contenants et normes de chaque examen.'])

    @php
        $tous = $sections->flatMap->examens;
        $sansPrix = $tous->filter(fn ($e) => $e->actif && (float) $e->prix === 0.0)->count();
    @endphp

    @if($catalogueVide)
        <p class="lb-alerte lb-alerte-info"><i class="fas fa-info-circle mt-1" aria-hidden="true"></i><span>Aucun examen. Le catalogue modèle contient une trentaine d'examens courants (hématologie, biochimie, sérologie, parasitologie, bactériologie). Après l'import, <strong>renseignez les prix</strong> (tous à 0) et <strong>faites valider les normes</strong> par votre biologiste.</span></p>
    @elseif($sansPrix > 0)
        <p class="lb-alerte lb-alerte-avert"><i class="fas fa-tag mt-1" aria-hidden="true"></i><span><strong>{{ $sansPrix }} examen{{ $sansPrix > 1 ? 's' : '' }} actif{{ $sansPrix > 1 ? 's' : '' }} sans prix</strong> (en rouge ci-dessous) : ils seraient facturés 0 GNF.</span></p>
    @endif

    <section class="hl-bloc" style="margin-bottom:16px">
        <div class="ct-outils">
            <label class="ct-filtre mb-0">
                <span class="sr-only visually-hidden">Filtrer</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="ctFiltre" class="form-control" placeholder="Filtrer : code, nom d'examen…" autocomplete="off">
            </label>
            @can('labo.catalogue.manage')
                <a href="{{ route('labo.catalogue.examens.create') }}" class="hl-bouton hl-bouton-plein"><i class="fa fa-plus" aria-hidden="true"></i> Nouvel examen</a>
                <button type="button" class="hl-bouton" data-bs-toggle="modal" data-bs-target="#modalSection"><i class="fa fa-plus" aria-hidden="true"></i> Section</button>
                <form method="POST" action="{{ route('labo.catalogue.importer') }}"
                      onsubmit="return confirm('Importer le catalogue modèle ? Les examens déjà présents ne sont jamais écrasés.')">@csrf
                    <button class="hl-bouton">{{ $catalogueVide ? 'Importer le catalogue modèle' : 'Compléter depuis le modèle' }}</button>
                </form>
            @endcan
        </div>
    </section>

    @foreach($sections as $section)
        <section class="hl-bloc ct-section">
            <h2 class="hl-bloc-titre">{{ $section->nom }} <small>{{ $section->examens->count() }} examen{{ $section->examens->count() > 1 ? 's' : '' }}</small></h2>
            @if($section->examens->isEmpty())
                <div class="hl-vide" style="padding:20px">Aucun examen dans cette section.</div>
            @else
                <div class="table-responsive">
                    <table class="lb-table">
                        <thead><tr><th>Examen</th><th>Échantillon</th><th class="lb-n">Paramètres</th><th class="lb-n">Délai</th><th class="lb-n">Prix</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @foreach($section->examens as $ex)
                            <tr class="ct-ligne {{ $ex->actif ? '' : 'est-inactif' }}" data-texte="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($ex->code . ' ' . $ex->nom)) }}">
                                <td>
                                    <span class="lb-fort">{{ $ex->nom }}</span>
                                    <span class="lb-sous"><code>{{ $ex->code }}</code>
                                        @if($ex->a_jeun) · à jeun @endif @if($ex->sous_traite) · sous-traité @endif</span>
                                </td>
                                <td style="font-size:.84rem">@if($ex->tube)<span class="labo-tube labo-tube-{{ $ex->tube }}"></span>@endif{{ $ex->libelleContenant() }}</td>
                                <td class="lb-n">{{ $ex->parametres_count }}</td>
                                <td class="lb-n">{{ $ex->delai_rendu_heures }} h</td>
                                <td class="lb-n {{ (float) $ex->prix === 0.0 ? 'ct-prix-zero' : '' }}">{{ number_format($ex->prix, 0, ',', ' ') }} GNF</td>
                                <td><span class="hl-statut {{ $ex->actif ? 'hl-s-succes' : 'hl-s-neutre' }}">{{ $ex->actif ? 'Actif' : 'Désactivé' }}</span></td>
                                <td class="lb-actions">
                                    @can('labo.catalogue.manage')
                                        <a href="{{ route('labo.catalogue.examens.edit', $ex) }}" class="hl-bouton lb-petit">Modifier</a>
                                        <form method="POST" action="{{ route('labo.catalogue.examens.basculer', $ex) }}">@csrf
                                            <button class="hl-bouton lb-petit" title="{{ $ex->actif ? 'Désactiver' : 'Réactiver' }}">{{ $ex->actif ? 'Désactiver' : 'Réactiver' }}</button></form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endforeach
</div></div>

@can('labo.catalogue.manage')
<div class="modal fade" id="modalSection" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('labo.catalogue.sections.store') }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Nouvelle section</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input name="nom" class="form-control mb-2" placeholder="Nom (ex. Immunologie)" required>
        <input name="code" class="form-control mb-2" placeholder="Code (ex. IMMUNO)" required>
        <input name="ordre" type="number" class="form-control" placeholder="Ordre d'affichage">
    </div>
    <div class="modal-footer"><button class="hl-bouton hl-bouton-plein">Ajouter la section</button></div>
</form></div></div>
@endcan
@endsection

@section('script')
<script>
document.getElementById('ctFiltre')?.addEventListener('input', function () {
    var q = this.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    document.querySelectorAll('.ct-ligne').forEach(function (l) { l.hidden = q && l.dataset.texte.indexOf(q) === -1; });
    document.querySelectorAll('.ct-section').forEach(function (s) {
        var lignes = s.querySelectorAll('.ct-ligne');
        s.hidden = q && lignes.length && [].every.call(lignes, function (l) { return l.hidden; });
    });
});
</script>
@endsection
