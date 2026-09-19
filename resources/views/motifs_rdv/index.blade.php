@extends('layouts.backend')

@php $gnf = fn ($v) => number_format((float) $v, 0, ',', ' '); @endphp

@section('style')
<style>
    .mt-dep { margin-bottom: 16px; }
    .mt-ligne { display: grid; grid-template-columns: 14px minmax(160px, 1.4fr) 110px minmax(160px, 1.2fr) 110px auto; align-items: center; gap: 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
    .mt-ligne:first-of-type { border-top: 0; }
    .mt-ligne.est-inactif { opacity: .55; }
    .mt-pastille { width: 12px; height: 12px; border-radius: 4px; }
    .mt-duree-aff { color: var(--hali-encre); font-weight: 700; font-variant-numeric: tabular-nums; }
    .mt-actions { display: flex; justify-content: flex-end; gap: 4px; }
    .mt-actions form { margin: 0; }
    .mt-duree { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 6px; }
    .mt-duree button { min-width: 38px; min-height: 30px; border: 1px solid var(--hali-bordure); border-radius: 7px; background: #fff; font-size: .8rem; font-weight: 700; cursor: pointer; }
    .mt-duree button.est-choisi { background: var(--hali-primaire); border-color: var(--hali-primaire); color: #fff; }
    .mt-couleurs { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
    .mt-couleurs button { width: 28px; height: 28px; border: 2px solid #fff; border-radius: 8px; box-shadow: 0 0 0 1px var(--hali-bordure); cursor: pointer; }
    .mt-couleurs button.est-choisi { box-shadow: 0 0 0 2px var(--hali-encre); }
    .mt-couleurs input[type=color] { width: 40px; height: 32px; padding: 2px; }
    @media (max-width: 991.98px) { .mt-ligne { grid-template-columns: 14px 1fr auto; } .mt-ligne > :nth-child(3), .mt-ligne > :nth-child(4), .mt-ligne > :nth-child(5) { grid-column: 2 / -1; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Motifs de rendez-vous</h1>
            <p>Ce que le patient choisit en prenant rendez-vous. Chaque département a ses motifs, avec leur durée et leur couleur dans l'agenda.</p>
        </div>
    </header>

    @include('partials.catalogue')

    @forelse($departments as $department)
        <section class="hl-bloc mt-dep">
            <h2 class="hl-bloc-titre">{{ ucfirst(mb_strtolower($department->name)) }} <small>{{ $department->motifsRdv->count() }} motif{{ $department->motifsRdv->count() > 1 ? 's' : '' }}</small>
                @can('motif_rdv.create')
                    <button type="button" class="hl-bouton" style="margin-left:auto; min-height:32px" data-bs-toggle="modal" data-bs-target="#addMotifModal-{{ $department->id }}"><i class="fa fa-plus" aria-hidden="true"></i> Motif</button>
                @endcan
            </h2>
            @if($department->motifsRdv->isEmpty())
                <div class="hl-vide" style="padding:20px">Aucun motif : les patients ne peuvent pas prendre rendez-vous dans ce département.</div>
            @else
                @foreach($department->motifsRdv as $motif)
                    <div class="mt-ligne {{ $motif->actif ? '' : 'est-inactif' }}">
                        <span class="mt-pastille" style="background: {{ $motif->couleur ?: '#9ca3af' }}" aria-hidden="true"></span>
                        <span class="cat-nom">{{ $motif->nom }}</span>
                        <span><span class="mt-duree-aff">{{ $motif->duree_minutes_defaut }} min</span><span class="cat-sous">+ {{ $motif->marge_tampon_minutes }} min de marge</span></span>
                        <span style="font-size:.84rem">@if($motif->service){{ $motif->service->name }}<span class="cat-sous">{{ $gnf($motif->service->amount) }} GNF à l'arrivée</span>@else<span class="cat-sous">Aucun acte automatique</span>@endif</span>
                        <span><span class="hl-statut {{ $motif->actif ? 'hl-s-succes' : 'hl-s-neutre' }}">{{ $motif->actif ? 'Proposé' : 'Masqué' }}</span></span>
                        <div class="mt-actions">
                            @can('motif_rdv.edit')
                                <button type="button" class="cat-icone edit-motif" title="Modifier" aria-label="Modifier {{ $motif->nom }}" data-bs-toggle="modal" data-bs-target="#editMotifModal"
                                        data-action="{{ route('motifs-rdv.update', $motif) }}" data-nom="{{ $motif->nom }}" data-duree="{{ $motif->duree_minutes_defaut }}"
                                        data-marge="{{ $motif->marge_tampon_minutes }}" data-couleur="{{ $motif->couleur }}" data-service="{{ $motif->service_id }}"><i class="fa fa-pen"></i></button>
                                <form action="{{ route('motifs-rdv.toggle', $motif) }}" method="POST">@csrf @method('PATCH')
                                    <button type="submit" class="cat-icone" title="{{ $motif->actif ? 'Masquer aux patients' : 'Proposer aux patients' }}" aria-label="{{ $motif->actif ? 'Masquer' : 'Proposer' }} {{ $motif->nom }}"><i class="fa {{ $motif->actif ? 'fa-eye-slash' : 'fa-eye' }}"></i></button></form>
                            @endcan
                            @can('motif_rdv.delete')
                                <form action="{{ route('motifs-rdv.delete', $motif) }}" method="POST" onsubmit="return confirm('Supprimer ce motif ? S\'il a déjà servi, masquez-le plutôt.');">@csrf @method('DELETE')
                                    <button type="submit" class="cat-icone est-risque" title="Supprimer" aria-label="Supprimer {{ $motif->nom }}"><i class="fa fa-trash"></i></button></form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            @endif
        </section>

        @can('motif_rdv.create')
            <div class="modal fade cat-modal" id="addMotifModal-{{ $department->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <form class="modal-content js-motif" action="{{ route('motifs-rdv.add') }}" method="POST">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ $department->id }}">
                        <div class="modal-header"><h5 class="modal-title">Nouveau motif · {{ ucfirst(mb_strtolower($department->name)) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                        <div class="modal-body">@include('motifs_rdv._form', ['departementId' => $department->id])</div>
                        <div class="modal-footer">
                            <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="hl-bouton hl-bouton-plein">Créer le motif</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @empty
        <section class="hl-bloc"><div class="hl-vide">Créez d'abord un département.</div></section>
    @endforelse

    <div class="modal fade cat-modal" id="editMotifModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content js-motif" id="editMotifForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h5 class="modal-title">Modifier le motif</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">@include('motifs_rdv._form', ['departementId' => null])</div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="hl-bouton hl-bouton-plein">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection

@section('script')
<script>
(function () {
    function synchroniser(f) {
        var d = f.querySelector('[name=duree_minutes_defaut]').value, c = (f.querySelector('[name=couleur]').value || '').toLowerCase();
        f.querySelectorAll('[data-duree]').forEach(function (b) { b.classList.toggle('est-choisi', b.dataset.duree === d); });
        f.querySelectorAll('[data-couleur]').forEach(function (b) { b.classList.toggle('est-choisi', b.dataset.couleur.toLowerCase() === c); });
    }
    document.querySelectorAll('.js-motif').forEach(function (f) {
        f.querySelectorAll('[data-duree]').forEach(function (b) { b.addEventListener('click', function () { f.querySelector('[name=duree_minutes_defaut]').value = b.dataset.duree; synchroniser(f); }); });
        f.querySelectorAll('[data-couleur]').forEach(function (b) { b.addEventListener('click', function () { f.querySelector('[name=couleur]').value = b.dataset.couleur; synchroniser(f); }); });
        f.addEventListener('input', function () { synchroniser(f); });
        synchroniser(f);
    });
    document.querySelectorAll('.edit-motif').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var f = document.getElementById('editMotifForm');
            f.action = btn.dataset.action;
            f.querySelector('[name=nom]').value = btn.dataset.nom || '';
            f.querySelector('[name=duree_minutes_defaut]').value = btn.dataset.duree || 15;
            f.querySelector('[name=marge_tampon_minutes]').value = btn.dataset.marge || 5;
            f.querySelector('[name=couleur]').value = btn.dataset.couleur || '#0f766e';
            f.querySelector('[name=service_id]').value = btn.dataset.service || '';
            synchroniser(f);
        });
    });
})();
</script>
@endsection
