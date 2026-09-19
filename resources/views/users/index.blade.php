@extends('layouts.backend')

@php
    $libellesRoles = ['super-admin' => 'Administrateur plateforme', 'admin' => 'Administrateur', 'medecin' => 'Médecin', 'secretaire' => 'Secrétariat',
        'comptable' => 'Comptabilité', 'infirmier' => 'Infirmier', 'laborantin' => 'Laboratoire', 'biologiste' => 'Biologiste', 'caissier' => 'Caisse'];
    $estSuspendu = fn ($u) => $u->status !== null && ! (bool) $u->status;
    $suspendus = $users->filter($estSuspendu)->count();
    $provisoires = $users->where('doit_changer_mot_de_passe', true)->count();
    $jamais = $users->whereNull('last_login_at')->count();
    $initiales = fn ($u) => collect(preg_split('/\s+/u', trim(preg_replace('/^(dr\.?|docteur)\s+/iu', '', (string) $u->name))))->filter()->take(2)->map(fn ($m) => mb_strtoupper(mb_substr($m, 0, 1)))->implode('') ?: '?';
@endphp

@section('style')
<style>
    .ua-ligne { display: grid; grid-template-columns: minmax(220px, 1.5fr) 150px 150px 160px 150px auto; align-items: center; gap: 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
    .ua-ligne:first-of-type { border-top: 0; }
    .ua-ligne.est-suspendu { background: #fafafa; }
    .ua-ligne.est-suspendu .ua-nom { color: var(--hali-discret); }
    .ua-qui { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .ua-qui .hl-avatar { width: 40px; height: 40px; flex-basis: 40px; border-radius: 11px; }
    .ua-nom { display: block; color: var(--hali-encre); font-weight: 650; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ua-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
    .ua-tel { color: var(--hali-encre); font-weight: 600; font-variant-numeric: tabular-nums; }
    .ua-actions { display: flex; justify-content: flex-end; gap: 4px; }
    .ua-actions form { margin: 0; }
    .ua-icone { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none; }
    .ua-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .ua-icone.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .ua-outils { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .ua-recherche { position: relative; flex: 1 1 240px; }
    .ua-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .ua-recherche input { width: 100%; min-height: 40px; padding-left: 40px; }
    @media (max-width: 1199.98px) { .ua-ligne { grid-template-columns: 1fr 1fr; } .ua-qui { grid-column: 1 / -1; } .ua-actions { grid-column: 1 / -1; justify-content: flex-start; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Comptes et accès</h1>
            <p>Qui peut se connecter à Hali, avec quel rôle. Pour ajouter quelqu'un ou modifier son accès, passez par sa fiche du personnel.</p>
        </div>
        <div class="hl-entete-actions">
            @if(Route::has('employee.index'))@can('employee.view')<a href="{{ route('employee.index') }}" class="hl-bouton"><i class="fas fa-users" aria-hidden="true"></i> Personnel</a>@endcan @endif
            @can('users.create')
                @can('employee.create')
                    <a href="{{ route('employee.create', ['acces' => 1]) }}" class="hl-bouton hl-bouton-plein"><i class="fa fa-plus" aria-hidden="true"></i> Nouveau membre avec accès</a>
                @endcan
            @endcan
        </div>
    </header>

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Comptes actifs</span><span class="hl-kpi-valeur">{{ $users->count() - $suspendus }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Suspendus</span><span class="hl-kpi-valeur" style="color:{{ $suspendus ? 'var(--hali-danger)' : 'inherit' }}">{{ $suspendus }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Mot de passe provisoire</span><span class="hl-kpi-valeur" style="color:{{ $provisoires ? 'var(--hali-alerte)' : 'inherit' }}">{{ $provisoires }}</span><span class="hl-kpi-detail">pas encore changé</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Jamais connectés</span><span class="hl-kpi-valeur">{{ $jamais }}</span></div>
    </div>

    <section class="hl-bloc">
        <div class="ua-outils">
            <label class="ua-recherche mb-0"><span class="sr-only visually-hidden">Rechercher</span><i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="uaRecherche" class="form-control" placeholder="Nom, téléphone, rôle…" autocomplete="off"></label>
        </div>
        @forelse($users as $u)
            @php
                $role = $u->roles->first()?->name;
                $suspendu = $estSuspendu($u);
            @endphp
            <div class="ua-ligne {{ $suspendu ? 'est-suspendu' : '' }}" data-recherche="{{ mb_strtolower($u->name . ' ' . $u->phone . ' ' . $u->email . ' ' . ($libellesRoles[$role] ?? $role)) }}">
                <div class="ua-qui">
                    <span class="hl-avatar" aria-hidden="true">{{ $initiales($u) }}</span>
                    <div style="min-width:0">
                        <span class="ua-nom">{{ $u->name }}</span>
                        <span class="ua-sous">{{ $u->employee?->department?->name ?? ($u->employee ? '' : 'Sans fiche du personnel') }}</span>
                    </div>
                </div>
                <div><span class="ua-tel">{{ $u->phone }}</span>@if($u->email)<span class="ua-sous" style="overflow:hidden; text-overflow:ellipsis">{{ $u->email }}</span>@endif</div>
                <div><span class="hl-statut {{ in_array($role, ['admin', 'super-admin'], true) ? 'hl-s-info' : 'hl-s-neutre' }}">{{ $libellesRoles[$role] ?? ucfirst((string) $role ?: 'Aucun rôle') }}</span></div>
                <div>
                    @if($suspendu)<span class="hl-statut hl-s-danger">Suspendu</span>
                    @elseif($u->doit_changer_mot_de_passe)<span class="hl-statut hl-s-alerte">Mot de passe provisoire</span>
                    @else<span class="hl-statut hl-s-succes">Actif</span>@endif
                </div>
                <div class="ua-sous">{{ $u->last_login_at ? \Illuminate\Support\Carbon::parse($u->last_login_at)->diffForHumans() : 'Jamais connecté' }}</div>
                <div class="ua-actions">
                    @if($u->employee)
                        @can('employee.edit')<a href="{{ route('employee.edit', $u->employee->id) }}" class="ua-icone" title="Fiche et accès" aria-label="Fiche et accès de {{ $u->name }}"><i class="fas fa-id-card"></i></a>@endcan
                    @endif
                    @can('users.permissions')<a href="{{ route('users.listePermissions', $u->id) }}" class="ua-icone" title="Autorisations fines" aria-label="Autorisations de {{ $u->name }}"><i class="fas fa-user-shield"></i></a>@endcan
                    @can('users.disable')
                        @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('user.disable', $u->id) }}" onsubmit="return confirm('{{ $suspendu ? 'Réactiver cet accès ?' : 'Suspendre cet accès ? La personne sera déconnectée immédiatement.' }}');">
                                @csrf @method('PATCH')
                                <button type="submit" class="ua-icone {{ $suspendu ? '' : 'est-risque' }}" title="{{ $suspendu ? 'Réactiver' : 'Suspendre' }}" aria-label="{{ $suspendu ? 'Réactiver' : 'Suspendre' }} {{ $u->name }}"><i class="fas {{ $suspendu ? 'fa-unlock' : 'fa-ban' }}"></i></button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="hl-vide">Aucun compte.</div>
        @endforelse
        <div class="hl-vide" id="uaAucun" hidden>Aucun compte ne correspond.</div>
    </section>
</div></div>
@endsection

@section('script')
<script>
(function () {
    var champ = document.getElementById('uaRecherche');
    champ.addEventListener('input', function () {
        var t = champ.value.trim().toLowerCase(), n = 0;
        document.querySelectorAll('.ua-ligne').forEach(function (l) { var ok = !t || l.dataset.recherche.indexOf(t) !== -1; l.hidden = !ok; if (ok) n++; });
        document.getElementById('uaAucun').hidden = n > 0;
    });
})();
</script>
@endsection
