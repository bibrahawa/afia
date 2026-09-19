@extends('layouts.backend')

@php $gnf = fn ($v) => number_format((float) $v, 0, ',', ' '); @endphp

@section('style')
<style>
    .pk-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 12px; padding: 18px; }
    .pk-carte { display: grid; grid-template-rows: auto 1fr auto; gap: 10px; padding: 16px; border: 1px solid var(--hali-bordure); border-radius: 12px; background: #fff; }
    .pk-carte-haut { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .pk-carte-nom { color: var(--hali-encre); font-size: 1rem; font-weight: 700; }
    .pk-carte-prix { color: var(--hali-primaire-fonce); font-size: 1.15rem; font-weight: 800; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .pk-contenu { margin: 0; padding: 0; list-style: none; font-size: .84rem; }
    .pk-contenu li { display: flex; justify-content: space-between; gap: 8px; padding: 4px 0; border-top: 1px dashed #f3f4f6; color: var(--hali-texte); }
    .pk-contenu li:first-child { border-top: 0; }
    .pk-contenu li b { color: var(--hali-discret); font-weight: 500; font-variant-numeric: tabular-nums; }
    .pk-pied { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-top: 8px; border-top: 1px solid var(--hali-bordure); }
    .pk-remise { color: var(--hali-succes); font-size: .78rem; font-weight: 700; }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Forfaits</h1>
            <p>Des actes et examens vendus ensemble à un prix fixé : accouchement, bilan prénatal, check-up…</p>
        </div>
        @can('package.create')
            <div class="hl-entete-actions"><button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addRowModal"><i class="fa fa-plus" aria-hidden="true"></i> Nouveau forfait</button></div>
        @endcan
    </header>

    @include('partials.catalogue')

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <section class="hl-bloc">
        @if($packages->isEmpty())
            <div class="hl-vide"><i class="fas fa-box-open" aria-hidden="true"></i>Aucun forfait.</div>
        @else
            <div class="pk-grille">
                @foreach($packages as $package)
                    @php
                        $somme = $package->services->sum('amount') + $package->tests->sum('amount');
                        $elements = $package->services->map(fn ($s) => [$s->name, $s->amount])->concat($package->tests->map(fn ($t) => [$t->name, $t->amount]));
                    @endphp
                    <article class="pk-carte">
                        <div class="pk-carte-haut">
                            <div>
                                <span class="pk-carte-nom">{{ $package->name }}</span>
                                <span class="cat-sous">{{ $package->department?->name ?? 'Tous départements' }} · {{ \App\Enums\Assurance\FamilleActe::tryFrom((string) $package->famille_acte)?->libelle() ?? '—' }}</span>
                            </div>
                            <span class="pk-carte-prix">{{ $gnf($package->price) }} <small style="font-size:.7rem; color:var(--hali-discret)">GNF</small></span>
                        </div>
                        <ul class="pk-contenu">
                            @forelse($elements->take(6) as [$nom, $prix])
                                <li><span>{{ $nom }}</span><b>{{ $gnf($prix) }}</b></li>
                            @empty
                                <li><span class="cat-sous">Aucun élément.</span></li>
                            @endforelse
                            @if($elements->count() > 6)<li><span class="cat-sous">et {{ $elements->count() - 6 }} autre{{ $elements->count() - 6 > 1 ? 's' : '' }}…</span></li>@endif
                        </ul>
                        <div class="pk-pied">
                            @if($somme - (float) $package->price >= 1)
                                <span class="pk-remise">{{ $gnf($somme - $package->price) }} GNF d'économie</span>
                            @else
                                <span class="cat-sous">{{ $elements->count() }} élément{{ $elements->count() > 1 ? 's' : '' }}</span>
                            @endif
                            <span>
                                @can('package.edit')<a class="cat-icone" href="{{ route('package.edit', $package->id) }}" title="Modifier" aria-label="Modifier {{ $package->name }}"><i class="fa fa-pen"></i></a>@endcan
                                @can('package.delete')
                                    <button type="button" class="cat-icone est-risque delete-button" title="Supprimer" aria-label="Supprimer {{ $package->name }}" data-id="{{ $package->id }}" data-name="{{ $package->name }}"><i class="fa fa-trash"></i></button>
                                @endcan
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @can('package.create')
        <div class="modal fade cat-modal" id="addRowModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <form class="modal-content" id="addPackageForm" action="{{ route('package.store') }}" method="POST">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Nouveau forfait</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                    <div class="modal-body">@include('packages._formulaire', ['package' => null])</div>
                    <div class="modal-footer">
                        <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" id="addRowButton" class="hl-bouton hl-bouton-plein">Créer le forfait <span class="spinner-border spinner-border-sm" role="status" id="addLoader" style="display:none"></span></button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <div class="modal fade cat-modal" id="deleteRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="deletePackageForm" action="#" method="POST">
                @csrf @method('DELETE')
                <input type="hidden" id="delete_id" name="id">
                <div class="modal-header"><h5 class="modal-title">Supprimer le forfait</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><p class="mb-0" id="package_name_to_delete"></p></div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="deleteRowButton" class="hl-bouton cat-danger">Supprimer <span class="spinner-border spinner-border-sm" role="status" id="deleteLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection

@section('script')
<script>
(function () {
    document.querySelectorAll('.delete-button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('delete_id').value = b.dataset.id;
            document.getElementById('package_name_to_delete').textContent = 'Supprimer le forfait « ' + b.dataset.name + ' » ? Un forfait déjà utilisé en consultation ne peut pas être supprimé.';
            document.getElementById('deletePackageForm').action = '/package/delete/' + b.dataset.id;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRowModal')).show();
        });
    });
    [['addPackageForm', 'addRowButton', 'addLoader'], ['deletePackageForm', 'deleteRowButton', 'deleteLoader']].forEach(function (t) {
        var f = document.getElementById(t[0]);
        if (f) f.addEventListener('submit', function () { document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).style.display = 'inline-block'; });
    });
    @if($errors->any() && old('name') !== null && ! request()->routeIs('package.edit'))
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addRowModal')).show();
    @endif
})();
</script>
@endsection
