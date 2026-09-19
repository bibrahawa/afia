@extends('layouts.backend')

@php
    $statuts = ['essai' => ['Essai', 'hl-s-alerte'], 'actif' => ['Actif', 'hl-s-succes'], 'suspendu' => ['Suspendu', 'hl-s-danger'], 'resilie' => ['Résilié', 'hl-s-neutre']];
    $types = ['clinique' => ['Clinique', 'fa-hospital'], 'laboratoire' => ['Laboratoire', 'fa-flask'], 'pharmacie' => ['Pharmacie', 'fa-pills'], 'cabinet' => ['Cabinet', 'fa-user-md']];
    $parStatut = $etablissements->countBy('statut');
@endphp

@section('style')
<style>
    .et-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 14px; }
    .et-carte { display: grid; grid-template-rows: auto auto 1fr auto; gap: 12px; padding: 18px; border: 1px solid var(--hali-bordure); border-radius: var(--hali-rayon); background: #fff; }
    .et-carte.est-suspendu { background: #fcfcfc; }
    .et-haut { display: flex; align-items: flex-start; gap: 12px; }
    .et-icone { display: grid; place-items: center; width: 44px; height: 44px; flex: none; border-radius: 12px; background: var(--hali-primaire-pale); color: var(--hali-primaire); font-size: 1.1rem; }
    .et-carte.est-suspendu .et-icone { background: #f3f4f6; color: #9ca3af; }
    .et-nom { display: block; color: var(--hali-encre); font-size: 1.05rem; font-weight: 750; line-height: 1.25; }
    .et-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
    .et-chiffres { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .et-chiffres div { padding: 8px; border-radius: 9px; background: #f9fafb; text-align: center; }
    .et-chiffres b { display: block; color: var(--hali-encre); font-size: 1.05rem; }
    .et-chiffres span { color: var(--hali-discret); font-size: .72rem; }
    .et-modules { display: flex; flex-wrap: wrap; align-content: flex-start; gap: 4px; }
    .et-modules span { padding: 2px 9px; border-radius: 999px; background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); font-size: .74rem; font-weight: 600; }
    .et-modules .est-vide { background: var(--hali-alerte-pale); color: #92400e; }
    .et-pied { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-top: 12px; border-top: 1px solid #f3f4f6; }
    .et-pied form { margin: 0; }
    .et-actions { display: flex; gap: 4px; }
    .et-bouton { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none; }
    .et-bouton:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .et-bouton.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .et-sms { font-family: "SF Mono", Consolas, monospace; font-size: .76rem; letter-spacing: .04em; }
    .et-modal .modal-content { border: 0; border-radius: 14px; }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Établissements</h1>
            <p>Les clients de la plateforme : leur statut, leur licence (modules) et leur expéditeur SMS.</p>
        </div>
        @can('etablissement.create')
            <div class="hl-entete-actions"><button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addEtablissementModal"><i class="fa fa-plus" aria-hidden="true"></i> Nouvel établissement</button></div>
        @endcan
    </header>

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Clients actifs</span><span class="hl-kpi-valeur" style="color:var(--hali-succes)">{{ $parStatut['actif'] ?? 0 }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">En essai</span><span class="hl-kpi-valeur">{{ $parStatut['essai'] ?? 0 }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Suspendus</span><span class="hl-kpi-valeur" style="color:{{ ($parStatut['suspendu'] ?? 0) ? 'var(--hali-danger)' : 'inherit' }}">{{ $parStatut['suspendu'] ?? 0 }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Patients suivis</span><span class="hl-kpi-valeur">{{ number_format($etablissements->sum('patients_count'), 0, ',', ' ') }}</span></div>
    </div>

    @if($etablissements->isEmpty())
        <section class="hl-bloc"><div class="hl-vide">Aucun établissement.</div></section>
    @else
        <div class="et-grille">
            @foreach($etablissements as $e)
                @php
                    [$libStatut, $tonStatut] = $statuts[$e->statut] ?? [ucfirst($e->statut), 'hl-s-neutre'];
                    [$libType, $iconeType] = $types[$e->type] ?? [ucfirst($e->type), 'fa-building'];
                    $finContrat = $e->date_fin_contrat;
                    $joursRestants = $finContrat ? (int) floor(today()->diffInDays($finContrat, false)) : null;
                @endphp
                <article class="et-carte {{ in_array($e->statut, ['suspendu', 'resilie'], true) ? 'est-suspendu' : '' }}">
                    <div class="et-haut">
                        <span class="et-icone" aria-hidden="true"><i class="fas {{ $iconeType }}"></i></span>
                        <div style="min-width:0; flex:1">
                            <span class="et-nom">{{ $e->nom }}</span>
                            <span class="et-sous">{{ $libType }}@if($e->adresse) · {{ $e->adresse }}@endif</span>
                        </div>
                        <span class="hl-statut {{ $tonStatut }}">{{ $libStatut }}</span>
                    </div>
                    <div class="et-chiffres">
                        <div><b>{{ $e->utilisateurs_count }}</b><span>comptes</span></div>
                        <div><b>{{ number_format($e->patients_count, 0, ',', ' ') }}</b><span>patients</span></div>
                        <div><b>{{ $e->modules->count() }}</b><span>modules</span></div>
                    </div>
                    <div class="et-modules">
                        @forelse($e->modules as $m)<span>{{ $m->nom }}</span>@empty<span class="est-vide">Aucun module : licence à configurer</span>@endforelse
                    </div>
                    <div class="et-pied">
                        <div>
                            <span class="et-sous">SMS : <span class="et-sms">{{ $e->sms_expediteur ?: \App\Support\Marque::expediteurSms() }}</span>@unless($e->sms_expediteur) <em>(défaut)</em>@endunless</span>
                            @if($joursRestants !== null)
                                <span class="et-sous" style="{{ $joursRestants < 0 ? 'color:var(--hali-danger); font-weight:700' : ($joursRestants <= 30 ? 'color:#b45309; font-weight:700' : '') }}">
                                    {{ $joursRestants < 0 ? 'Contrat expiré depuis ' . abs($joursRestants) . ' j' : 'Contrat jusqu\'au ' . $finContrat->format('d/m/Y') }}</span>
                            @endif
                        </div>
                        <div class="et-actions">
                            @can('etablissement.licence')
                                <a href="{{ route('etablissement.modules', $e) }}" class="et-bouton" title="Licence (modules)" aria-label="Licence de {{ $e->nom }}"><i class="fas fa-puzzle-piece"></i></a>
                            @endcan
                            @can('etablissement.edit')
                                <button type="button" class="et-bouton edit-etablissement" title="Modifier" aria-label="Modifier {{ $e->nom }}"
                                        data-bs-toggle="modal" data-bs-target="#editEtablissementModal"
                                        data-action="{{ route('etablissement.update', $e) }}"
                                        data-nom="{{ $e->nom }}" data-type="{{ $e->type }}" data-statut="{{ $e->statut }}"
                                        data-adresse="{{ $e->adresse }}" data-contact="{{ $e->contact }}" data-email="{{ $e->email }}" data-sms="{{ $e->sms_expediteur }}"><i class="fa fa-pen"></i></button>
                            @endcan
                            @can('etablissement.delete')
                                @if($e->utilisateurs_count === 0 && $e->patients_count === 0)
                                    <form action="{{ route('etablissement.delete', $e) }}" method="POST" onsubmit="return confirm('Supprimer {{ addslashes($e->nom) }} ?');">
                                        @csrf @method('DELETE')<button type="submit" class="et-bouton est-risque" title="Supprimer" aria-label="Supprimer {{ $e->nom }}"><i class="fa fa-trash"></i></button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @can('etablissement.create')
        <div class="modal fade et-modal" id="addEtablissementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <div class="modal-header border-0"><h5 class="modal-title">Nouvel établissement</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <form action="{{ route('etablissement.add') }}" method="POST">
                    @csrf
                    <div class="modal-body">@include('etablissements._form')</div>
                    <div class="modal-footer border-0"><button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button><button type="submit" class="hl-bouton hl-bouton-plein">Créer</button></div>
                </form>
            </div></div>
        </div>
    @endcan

    @can('etablissement.edit')
        <div class="modal fade et-modal" id="editEtablissementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <div class="modal-header border-0"><h5 class="modal-title">Modifier l'établissement</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <form id="editEtablissementForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">@include('etablissements._form')</div>
                    <div class="modal-footer border-0"><button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button><button type="submit" class="hl-bouton hl-bouton-plein">Enregistrer</button></div>
                </form>
            </div></div>
        </div>
    @endcan
</div></div>

<script>
document.querySelectorAll('.edit-etablissement').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var f = document.getElementById('editEtablissementForm');
        f.action = btn.dataset.action;
        ['nom', 'type', 'statut', 'adresse', 'contact', 'email'].forEach(function (k) { f.querySelector('[name=' + k + ']').value = btn.dataset[k] || ''; });
        f.querySelector('[name=sms_expediteur]').value = btn.dataset.sms || '';
    });
});
</script>
@endsection
